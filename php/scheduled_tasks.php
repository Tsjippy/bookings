<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('init', function(){
	//add action for booking reminders
	add_action('send_booking_reminder_action', function ($bookingId){
		$bookings = new Bookings();
		$bookings->sendBookingReminder($bookingId);
	});
});