<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

// Filter e-mail transforms
add_filter('tsjippy-forms-transform-array', __NAMESPACE__ . '\transformArray', 10, 4);
/**
 * Transform an array of values into a single string
 *
 * @param string $string The original string
 * @param array $replaceValue The list of values to replace with
 * @param object $forms The forms object
 * @param string $match The match pattern
 *
 * @return string The updated string
 */
function transformArray($string, $replaceValue, $forms, $match)
{
    if (count(array_unique($replaceValue)) == 1) {
        $string = array_unique($replaceValue)[0];
    } else {
    }
    return $string;
}

// add the booking details to the drop down for use in e-mails
add_action('tsjippy-add-email-placeholder-option', __NAMESPACE__ . '\placeholderOption');
/**
 * Add booking details as email placeholders
 *
 * @param object $formBuilderForm The form builder form object
 */
function placeholderOption($formBuilderForm)
{
    if ($formBuilderForm->getElementByType('booking-selector')) {
        echo "<option>%booking-start-date%</option>";
        echo "<option>%booking-end-date%</option>";
        echo "<option>%booking-rooms%</option>";
        echo "<option>%booking-details%</option>";
        echo "<option>%paid%</option>";
        echo "<option title='total amount to be paid'>%payable%</option>";
        echo "<option>%payment_details%</option>";
        echo "<option>%price_per_night%</option>";
        echo "<option title='from %start_date% till %end_date%'>%duration%</option>";
    }
}

add_filter('tsjippy-forms-transform-empty', __NAMESPACE__ . '\transformEmpty', 10, 4);
/**
 * Transform empty values for booking details
 *
 * @param string $replaceValue The value to replace
 * @param string $match The match pattern
 * @param array $replaceValues The list of replacement values
 * @param object $instance The form instance
 *
 * @return string The updated value
 */
function transformEmpty($replaceValue, $match, $replaceValues, $instance)
{
    if (
        $match != "booking-details" ||
        (
            empty($_POST['booking-start-date']) &&
            empty($replaceValues['booking-start-date'])
        )
    ) {
        return $replaceValue;
    }

    if (empty($_POST['booking-start-date'])) {
        $startDates     = (array)$replaceValues['booking-start-date'];
        $endDates       = (array)$replaceValues['booking-end-date'];
        $rooms          = (array)$replaceValues['booking-rooms'];
    } else {
        $startDates     = $_POST['booking-start-date'];
        $endDates       = $_POST['booking-end-date'];
        $rooms          = (array)$_POST['booking-rooms'];
    }

    // NO ROOMS
    if (empty($rooms)) {
        $startDate      = gmdate(DATEFORMAT, strtotime((string)$startDates[0]));
        $endDate        = gmdate(DATEFORMAT, strtotime((string)$endDates[0]));
        $replaceValue   = "from $startDate till $endDate";
    } else {
        if (count(array_unique($startDates)) == 1 && count(array_unique($endDates)) == 1) {
            $startDate      = array_values($startDates)[0];
            $endDate        = array_values($endDates)[0];
            $rooms          = implode('&', $rooms);
            $startDate      = gmdate(DATEFORMAT, strtotime((string)$startDate));
            $endDate        = gmdate(DATEFORMAT, strtotime((string)$endDate));
            $replaceValue   = "room $rooms from $startDate till $endDate";
        } else {
            $replaceValue   = "room:<br>";
            foreach ($rooms as $room) {
                $startDate      = gmdate(DATEFORMAT, strtotime((string)$startDates[$room]));
                $endDate        = gmdate(DATEFORMAT, strtotime((string)$endDates[$room]));

                $replaceValue   .= "$room from $startDate till $endDate<br>";
            }
        }
    }

    return $replaceValue;
}
