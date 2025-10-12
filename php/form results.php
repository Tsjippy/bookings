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
        foreach($bookings->subjects[$element->id] as $subject){
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
        $type == 'own'                                                  ||          // own is always an table
        !isset($displayFormResults->tableSettings->booking_display)   ||          // no option choosen
        (
            isset($displayFormResults->tableSettings->booking_display) &&         // option chosen
            $displayFormResults->tableSettings->booking_display != 'calendar'     // but choose table view
        )      ||
        isset($_REQUEST['export-xls'])  ||                                          // exporting an excel
        isset($_REQUEST['export-pdf'])                                              // exporting a pdf
    ){
        if($type == 'own' && $displayFormResults->tableSettings->booking_display == 'calendar'){
            $bookings    = new Bookings($displayFormResults);
            
            echo $bookings->pendingBookingsHtml('approval');

            echo $bookings->pendingBookingsHtml('payment');
        }

        return $shouldShow;
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
        $bookings->forms->submission    = $bookings->forms->getSubmission($_REQUEST['id']);

        // Find the subject
        foreach($elements as $element){
            $elementName                = $element->name;
            if(isset($bookings->forms->submission->formresults[$elementName])){
                $bookedSubject          = $bookings->forms->submission->formresults[$elementName];
                break;
            }
        }

        $targetDate                     = strtotime(array_values($bookings->forms->submission->formresults['booking-startdate'])[0]);
    }
    
    $html   = '<div class="tables-wrapper">';
        if($type != 'others'){ // has already been rendered for own submissions if the type is others
            $html       .= $bookings->pendingBookingsHtml('approval');
            $html       .= $bookings->pendingBookingsHtml('payment');
        }

        $calendars  = '';
        $subjects   = [];

        // Find the subject names
        foreach($elements as $element){
            foreach($bookings->subjects[$element->id] as $subject){
                // Only show the subjects we are manager of
                if(!is_array($subject['managers']) || !in_array($bookings->user->ID, $subject['managers'])){
                    continue;
                }

                $subjects[]   = $subject;
            }
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

            $calendars  .= $bookings->modalContent($subject, $targetDate, true, $hidden, true);
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
function actionHtml($buttonsHtml, $bookingData, $index, $instance){
    if(get_class($instance) != 'SIM\BOOKINGS\Bookings' || !isset($buttonsHtml['archive'])){
        return $buttonsHtml;
    }

    $buttonsHtml['archive'] = str_replace('>Archive', 'style="width: max-content;">Cancel booking', $buttonsHtml['archive']);

    return $buttonsHtml;
}

// only show upcoming bookings for own bookings
add_filter('sim_retrieved_formdata', __NAMESPACE__.'\formdataRetrieved', 10, 3);
function formdataRetrieved($submissions, $userId, $object){
    // Do not filter if this is for a specific user
    if(is_numeric($userId)){
        return $submissions;
    }

    $bookingSelectors   = $object->getElementByType('booking-selector');
    if(!$bookingSelectors){
        return $submissions;
    }

    $bookings   = new Bookings($object);

    $bookings->getBookingElements();

    // Get the subjects for the current user
    $bookings->getSubjectManagers($bookings->user->ID);

    $subjectsToKeep  = array_keys($bookings->managers);

    // find the user id element
	$userIdKey	= $bookings->forms->findUserIdElementName();
    
    // Loop over all booking selctors in the form
    foreach($bookingSelectors as $bookingSelector){
        // loop over all submissions
        foreach($submissions as $index=>$submission){
            // remove any submission not belonging to the $subjectsToKeep
            if(
                !in_array($submission->formresults[$bookingSelector->name], $subjectsToKeep)    &&  // Not managed by us
                $submission->formresults[$userIdKey]    != $bookings->user->ID                      // Not our own sumissionn

            ){
                unset($submissions[$index]);
            }
        }
    }

    return $submissions;
}

// Use room as subId in table view
add_filter('sim-formresults-split-subid', __NAMESPACE__.'\adjustSubId', 10, 3);
function adjustSubId($x, $newSubmission, $object){
    // Return if this is not a booking
    if(!isset($newSubmission->formresults['booking-startdate']) || !is_array($newSubmission->formresults['booking-startdate'])){
        return $x;
    }

    // find the number to be used
    return array_keys($newSubmission->formresults['booking-startdate'])[$x];
}

// only show the date for the current room
add_filter('sim-form-result-table-value', __NAMESPACE__.'\adjustCellValue', 10, 3);
function adjustCellValue($value, $columnSetting, $values){
    if(
        (
            $columnSetting['name'] != 'booking-startdate' &&
            $columnSetting['name'] != 'booking-enddate' &&
            $columnSetting['name'] != 'booking-room' 
        ) || 
        !isset($values['subid'])
    ){
        return $value;
    }

    if($columnSetting['name'] == 'booking-room' ){
        return $values['subid'];
    }

    // return only the value for this room
    if(is_array($value)){
        return $value[$values['subid']];
    }

    return $value;
}

// only show future bookings in table view
add_filter('sim_formdata_retrieval_query', __NAMESPACE__.'\alterQuery', 10, 3);
function alterQuery($query, $userId, $instance){
    if(str_contains($query, " id='")){
        return $query;
    }

    $bookings   = new Bookings($instance);

    if(empty($instance->getElementByType('booking-selector'))){
        return $query;
    }

    $date   = date('Y-m-d');

    $query      .= " and id IN(SELECT submission_id FROM `$bookings->tableName` WHERE enddate >= '$date' ORDER BY 'startdate')";

    return $query;
}