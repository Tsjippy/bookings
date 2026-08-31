<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('tsjippy-forms-extra-form-settings', __NAMESPACE__ . '\extraFormSettings');
/**
 * Add extra form settings for booking forms
 *
 * @param   object  $object   The form object
 */
function extraFormSettings($object)
{
    // check if the form has a booking selector eement
    $bookingBlocks   = $object->getBlockByType('accomodation');

    if (!$bookingBlocks || is_wp_error($bookingBlocks)) {
        return;
    }

    ?>
    <br>
    <h4>
        Payment Indicator Block
    </h4>
    <select name="payment-amount-el">
        <option value=''>---</option>
        <?php
        foreach ($object->formBlocks as $block) {
        ?>
            <option value='<?php echo esc_attr($block->blockId); ?>' <?php if ($object->formData->payment_indicator == $block->blockId) echo 'selected'; ?>>
                <?php echo esc_html($block->name); ?>
            </option>
        <?php
        }
        ?>
    </select>

    <br>
    <h4>
        Payment Amount Block
    </h4>
    <select name="payment-amount-el">
        <option value=''>---</option>
        <?php
        foreach ($object->formBlocks as $block) {
        ?>
            <option value='<?php echo esc_attr($block->blockId); ?>' <?php if ($object->formData->payment_amount_el == $block->blockId) echo 'selected'; ?>>
                <?php echo esc_html($block->name); ?>
            </option>
        <?php
        }
        ?>
    </select>

    <br>
    <h4>
        Payment Details Block
    </h4>
    <select name="payment-details-el">
        <option value=''>---</option>
        <?php
        foreach ($object->formBlocks as $block) {
        ?>
            <option value='<?php echo esc_attr($block->blockId); ?>' <?php if ($object->formData->payment_details_el == $block->blockId) echo 'selected'; ?>>
                <?php echo esc_html($block->name); ?>
            </option>
        <?php
        }
        ?>
    </select>

    <br>
    <h4>
        Price Per Night Block
    </h4>
    <select name="price-per-night-el">
        <option value=''>
            ---
        </option>
        <?php
        foreach ($object->formBlocks as $block) {
        ?>
            <option value='<?php echo esc_attr($block->blockId); ?>' <?php if ($object->formData->price_per_night_el == $block->blockId) echo 'selected'; ?>>
                <?php echo esc_html($block->name); ?>
            </option>
        <?php
        }
        ?>
    </select>
<?php
}

add_filter('tsjippy-forms-before-saving-settings', __NAMESPACE__ . '\beforeSavingSettings', 10, 3);
/**
 * Add extra form settings for booking forms
 *
 * @param   array   $request   The origal request data
 * @param   object  $object    The form object
 *
 * @param   int     $formId    The id of the form being saved
 */
function beforeSavingSettings($request, $object, $formId)
{
    $request['payment_amount_el']  = is_numeric($request['payment-amount-el'] ?? '') ? $request['payment-amount-el'] : false;

    $request['payment_indicator']  = is_numeric($request['payment-amount-el'] ?? '') ? $request['payment-amount-el'] : false;
    $request['payment_details_el'] = is_numeric($request['payment-details-el'] ?? '') ? $request['payment-details-el'] : false;

    $request['price_per_night_el'] = is_numeric($request['price-per-night-el'] ?? '') ? $request['price-per-night-el'] : false;

    return $request;
}
