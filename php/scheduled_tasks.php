<?php
namespace TSJIPPY\BOOKINGS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('init', __NAMESPACE__.'\initTasks');
function initTasks(){
	add_action( 'payment_reminder_action', __NAMESPACE__.'\paymentReminder' );

	add_action( 'booking_emails_action', __NAMESPACE__.'\bookingEmails' );
}

function scheduleTasks(){
	TSJIPPY\scheduleTask('booking_emails_action', 'daily');

    $freq   = SETTINGS['payment-reminder-freq'] ?? false;
    if($freq){
        TSJIPPY\scheduleTask('payment_reminder_action', $freq);
    }
}

function paymentReminder(){
	$forms 		= new TSJIPPY\FORMS\EditFormResults([]);
	$bookings 	= new BookingPayments($forms);
	$bookings->sendPaymentReminders();
}

/**
 * Sends reminders by e-mail and Signal to fill in a form
 */
function bookingEmails(){
	$bookings			= new Bookings();

	$bookings->sendBookingEmails();
}