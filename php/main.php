<?php
namespace SIM\BOOKINGS;
use SIM;

// check if a booking request is ok
add_filter('sim_before_saving_formdata', __NAMESPACE__.'\beforeSavingFormData', 99, 2);
function beforeSavingFormData($formResults, $object){
    // find the subject
    $elements       = $object->getElementByType('booking_selector');

    if(empty($elements)){
        return $formResults;
    }

    // loop over all booking selectors (usually one)
    foreach($elements as $element){
        $bookingDetails = unserialize($element->booking_details);
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
    }

    return $formResults;
}

// Create a booking
add_filter('sim_after_saving_formdata', __NAMESPACE__.'\afterSavingFormData', 10, 2);
function afterSavingFormData($message, $formBuilder){
    // find the subject
    $elements        = $formBuilder->getElementByType('booking_selector');

    if(isset($elements)){
    
        $bookings       = new Bookings($formBuilder);

        foreach($elements as $element){
            $startDate      = $formBuilder->submission->formresults['booking-startdate'];
            $endDate        = $formBuilder->submission->formresults['booking-enddate'];
            $subject        = $formBuilder->submission->formresults[$element->name];
            $submissionId   = $formBuilder->submission->formresults['id'];

            if(!empty($formBuilder->submission->formresults['booking-room'])){
                foreach($formBuilder->submission->formresults['booking-room'] as $index=>$room){
                    $result         = $bookings->insertBooking($startDate[$index], $endDate[$index], "$subject;$room", $submissionId);
                }
            }else{
                $result         = $bookings->insertBooking($startDate[0], $endDate[0], $subject, $submissionId);
            }

            if(is_wp_error($result)){
                return $result;
            }

            $bookings->calculatePaymentAmount();
        }
    }

    return $message;
}

// Update an existing booking
add_filter('sim-forms-submission-updated', __NAMESPACE__.'\onSubmissionUpdate', 10, 5);
function onSubmissionUpdate($message, $formTable, $elementName, $oldValue, $newValue){
    $element    =  $formTable->getElementByName($elementName);
    $bookings   =  new Bookings($formTable);

    // Change payment status
    if($formTable->formData->payment_indicator  == $element->id){
        // Mark as paid
        foreach($bookings->getBookingsBySubmission($formTable->submission->id) as $b){
            $bookings->updateBooking($b, ['paid' => 1]);
        }
    }

    // Update the payable amount
    if(
        in_array($elementName, ['booking-startdate', 'booking-enddate']) || // We are dealing with start or end date,
        $element->id == $formTable->formData->price_per_night_el ||         // change or night price
        $elementName == 'booking-room'                                              // or change in #rooms
    ){
        // calculate payable
        $bookings->calculatePaymentAmount();
    }

    // Subject changed
    $subject    = $formTable->getElementByType('booking_selector');
    if(!$subject){
        return $message;
    }
    $subject    = $subject[0]->name;

    // location and date & time are editable
    if(!in_array($elementName, ['booking-startdate', 'booking-enddate', 'startime', 'endtime', $subject, 'booking-room'])){
        return $message;
    }

    $elementName        = str_replace('booking-', '', $elementName);
    
    $currentBookings    = $bookings->getBookingsBySubmission($formTable->submission->id);

    if(isset($_POST['booking_id']) && is_numeric($_POST['booking_id'])){
        foreach($currentBookings as $index=>$booking){
            if($booking->id == $_POST['booking_id']){
                break;
            }
        }
    }else{
        if(!$currentBookings || !isset($currentBookings[0])){
            return $message;
        }

        $booking    = $currentBookings[0];

        $index      = 0;
    }

    // change the $elementName to subject as that is the name of the column in the db
    if($subject == $elementName){
        $elementName  = 'subject';
    }

    // multiple rooms and bookings
    if($elementName == 'room'){
        if(is_string($newValue) && str_contains($newValue, ';')){
            $newValue   = explode(';', $newValue);
        }
        
        $baseSubject= explode(';', $booking->subject)[0];

        $oldMessage = implode('&', $oldValue);
        $newMessage = implode('&', $newValue);

        $message    = str_replace($oldMessage, $newMessage, $message);

        $deleted    = array_diff($oldValue, $newValue);
        $added      = array_diff($newValue, $oldValue);

        // we changed a room
        if(count($oldValue) == count($newValue)){
            $deleted    = [];
            $added      = [];

            foreach($oldValue as $i=>$oldRoom){
                $newRoom    = $newValue[$i];

                $oldSubject = "$baseSubject;$oldRoom";

                // Find the booking for this room
                foreach($currentBookings as $b){
                    if($oldSubject == $b->subject){
                        $newSubject = "$baseSubject;$newRoom";
                        $result     = $bookings->updateBooking($b, ['subject' => $newSubject]);
                        break;
                    }
                }
            }
        }

        // remove any removed bookings
        if(!empty($deleted)){
            foreach($currentBookings as $booking){
                // if this is the booking for the room
                if(in_array(explode(';', $booking->subject)[1], $deleted)){
                    // Delete the booking
                    $result = $bookings->removeBooking($booking);
                }
            }
        }

        // add new ones
        foreach($added as $room){
            $result = $bookings->insertBooking($booking->startdate, $booking->enddate, $baseSubject.';'.$room, $formTable->submission->id);
        }

        $formTable->submission->formresults['booking-room'] = array_values($newValue);
    }else{
        // update the booking
        $result = $bookings->updateBooking($booking, [$elementName => $newValue]);
    }

    if(is_wp_error($result)){
        return $result;
    }

    return $message;
}

