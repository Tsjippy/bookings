<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('sim-forms-extra-form-settings', __NAMESPACE__.'\extraFormSettings');
function extraFormSettings($object){
    // check if the form has a booking selector eement
    $bookingElements   = $object->getElementByType('booking-selector');

    if(!$bookingElements || is_wp_error($bookingElements)){
        return;
    }

    ?>
    <br>
    <h4>Payment Indicator Element</h4>
    <select name="payment-amount-el">
        <option value=''>---</option>
        <?php
        foreach($object->formElements as $element){
            $checked    = '';
            if($checked == '' && $object->formData->payment_indicator == $element->id){
                $checked = 'selected';
            }

            echo "<option value='$element->id' $checked>$element->nicename</option>";
        }
        ?>
    </select>

    <br>
    <h4>Payment Amount Element</h4>
    <select name="payment-amount-el">
        <option value=''>---</option>
        <?php
        foreach($object->formElements as $element){
            $checked    = '';
            if($checked == '' && $object->formData->payment_amount_el == $element->id){
                $checked = 'selected';
            }

            echo "<option value='$element->id' $checked>$element->nicename</option>";
        }
        ?>
    </select>

    <br>
    <h4>Payment Details Element</h4>
    <select name="payment-details-el">
        <option value=''>---</option>
        <?php
        foreach($object->formElements as $element){
            $checked    = '';
            if($checked == '' && $object->formData->payment_details_el == $element->id){
                $checked = 'selected';
            }

            echo "<option value='$element->id' $checked>$element->nicename</option>";
        }
        ?>
    </select>

    <br>
    <h4>Price Per Night Element</h4>
    <select name="price-per-night-el">
        <option value=''>---</option>
        <?php
        foreach($object->formElements as $element){
            $checked    = '';
            if($checked == '' && $object->formData->price_per_night_el == $element->id){
                $checked = 'selected';
            }

            echo "<option value='$element->id' $checked>$element->nicename</option>";
        }
        ?>
    </select>
    <?php
}

add_filter('sim-forms-before-saving-settings', __NAMESPACE__.'\beforeSavingSettings', 10, 3);
function beforeSavingSettings($settings, $object, $formId){
    $settings['payment_amount_el']	= is_numeric($_POST['payment-amount-el'])   ? $_POST['payment-amount-el'] : false;

    $settings['payment_indicator']	= is_numeric($_POST['payment-amount-el'])   ? $_POST['payment-amount-el'] : false;

    $settings['payment_details_el']	= is_numeric($_POST['payment-details-el'])   ? $_POST['payment-details-el'] : false;

    $settings['price_per_night_el']	= is_numeric($_POST['price-per-night-el'])   ? $_POST['price-per-night-el'] : false;

    return $settings;
}