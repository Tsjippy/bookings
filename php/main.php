<?php
namespace SIM\BOOKINGS;
use SIM;

// check if a booking request is ok
add_filter('sim_before_saving_formdata', __NAMESPACE__.'\beforeSavingFormData', 99, 2);
function beforeSavingFormData($formResults, $object){
    $bookings                   = new Bookings($object);

    // find the subject
    $elements             = $bookings->getSubjectData();
    if(is_wp_error($elements)){
        return $elements;
    }

    if(empty($elements)){
        return $formResults;
    }

    // loop over all booking selectors (usually one)
    foreach($elements as $element){
        $bookingDetails = $element->booking_details;
        $subjectName    = $formResults[$element->name];

        // somehow we do not have any data
        if(empty($bookingDetails['subjects'])){
            return new \WP_Error('bookings', "No booking details found");
        }

        // Same start and end date
        foreach($formResults['booking-startdate'] as $index=>$startdate){
            if($startdate == $formResults['booking-enddate'][$index]){
                return new \WP_Error('bookings', "End date cannot be the same as the start date");
            }
        }

        // find the selected subject
        foreach($bookingDetails['subjects'] as $subject){
            if(
                !empty($subject['name']) &&             // Subjects name is set 
                $subject['name'] == $subjectName &&     // and this is the selected subject
                !empty($subject['rooms'])   &&          // and the subject has a key called rooms
                count($subject['rooms']) > 1 &&         // and there is more than 1 room for this subject
                empty($formResults['booking-room'])     // but there is no room selected
            ){
                return new \WP_Error('bookings', "Please select a room");
            }
        }

        // Check for overlapping dates
        $startDate      = $formResults['booking-startdate'];
        $endDate        = $formResults['booking-enddate'];
        $subject        = $formResults[$element->name];
        $submissionId   = $formResults['id'];

        if(!empty($formResults['booking-room'])){
            $startDates = [];
            $endDates   = [];

            foreach($formResults['booking-room'] as $index=>$room){
                // Create a booking for this room
                $result     = $bookings->insertBooking($startDate[$index], $endDate[$index], $subject, $room, $submissionId);

                if(is_wp_error($result)){
                    return $result;
                }

                // use the room name as index for the dates
                $startDates[$room]  = $startDate[$index];
                $endDates[$room]    = $endDate[$index];
            }

            // Store dates indexed by room
            $formResults['booking-startdate']  = $startDates;
            $formResults['booking-enddate']    = $endDates;
        }else{
            $result         = $bookings->insertBooking($startDate[0], $endDate[0], $subject, '', $submissionId);

            if(is_wp_error($result)){
                return $result;
            }
        }
    }

    return $formResults;
}

// Calculate the payable for the booking
add_filter('sim_after_saving_formdata', __NAMESPACE__.'\afterSavingFormData', 10, 2);
function afterSavingFormData($message, $object){
    $bookingElements   = $object->getElementByType('booking_selector');

    if(!$bookingElements || is_wp_error($bookingElements)){
        return $message;
    }

    $bookings                   = new Bookings($object);
    $bookings->calculatePaymentAmount();

    return $message;
}

// Update an existing booking
add_filter('sim-forms-submission-updated', __NAMESPACE__.'\onSubmissionUpdate', 10, 5);
function onSubmissionUpdate($message, $formTable, $elementName, $oldValue, $newValue){
    $bookings   =  new Bookings($formTable);
    $element    =  $bookings->forms->getElementByName($elementName);

    if($oldValue == $newValue){
        return $message;
    }

    // Get the subject
    $elements    = $bookings->forms->getElementByType('booking_selector');
    if(!$elements){
        return $message;
    }

    $subject            = $elements[0]->name;

    $currentBookings    = $bookings->getBookingsBySubmission($bookings->forms->submission->id);
    if(!$currentBookings || !isset($currentBookings[0])){
        return $message;
    }

    if(isset($_POST['booking_id']) && is_numeric($_POST['booking_id'])){
        foreach($currentBookings as $booking){
            if($booking->id == $_POST['booking_id']){
                $currentBooking  = $booking;
                break;
            }
        }
    }else{
        $currentBooking    = $currentBookings[0];
    }

    $elementName        = str_replace('booking-', '', $elementName);
    // change the $elementName to subject as that is the name of the column in the db
    if($subject == $elementName){
        $elementName  = 'subject';
    }

    // Change to paid / unpaid
    changePaymentStatus($bookings, $newValue, $element, $currentBookings);

    // Add or remove bookings
    $message    = updateRooms($message, $elementName, $oldValue, $newValue, $currentBooking, $currentBookings, $bookings);

    // location and date & time are editable
    if(in_array($elementName, ['startdate', 'enddate', 'startime', 'endtime', 'subject'])){
        // update the booking
        $result = $bookings->updateBooking($currentBooking, [$elementName => $newValue]);
        if(is_wp_error($result)){
            return $result;
        }
    }

    // Update the payable amount
    $message    = updatePayable($elementName, $bookings, $element, $message);

    return $message;
}

