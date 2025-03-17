<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('sim-forms-extra-form-settings', __NAMESPACE__.'\extraFormSettings');
function extraFormSettings($object){
    ?>
    <br>
    <h4>Payment Indicator Element</h4>
    <select name="payment_indicator">
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
    <select name="payment_amount_el">
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
    <select name="payment_details_el">
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
    <select name="price_per_night_el">
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
function beforeSavingSettings($newSettings, $object, $formId){
    $newSettings['payment_amount_el']	= is_numeric($_POST['payment_amount_el'])   ? $_POST['payment_amount_el'] : false;

    $newSettings['payment_indicator']	= is_numeric($_POST['payment_indicator'])   ? $_POST['payment_indicator'] : false;

    $newSettings['payment_details_el']	= is_numeric($_POST['payment_details_el'])   ? $_POST['payment_details_el'] : false;

    $newSettings['price_per_night_el']	= is_numeric($_POST['price_per_night_el'])   ? $_POST['price_per_night_el'] : false;

    return $newSettings;
}