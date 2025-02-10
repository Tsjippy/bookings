<?php
namespace SIM\BOOKINGS;
use SIM;

const MODULE_VERSION		= '8.1.0';

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_module_updated', __NAMESPACE__.'\moduleUpdated', 10, 2);
function moduleUpdated($newOptions, $moduleSlug){
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
}

add_filter('sim_submenu_options', __NAMESPACE__.'\moduleOptions', 10, 4);
function moduleOptions($optionsHtml, $moduleSlug, $settings, $moduleName){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

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

	return ob_get_clean();
}

add_filter('sim_email_settings', __NAMESPACE__.'\emailSettings', 10, 3);
function emailSettings($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

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
			"accomodation"	=> "empty",
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

	return ob_get_clean();
}

//run on module activation
add_action('sim_module_activated', __NAMESPACE__.'\moduleActivated', 10, 2);
function moduleActivated($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	// add an extra form setting column in db
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

	$forms	= new SIM\FORMS\SimForms();

	$forms	= new SIM\FORMS\SimForms();

	maybe_add_column($forms->tableName, 'payment_indicator', "ALTER TABLE $forms->tableName ADD COLUMN `payment_indicator` int");
	maybe_add_column($forms->tableName, 'payment_amount_el', "ALTER TABLE $forms->tableName ADD COLUMN `payment_amount_el` int");
	maybe_add_column($forms->tableName, 'payment_details_el', "ALTER TABLE $forms->tableName ADD COLUMN `payment_details_el` int");
	maybe_add_column($forms->tableName, 'price_per_night_el', "ALTER TABLE $forms->tableName ADD COLUMN `price_per_night_el` int");
}
