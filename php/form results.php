<?php
namespace SIM\BOOKINGS;
use SIM;

// the choice for table view or calendar view
add_action('sim-formstable-after-table-settings', __NAMESPACE__.'\tableSettings');
function tableSettings($displayFormResults){
    // Check if it has an booking selector
    if(empty($displayFormResults->getElementByType('booking-selector'))){
        return;
    }

    $setting    = '';
    if(isset($displayFormResults->tableSettings->booking_display)){
        $setting    = $displayFormResults->tableSettings->booking_display;
    }

    ?>
    <div class="table-rights-wrapper">
        <label>
            Select if you want to see the bookings as table or as calendar
        </label>
        <br>
        <label>
            <input type='radio' name='table-settings[booking-display]' value='table' <?php if($setting == 'table'){echo 'checked';}?>>
            Table
        </label>
        <label>
            <input type='radio' name='table-settings[booking-display]' value='calendar'<?php if($setting == 'calendar'){echo 'checked';}?>>
            Calendar
        </label>
    </div>
    <?php
}

// give table view permissions if we are a subject manager
add_filter('sim-table-edit-permissions', __NAMESPACE__.'\changeTableViewPermissions', 10, 2);
function changeTableViewPermissions($tableViewPermissions, $object){
    if($tableViewPermissions){
        return $tableViewPermissions;
    }

    $bookings       = new Bookings($object);

    // get all booking selectors
    $elements       = $bookings->getBookingElements();

    // Loop over all subjects
    foreach($elements as $element){
        foreach($bookings->getElementSubjects($element->id) as $subject){
            // if we are the manager of one of the subjects
            if(is_array($subject['managers']) && in_array($object->user->ID, $subject['managers'])){
                return true;
            }
        }
    }

    return $tableViewPermissions;
}

// Display calendar instead of a table
add_filter('sim-formstable-should-show', __NAMESPACE__.'\shouldShow', 10, 3);
function shouldShow($shouldShow, $displayFormResults, $type){
    // Check if we should show the table view
    if(
        !isset($displayFormResults->tableSettings->booking_display)   ||          // no option choosen
        (
            isset($displayFormResults->tableSettings->booking_display) &&         // option chosen
            $displayFormResults->tableSettings->booking_display != 'calendar'     // but choose table view
        )      ||
        isset($_REQUEST['export-xls'])  ||                                          // exporting an excel
        isset($_REQUEST['export-pdf'])                                              // exporting a pdf
    ){
        return $shouldShow;
    }

    $html   = '';

    /**
     * Data should always be splitted if we are in calendar view
     * So the type 'all' is not allowed.
     * We render our own submissions as a table, before continuing with the calendar view
     */
    if($type == 'all'){
        $html       = $displayFormResults->renderTable('own');

        $type       = 'others';
    }
    
    // display the calendar instead of the table
    wp_enqueue_script('sim-bookings');

    $bookings                   = new Bookings($displayFormResults);

    $elements                   = $bookings->getBookingElements();
    if(is_wp_error($elements)){
        return $elements;
    }

    $targetDate                 = time();
    $bookedSubject              = '';
    $bookings->forms->submission = null;

    // Show a specific booking
    if(!empty($_REQUEST['id'])){
        $bookings->forms->submission    = $bookings->forms->getSubmissions('', $_REQUEST['id'])[0];

        // Find the subject
        foreach($elements as $element){
            if(isset($bookings->forms->submission->{$element->id})){
                $bookedSubject          = $bookings->forms->submission->{$element->id};
                break;
            }
        }

        $targetDate                     = strtotime(array_values($bookings->forms->submission->booking_startdate)[0]);
    }
    
    $html   .= '<div class="tables-wrapper">';

        $calendars  = '';
        $subjects   = [];

        // Find the subject names
        foreach($elements as $element){
            foreach($bookings->getElementSubjects($element->id) as $subject){
                // Only show the subjects we are manager of
                if(!is_array($subject['managers']) || !in_array($bookings->user->ID, $subject['managers'])){
                    continue;
                }

                $subjects[]   = $subject;
            }
        }

        /**
         * Display a list of bookings
         */
        if($type == 'own'){

            // Pending bookings
            $html       .= $bookings->pendingBookingsHtml('approval');
            $html       .= $bookings->pendingBookingsHtml('payment');

            // Get the bookings for the current user
            $displayFormResults->parseSubmissions($bookings->user->ID);

            if(empty($displayFormResults->submissions)){
                return $html;
            }

            $html       .= "<style>.booking-detail-wrapper{padding: 10px;}</style>";
            $html       .= "<h4>Your Current Bookings</h4>";
            $html       .= "<div class='details-wrapper' style='max-width:500px;display:flex;'>";

                foreach($displayFormResults->submissions as $submission){
                    $result = $bookings->getBookingsBySubmission($submission->id);

                    if(is_array($result)){
                        // We only need the details for the first booking of each submission
                        $html   .= $bookings->submissionDetails($result[0], $submission, false);
                    }
                }
            $html       .= '</div>';

            return $html.'</div>';
        }

        // Only show subject selection if there is something to choose
        if(count($subjects) > 1){
            $checkboxes = '<h4>Please select the calendar you like to see</h4>';
        }

        foreach($subjects as $subject){
            $bookings->bookings  = [];   // reset the bookings so they do not include the previous location

            $checked    = '';
            $hidden     = true;
            if($subject['name'] == $bookedSubject || count($subjects) == 1){
                $checked    = 'checked';
                $hidden     = false;
            }

            $cleanSubject   = trim($subject['name']);

            if(count($subjects) > 1){
                $checkboxes .= "<label>";
                    $checkboxes .= "<input type='checkbox' class='admin-booking-subject-selector' value='$cleanSubject' $checked>";
                    $checkboxes .= $cleanSubject;
                $checkboxes .= "</label>";
            }

            $calendars  .= $bookings->modalContent('', $subject, $targetDate, true, $hidden, true);
        }

        $html   .= '<div class="form-data-table">';
            $html   .= $checkboxes;
            $html   .= $calendars;
        $html   .= "</div>";

        // Export buttons
        if(array_intersect($bookings->forms->userRoles, array_keys($bookings->forms->tableSettings->view_right_roles))){
            $html   .= "<div>";
                $html   .= "<form method='post' class='export-form' id='export-xls'>";
                    $html   .= "<button class='button button-primary' type='submit' name='export-xls'>Export data to excel</button>'";
                $html   .= "</form>";
                if(SIM\getModuleOption('pdf', 'enable')){
                    $html   .= "<form method='post' class='export-form' id='export-pdf'>";
                        $html   .= "<button class=button button-primary type='submit' name='export-pdf'>Export data to pdf</button>";
                    $html   .= "</form>";
                }
            $html   .= "</div>";
        }
    $html   .= '</div>';

    return $html;
}

