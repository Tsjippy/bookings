<?php
namespace SIM\BOOKINGS;
use SIM;

const MODULE_VERSION		= '7.1.3';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_module_updated', function($newOptions, $moduleSlug){
	global $wpdb;

	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $newOptions;
	}

	// enable forms and events modules
	if(!SIM\getModuleOption('forms', 'enable')){
		SIM\ADMIN\enableModule('forms');
	}
	if(!SIM\getModuleOption('events', 'enable')){
		SIM\ADMIN\enableModule('events');
	}

	// Create the table
	$bookings	= new Bookings();
	$bookings->createBookingsTable();

	// Add booking manager role
	if(!wp_roles()->is_role( 'bookingmanager' )){
		add_role(
			'bookingmanager',
			'Booking manager',
			get_role( 'contributor' )->capabilities
		);
	}

	// Add columns to forms element table
	$forms	= new SIM\FORMS\SimForms();

	// Add columns to forms table
	$row 	= $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$forms->tableName' AND column_name = 'default_booking_state'"  );
	if(empty($row)){
		$wpdb->query("ALTER TABLE $forms->tableName ADD default_booking_state text NOT NULL");
	}

	$row 	= $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$forms->tableName' AND column_name = 'confirmed_booking_roles'"  );
	if(empty($row)){
		$wpdb->query("ALTER TABLE $forms->tableName ADD confirmed_booking_roles text NOT NULL");
	}

	// Add columns to forms element table
	$row 	= $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$forms->elTableName' AND column_name = 'booking_details'"  );
	if(empty($row)){
		$wpdb->query("ALTER TABLE $forms->elTableName ADD booking_details text NOT NULL");
	}

	return $newOptions;
}, 10, 2);

add_filter('sim_role_description', function($description, $role){
    if($role == 'bookingmanager'){
        return 'Someone who can manage accomodation bookings';
    }
	
    return $description;
}, 10, 2);

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module is dependend on the events and forms module.<br>
		It adds the possibility to book something via a form.<br>
		It will display a calendar showing available dates
	</p>

	<?php

	return ob_get_clean();
}, 10, 2);