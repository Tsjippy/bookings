<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('init', __NAMESPACE__ . '\initBlocks');
function initBlocks()
{
    // Register all js blocks
    wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
}

register_post_meta( 'booking-subject', 'tsjippy_managers', [
    'single'       => false,
    'type'         => 'integer',
    'show_in_rest' => true,
] );

register_post_meta( 'booking-subject', 'tsjippy_payments', [
    'single'       => true,
    'type'         => 'boolean',
    'show_in_rest' => true,
] );

register_post_meta( 'booking-subject', 'tsjippy_overlap', [
    'single'       => true,
    'type'         => 'boolean',
    'show_in_rest' => true,
] );

register_post_meta( 'booking-subject', 'tsjippy_overlap_period', [
    'single'       => true,
    'type'         => 'integer',
    'show_in_rest' => true,
] );

register_post_meta( 'booking-subject', 'tsjippy_oneday', [
    'single'       => true,
    'type'         => 'boolean',
    'show_in_rest' => true,
] );

register_post_meta( 'booking-subject', 'tsjippy_default_booking_state', [
    'single'       => true,
    'type'         => 'string',
    'show_in_rest' => true,
] );