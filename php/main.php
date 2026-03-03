<?php
namespace SIM\BOOKINGS;
use SIM;

// check if a booking request is ok
add_filter('sim_before_inserting_formdata', __NAMESPACE__.'\beforeSavingFormData', 99, 2);

function beforeSavingFormData($submission, $object){
    $startDates = [];
    if(isset($submission->{'booking_startdate'})){
        $startDates = (array)$submission->{'booking_startdate'};

        $startDates = SIM\cleanUpNestedArray($startDates);

        unset($submission->{'booking_startdate'});
    }

    $endDates   = [];
    if(isset($submission->{'booking_enddate'})){
        $endDates   = (array)$submission->{'booking_enddate'};
        $endDates   = SIM\cleanUpNestedArray($endDates);

        unset($submission->{'booking_enddate'});
    }

    if(empty($startDates) || empty($endDates)){
        SIM\printArray("No dates found for submission with id $submission->id");
        return;
    }

    $rooms  = [];
    if(!empty($submission->{'booking_rooms'})){
        $rooms   = $submission->{'booking_rooms'};

        if(!is_array($rooms)){
            $rooms  = [$rooms];
        }

        unset($submission->{'booking_rooms'});
    }

    $bookings                   = new Bookings($object);

    // find the subject
    $elements             = $bookings->getBookingElements();
    if(is_wp_error($elements) || empty($elements)){
        return $submission;
    }

    // loop over all booking selectors (usually one)
    foreach($elements as $element){
        $subjects       = $bookings->getElementSubjects($element->id);
        $subjectName    = $submission->{$element->id};

        // somehow we do not have any data
        if(empty($subjects)){
            return new \WP_Error('bookings', "No booking details found");
        }

        if(!empty($submission->id)){
            $currentBookings    = $bookings->getBookingsBySubmission($submission->id);
        }

        // Same start and end date
        foreach($startDates as $index => $startdate){
            if($startdate == $endDates[$index]){
                return new \WP_Error('bookings', "End date cannot be the same as the start date");
            }

            $bookingId = -1;
            if(!empty($currentBookings)){
                $bookingId = $currentBookings[0]->id;
            }

            $overlappingBookings    = $bookings->checkOverlap($startdate, $endDates[$index], $subjectName, $rooms[$index], $bookingId);
            if(!empty($overlappingBookings)){
                if(!empty($rooms[$index])){
                    $subjectName    .= " room {$rooms[$index]}";
                }

                $startDateString    = date(DATEFORMAT, strtotime($startdate));
                $endDateString      = date(DATEFORMAT, strtotime($endDates[$index]));
                return new \WP_Error('booking', "The booking for $subjectName overlaps with an existing one from $startDateString till $endDateString, try again");
            }
        }

        // find the selected subject
        foreach($subjects as $subject){
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
    }

    // Update the amount to be paid
    $amount             = $bookings->calculatePaymentAmount($startDates, $endDates, $rooms);

    $paymentAmountElId  = $bookings->forms->formData->payment_amount_el;

    if(!empty($paymentAmountElId)){
        $submission->{$paymentAmountElId} = $amount;
    }

    return $submission;
}

// Insert a new booking
add_filter('sim_after_form_submission', __NAMESPACE__.'\afterFormSubmission', 99, 3);
function afterFormSubmission($message, $submission, $object){
    $startDates = [];
    if(isset($submission['booking-startdate'])){
        $startDates = (array)$submission['booking-startdate'];

        unset($submission['booking-startdate']);
    }

    $endDates   = [];
    if(isset($submission['booking-enddate'])){
        $endDates   = (array)$submission['booking-enddate'];

        unset($submission['booking-enddate']);
    }

    $rooms  = [];
    if(isset($submission['booking-rooms']) && is_array($submission['booking-rooms'])){
        $rooms   = $submission['booking-rooms'];

        unset($submission['booking-rooms']);
    }

    $bookings                   = new Bookings($object);

    // find the subject
    $elements             = $bookings->getBookingElements();
    if(is_wp_error($elements) || empty($elements)){
        return $message;
    }

    // loop over all booking selectors (usually one)
    foreach($elements as $element){

        $subject        = $submission[$element->name];
        $submissionId   = $object->submission->id;
        
        //Create a booking for each room
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

    // Update the amount to be paid
    $amount             = $bookings->calculatePaymentAmount($startDates, $endDates, $rooms);

    $paymentAmountEl    = $bookings->forms->formData->payment_amount_el;
    $paymentAmountName  = $bookings->forms->getElementById($paymentAmountEl, 'name');

    if(!empty($paymentAmountName)){
        $submission[$paymentAmountName] = $amount;
    }

    return $message;
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
            empty($replaceValues['booking_startdate'])
        )
    ){
        return $replaceValue;
    }

    if(empty($_POST['booking-startdate'])){
        $startDates     = (array)$replaceValues['booking_startdate'];
        $endDates       = (array)$replaceValues['booking_enddate'];
        $rooms          = (array)$replaceValues['booking_rooms'];
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
    if($single == 'booking_rooms'){
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
