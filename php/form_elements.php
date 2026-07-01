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

/**
 * Get the payment information for a given element
 *
 * @param mixed $v The element to check
 *
 * @return string The payment information or an empty string
 */
function getElementSubjectsPayments($v)
{
    if (is_array($v) && isset($v['payments'])) {
        return $v['payments'];
    }
    return '';
}
