<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('sim_bookings_module_update', __NAMESPACE__.'\moduleUpdate');
function moduleUpdate($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    SIM\printArray($oldVersion);

    $bookings = new Bookings();

    if($oldVersion < '8.1.0'){
        maybe_add_column($bookings->tableName, 'paid', "ALTER TABLE $bookings->tableName ADD COLUMN `paid` BOOL");

        SIM\printArray("Added 'paid' column to '$bookings->tableName' table");
        
        $forms	= new SIM\FORMS\SimForms();

        maybe_add_column($forms->tableName, 'payment_indicator', "ALTER TABLE $forms->tableName ADD COLUMN `payment_indicator` int");
        maybe_add_column($forms->tableName, 'payment_amount_el', "ALTER TABLE $forms->tableName ADD COLUMN `payment_amount_el` int");
        maybe_add_column($forms->tableName, 'payment_details_el', "ALTER TABLE $forms->tableName ADD COLUMN `payment_details_el` int");
        maybe_add_column($forms->tableName, 'price_per_night_el', "ALTER TABLE $forms->tableName ADD COLUMN `price_per_night_el` int");
    }

    if($oldVersion < '8.1.1'){
        maybe_add_column($bookings->tableName, 'room', "ALTER TABLE $bookings->tableName ADD COLUMN `room` varchar(80)");

        global $wpdb;

		$query	    = "SELECT * FROM $bookings->tableName";

        $results    = $wpdb->get_results($query);

        foreach($results as $booking){
            $exploded   = explode(';', $booking->subject);

            $subject    = $exploded[0];
            $room       = '';
            if(!empty($exploded[1])){
                $room   = $exploded[1];
            }

            $wpdb->update(
                $bookings->tableName,
                [
                    'subject'   => $subject,
                    'room'      => $room
                ],
                array(
                    'id'		=> $booking->id
                ),
            );
        }
    }

    if($oldVersion < '8.4.1'){
        maybe_add_column($forms->formEmailTable, 'days_before', "ALTER TABLE $forms->formEmailTable ADD COLUMN `days_before` int");
        maybe_add_column($forms->formEmailTable, 'days_after', "ALTER TABLE $forms->formEmailTable ADD COLUMN `days_after` int");
    }
}