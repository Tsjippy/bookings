<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('sim_bookings_module_update', __NAMESPACE__.'\moduleUpdate');
function moduleUpdate($oldVersion){
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

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

        maybe_add_column($forms->shortcodeTable, 'booking_display', "ALTER TABLE $forms->shortcodeTable ADD COLUMN `booking_display` tinytext");
        $shortcodes   = $wpdb->get_results("SELECT * FROM $forms->shortcodeTable");
        
        foreach($shortcodes as &$shortcode){
            $tableSettings  = maybe_unserialize($shortcode->table_settings);

            if(!empty($tableSettings['booking-display'])){
                $data = [
                    'booking_display'   => $tableSettings['booking-display']
                ];

                $wpdb->update(
                    $forms->shortcodeTable, 
                    $data, 
                    ['id' => $shortcode->id]
                );
            }
        }

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

                $subject['confirmed_booking_roles'] = array_keys(array_filter($subject['confirmed_booking_roles']));
                
                foreach($subject as $key => $value){
                    $newKey = str_replace('_', '-', strtolower($key));

                    unset($subject[$key]);
                    $subject[$newKey] = $value;
                }

                if(count($subject['rooms']) < 2){
                    unset($subject['rooms']);
                }

                $subject['element-id']  = $element->id;

                $bookings->addSubject($subject);
            }
        }
    }

    if($oldVersion < '8.4.2'){
        $posts = get_posts([
            'post_type'         => 'booking-subject', 
            'posts_per_page'    => -1, 
            'post_status'       => 'publish',
            'orderby'           => 'title',
            'order'             => 'ASC',
        ]);
        
        foreach($posts as $post){
            $managers   = get_post_meta($post->ID, 'managers', true);

            delete_post_meta($post->ID, 'managers');

            foreach($managers as $key => $manager){
                if(is_numeric($manager)){
                    add_post_meta($post->ID, 'managers', $manager);
                }
            }
        }
    }

    if($oldVersion < '8.5.3'){
        maybe_drop_column($forms->elTableName, 'booking_details', "ALTER TABLE `$forms->elTableName` DROP `booking_details`;");
    }
}