<?php
namespace SIM\BOOKINGS;
use SIM;

// check if a booking request is ok
add_filter('sim_before_saving_formdata', __NAMESPACE__.'\beforeSavingFormData', 99, 3);
function beforeSavingFormData($formResults, $object, $update){
    $startDates = [];
    if(isset($formResults['booking-startdate'])){
        $startDates = (array)$formResults['booking-startdate'];

        unset($formResults['booking-startdate']);
    }

    $endDates   = [];
    if(isset($formResults['booking-enddate'])){
        $endDates   = (array)$formResults['booking-enddate'];

        unset($formResults['booking-enddate']);
    }

    $rooms  = [];
    if(isset($formResults['booking-rooms']) && is_array($formResults['booking-rooms'])){
        $rooms   = $formResults['booking-rooms'];

        unset($formResults['booking-rooms']);
    }

    $bookings                   = new Bookings($object);

    // find the subject
    $elements             = $bookings->getBookingElements();
    if(is_wp_error($elements) || empty($elements)){
        return $formResults;
    }

    // loop over all booking selectors (usually one)
    foreach($elements as $element){
        $bookingDetails = $bookings->getElementSubjects($element->id);
        $subjectName    = $formResults[$element->name];

        // somehow we do not have any data
        if(empty($bookingDetails)){
            return new \WP_Error('bookings', "No booking details found");
        }

        // Same start and end date
        foreach($startDates as $index => $startdate){
            if($startdate == $endDates[$index]){
                return new \WP_Error('bookings', "End date cannot be the same as the start date");
            }
        }

        // find the selected subject
        foreach($bookingDetails as $subject){
            if(
                !empty($subject['name']) &&             // Subjects name is set 
                $subject['name'] == $subjectName &&     // and this is the selected subject
                !empty($subject['rooms'])   &&          // and the subject has a key called rooms
                count($subject['rooms']) > 1 &&         // and there is more than 1 room for this subject
                empty($rooms)                           // but there is no room selected
            ){
                return new \WP_Error('bookings', "Please select a room");
            }
        }

        // Check for overlapping dates
        $subject        = $formResults[$element->name];
        $submissionId   = $formResults['id'];

        // We are updating an existing booking
        if($update){
            // Update booking dates
            if(!empty($startDates)){
                // Get the booking to update
                $currentBookings    = $bookings->getBookingsBySubmission($formResults['id']);

                foreach($currentBookings as $index => $booking){
                    $values = [
                        'startdate' => $startDates[$index],
                        'enddate'   => $endDates[$index]
                    ];

                    $bookings->updateBooking($booking, $values, true);
                }
            }

            // Change to paid / unpaid
            $paymentIndicatorEl    = $object->formData->payment_indicator;
            $paymentIndicatorName  = $object->getElementById($paymentIndicatorEl, 'name');
            if(!empty($formResults[$paymentIndicatorName])){
                $paid   = $formResults[$paymentIndicatorName] != 'not paid';
                $bookings->changePaymentStatus($paid, $booking);
            }

            // Add, update or remove rooms
            if(!empty($rooms)){
                $result = $bookings->updateRooms($rooms, $currentBookings);
                if(is_wp_error($result)){
                    return $result;
                }
            }
        }
        
        // we are making a new booking
        else{

            if(!empty($rooms)){
                foreach($rooms as $index => $room){
                    // Create a booking for this room
                    $result     = $bookings->insertBooking($startDates[$index], $endDates[$index], $subject, $room, $submissionId);

                    if(is_wp_error($result)){
                        return $result;
                    }
                }
            }else{
                // Create a booking
                $result         = $bookings->insertBooking($startDates[0], $endDates[0], $subject, '', $submissionId);

                if(is_wp_error($result)){
                    return $result;
                }
            }
        }
    }

    // Update the amount to be paid
    $amount             = $bookings->calculatePaymentAmount($startDates, $endDates, $rooms);

    $paymentAmountEl    = $bookings->forms->formData->payment_amount_el;
    $paymentAmountName  = $bookings->forms->getElementById($paymentAmountEl, 'name');

    if(!empty($paymentAmountName)){
        $formResults[$paymentAmountName] = $amount;
    }

    return $formResults;
}

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
    }
}

add_filter('sim-forms-transform-empty', __NAMESPACE__.'\transformEmpty', 10, 3);
function transformEmpty($replaceValue, $instance, $match){

    if($match == "booking-details"){
        
        if(!empty($instance->submission->formresults['booking-startdate'])){
            $startDates     = $instance->submission->formresults['booking-startdate'];
            $endDates       = $instance->submission->formresults['booking-enddate'];

            // NO ROOMS
            if(empty($instance->submission->formresults['booking-rooms'])){
                $startDate      = date(DATEFORMAT, strtotime((string)$startDates[0]));
                $endDate        = date(DATEFORMAT, strtotime((string)$endDates[0]));
                $replaceValue   = "from $startDate till $endDate";
            }else{
                if(!is_array($instance->submission->formresults['booking-rooms'])){
                    SIM\printArray($instance->submission->formresults['booking-rooms']);
                    $instance->submission->formresults['booking-rooms']  = [$instance->submission->formresults['booking-rooms']];
                }

                if(count( array_unique($startDates)) == 1 && count(array_unique($endDates)) == 1){
                    $startDate      = array_values($startDates)[0];
                    $endDate        = array_values($endDates)[0];
                    $rooms          = implode('&', $instance->submission->formresults['booking-rooms']);
                    $startDate      = date(DATEFORMAT, strtotime((string)$startDate));
                    $endDate        = date(DATEFORMAT, strtotime((string)$endDate));
                    $replaceValue   = "room $rooms from $startDate till $endDate";
                }else{
                    $replaceValue   = "room:<br>";
                    foreach($instance->submission->formresults['booking-rooms'] as $room){
                        $startDate      = date(DATEFORMAT, strtotime((string)$startDates[$room]));
                        $endDate        = date(DATEFORMAT, strtotime((string)$endDates[$room]));

                        $replaceValue   .= "$room from $startDate till $endDate<br>";
                    }
                }
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
