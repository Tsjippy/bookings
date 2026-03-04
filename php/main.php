<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('sim-forms-entry-archived', __NAMESPACE__.'\removeBookings', 10, 2);
add_action('sim-forms-entry-removed', __NAMESPACE__.'\removeBookings', 10, 2);
function removeBookings($instance, $submissionId){
    // remove the booking
    $bookings           = new Bookings();

    $currentBookings    = $bookings->getBookingsBySubmission($submissionId);

    if(!$currentBookings){
        return;
    }

    if($_POST['action'] == 'archive'){

        foreach($currentBookings as $booking){

            if(!empty($_POST['subid']) && $booking->room != $_POST['subid']){
                // we should only remove the requested booking
                continue;
            }

            $bookings->removeBooking($booking);
        }
    }else{
        // to do re-insert booking on inarchive
    }
}

// Filter e-mail transforms
add_filter('sim-forms-transform-array', __NAMESPACE__.'\transformArray', 10, 4);
function transformArray($string, $replaceValue, $forms, $match){
    if(count(array_unique($replaceValue)) == 1){
        $string = array_unique($replaceValue)[0];
    }else{

    }
    return $string;
}

// add the booking details to the drop down for use in e-mails
add_action('sim-add-email-placeholder-option', __NAMESPACE__.'\placeholderOption');
function placeholderOption($formBuilderForm){
    if($formBuilderForm->getElementByType('booking-selector')){
        echo "<option>%booking-startdate%</option>";
        echo "<option>%booking-enddate%</option>";
        echo "<option>%booking-rooms%</option>";
        echo "<option>%booking-details%</option>";
        echo "<option>%paid%</option>";
        echo "<option title='total amount to be paid'>%payable%</option>";
        echo "<option>%payment_details%</option>";
        echo "<option>%price_per_night%</option>";
        echo "<option title='from %startdate% till %enddate%'>%duration%</option>";
    }
}

add_filter('sim-forms-transform-empty', __NAMESPACE__.'\transformEmpty', 10, 4);
function transformEmpty($replaceValue, $match, $replaceValues, $instance){
    if(
        $match != "booking-details" || 
        (
            empty($_POST['booking-startdate']) &&
            empty($replaceValues['booking-startdate'])
        )
    ){
        return $replaceValue;
    }

    if(empty($_POST['booking-startdate'])){
        $startDates     = (array)$replaceValues['booking-startdate'];
        $endDates       = (array)$replaceValues['booking-enddate'];
        $rooms          = (array)$replaceValues['booking-rooms'];
    }else{
        $startDates     = $_POST['booking-startdate'];
        $endDates       = $_POST['booking-enddate'];
        $rooms          = (array)$_POST['booking-rooms'];
    }

    // NO ROOMS
    if(empty($rooms)){
        $startDate      = date(DATEFORMAT, strtotime((string)$startDates[0]));
        $endDate        = date(DATEFORMAT, strtotime((string)$endDates[0]));
        $replaceValue   = "from $startDate till $endDate";
    }else{
        if(count( array_unique($startDates)) == 1 && count(array_unique($endDates)) == 1){
            $startDate      = array_values($startDates)[0];
            $endDate        = array_values($endDates)[0];
            $rooms          = implode('&', $rooms);
            $startDate      = date(DATEFORMAT, strtotime((string)$startDate));
            $endDate        = date(DATEFORMAT, strtotime((string)$endDate));
            $replaceValue   = "room $rooms from $startDate till $endDate";
        }else{
            $replaceValue   = "room:<br>";
            foreach($rooms as $room){
                $startDate      = date(DATEFORMAT, strtotime((string)$startDates[$room]));
                $endDate        = date(DATEFORMAT, strtotime((string)$endDates[$room]));

                $replaceValue   .= "$room from $startDate till $endDate<br>";
            }
        }
    }

    return $replaceValue;
}

add_action('init', __NAMESPACE__.'\addEventPostType', 999);
function addEventPostType(){
	SIM\registerPostTypeAndTax('booking-subject', 'booking-subjects');
	SIM\registerPostTypeAndTax('booking-room', 'booking-rooms');
}

add_filter('sim-template-filter', __NAMESPACE__.'\renameModule');
function  renameModule($templateFile){
    $templateFile   = str_replace('/booking-subjects/', '/bookings/', $templateFile);
    $templateFile   = str_replace('/booking-room/', '/bookings/', $templateFile);

    return $templateFile;
}

// Alters the arguments used to register the booking post types
add_filter('sim-post-type-creation-args', function($args, $single){
    if($single == 'booking-rooms'){
        $args['hierarchical']   = false;

        $args['rewrite']    = [
            'slug'  => 'accomodation-rooms',
        ];
    }

    if($single == 'booking-subject'){
        $args['hierarchical']   = false;

        $args['rewrite']    = [
            'slug'  => 'accomodations',
        ];
    }

    return $args;
}, 10, 2);
