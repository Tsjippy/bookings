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

register_post_meta( 'booking-subject', 'managers', [
    'single'       => false,
    'type'         => 'integer',
    'show_in_rest' => true,
] );

register_post_meta( 'booking-subject', 'payments', [
    'single'       => true,
    'type'         => 'boolean',
    'show_in_rest' => true,
] );

register_post_meta( 'booking-subject', 'overlap', [
    'single'       => true,
    'type'         => 'boolean',
    'show_in_rest' => true,
] );

register_post_meta( 'booking-subject', 'overlap_period', [
    'single'       => true,
    'type'         => 'integer',
    'show_in_rest' => true,
] );

register_post_meta( 'booking-subject', 'oneday', [
    'single'       => true,
    'type'         => 'boolean',
    'show_in_rest' => true,
] );

register_post_meta( 'booking-subject', 'default_booking_state', [
    'single'       => true,
    'type'         => 'string',
    'show_in_rest' => true,
] );