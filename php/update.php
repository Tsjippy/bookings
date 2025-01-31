<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('sim_bookings_module_update', __NAMESPACE__.'\pluginUpdate');
function pluginUpdate($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    SIM\printArray($oldVersion);

    $bookings = new Bookings();

    if($oldVersion < '8.0.7'){
        maybe_add_column($bookings->tableName, 'paid', "ALTER TABLE $bookings->tableName ADD COLUMN `paid` BOOL");

        SIM\printArray("Added 'paid' column to '$bookings->tableName' table");
        
        $forms	= new SIM\FORMS\SimForms();

        maybe_add_column($forms->tableName, 'payment_indicator', "ALTER TABLE $forms->tableName ADD COLUMN `payment_indicator` int");
        maybe_add_column($forms->tableName, 'payment_amount_el', "ALTER TABLE $forms->tableName ADD COLUMN `payment_amount_el` int");
        maybe_add_column($forms->tableName, 'payment_details_el', "ALTER TABLE $forms->tableName ADD COLUMN `payment_details_el` int");
        maybe_add_column($forms->tableName, 'price_per_night_el', "ALTER TABLE $forms->tableName ADD COLUMN `price_per_night_el` int");
    }
}