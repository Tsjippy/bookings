<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('init', __NAMESPACE__.'\init');
function init(){
	//add action for booking reminders
	add_action('send_booking_reminder_action', __NAMESPACE__.'\bookingReminder');
}

function bookingReminder($bookingId){
	$bookings = new Bookings();
	$bookings->sendBookingReminder($bookingId);
}