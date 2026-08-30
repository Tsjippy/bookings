<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Render the booking date elements on the form with the correct min and max attributes based on the existing bookings
 *
 * @param object $node   The current DOM node to render the element in
 * @param object $object The form object
 *
 * @return object The rendered element
 */
function bookingDateElementHtml(&$node, $object, $bookingId = false)
{
    global $wpdb;

    if (is_numeric($bookingId)) {
        $node->setAttribute('data-booking-id', $bookingId);
    }

    if ($object->element->slug != 'booking-start-date' && $object->element->slug != 'booking-end-date') {
        return;
    }

    // Get the subject
    $subject    = $object->submission->{$object->getBlockByType('accomodation')[0]->slug};

    $startDates = (array) $object->submission->{'booking-start-date'};
    $endDates   = (array) $object->submission->{'booking-end-date'};

    $early      = array_values($startDates)[0];
    $late       = array_values($endDates)[0];

    foreach ($startDates as $index => $date) {
        if ($date < $early) {
            $early  = $date;
        }

        if ($endDates[$index] > $late) {
            $late   = $endDates[$index];
        }
    }

    if ($object->element->slug == 'booking-start-date') {
        // get the first event after this one
        $max    = TSJIPPY\getFromDb(
            "get_start_date_for_{$subject}_after_$late",
            "bookings",
            "SELECT start_date FROM %i WHERE subject = %s AND start_date > %s ORDER BY start_date LIMIT 1",
            "{$wpdb->prefix}tsjippy_bookings",
            $subject,
            $late
        );

        if (!empty($max)) {
            $node->setAttribute('max', $max);
        }

        $node->setAttribute('min', $early);
    } elseif ($object->element->slug == 'booking-end-date') {
        // get the first event before this one
        $min    = TSJIPPY\getFromDb(
            "get_end_date_for_{$subject}_before_$early",
            "bookings",
            "SELECT end_date FROM %i WHERE subject = %s AND end_date <= %s ORDER BY end_date LIMIT 1",
            "{$wpdb->prefix}tsjippy_bookings",
            $subject,
            $early
        );

        if (!empty($min)) {
            $node->setAttribute('min', $min);
        }

        $node->setAttribute('max', $late);
    }
}

// Display the date selector in the form
add_filter('tsjippy-forms-element-html', __NAMESPACE__ . '\elementHtml', 10, 2);
/**
 * Render the form element HTML
 *
 * @param object $node The current DOM node to render the element in
 * @param object $object The form object
 *
 * @return object The rendered element
 */
function elementHtml($node, $object)
{
    // Check if the form has a booking selector
    if (empty($object->getBlockByType('accomodation'))) {
        return $node;
    }

    if ($object->element->slug == 'booking-rooms') {
        $bookings       = new Bookings($object);

        if (empty($subjects)) {
            return 'Please add one or more subjects';
        }

        $elementName    = $object->getBlockByType('accomodation')[0]->slug;

        foreach ($subjects as $subject) {
            if ($subject['name'] == $object->submission->{$elementName}) {
                break;
            }
        }

        $bookings->roomSelector($node, $subject, true);
    }

    // Display existing form entry element element
    elseif (!empty($object->submission)) {
        // phpcs:ignore
        bookingDateElementHtml($node, $object, (int) $_POST['booking-id']);
    }

    // Add a class for payment_amount_el
    elseif ($object->element->id == $object->formData->payment_amount_el) {
        $class  = $node->getAttribute('class');

        $class  .= ' payment-amount';

        $node->setAttribute('class', $class);
    }

    // Add a class for payment_details_el
    elseif ($object->element->id == $object->formData->payment_details_el) {
        $class  = $node->getAttribute('class');

        $class  .= ' payment-details';

        $node->setAttribute('class', $class);
    }

    // Add a class for payment_details_el
    elseif ($object->element->id == $object->formData->price_per_night_el) {
        $class  = $node->getAttribute('class');

        $class  .= ' price-per-night';

        $node->setAttribute('class', $class);
    }

    return $node;
}