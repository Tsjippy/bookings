<?php
namespace SIM\BOOKINGS;
use SIM;

// the choice for table view or calendar view
add_action('sim-formstable-after-table-settings', __NAMESPACE__.'\tableSettings');
function tableSettings($displayFormResults){
    // Check if it has an booking selector
    if(empty($displayFormResults->getElementByType('booking_selector'))){
        return;
    }

    $setting    = '';
    if(isset($displayFormResults->tableSettings['booking-display'])){
        $setting    = $displayFormResults->tableSettings['booking-display'];
    }

    ?>
    <div class="table_rights_wrapper">
        <label>
            Select if you want to see the bookings as table or as calendar
        </label>
        <br>
        <label>
            <input type='radio' name='table_settings[booking-display]' value='table' <?php if($setting == 'table'){echo 'checked';}?>>
            Table
        </label>
        <label>
            <input type='radio' name='table_settings[booking-display]' value='calendar'<?php if($setting == 'calendar'){echo 'checked';}?>>
            Calendar
        </label>
    </div>
    <?php
}

// give table view permissions if we are a subject manager
add_filter('sim-table-view-permissions', __NAMESPACE__.'\changeTableViewPermissions', 10, 2);
function changeTableViewPermissions($tableViewPermissions, $object){
    if($tableViewPermissions){
        return $tableViewPermissions;
    }

    $bookings       = new Bookings($object);

    $subjects       = $bookings->getSubjectData();

}

// Display calendar instead of a table
add_filter('sim-formstable-should-show', __NAMESPACE__.'\shouldShow', 10, 3);
function shouldShow($shouldShow, $displayFormResults, $type){
    // Check if we should show the table view
    if(
        $type == 'own'                                                  ||          // own is always an table
        !isset($displayFormResults->tableSettings['booking-display'])   ||          // no option choosen
        (
            isset($displayFormResults->tableSettings['booking-display']) &&         // option chosen
            $displayFormResults->tableSettings['booking-display'] != 'calendar'     // but choose table view
        )      ||
        isset($_REQUEST['export_xls'])  ||                                          // exporting an excel
        isset($_REQUEST['export_pdf'])                                              // exporting a pdf
    ){
        if($type == 'own' && $displayFormResults->tableSettings['booking-display'] == 'calendar'){
            $bookings    = new Bookings($displayFormResults);
            
            echo $bookings->pendingBookingsHtml('approval');

            echo $bookings->pendingBookingsHtml('payment');
        }

        return $shouldShow;
    }
    
    // display the calendar instead of the table
    wp_enqueue_script('sim-bookings');

    $bookings                   = new Bookings($displayFormResults);

    $elements                   = $bookings->getSubjectData();
    if(is_wp_error($elements)){
        return $elements;
    }

    $targetDate                 = time();
    $bookedSubject              = '';
    $bookings->forms->submission = null;
    if(!empty($_REQUEST['id'])){
        $bookings->forms->submission    = $bookings->forms->getSubmissions(null, $_REQUEST['id'])[0];
        $targetDate                     = strtotime($bookings->forms->submission->formresults['booking-startdate'][0]);
        $elementName                    = $elements[0]->name;
        $bookedSubject                  = $bookings->forms->submission->formresults[$elementName];
    }

    $bookings->getSubjectManagers($bookings->user->ID);
    
    $html   = '<div class="tables-wrapper">';
        if($type != 'others'){ // has already been rendered for own submissions if the type is others
            $html       .= $bookings->pendingBookingsHtml('approval');
            $html       .= $bookings->pendingBookingsHtml('payment');
        }

        $calendars  = '';
        $checkboxes = '<h4>Please select the accomodation you want to see the calendar for</h4>';

        // Find the accomodation names
        foreach($elements[0]->booking_details['subjects'] as $subject){
            $bookings->bookings  = [];   // reset the bookings so they do not include the previous location

            $checked    = '';
            $hidden     = true;
            if($subject['name'] == $bookedSubject){
                $checked    = 'checked';
                $hidden     = false;
            }

            $cleanSubject   = trim($subject['name']);
            $checkboxes .= "<label>";
                $checkboxes .= "<input type='checkbox' class='admin-booking-subject-selector' value='$cleanSubject' $checked>";
                $checkboxes .= $cleanSubject;
            $checkboxes .= "</label>";

            $calendars  .= $bookings->modalContent($subject, $targetDate, true, $hidden, true);
        }
        $html   .= '<div class="form-data-table">';
            $html   .= $checkboxes;
            $html   .= $calendars;
        $html   .= "</div>";

        // Export buttons
        if(array_intersect($bookings->forms->userRoles, array_keys($bookings->forms->tableSettings['view_right_roles']))){
            $html   .= "<div>";
                $html   .= "<form method='post' class='exportform' id='export_xls'>";
                    $html   .= "<button class='button button-primary' type='submit' name='export_xls'>Export data to excel</button>'";
                $html   .= "</form>";
                if(SIM\getModuleOption('pdf', 'enable')){
                    $html   .= "<form method='post' class='exportform' id='export_pdf'>";
                        $html   .= "<button class=button button-primary type='submit' name='export_pdf'>Export data to pdf</button>";
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

// include room if needed
add_filter('sim_transform_formtable_data', __NAMESPACE__.'\formtableData', 10, 2);
function formtableData($output, $elementName){
    if(str_contains($output, ';')){
        $rooms  = explode(';', $output);
        $output = implode('&', $rooms);
    }

    return $output;
}

// only show upcoming bookings for own bookings
add_filter('sim_retrieved_formdata', __NAMESPACE__.'\formdataRetrieved', 10, 3);
function formdataRetrieved($submissions, $userId, $object){
    if(is_numeric($userId) && $object->getElementByType('booking_selector')){
        foreach($submissions as $index=>$submission){
            $result = maybe_unserialize($submission->formresults);

            if(isset($result['booking-enddate'])){
                $endDate    = $result['booking-enddate'];
                if(is_array($endDate)){
                    $endDate    = $endDate[0];
                }

                if($endDate < date('Y-m-d')){
                    unset($submissions[$index]);
                    $object->total--;
                }
            }
        }
    }

    return $submissions;
}

// add the booking id to a form result cell dataset
add_filter('sim-formresult-cell-opening-tag', __NAMESPACE__.'\cellOpeningTag', 10, 4);
function cellOpeningTag($cellOpeningTag, $object, $columnSetting, $values){
    if(
        isset($object->submission->formresults['booking-id']) && 
        in_array($columnSetting['name'], ['booking-startdate', 'booking-enddate', 'booking-room'])
    ){
        $cellOpeningTag .= " data-booking_id='{$object->submission->formresults['booking-id']}'";
    }
    return $cellOpeningTag;
}