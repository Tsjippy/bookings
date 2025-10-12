<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('sim_bookings_module_update', __NAMESPACE__.'\moduleUpdate');
function moduleUpdate($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    SIM\printArray($oldVersion);

    $bookings = new Bookings();

    $forms	=  $bookings->forms;

    if($oldVersion < '8.1.0'){
        maybe_add_column($bookings->tableName, 'paid', "ALTER TABLE $bookings->tableName ADD COLUMN `paid` BOOL");

        SIM\printArray("Added 'paid' column to '$bookings->tableName' table");

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

        $results    = $wpdb->get_results("SELECT * FROM $forms->elTableName WHERE type = 'booking_selector'");
        foreach($results as $element){
            $wpdb->update(
                $forms->elTableName,
                [
                    'type'  => 'booking-selector'
                ],
                array(
                    'id'		=> $element->id
                ),
            );
        }

        $results    = $wpdb->get_results("SELECT * FROM $forms->elTableName WHERE type = 'booking-selector'");

        foreach($results as $element){
            $bookingDetails  = maybe_unserialize($element->booking_details);

            if(!is_array($bookingDetails)){
                continue;
            }

            foreach($bookingDetails['subjects'] as $subject){

                $subject['element-id'] = $element->id;

                // insert a post for subject description
                $postId  = wp_insert_post([
                    'post_title'    => $subject['name'],
                    'post_type'     => 'booking subject',
                    'post_status'   => 'publish',
                    'post_content'  => isset($subject['description']) ? $subject['description'] : ''
                ]);

                unset($subject['description']);
                unset($subject['name']);

                if(isset($subject['rooms']) && is_array($subject['rooms']) && count($subject['rooms']) > 1){
                    foreach($subject['rooms'] as $room){
                        if(isset($room['name'])){
                            $room = $room['name'];
                        }

                        $roomId = wp_insert_post([
                            'post_title'    => $subject['name']." Room $room",
                            'post_type'     => 'booking room',
                            'post_status'   => 'publish',
                            'post_content'  => '',
                            'post_parent'   => $postId
                        ]);
                        
                        add_post_meta($postId, 'room', [$roomId => $room]);
                    } 
                }  
                unset($subject['rooms']);

                $subject['confirmed_booking_roles'] = array_keys(array_filter($subject['confirmed_booking_roles']));
                
                foreach($subject as $key => $value){
                    $key = str_replace('_', '-', strtolower($key));

                    update_post_meta($postId, $key, $value);
                }
            }
        }
    }
}