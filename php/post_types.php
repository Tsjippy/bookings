<?php
namespace TSJIPPY\BOOKINGS;
use TSJIPPY;

if ( ! defined('ABSPATH')) {
    exit;
}

add_action('init', __NAMESPACE__ . '\addEventPostType', 999);
function addEventPostType() {
    TSJIPPY\registerPostTypeAndTax('booking-subject', 'booking-subjects');
    TSJIPPY\registerPostTypeAndTax('booking-room', 'booking-rooms');
}

add_filter('tsjippy-template-filter', __NAMESPACE__ . '\changeTemplatePath');
/**
 * Alters the template path for the booking post types
 *
 * @param string $templateFile The template file path
 *
 * @return string The altered template file path
 */
function  changeTemplatePath($templateFile) {
    $templateFile   = str_replace('/tsjippy-booking-subjects/', '/tsjippy-bookings/', $templateFile);
    $templateFile   = str_replace('/tsjippy-booking-room/', '/tsjippy-bookings/', $templateFile);

    return $templateFile;
}

// Alters the arguments used to register the booking post types
add_filter('tsjippy-post-type-creation-args', function ($args, $single) {
    if ($single == 'booking-rooms') {
        $args['hierarchical']   = false;

        $args['rewrite']    = [
            'slug'  => 'accomodation-rooms',
        ];
    }

    if ($single == 'booking-subject') {
        $args['hierarchical']   = false;

        $args['rewrite']    = [
            'slug'  => 'accomodations',
        ];
    }

    return $args;
}, 10, 2);
