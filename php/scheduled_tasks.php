<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('init', __NAMESPACE__.'\initTasks');
function initTasks(){
	add_action( 'payment_reminder_action', __NAMESPACE__.'\paymentReminder' );

	add_action( 'booking_emails_action', __NAMESPACE__.'\bookingEmails' );
}

function scheduleTasks(){
	SIM\scheduleTask('booking_emails_action', 'daily');

    $freq   = SIM\getModuleOption(MODULE_SLUG, 'payment-reminder-freq');
    if($freq){
        SIM\scheduleTask('payment-reminder_action', $freq);
    }
}

function paymentReminder(){
	$bookings = new Bookings();
	$bookings->sendPaymentReminders();
}

/**
 * Sends reminders by e-mail and Signal to fill in a form
 */
function bookingEmails(){
	$bookings			= new Bookings();

	$bookings->sendBookingEmails();
}