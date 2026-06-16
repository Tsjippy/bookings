<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', __NAMESPACE__ . '\initTasks');
function initTasks()
{
    add_action('tsjippy-payment-reminder', __NAMESPACE__ . '\paymentReminder');

    add_action('tsjippy-booking-emails', __NAMESPACE__ . '\bookingEmails');
}

function scheduleTasks()
{
    TSJIPPY\scheduleTask('tsjippy-booking-emails', 'daily');

    $freq   = SETTINGS['payment-reminder-freq'] ?? false;
    if ($freq) {
        TSJIPPY\scheduleTask('tsjippy-payment-reminder', $freq);
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
