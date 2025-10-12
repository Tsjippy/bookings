<?php
namespace SIM\BOOKINGS;
use SIM;

function getNextMonth(){
	$bookings	= new Bookings();

	$bookings->forms->getForm($_POST['form-id']);

	$bookings->forms->shortcodeId		= $_POST['shortcode-id'];

	if(isset($_POST['element-id']) && is_numeric($_POST['element-id'])){
		$element						= $bookings->forms->getElementById($_POST['element-id']);
	}else{
		foreach($bookings->forms->formElements as $element){
			if($element->type == 'booking-selector'){
				break;
			}
		}
	}
	$bookings->forms->currentElement	= $element;

	$subjectName	= sanitize_text_field($_POST['subject']);
	$date			= strtotime($_POST['year'].'-'.$_POST['month'].'-01');

	$months			= [];
	foreach($bookings->getElementSubjects($element->id) as $subject){
		if($subject['name'] == $subjectName){
			if($subject['amount'] > 1){									
				if(isset($subject['nrtype']) && $subject['nrtype'] == 'letters'){
					$alphabet = range('A', 'Z');
					for ($x = 0; $x < $subject['amount']; $x++) {
						$months[]	= $bookings->monthCalendar($subject['name'], $alphabet[$x], $date);
					}
				}elseif(isset($subject['nrtype']) && $subject['nrtype'] == 'custom'){
					foreach ($subject['rooms'] as $room) {
						$months[]	= $bookings->monthCalendar($subject['name'], $room, $date);
					}
				}else{
					for ($x = 1; $x <= $subject['amount']; $x++) {
						$months[]	= $bookings->monthCalendar($subject['name'], $x, $date);
					}
				}
			}else{
				$months[]	= $bookings->monthCalendar($subject['name'], '', $date);
			}
		}
	}

	/**
	 * date is the month we are requesting
	 * the navigator expect the month given to be the first visible month
	 * So if we are adding a new month the first visible month will be the month before that
	 */ 
	if(isset($_POST['type']) && $_POST['type'] == 'prev'){
		$navDate	= $date;
	}else{
		$navDate	= strtotime('-1 month', $date);
	}
	$navigator	= $bookings->getNavigator($navDate);
	$detail		= '';
	if(!empty($_POST['shortcode-id'])){
		$detail		= $bookings->detailHtml();
	}

	if(is_wp_error($detail)){
		return $detail;
	}

	if(is_wp_error($navigator)){
		return $navigator;
	}
	
	return [
		'months'	=> $months,
		'navigator'	=> $navigator,
		'details'	=> $detail
	];
}

function approveBooking(){
	$bookingsObject	= new Bookings();

	$bookingsObject->forms->formId	= $_POST['form-id'];

	$bookings    				= $bookingsObject->getBookingsBySubmission($_POST['id']);

	foreach($bookings as $booking){
		$result	= $bookingsObject->updateBooking($booking, ['pending' => 0]);
	}

	if(is_wp_error($result)){
		return $result;
	}

	return $result;
}

function removeBooking(){
	$bookings	= new Bookings();

	$result	= $bookings->removeBooking($_POST['id']);

	if(is_wp_error($result)){
		return $result;
	}

	return 'Booking removed succesfully';
}

add_action( 'rest_api_init', __NAMESPACE__.'\restapiInit' );
function restapiInit() {
	// Next month
	register_rest_route(
		RESTAPIPREFIX.'/bookings',
		'/get_next_month',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\getNextMonth',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'month'	=> array(
					'required'	=> true,
					'validate_callback' => function($month){
						return is_numeric($month);
					}
				),
				'year'		=> array(
					'required'	=> true,
					'validate_callback' => function($year){
						return is_numeric($year);
					}
				),
				'subject'		=> array(
					'required'	=> true
				)
			)
		)
	);

	// Approve pending booking
	register_rest_route(
		RESTAPIPREFIX.'/bookings',
		'/approve',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\approveBooking',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'id'	=> array(
					'required'	=> true,
					'validate_callback' => function($bookingId){
						return is_numeric($bookingId);
					}
				)
			)
		)
	);

	// Delete a booking
	register_rest_route(
		RESTAPIPREFIX.'/bookings',
		'/remove',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\removeBooking',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'id'	=> array(
					'required'	=> true,
					'validate_callback' => function($bookingId){
						return is_numeric($bookingId);
					}
				)
			)
		)
	);
}

