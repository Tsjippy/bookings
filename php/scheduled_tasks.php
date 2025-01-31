<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('init', __NAMESPACE__.'\initTasks');
function initTasks(){
	//add action for booking reminders
	add_action('send_booking_reminder_action', __NAMESPACE__.'\bookingReminder');

	add_action( 'payment_reminder_action', __NAMESPACE__.'\paymentReminder' );
}

function scheduleTasks(){
    $freq   = SIM\getModuleOption(MODULE_SLUG, 'payment_reminder_freq');
    if($freq){
        SIM\scheduleTask('payment_reminder_action', $freq);
    }
}

function bookingReminder($bookingId){
	$bookings = new Bookings();
	$bookings->sendBookingReminder($bookingId);
}

function paymentReminder(){
	$bookings = new Bookings();
	$bookings->sendPaymentReminders();
}