<?php
namespace SIM\BOOKINGS;
use SIM;

//add js special to the travelform
add_filter('sim_form_extra_js', __NAMESPACE__.'\extraJs', 10, 3);
function extraJs($js, $object, $minimized){
	$elements        = $object->getElementByType('booking_selector');

	if(empty($elements)){
		return $js;
	}

	$path	= plugin_dir_path( __DIR__)."js/bookings-extra.min.js";
	if(!$minimized || !file_exists($path)){
		$path	= plugin_dir_path( __DIR__)."js/bookings-extra.js";
	}

	if(file_exists($path)){
		$js		= file_get_contents($path);
	}

	return $js;
}
