<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('init', __NAMESPACE__.'\addEventPostType', 999);
function addEventPostType(){
	SIM\registerPostTypeAndTax('booking-subject', 'booking-subjects');
	SIM\registerPostTypeAndTax('booking-room', 'booking-rooms');
}

add_filter('sim-template-filter', __NAMESPACE__.'\renameModule');
function  renameModule($templateFile){
    $templateFile   = str_replace('/booking-subjects/', '/bookings/', $templateFile);
    $templateFile   = str_replace('/booking-room/', '/bookings/', $templateFile);

    return $templateFile;
}

// Alters the arguments used to register the booking post types
add_filter('sim-post-type-creation-args', function($args, $single){
    if($single == 'booking-rooms'){
        $args['hierarchical']   = false;

        $args['rewrite']    = [
            'slug'  => 'accomodation-rooms',
        ];
    }

    if($single == 'booking-subject'){
        $args['hierarchical']   = false;

        $args['rewrite']    = [
            'slug'  => 'accomodations',
        ];
    }

    return $args;
}, 10, 2);