add_action('sim-forms-entry-archived', __NAMESPACE__.'\removeBookings', 10, 2);
add_action('sim-forms-entry-removed', __NAMESPACE__.'\removeBookings', 10, 2);
function removeBookings($instance, $submissionId){
    // remove the booking
    $bookings   = new Bookings();

    $currentBookings    = $bookings->getBookingsBySubmission($submissionId);

    if(!$currentBookings){
        return;
    }

    foreach($currentBookings as $booking){
        $bookings->removeBooking($booking);
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
        echo "<option>%booking-detalis%</option>";
        echo "<option>%paid%</option>";
    }
}

add_filter('sim-forms-transform-empty', __NAMESPACE__.'\transformEmpty', 10, 3);
function transformEmpty($replaceValue, $instance, $match){

    if($match == "booking-detalis"){
        
        if(!empty($instance->submission->formresults['booking-startdate'])){
            $startDates     = array_unique($instance->submission->formresults['booking-startdate']);
            $endDates       = array_unique($instance->submission->formresults['booking-enddate']);
        
            // NO ROOMS
            if(empty($instance->submission->formresults['booking-room'])){
                
                $startDate      = date(get_option('date_format'), strtotime((string)$startDates[0]));
                $endDate        = date(get_option('date_format'), strtotime((string)$endDates[0]));
                $replaceValue   = "from $startDate till $endDate";
            }else{
                if(count($startDates) == 1 && count($endDates) == 1){
                    $rooms          = implode('&', $instance->submission->formresults['booking-room']);
                    $startDate      = date(get_option('date_format'), strtotime((string)$startDates[0]));
                    $endDate        = date(get_option('date_format'), strtotime((string)$endDates[0]));
                    $replaceValue   = "room $rooms from $startDate till $endDate";
                }else{
                    $replaceValue   = "room:<br>";
                    foreach($instance->submission->formresults['booking-room'] as $index=>$room){
                        $startDate      = date(get_option('date_format'), strtotime((string)$startDates[$index]));
                        $endDate        = date(get_option('date_format'), strtotime((string)$endDates[$index]));

                        $replaceValue   .= "$room from $startDate till $endDate<br>";
                    }
                }
            }
        }
        
    }
    return $replaceValue;
}
