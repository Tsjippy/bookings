<?php
namespace TSJIPPY\BOOKINGS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('init', __NAMESPACE__.'\addEventPostType', 999);
function addEventPostType(){
	TSJIPPY\registerPostTypeAndTax('booking-subject', 'booking-subjects');
	TSJIPPY\registerPostTypeAndTax('booking-room', 'booking-rooms');
}

add_filter('tsjippy-template-filter', __NAMESPACE__.'\renameModule');
function  renameModule($templateFile){
    $templateFile   = str_replace('/booking-subjects/', '/bookings/', $templateFile);
    $templateFile   = str_replace('/booking-room/', '/bookings/', $templateFile);

    return $templateFile;
}

// Alters the arguments used to register the booking post types
add_filter('tsjippy-post-type-creation-args', function($args, $single){
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
