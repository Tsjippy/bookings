<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * This filter runs before the submission is inserted in the database.
 * We use it to check if the booking overlaps with an existing one.
 * It returns an error if there is an overlap, or an other issue that prevents creating the booking
 *
 * It updates the amount to be paid if there are no issues
 */
add_filter('tsjippy-forms-before-inserting-formdata', __NAMESPACE__ . '\beforeSavingFormData', 99, 2);
/**
 * Check for booking overlaps before saving form data
 *
 * @param object $submission   The form submission request data
 * @param object $object       The form object
 *
 * @return object The updated form submission or an error
 */
function beforeSavingFormData($submission, $object)
{
    $bookings                   = new BookingPayments($object);

    // Check if this is a form with a booking selector
    $blocks             = $bookings->getBookingBlocks();
    if (empty($blocks) || is_wp_error($blocks)) {
        return $submission;
    }

    $startDates = [];
    if (isset($submission->{'-102'})) {
        $startDates = (array)$submission->{'-102'};

        $startDates = TSJIPPY\cleanUpNestedArray($startDates);

        unset($submission->{'-102'});
    }

    $endDates   = [];
    if (isset($submission->{'-103'})) {
        $endDates   = (array)$submission->{'-103'};
        $endDates   = TSJIPPY\cleanUpNestedArray($endDates);

        unset($submission->{'-103'});
    }

    $rooms  = [];
    if (!empty($submission->{'-104'})) {
        $rooms   = $submission->{'-104'};

        if (!is_array($rooms)) {
            $rooms  = [$rooms];
        }

        unset($submission->{'-104'});
    }

    if (empty($startDates) || empty($endDates)) {
        return new \WP_Error('bookings', "Please provide a start and end date");
    }

    // loop over all booking selectors (usually one)
    foreach ($blocks as $block) {
        $subjects       = $bookings->getBlockSubjects($block->blockId);
        $subjectName    = $submission->{$block->blockId};

        // Somehow we do not have any data
        if (empty($subjects)) {
            return new \WP_Error('bookings', "No booking subjects found");
        }

        if (!empty($submission->id)) {
            $currentBookings    = $bookings->getBookingsBySubmission($submission->id);
        }

        // Same start and end date
        foreach ($startDates as $index => $start_date) {
            if ($start_date == $endDates[$index]) {
                return new \WP_Error('bookings', "End date cannot be the same as the start date");
            }

            $bookingId = -1;
            if (!empty($currentBookings)) {
                $bookingId = $currentBookings[0]->id;
            }

            $overlappingBookings    = $bookings->checkOverlap($start_date, $endDates[$index], $subjectName, $rooms[$index], $bookingId);
            if (!empty($overlappingBookings)) {
                if (!empty($rooms[$index])) {
                    $subjectName    .= " room {$rooms[$index]}";
                }

                $startDateString    = gmdate(TSJIPPY\DATEFORMAT, strtotime($start_date));
                $endDateString      = gmdate(TSJIPPY\DATEFORMAT, strtotime($endDates[$index]));
                return new \WP_Error('booking', "The booking for $subjectName overlaps with an existing one from $startDateString till $endDateString, try again");
            }
        }

        // find the selected subject
        foreach ($subjects as $subject) {
            if (
                !empty($subject['slug']) &&             // Subjects slug is set
                $subject['slug'] == $subjectName &&     // and this is the selected subject
                !empty($subject['rooms'])   &&          // and the subject has a key called rooms
                count($subject['rooms']) > 1 &&         // and there is more than 1 room for this subject
                empty($rooms)                           // but there is no room selected
            ) {
                return new \WP_Error('bookings', "Please select a room");
            }
        }
    }

    // Everything is ok - Update the amount to be paid
    $amount             = $bookings->calculatePaymentAmount($startDates, $endDates);

    $paymentAmountElId  = $bookings->forms->formData->payment_amount_el;

    if (!empty($paymentAmountElId)) {
        $slug                = $bookings->forms->getBlockById($paymentAmountElId, 'slug');
        $submission->{$slug} = $amount;
    }

    return $submission;
}

/**
 * This filter runs after the submission is inserted in the database.
 * We use it to create the booking in the database, and link it to the submission
 */
add_filter('tsjippy-forms-after-submission', __NAMESPACE__ . '\afterFormSubmission', 99, 3);
/**
 * Process the form submission after it has been saved
 *
 * @param string $message The message to display
 * @param object $submission The form submission
 * @param object $object The form object
 *
 * @return string The updated message
 */
function afterFormSubmission($message, $submission, $object)
{
    $startDates = [];
    if (isset($submission['-102'])) {
        $startDates = (array)$submission['-102'];

        unset($submission['-102']);
    }

    $endDates   = [];
    if (isset($submission['-103'])) {
        $endDates   = (array)$submission['-103'];

        unset($submission['-103']);
    }

    $rooms  = [];
    if (isset($submission['-104']) && is_array($submission['-104'])) {
        $rooms   = $submission['-104'];

        unset($submission['-104']);
    }

    $bookings                   = new Bookings($object);

    // find the subject
    $blocks             = $bookings->getBookingBlocks();
    if (is_wp_error($blocks) || empty($blocks)) {
        return $message;
    }

    // loop over all booking selectors (usually one)
    foreach ($blocks as $block) {

        $subject        = $submission[$block->slug];
        $submissionId   = $object->submission->id;

        //Create a booking for each room
        if (!empty($rooms)) {
            foreach ($rooms as $index => $room) {
                // Create a booking for this room
                $result     = $bookings->insertBooking($startDates[$index], $endDates[$index], $subject, $room, $submissionId);

                if (is_wp_error($result)) {
                    return $result;
                }
            }
        } else {
            // Create a booking
            $result         = $bookings->insertBooking($startDates[0], $endDates[0], $subject, '', $submissionId);

            if (is_wp_error($result)) {
                return $result;
            }
        }
    }

    return $message;
}
