<?php

namespace TSJIPPY\BOOKINGS;

if (! defined('ABSPATH')) {
    exit;
}

add_filter('tsjippy-forms-shortcode-table-formats', __NAMESPACE__ . '\addShortcodeFormat', 10, 2);
/**
 * Add extra formats for the booking selector shortcode
 *
 * @param array $formats The current list of formats
 * @param object $object The form results object
 *
 * @return array The updated list of formats
 */
function addShortcodeFormat($formats, $object)
{
    $formats['booking_display']       = '%s';

    return $formats;
}

add_filter('tsjippy-forms-form-table-formats', __NAMESPACE__ . '\addFormFormat', 10, 2);
/**
 * Add extra formats for the form table
 *
 * @param array $formats The current list of formats
 * @param object $object The form results object
 *
 * @return array The updated list of formats
 */
function addFormFormat($formats, $object)
{
    $formats['payment_indicator']       = '%d'; // payment_indicator
    $formats['payment_amount_el']       = '%d'; // payment_amount_el
    $formats['payment_details_el']      = '%d'; // payment_details_el
    $formats['price_per_night_el']      = '%d'; // price_per_night_el
    $formats['default_booking_state']   = '%s'; // default_booking_state

    return $formats;
}

add_filter('tsjippy-forms-blocks', __NAMESPACE__ . '\addBookingBlocks', 10, 3);
/**
 * Filters the blocks of this form,
 * @param    array   $blocks  The blocks array
 * @param    object  $object    The form instance
 * @param    bool    $force     Wheter to force a requery
 */
function addBookingBlocks($blocks, $object, $force)
{
    foreach($blocks as $block) {
        if ($block->type === 'accomodation') {
            $blocks[] = (object) [
                'block' => [
                    'blockName'     => 'tsjippy-forms/input',
                    'attrs'         => [
                        'type' => 'date',
                        'name' => 'booking-start-date',
                    ],
                    'innerBlocks'   => [],
                    'innerHTML'     => '<input class="wp-block-tsjippy-forms-input" type="date" name="booking-start-date" autocomplete="on" value="%value-placeholder%"/>',
                    'innerContent'  => ['<input class="wp-block-tsjippy-forms-input" type="date" name="booking-start-date" autocomplete="on" value="%value-placeholder%"/>'],
                ],
                'type'      => 'input',
                'blockId'   => '-102',
                'required'  => true,
                'slug'      => 'booking-start-date',
                'name'      => __('Booking Start Date', '%TEXTDOMAIN%'),
            ];

            $blocks[] = (object) [
                'block' => [
                    'blockName'     => 'tsjippy-forms/input',
                    'attrs'         => [
                        'type' => 'date',
                        'name' => 'booking-end-date',
                    ],
                    'innerBlocks'   => [],
                    'innerHTML'     => '<input class="wp-block-tsjippy-forms-input" type="date" name="booking-end-date" autocomplete="on" value="%value-placeholder%"/>',
                    'innerContent'  => ['<input class="wp-block-tsjippy-forms-input" type="date" name="booking-end-date" autocomplete="on" value="%value-placeholder%"/>'],
                ],
                'type'      => 'input',
                'blockId'   => '-103',
                'required'  => true,
                'slug'      => 'booking-end-date',
                'name'      => __('Booking End Date', '%TEXTDOMAIN%'),
            ];
        }
    }

    return $blocks;
}