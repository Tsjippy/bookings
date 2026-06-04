<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('tsjippy-forms-entry-archived', __NAMESPACE__ . '\removeBookings', 10, 2);
add_action('tsjippy-forms-entry-removed', __NAMESPACE__ . '\removeBookings', 10, 2);
function removeBookings($instance, $submissionId)
{
    // remove the booking
    $bookings           = new Bookings();

    $currentBookings    = $bookings->getBookingsBySubmission($submissionId);

    if (!$currentBookings) {
        return;
    }

    if ($_POST['action'] == 'archive') {

        foreach ($currentBookings as $booking) {

            if (!empty($_POST['subid']) && $booking->room != $_POST['subid']) {
                // we should only remove the requested booking
                continue;
            }

            $bookings->removeBooking($booking);
        }
    } else {
        // to do re-insert booking on inarchive
    }
}
