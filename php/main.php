<?php
namespace SIM\BOOKINGS;
use SIM;

// Add a new type to the element choice dropdown
add_filter('sim-special-form-elements', function($options){
    $options['booking_selector']    = 'Booking selector';

    return $options;
});

// add element options
add_action('sim-after-formbuilder-element-options', function($element){
    global $wp_roles;
    
    //Get all available roles
    $userRoles = $wp_roles->role_names;
    
    //Sort the roles
    asort($userRoles);

    $bookingDetails = [];
    if($element != null && !empty($element->booking_details)){
        $bookingDetails = maybe_unserialize($element->booking_details);
    }else{
        return;
    }

    if(!isset($bookingDetails['subjects'])){
        $bookingDetails['subjects'] = ['No Subjects defined yet'];
    }

    if(!is_array($bookingDetails['subjects'])){
        $bookingDetails['subjects'] = explode("\n", $bookingDetails['subjects']);
    }
    ?>
    <div class='elementoption booking_selector hidden'>
        <label>
            Specify the subjects to show a calendar for
            <div class="clone_divs_wrapper">
                <?php
                foreach($bookingDetails['subjects'] as $index=>$subject){
                    if(!is_array($subject)){
                        $subject    = [
                            'name'   => $subject,
                            'amount' => 1
                        ];
                    }
                    ?>
                    <div class="clone_div" data-divid="<?php echo $index;?>" style='display: flex;'>
                        <label name="Subject" class=" formfield formfieldlabel" style='width: auto;margin-right: 20px;'>
                            <h4 class="labeltext">Subject <?php echo $index+1;?></h4>
                            <h5 style='margin-bottom:2px;'><strong>Name</bold></strong>
                            <input type="text" name="formfield[booking_details][subjects][<?php echo $index;?>][name]" id="subjects" class=" formfield formfieldinput" value="<?php echo $subject['name'];?>" placeholder="Enter subject name" style='width: unset;'>
                            <h5 style='margin-bottom:2px;'><strong>Manager</strong></h5>
                            <?php
                            echo SIM\userSelect('', false, false, '', "formfield[booking_details][subjects][$index][manager]", [], $subject['manager']);
                            ?>
                        </label>

                        <label class="formfield formfieldlabel">
                            <h4 class="labeltext">Allow overlap <?php echo $index+1;?></h4>
                            Allow new arrivals on the day the previous people leave<br>
                            <label>
                                <input type='radio' class='booking-subject-selector overlap' name='formfield[booking_details][subjects][<?php echo $index;?>][overlap]' value='yes' <?php if($subject['overlap'] == 'yes'){echo 'checked';}?>>
                                Yes
                            </label>    

                            <label>
                                <input type='radio' class='booking-subject-selector overlap' name='formfield[booking_details][subjects][<?php echo $index;?>][overlap]' value='no' <?php if($subject['overlap'] == 'no'){echo 'checked';}?>>
                                No
                            </label>
                            <br>
                            <br>
                            <div class='min-bookking-gap-time <?php if(!isset($subject['overlap']) || $subject['overlap'] == 'yes'){echo 'hidden';}?>'>
                                <label>
                                    Minimum time between two bookings in days
                                    <div class="infobox" name="info">
                                        <div>
                                            <p class="info_icon" style='float:right'>
                                                <img draggable="false" role="img" class="emoji" alt="ℹ" src="<?php echo SIM\PICTURESURL."/info.png";?>" loading='lazy' >
                                            </p>
                                        </div>
                                        <span class="info_text">
                                            Use 0 for allowing guests to arrive the next day.<br>
                                            1 means there is one full day between the previous and the next booking
                                        </span>
                                    </div>
                                    <input type='number' name='formfield[booking_details][subjects][<?php echo $index;?>][overlap-period]' value='<?php echo $subject['overlap-period'];?>' min='0'>
                                </label>
                            </div>
                        </label>

                        <label class="formfield formfieldlabel">
                            <h4>Default status for new bookings</h4>
                            <label>
                                <input type='radio' name='formfield[booking_details][subjects][<?php echo $index;?>][default_booking_state]' value='pending' <?php if($subject['default_booking_state'] == 'pending'){echo 'checked';}?>>
                                Pending
                            </label>
                            <br>
                            <label>
                                <input type='radio' name='formfield[booking_details][subjects][<?php echo $index;?>][default_booking_state]' value='confimed' <?php if($subject['default_booking_state'] == 'confimed'){echo 'checked';}?>>
                                Confimed
                            </label>
                            <br>
                            <br>
                            <button class="button sim small confirmed-roles-switcher <?php if($subject['default_booking_state'] != 'pending'){echo 'hidden';}?>" type="button" style='max-width: unset;'>Advanced</button>
                            <div class='confirmed-roles-wrapper hidden'>
                                <h4>Select roles for which bookings are confirmed by default</h4>
                                <div class="role_info">
                                    <?php
                                    foreach($userRoles as $key=>$roleName){
                                        if(!empty($subject['confirmed_booking_roles'][$key])){
                                            $checked = 'checked';
                                        }else{
                                            $checked = '';
                                        }
                                        echo "<label class='option-label'>";
                                            echo "<input type='checkbox' class='formbuilder formfieldsetting' name='formfield[booking_details][subjects][$index][confirmed_booking_roles][$key]' value='$roleName' $checked>";
                                            echo $roleName;
                                        echo"</label><br>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </label>
                        <br>

                        <label class=" formfield formfieldlabel">
                            <h4 class="labeltext">Room numbering type <?php echo $index+1;?></h4>
                            <input type='radio' class='booking-subject-selector' name='formfield[booking_details][subjects][<?php echo $index;?>][nrtype]' value='none' <?php if($subject['nrtype'] == ''){echo 'checked';}?> onchange='this.closest(`.clone_div`).querySelector(`label.amount`).classList.add(`hidden`);this.closest(`.clone_div`).querySelector(`.rooms`).classList.add(`hidden`)'>
                            No seperate rooms
                            <br>

                            <input type='radio' class='booking-subject-selector' name='formfield[booking_details][subjects][<?php echo $index;?>][nrtype]' value='numbers' <?php if($subject['nrtype'] == 'numbers'){echo 'checked';}?> onchange='this.closest(`.clone_div`).querySelector(`label.amount`).classList.remove(`hidden`);this.closest(`.clone_div`).querySelector(`.rooms`).classList.add(`hidden`)'>
                            Numbers
                            <br>

                            <input type='radio' class='booking-subject-selector' name='formfield[booking_details][subjects][<?php echo $index;?>][nrtype]' value='letters' <?php if($subject['nrtype'] == 'letters'){echo 'checked';}?> onchange='this.closest(`.clone_div`).querySelector(`label.amount`).classList.remove(`hidden`);this.closest(`.clone_div`).querySelector(`.rooms`).classList.add(`hidden`)'>
                            Letters
                            <br>

                            <input type='radio' class='booking-subject-selector' name='formfield[booking_details][subjects][<?php echo $index;?>][nrtype]' value='custom' <?php if($subject['nrtype'] == 'custom'){echo 'checked';}?> onchange='this.closest(`.clone_div`).querySelector(`label.amount`).classList.add(`hidden`);this.closest(`.clone_div`).querySelector(`.rooms`).classList.remove(`hidden`)'>
                            Custom
                        </label>

                        <label class="amount formfield formfieldlabel <?php if($subject['nrtype'] == 'custom' || empty($subject['nrtype'])){echo 'hidden';}?>">
                            <h4 class="labeltext">Room amount <?php echo $index+1;?></h4>
                            <input type="number" name="formfield[booking_details][subjects][<?php echo $index;?>][amount]" id="subjects" class=" formfield formfieldinput" value="<?php echo $subject['amount'];?>" placeholder="Enter subject amount" style='width: unset;'>
                        </label>                            

                        <div class="rooms clone_divs_wrapper <?php if($subject['nrtype'] != 'custom'){echo 'hidden';}?>" style='display: inline-block;background: lightgrey;padding-bottom: 10px;padding-left: 10px;margin-right:10px'>
                            <?php
                            if(empty($subject['rooms'])){
                                $subject['rooms']   = ['1'];
                            }

                            foreach($subject['rooms'] as $i=>$room){
                                ?>
                                <div class="clone_div" data-divid="<?php echo $i;?>" style='display: flex;'>
                                    <label name="roomname" class=" formfield formfieldlabel">
                                        <h4 class="labeltext">Room name <?php echo $i+1;?></h4>
                                        <input type="text" name="formfield[booking_details][subjects][<?php echo $index;?>][rooms][<?php echo $i;?>]" id="rooms" class=" formfield formfieldinput" value="<?php echo $room;?>" placeholder="Enter room name" style='width: unset;'>
                                    </label>
                                    
                                    <div class="buttonwrapper" style="width:100%; display: flex;">
                                        <button type="button" class="add button" style="flex: 1;">+</button>
                                        <?php
                                        if(count($subject['rooms'])> 1){
                                            ?>
                                            <button type="button" class="remove button" style="flex: 1;">-</button>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                            
                        <div class="buttonwrapper" style="display: flex;">
                            <button type="button" class="add button" style="flex: 1;">+</button>
                            <?php
                            if(count($bookingDetails['subjects'])> 1){
                                ?>
                                <button type="button" class="remove button" style="flex: 1;">-</button>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </label>
        <br>
        <label>
            <input type='checkbox' name='formfield[booking_details][oneday]' value='yes' <?php if(isset($bookingDetails['oneday']) && $bookingDetails['oneday'] == 'yes'){echo 'checked';}?>>
            Allow one day events
        </label>
        <br>
    </div>
    <?php
});

// add extra elements for displaying in results table
add_filter('sim-forms-elements', function($elements, $displayFormResults, $force){
    if(!$force && !in_array(get_class($displayFormResults), ["SIM\FORMS\DisplayFormResults", "SIM\FORMS\SubmitForm", "SIM\FORMS\EditFormResults"])){
        return $elements;
    }

    // do not add to the formbuilder screen
    if(str_contains($_SERVER['QUERY_STRING'], 'formbuilder=true')){
        return $elements;
    }

    // We cannot use getElementByType here as we have not gotten all elements yet.
    $element    = false;
    foreach($elements as $el){
        if($el->type == 'booking_selector'){
            $element    = $el;
            break;
        }
    }

    if($element){
        // Add the startdate and enddate
        $startdate          = clone $element;
        $startdate->type    = 'date';
        $startdate->name    = 'booking-startdate';
        $startdate->nicename= 'booking-startdate';
        $startdate->id      = -102;

        $enddate            = clone $element;
        $enddate->type      = 'date';
        $enddate->name      = 'booking-enddate';
        $enddate->nicename  = 'booking-enddate';
        $enddate->id        = -103;

        $room               = clone $element;
        $room->type         = 'checkbox';
        $room->name         = 'booking-room';
        $room->nicename     = 'booking-room';
        $room->id           = -104;
        
        $elements[]         = $startdate;
        $elements[]         = $enddate;
        $elements[]         = $room;
    }
    
    return $elements;
}, 10, 3);

// Display the date selector in the form
add_filter('sim-forms-element-html', function($html, $element, $displayForm){
    if($element->type == 'booking_selector'){
        $bookingDetails = maybe_unserialize($element->booking_details);

        if(!isset($bookingDetails['subjects'])){
            return 'Please add one or more subjects';
        }else{
            $subjects       = $bookingDetails['subjects'];
        }

        $html   = '';
        $hidden     = 'hidden';
        $buttonText = 'Change';
        $required   = '';
        if($element->required){
            $required   = 'required';
        }

        if(empty($subjects)){
            $hidden     = "";
            $buttonText = 'Select dates';
        }elseif(count($subjects) < 6){
            foreach($subjects as $subject){
                $cleanSubject    = trim($subject['name']);
                $checked    = '';
                if(isset($displayForm->submission->formresults['accomodation']) && $displayForm->submission->formresults['accomodation'] == $cleanSubject){
                    $checked    = 'checked';
                }
                $html   .= "<label style='margin-right:5px;'>";
                    $html   .= "<input type='radio' class='booking-subject-selector' name='$element->name' value='$cleanSubject' $checked>";
                    $html   .= "$cleanSubject";
                $html   .= "</label>";
            }
        }else{
            $html   .= "<select class='booking-subject-selector' name='$element->name' $required>";
                foreach($subjects as $subject){
                    $cleanSubject    = trim($subject['name']);
                    $html   .= "<option value='$cleanSubject'>$cleanSubject</option>";
                }
            $html   .= "</select>";
        }

        ob_start();

        ?>
        <div style='display:flex;align-items: center;'>
            <div class="clone_divs_wrapper selected-booking-dates <?php echo $hidden;?>">
                <div class="clone_div" data-divid="0">
                    <div class="buttonwrapper">
                        <div class='hidden'>
                            <h4>Room</h4>
                            <input type='text' name='booking-room[0]' disabled <?php echo $required;?>>
                        </div>
                        <div>
                            <h4>Arrival Date</h4>
                            <input type='date' name='booking-startdate[0]' disabled <?php echo $required;?>>
                        </div>
                        <div>
                            <h4>Departure Date</h4>
                            <input type='date' name='booking-enddate[0]' disabled <?php echo $required;?>>
                        </div>
                    </div>
                </div>
            </div>
            <button class='button change-booking-date hidden' type='button' style='margin-left: 20px;'><?php echo $buttonText;?></button>
        </div>
        <?php
        $html   .= ob_get_clean();

        wp_enqueue_script('sim-bookings');

        $booking   = new Bookings($displayForm);

        // Find the accomodation names
        foreach($subjects as $subject){
            $html   .= $booking->dateSelectorModal($subject);
        }
    }

    if($element->name == 'booking-room'){
        $bookings   = new Bookings($displayForm);

        $bookingDetails = maybe_unserialize($element->booking_details);

        if(!isset($bookingDetails['subjects'])){
            return 'Please add one or more subjects';
        }else{
            $subjects       = $bookingDetails['subjects'];
        }

        $elementName    = $displayForm->getElementByType('booking_selector')[0]->name;

        foreach($subjects as $subject){
            if($subject['name'] == $displayForm->submission->formresults[$elementName]){
                break;
            }
        }

        $radio  = false;
        if(isset($_POST['oldvalue'])){
            $radio  = true;
        }
        $html   .= $bookings->roomSelector($subject, true, $radio);
    }

    return $html;

}, 10, 3);

// the choice for table view or calendar view
add_action('sim-formstable-after-table-settings', function($displayFormResults){
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
});

/**
 * Adds the buttons to approve or delete a pending booking
 */
function pendingButtons($buttonsHtml, $values, $subId, $object){
    $buttonsHtml['approve'] = "<button class='button approve' type='button' data-id='{$values['booking-id']}' data-formid='{$object->submission->form_id}'>Approve</button>";
    $buttonsHtml['delete']  = "<button class='button delete' type='button' data-id='{$values['booking-id']}' data-formid='{$object->submission->form_id}'>Delete</button><br>";
    unset($buttonsHtml['archive']);

    return $buttonsHtml;
}

function pendingBookingsHtml($bookings, $displayFormResults){
    // do not show if no permissions
    if(!array_intersect(array_keys($bookings->forms->formData->full_right_roles), $bookings->forms->userRoles)){
        return '';
    }

    $pendingBookings    = $bookings->retrievePendingBookings();

    if(empty($pendingBookings)){
        return '';
    }

    $html   = "<h4>Pending Bookings</h4>";

    $submissions    = [];

    // only show one booking for submissions with multiple
    foreach($pendingBookings as $pendingBooking){
        // one submission can have multiple bookings, only load the submission once
        if(empty($submission) || $submission->id != $pendingBooking->submission_id){
            $submission = $bookings->forms->getSubmissions(null, $pendingBooking->submission_id)[0];
        }

        $submission->formresults['booking-room']                  = explode(';',$pendingBooking->subject)[1];
        $submission->formresults['booking-startdate']             = $pendingBooking->startdate;
        $submission->formresults['booking-enddate']               = $pendingBooking->enddate;
        $submission->formresults['booking-id']                    = $pendingBooking->id;

        $submissions[]                                            = clone $submission;
    }

    add_filter('sim_form_actions_html', __NAMESPACE__.'\pendingButtons', 10, 4);

    ob_start();

    $displayFormResults->theTable('pending', $submissions);

    $html   .= ob_get_clean();

    remove_filter('sim_form_actions_html', __NAMESPACE__.'\pendingButtons', 10);

    return $html;
}

// Display calendar instead of a table
add_filter('sim-formstable-should-show', function($shouldShow, $displayFormResults, $type){
    // display the calendar instead of the table
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
            echo pendingBookingsHtml($bookings, $displayFormResults);
        }
        return $shouldShow;
    }
    
    wp_enqueue_script('sim-bookings');

    $bookings    = new Bookings($displayFormResults);

    $elements   = $displayFormResults->getElementByType('booking_selector');

    foreach($elements as $element){
        $bookingDetails = maybe_unserialize($element->booking_details);

        if(!isset($bookingDetails['subjects'])){
            return 'Please add one or more booking subjects';
        }else{
            $subjects       = $bookingDetails['subjects'];
        }
    }

    $targetDate                 = time();
    $bookedSubject              = '';
    $bookings->forms->submission = null;
    if(!empty($_REQUEST['id'])){
        $bookings->forms->submission = $bookings->forms->getSubmissions(null, $_REQUEST['id'])[0];
        $targetDate     = strtotime($bookings->forms->submission->formresults['booking-startdate'][0]);
        $elementName    = $bookings->forms->getElementByType('booking_selector')[0]->name;
        $bookedSubject  = $bookings->forms->submission->formresults[$elementName];
    }
    
    $html   = '<div class="tables-wrapper">';
        if($type != 'others'){ // has already been rendered for above own submissions
            $html       .= pendingBookingsHtml($bookings, $displayFormResults);
        }

        $calendars  = '';
        $checkboxes = '<h4>Please select the accomodation you want to see the calendar for</h4>';

        // Find the accomodation names
        foreach($subjects as $subject){
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
    
    
}, 10, 3);

// check if a booking request is ok
add_filter('sim_before_saving_formdata', function($formResults, $object){
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
}, 99, 2);

// Create a booking
add_filter('sim_after_saving_formdata', function($message, $formBuilder){
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
        }
    }

    return $message;
}, 10, 2);

// Update an existing booking
add_filter('sim-forms-submission-updated', function($message, $formTable, $elementName, $oldValue, $newValue){
    // Get the element name
    $subject    = $formTable->getElementByType('booking_selector');
    if(!$subject){
        return $message;
    }
    $subject    = $subject[0]->name;

    // location and date & time are editable
    if(!in_array($elementName, ['booking-startdate', 'booking-enddate', 'startime', 'endtime', $subject, 'booking-room'])){
        return $message;
    }

    $elementName  = str_replace('booking-', '', $elementName);

    $bookings          =  new Bookings($formTable);
    
    $currentBookings   = $bookings->getBookingsBySubmission($formTable->submission->id);

    if(isset($_POST['bookingid']) && is_numeric($_POST['bookingid'])){
        foreach($currentBookings as $index=>$booking){
            if($booking->id == $_POST['bookingid']){
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

    $new            = $newValue;
    if(is_array($oldValue)){
        $new            = $oldValue;
        
        if(is_array($newValue)){
            $new[$index]    = $newValue[0];
        }else{
            $new[$index]    = $newValue;
        }
    }

    // change the $elementName to subject as that is the name of the column in the db
    if($subject == $elementName){
        $elementName  = 'subject';
    }

    // multiple rooms and bookings
    if($elementName == 'room'){
        if(is_string($newValue) && str_contains($newValue, ';')){
            $newValue   = explode(';', $newValue);
            $baseSubject= explode(';', $booking->subject)[0];

            //$oldRooms   = explode(';', $oldValue);

            $deleted    = array_diff($oldValue, $newValue);
            $added      = array_diff($newValue, $oldValue);

            // remove any removed bookings
            if(!empty($deleted)){
                // find all bookings with the same submission_id
                $currentBookings    = $bookings->getBookingsBySubmission($formTable->submission->id);

                foreach($currentBookings as $booking){
                    if(in_array(explode(';', $booking->subject)[1], $deleted)){
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
            $oldRoom    = $oldValue[$index];
            $newSubject = str_replace($oldRoom, $newValue, $booking->subject);
            $result = $bookings->updateBooking($booking, ['subject' => $newSubject]);

            // update the form result
            $formTable->submission->formresults[$elementName]   = $new;
        }
    }else{
        // update the booking
        $result = $bookings->updateBooking($booking, [$elementName => $newValue]);

        // update the form result
        $formTable->submission->formresults[$elementName]   = $new;
    }

    if(is_wp_error($result)){
        return $result;
    }

    return $message;
}, 10, 5);

// add attributes to booking dates on edit
add_filter('sim-forms-element-html', function($html, $element, $displayFormResults){
    global $wpdb;

    // Get the subject
    $subject    = $displayFormResults->submission->formresults[$displayFormResults->getElementByType('booking_selector')[0]->name];
        
    $startDate  = $displayFormResults->submission->formresults['booking-startdate'];
    $endDate    = $displayFormResults->submission->formresults['booking-enddate'];

    if(isset($_POST['bookingid']) && is_numeric($_POST['bookingid'])){
        $html   = str_replace('>', " data-bookingid='{$_POST['bookingid']}'>", $html);
    }

    if($element->name == 'booking-enddate'){
        // get the first event after this one
        $query  = "SELECT startdate FROM {$wpdb->prefix}sim_bookings WHERE subject = '$subject' AND startdate > '{$endDate[0]}' ORDER BY startdate LIMIT 1";
        $max    = $wpdb->get_var($query);

        if(!empty($max)){
            $max    = "max='$max'";
        }

        $min    = "min='$startDate'";
    }elseif($element->name == 'booking-startdate'){
        // get the first event before this one
        $query  = "SELECT enddate FROM {$wpdb->prefix}sim_bookings WHERE subject = '$subject' AND enddate <= '{$startDate[0]}' ORDER BY enddate LIMIT 1";
        $min    = $wpdb->get_var($query);

        if(!empty($min)){
            $min    = "min='$min'";
        }

        $max    = $endDate;
        if(is_array($max)){
            $max    = $max[0];
        }
    }else{
        return $html;
    }

    $html   = str_replace('>', " $min max='$max'>", $html);

    return $html;    
}, 10, 3);

add_action('sim-forms-entry-archived', __NAMESPACE__.'\removeBooking', 10, 2);
add_action('sim-forms-entry-removed', __NAMESPACE__.'\removeBooking', 10, 2);
function removeBooking($instance, $submissionId){
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

add_filter('sim_form_actions_html', function($buttonsHtml, $bookingData, $index, $instance){
    if(get_class($instance) != 'SIM\BOOKINGS\Bookings' || !isset($buttonsHtml['archive'])){
        return $buttonsHtml;
    }

    $buttonsHtml['archive'] = str_replace('>Archive', 'style="width: max-content;">Cancel booking', $buttonsHtml['archive']);

    return $buttonsHtml;
}, 10, 4);

add_filter('sim_transform_formtable_data', function($output, $elementName){
    if(str_contains($output, ';')){
        // include room if needed
        $rooms  = explode(';', $output);
        $output = implode('&', $rooms);
    }

    return $output;
}, 10, 2);

// Filter e-mail transforms
add_filter('sim-forms-transform-array', function($string, $replaceValue, $forms, $match){
    if(count(array_unique($replaceValue)) == 1){
        $string = array_unique($replaceValue)[0];
    }else{

    }
    return $string;
}, 10, 4);

// add the booking details to the drop down for use in e-mails
add_action('sim-add-email-placeholder-option', function($formBuilderForm){
    if($formBuilderForm->getElementByType('booking_selector')){
        echo "<option>%booking-startdate%</option>";
        echo "<option>%booking-enddate%</option>";
        echo "<option>%booking-room%</option>";
        echo "<option>%booking-detalis%</option>";
    }
});

add_filter('sim-forms-transform-empty', function($replaceValue, $instance, $match){

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
}, 10, 3);

// Update the booking subjects name if the form name has changed
add_action('sim-after-formelement-updated', function($element, $instance){
    global $wpdb;

    if($element->type == 'booking_selector'){
        $oldBookingDetails  = maybe_unserialize($instance->getElementById($element->id)->booking_details);
        $newBookingDetails  = maybe_unserialize($element->booking_details);

        $oldSubjects        = array_map(__NAMESPACE__.'\getSubjectNames', $oldBookingDetails['subjects']);
        $newSubjects        = array_map(__NAMESPACE__.'\getSubjectNames', $newBookingDetails['subjects']);

        $changedNames       = array_diff($newSubjects, $oldSubjects);

        $bookings   = new Bookings($instance);

        foreach($changedNames as $index=>$newName){
            $oldName    = $oldSubjects[$index];

            // update existing bookings
            $query  = "UPDATE `$bookings->tableName` SET subject = REPLACE( `subject`, '$oldName', '$newName' ) WHERE `subject` LIKE '$oldName%'";
            
            $wpdb->query($query);
        }
    }
}, 10, 2);

function getSubjectNames($v){
    if(is_array($v) && isset($v['name'])){
        return $v['name'];
    }
    return '';
}

// only show upcoming bookings for own bookings
add_filter('sim_retrieved_formdata', function($submissions, $userId, $object){
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
}, 10, 3);

// add the booking id to a form result cell dataset
add_filter('sim-formresult-cell-opening-tag', function($cellOpeningTag, $object, $columnSetting, $values){
    if(
        isset($object->submission->formresults['booking-id']) && 
        in_array($columnSetting['name'], ['booking-startdate', 'booking-enddate', 'booking-room'])
    ){
        $cellOpeningTag .= " data-booking-id='{$object->submission->formresults['booking-id']}'";
    }
    return $cellOpeningTag;
}, 10, 4);