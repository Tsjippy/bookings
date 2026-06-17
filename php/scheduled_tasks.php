<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', __NAMESPACE__ . '\scheduleTasks');
function scheduleTasks()
{
    TSJIPPY\scheduleTask('tsjippy-bookings-emails', 'daily', __NAMESPACE__, 'bookingEmails');

    $freq   = SETTINGS['payment-reminder-freq'] ?? false;
    if ($freq) {
        TSJIPPY\scheduleTask('tsjippy-bookings-payment-reminder', $freq, __NAMESPACE__, 'paymentReminder');
    }
}

function paymentReminder()
{
    $forms    = new TSJIPPY\FORMS\EditFormResults([]);
    $bookings = new BookingPayments($forms);
    $bookings->sendPaymentReminders();
}

/**
 * Sends reminders by e-mail and Signal to fill in a form
 */
function bookingEmails()
{
    $bookings = new Bookings();

    $bookings->sendBookingEmails();
}