// Change Archive button text
add_filter('sim_form_actions_html', __NAMESPACE__.'\actionHtml', 10, 4);
function actionHtml($buttonsHtml, $submission, $index, $instance){
    if(get_class($instance) != 'SIM\BOOKINGS\Bookings' || !isset($buttonsHtml['archive'])){
        return $buttonsHtml;
    }

    $buttonsHtml['archive'] = str_replace('>Archive', 'style="width: max-content;">Cancel booking', $buttonsHtml['archive']);

    return $buttonsHtml;
}

// Show the possible booking rooms
add_filter('sim-forms-checkbox-options', function ($options, $object){
    if(!isset($object->element) || $object->element->name != 'booking_rooms[]'){
        return $options;
    }
    
    $bookingSelectors   = $object->getElementByType('booking-selector');
    if(!$bookingSelectors){
        return $options;
    }

    // find the accomdation
    foreach($bookingSelectors as $bookingSelector){
        if(empty($object->submissions[0]->{$bookingSelector->name})){
            continue;
        }

        $accomodation   = $object->submissions[0]->{$bookingSelector->name};

        // Get the rooms of this accomodation
        $bookings   = new Bookings($object);
        $details    = $bookings->getElementSubjects($bookingSelector->id, $accomodation);

        foreach($details['rooms'] as $room){
            $options[$room['name']]  = $room['name'];
        }

        break;
    }

    return $options;
}, 10, 2);