function updatePayable($elementName, $bookings, $element, $message){
    if(
        in_array($elementName, ['startdate', 'enddate', 'room']) || // We are dealing with a room change or a start or end date,
        $element->id == $bookings->forms->formData->price_per_night_el   // change in night price
    ){
        // calculate payable
        $payable    = $bookings->calculatePaymentAmount();

        $message    .= "<br>Payable amount is $payable";
    }

    return $message;
}

function changePaymentStatus($bookings, $newValue, $element, $currentBookings){
    // Change payment status
    if($bookings->forms->formData->payment_indicator != $element->id){
        return;
    }

    if($newValue == 'not paid'){
        $paid   = 0;
    }else{
        $paid   = 1;
    }

    // Mark as paid
    foreach($currentBookings as $b){
        $bookings->updateBooking($b, ['paid' => $paid]);
    }
    do_action('sim-booking-paid', $currentBookings, $bookings, $element, $newValue);
}

function updateRooms($message, $elementName, $oldValue, $newValue, $booking, $currentBookings, $bookings){
    if($elementName != 'room'){
        return $message;
    }

    $newMessage = implode('&', $newValue);
    $oldMessage = implode('<br>', $newValue);
    $message    = str_replace($oldMessage, $newMessage, $message, $count);

    $deleted    = array_diff($oldValue, $newValue);
    $added      = array_diff($newValue, $oldValue);

    // we changed a room
    if(count($oldValue) == count($newValue)){
        $deleted    = [];
        $added      = [];

        foreach($oldValue as $i=>$oldRoom){
            $newRoom    = $newValue[$i];

            // Find the booking for this room
            foreach($currentBookings as $b){
                if($oldRoom == $b->room){
                    $result     = $bookings->updateBooking($b, ['room' => $newRoom]);
                    break;
                }
            }
        }

        // Update the room
        $bookings->forms->submission->formresults['booking-room']         = array_values($newValue);

        // Update in db
        $bookings->updateSubmissionData();
    }

    // remove any removed bookings
    if(!empty($deleted)){
        foreach($currentBookings as $booking){
            $room   = $booking->room;
            // if this is the booking for the room
            if(in_array($room, $deleted)){
                // Delete the booking
                $result = $bookings->removeBooking($booking);

                // Delete the dates
                unset($bookings->forms->submission->formresults['booking-startdate'][$room]);
                unset($bookings->forms->submission->formresults['booking-enddate'][$room]);
            }
        }

        // Update the room
        $bookings->forms->submission->formresults['booking-room']         = array_values($newValue);

        // Update in db
        $bookings->updateSubmissionData();
    }

    // add new ones
    if(!empty($added)){
        foreach($added as $room){
            //Insert the new booking
            $result = $bookings->insertBooking($booking->startdate, $booking->enddate, $booking->subject, $room, $bookings->forms->submission->id);

            // Add the new dates
            $bookings->forms->submission->formresults['booking-startdate'][$room]  = $booking->startdate;
            $bookings->forms->submission->formresults['booking-enddate'][$room]    = $booking->enddate;
        }

        // Update the room
        $bookings->forms->submission->formresults['booking-room']         = array_values($newValue);

        // Update in db
        $bookings->updateSubmissionData();
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
    if($formBuilderForm->getElementByType('booking_selector')){
        echo "<option>%booking-startdate%</option>";
        echo "<option>%booking-enddate%</option>";
        echo "<option>%booking-room%</option>";
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
            if(empty($instance->submission->formresults['booking-room'])){
                
                $startDate      = date(get_option('date_format'), strtotime((string)$startDates[0]));
                $endDate        = date(get_option('date_format'), strtotime((string)$endDates[0]));
                $replaceValue   = "from $startDate till $endDate";
            }else{
                if(count( array_unique($startDates)) == 1 && count(array_unique($endDates)) == 1){
                    $rooms          = implode('&', $instance->submission->formresults['booking-room']);
                    $startDate      = date(get_option('date_format'), strtotime((string)$startDates[0]));
                    $endDate        = date(get_option('date_format'), strtotime((string)$endDates[0]));
                    $replaceValue   = "room $rooms from $startDate till $endDate";
                }else{
                    $replaceValue   = "room:<br>";
                    foreach($instance->submission->formresults['booking-room'] as $room){
                        $startDate      = date(get_option('date_format'), strtotime((string)$startDates[$room]));
                        $endDate        = date(get_option('date_format'), strtotime((string)$endDates[$room]));

                        $replaceValue   .= "$room from $startDate till $endDate<br>";
                    }
                }
            }
        }
        
    }

    return $replaceValue;
}
