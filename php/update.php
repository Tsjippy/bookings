<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('sim_bookings_module_update', __NAMESPACE__.'\pluginUpdate');
function pluginUpdate($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    SIM\printArray($oldVersion);

    $bookings = new Bookings();

    if($oldVersion < '8.0.4'){
        maybe_add_column($bookings->tableName, 'paid', "ALTER TABLE $simForms->tableName ADD COLUMN `paid` BOOL");

        SIM\printArray("Added 'paid' column to '$bookings->tableName' table");
    }
}