// Alter form results
add_filter('sim_retrieved_formdata', __NAMESPACE__.'\formdataRetrieved', 10, 3);
function formdataRetrieved($submissions, $userId, $object){
    $bookingSelectors   = $object->getElementByType('booking-selector');
    if(!$bookingSelectors){
        return $submissions;
    }

    $booker   = new Bookings($object);

    $booker->getBookingElements();

    /**
     * Add the booking dates to the form results
     * Split on dates, add extra results if necessary
     */
    foreach($submissions as $index => $submission){
        // Get all the bookings belonging to this form submission
        $bookings   = $booker->getBookingsBySubmission($submission->id);

        $startDates = [];
        $endDates   = [];
        $rooms      = [];
        $bookingIds = [];

        // Store the dates
        foreach($bookings as $booking){
            $startDates[]   = $booking->startdate;
            $endDates[]     = $booking->enddate;

            if(!empty($booking->room)){
                $rooms[]        = $booking->room;
            }

            $bookingIds[]   = $booking->id;
        }

        $newSubmissions      = [];

        // Add submissions for each room, using the room name as sub id
        foreach($startDates as $i => $date){
            // Add the dates to the form results
            $submission->booking_startdate   = $date;
            $submission->booking_enddate     = $endDates[$i];
            $submission->booking_id          = $bookingIds[$i];

            if(!empty($rooms)){
                $submission->booking_rooms   = $rooms[$i];
                $submission->subId           = $rooms[$i];
            }

            $newSubmissions[]                = clone $submission;
        }

        // replace the original with the first
        $submissions[$index]    = $newSubmissions[0];

        // remove that one
        unset($newSubmissions[0]);

        // Add the extra submissions
        $submissions    = array_merge($submissions, $newSubmissions);
    }

    /**
     * Only show upcoming bookings for own bookings
     */

    // Do not filter if this is for a specific user
    if(is_numeric($userId)){
        return $submissions;
    }

    // Get the subjects for the current user
    $booker->getSubjectManagers($booker->user->ID);

    $subjectsToKeep = array_keys($booker->managers);
    
    // Loop over all booking selctors in the form
    foreach($bookingSelectors as $bookingSelector){
        // loop over all submissions
        foreach($submissions as $index => $submission){
            // remove any submission not belonging to the $subjectsToKeep
            if(
                !empty($submission->{$bookingSelector->name})    &&
                !in_array($submission->{$bookingSelector->name}, $subjectsToKeep)    &&  // Not managed by us
                $submission->userid    != $booker->user->ID                      // Not our own sumissionn

            ){
                unset($submissions[$index]);
            }
        }
    }

    return $submissions;
}

// only show the date for the current room
//add_filter('sim-form-result-table-value', __NAMESPACE__.'\adjustCellValue', 10, 3);
function adjustCellValue($value, $columnSetting, $values){
    if(
        (
            $columnSetting['name'] != 'booking-startdate' &&
            $columnSetting['name'] != 'booking-enddate' &&
            $columnSetting['name'] != 'booking-rooms' 
        ) || 
        !isset($values['subid'])
    ){
        return $value;
    }

    if($columnSetting['name'] == 'booking_rooms' ){
        return $values['subid'];
    }

    // return only the value for this room
    if(is_array($value)){
        return $value[$values['subid']];
    }

    return $value;
}

/**
 * Change the submission data retrieved 
 */
add_filter('sim_formdata_retrieval_query', __NAMESPACE__.'\alterQuery', 10, 4);
function alterQuery($params, $userId, $instance){
    if( empty($instance->getElementByType('booking-selector'))){
        return $params;
    }

    $bookings   = new Bookings($instance);

    // only show future bookings in table view
    if(!in_array("id=%d", $params['where'])){
        $params['where'][]   .= "id IN(SELECT submission_id FROM %i WHERE enddate >= %s ORDER BY 'startdate')";
        $params['values'][] = $bookings->tableName;
        $params['values'][] = date('Y-m-d');
    }

    // We are requesting a submission value and the element index is negative
    if(
        intval($params['values'][2]) < -101
    ){
        $elementId      = $params['values'][2];
        $submissionId   = $_POST['submission-id'];
        if(!is_numeric($submissionId)){
            return $params;
        }

        switch($elementId){
            case -102:
                $column = 'startdate';
                break;
            case -103:
                $column = 'enddate';
                break;
            case -104:
                $column = 'room';
                break;
            default:
                return $params;
        }

        $params['base']     = "select $column from %i WHERE ";

        $params['where']    = [
            "submission_id = %d"
        ];

        $params['values']   = [
            $bookings->tableName,
            $submissionId
        ];
    }

    return $params;
}

//Store updated date or room
add_filter('sim-forms-should-update-form-data', __NAMESPACE__.'\updateBookingData', 10, 6);
function updateBookingData($shouldContinue, $elementId, $submissionId, $subId, $value, $instance){
    // Change to paid / unpaid
    $paymentIndicatorElId    = $instance->formData->payment_indicator;

    if( $elementId > -102 && $elementId != $paymentIndicatorElId ){
        return $shouldContinue;
    }

    switch($elementId){
        case $paymentIndicatorElId:
            $column = 'paid';
            $value  = $value != 'not paid';
            break;
        case -102:
            $column = 'startdate';
            break;
        case -103:
            $column = 'enddate';
            break;
        case -104:
            $column = 'room';
            break;
        default:
            return $shouldContinue;
    }
    
    $bookings   = new Bookings($instance);

    foreach($bookings->getBookingsBySubmission($submissionId) as $booking){
        if(empty($subId) || $booking->room == $subId){
            $bookings->updateBooking($booking, [$column => $value], true);
        }
    }

    if($elementId != $paymentIndicatorElId ){
        return false;
    }

    return true;
}