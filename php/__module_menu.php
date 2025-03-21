<?php
namespace SIM\BOOKINGS;
use SIM;

const MODULE_VERSION		= '8.2.2';

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_module_bookings_after_save', __NAMESPACE__.'\moduleUpdated');
function moduleUpdated($newOptions){
	// enable forms and events modules
	if(!SIM\getModuleOption('forms', 'enable')){
		SIM\ADMIN\enableModule('forms');
	}
	if(!SIM\getModuleOption('events', 'enable')){
		SIM\ADMIN\enableModule('events');
	}

	scheduleTasks();

	return $newOptions;
}

add_filter('sim_submenu_bookings_options', __NAMESPACE__.'\moduleOptions', 10, 2);
function moduleOptions($optionsHtml, $settings){
	ob_start();
	
    ?>
	<br>
	<br>
	<label for="reminder_freq">
		How often should people be reminded to pay?
	</label>
	<select name="payment_reminder_freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['payment_reminder_freq']);
		?>
	</select>

	<?php

	return $optionsHtml.ob_get_clean();
}

add_filter('sim_email_bookings_settings', __NAMESPACE__.'\emailSettings', 10, 2);
function emailSettings($html, $settings){
	ob_start();

	?>
	<label>
		Define the e-mail people get when they still need to pay for some booking(s).<br>
	</label>
	<br>

	<?php
	$emails    = new BookingEmail(
		wp_get_current_user(), 
		(object)[
			"id"			=> -1,
			"subject"		=> "empty",
			"startdate"		=> "2000-01-01",
			"enddate"		=> "2000-01-01",
			"payable"		=> "$23"
		]
	);
	$emails->printPlaceholders();
	?>

	<h4>Payment Reminder E-mail</h4>
	<?php

	$emails->printInputs($settings);

	return $html.ob_get_clean();
}

//run on module activation
add_action('sim_module_bookings_activated', __NAMESPACE__.'\moduleActivated');
function moduleActivated($options){
	// Create the table
	$bookings	= new Bookings();
	$bookings->createBookingsTable();

	// Add columns to forms element table
	$forms	= new SIM\FORMS\SimForms();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

	// add columns to the forms table
    maybe_add_column($forms->tableName, 'payment_indicator', "ALTER TABLE $forms->tableName ADD COLUMN `payment_indicator` int");
    maybe_add_column($forms->tableName, 'payment_amount_el', "ALTER TABLE $forms->tableName ADD COLUMN `payment_amount_el` int");
    maybe_add_column($forms->tableName, 'payment_details_el', "ALTER TABLE $forms->tableName ADD COLUMN `payment_details_el` int");
    maybe_add_column($forms->tableName, 'price_per_night_el', "ALTER TABLE $forms->tableName ADD COLUMN `price_per_night_el` int");
	maybe_add_column($forms->tableName, 'default_booking_state', "ALTER TABLE $forms->tableName ADD COLUMN `default_booking_state` text");
    maybe_add_column($forms->tableName, 'confirmed_booking_roles', "ALTER TABLE $forms->tableName ADD COLUMN `confirmed_booking_roles` text");
    
	// Add column to the form element table
	maybe_add_column($forms->elTableName, 'booking_details', "ALTER TABLE $forms->elTableName ADD COLUMN `booking_details` text");

}
