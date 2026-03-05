<?php
namespace SIM\BOOKINGS;
use SIM;

/**
 * This filter runs before the submission is inserted in the database.
 * We use it to check if the booking overlaps with an existing one.
 * It returns an error if there is an overlap, or an other issue that prevents creating the booking
 * 
 * It updates the amount to be paid if there are no issues
 */
add_filter('sim_before_inserting_formdata', __NAMESPACE__.'\beforeSavingFormData', 99, 2);
function beforeSavingFormData($submission, $object){
    $bookings                   = new BookingPayments($object);

    // Check if this is a form with a booking selector
    $elements             = $bookings->getBookingElements();
    if(empty($elements) || is_wp_error($elements)){
        return $submission;
    }

    $startDates = [];
    if(isset($submission->{'booking-startdate'})){
        $startDates = (array)$submission->{'booking-startdate'};

        $startDates = SIM\cleanUpNestedArray($startDates);

        unset($submission->{'booking-startdate'});
    }

    $endDates   = [];
    if(isset($submission->{'booking-enddate'})){
        $endDates   = (array)$submission->{'booking-enddate'};
        $endDates   = SIM\cleanUpNestedArray($endDates);

        unset($submission->{'booking-enddate'});
    }

    $rooms  = [];
    if(!empty($submission->{'booking-rooms'})){
        $rooms   = $submission->{'booking-rooms'};

        if(!is_array($rooms)){
            $rooms  = [$rooms];
        }

        unset($submission->{'booking-rooms'});
    }

    if(empty($startDates) || empty($endDates)){
        return new \WP_Error('bookings', "Please provide a start and end date");
        return $submission;
    }

    // loop over all booking selectors (usually one)
    foreach($elements as $element){
        $subjects       = $bookings->getElementSubjects($element->id);
        $subjectName    = $submission->{$element->id};

        // Somehow we do not have any data
        if(empty($subjects)){
            return new \WP_Error('bookings', "No booking subjects found");
        }

        if(!empty($submission->id)){
            $currentBookings    = $bookings->getBookingsBySubmission($submission->id);
        }

        // Same start and end date
        foreach($startDates as $index => $startdate){
            if($startdate == $endDates[$index]){
                return new \WP_Error('bookings', "End date cannot be the same as the start date");
            }

            $bookingId = -1;
            if(!empty($currentBookings)){
                $bookingId = $currentBookings[0]->id;
            }

            $overlappingBookings    = $bookings->checkOverlap($startdate, $endDates[$index], $subjectName, $rooms[$index], $bookingId);
            if(!empty($overlappingBookings)){
                if(!empty($rooms[$index])){
                    $subjectName    .= " room {$rooms[$index]}";
                }

                $startDateString    = date(DATEFORMAT, strtotime($startdate));
                $endDateString      = date(DATEFORMAT, strtotime($endDates[$index]));
                return new \WP_Error('booking', "The booking for $subjectName overlaps with an existing one from $startDateString till $endDateString, try again");
            }
        }

        // find the selected subject
        foreach($subjects as $subject){
            if(
                !empty($subject['name']) &&             // Subjects name is set 
                $subject['name'] == $subjectName &&     // and this is the selected subject
                !empty($subject['rooms'])   &&          // and the subject has a key called rooms
                count($subject['rooms']) > 1 &&         // and there is more than 1 room for this subject
                empty($rooms)                           // but there is no room selected
            ){
                return new \WP_Error('bookings', "Please select a room");
            }
        }        
    }

    // Everything is ok - Update the amount to be paid
    $amount             = $bookings->calculatePaymentAmount($startDates, $endDates);

    $paymentAmountElId  = $bookings->forms->formData->payment_amount_el;

    if(!empty($paymentAmountElId)){
        $name = $bookings->forms->getElementById($paymentAmountElId, 'name');
        $submission->{$name} = $amount;
    }

    return $submission;
}

/**
 * This filter runs after the submission is inserted in the database.
 * We use it to create the booking in the database, and link it to the submission
 */
add_filter('sim_after_form_submission', __NAMESPACE__.'\afterFormSubmission', 99, 3);
function afterFormSubmission($message, $submission, $object){
    $startDates = [];
    if(isset($submission['booking-startdate'])){
        $startDates = (array)$submission['booking-startdate'];

        unset($submission['booking-startdate']);
    }

    $endDates   = [];
    if(isset($submission['booking-enddate'])){
        $endDates   = (array)$submission['booking-enddate'];

        unset($submission['booking-enddate']);
    }

    $rooms  = [];
    if(isset($submission['booking-rooms']) && is_array($submission['booking-rooms'])){
        $rooms   = $submission['booking-rooms'];

        unset($submission['booking-rooms']);
    }

    $bookings                   = new Bookings($object);

    // find the subject
    $elements             = $bookings->getBookingElements();
    if(is_wp_error($elements) || empty($elements)){
        return $message;
    }

    // loop over all booking selectors (usually one)
    foreach($elements as $element){

        $subject        = $submission[$element->name];
        $submissionId   = $object->submission->id;
        
        //Create a booking for each room
        if(!empty($rooms)){
            foreach($rooms as $index => $room){
                // Create a booking for this room
                $result     = $bookings->insertBooking($startDates[$index], $endDates[$index], $subject, $room, $submissionId);

                if(is_wp_error($result)){
                    return $result;
                }
            }
        }else{
            // Create a booking
            $result         = $bookings->insertBooking($startDates[0], $endDates[0], $subject, '', $submissionId);

            if(is_wp_error($result)){
                return $result;
            }
        }
    }

    return $message;
}