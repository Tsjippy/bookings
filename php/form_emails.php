<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('tsjippy-forms-after-email-triggers', __NAMESPACE__ . '\addBookingEmails', 10, 2);

/**
 * Adds booking-specific email triggers to the form
 *
 * @param int $key The email key
 * @param object $email The email object
 */
function addBookingEmails($key, $email)
{
    ?>
    <label>
        <input 
            type='radio' 
            name='emails[<?php echo esc_attr($key); ?>][email-trigger]' 
            class='email-trigger' 
            value='before-stay' 
            <?php if ($email->email_trigger == 'before-stay') {
                echo 'checked';
            } ?>>
        <input 
            type='number' 
            name='emails[<?php echo esc_attr($key); ?>][days-before]' 
            <?php if (is_numeric($email->days_before)) {
                echo esc_attr("value='{$email->days_before}'");
            } ?> 
            style='max-width: 70px;'> 
            days before their booking starts
    </label>
    <br>
    <label>
        <input 
            type='radio' 
            name='emails[<?php echo esc_attr($key); ?>][email-trigger]' 
            class='email-trigger' 
            value='after-stay' 
            <?php if ($email->email_trigger == 'after-stay') {
                echo 'checked';
            } ?>>
        <input 
            type='number' 
            name='emails[<?php echo esc_attr($key); ?>][days-after]' 
            <?php if (is_numeric($email->days_after)) {
                echo esc_attr("value='{$email->days_after}'");
            } ?> 
            style='max-width: 70px;'> 
            days after their booking finished (0 means on the end date)
    </label>
    <br>
    <?php
}

// adds validation for the extra columns in the emails table
add_filter('tsjippy-forms-email-table-formats', __NAMESPACE__ . '\addEmailTableFormats', 10, 2);
/**
 * Adds the formats for the extra columns in the emails table
 *
 * @param array $formats The current formats for the emails table
 * @param object $object The form object
 *
 * @return array The updated formats for the emails table
 */
function addEmailTableFormats($formats, $object)
{
    $formats['days_before'] = '%s';
    $formats['days_after']  = '%s';

    return $formats;
}
