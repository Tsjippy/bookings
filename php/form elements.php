<?php
namespace SIM\BOOKINGS;
use SIM;

// Add a new type to the element choice dropdown
add_filter('sim-special-form-elements', __NAMESPACE__.'\specialFormElements');
function specialFormElements($options){
    $options['booking-selector']    = 'Booking selector';

    return $options;
}

// add element options
add_action('sim-after-formbuilder-element-options', __NAMESPACE__.'\addFormElementOptions');
function addFormElementOptions($element){
    global $wp_roles;
    
    //Get all available roles
    $userRoles = $wp_roles->role_names;
    
    //Sort the roles
    asort($userRoles);

    $bookings       = new Bookings();

    $bookingDetails = [];
    if($element != null && $element->type == 'booking-selector'){
        $bookingDetails = $bookings->getElementSubjects($element->id);
    }else{
        return;
    }

    if(empty($bookingDetails)){
        $bookingDetails = ['No Subjects defined yet'];
    }

    ?>
    <div class='element-option booking-selector hidden'>
        <label>
            Specify the subjects to show a calendar for
            <div class="clone-divs-wrapper">
                <?php
                // Render tab buttons
                foreach($bookingDetails as $index => $subject){                    
                    if(!is_array($subject)){
                        $subject = $bookingDetails[$index]    = [
                            'name'   => $subject,
                            'amount' => 1
                        ];
                    }
                    $active	= '';

                    if($index === 0){
                        $active = 'active';
                    }

                    ?>
                    <button class='button tablink formbuilder-form <?php echo $active;?>' type='button' id='show-subject-<?php echo $index;?>' data-target='subject-<?php echo $index;?>' style='margin-right:4px;'>
                        <?php echo $subject['name'];?>
                    </button>
                    <?php
                }
                    
                // Render tab contents
                foreach($bookingDetails as $index=>$subject){
                    $hidden	= 'hidden';
                    if($index === 0){
                        $hidden = '';
                    }

                    ?>
                    <div id="subject-<?php echo $index;?>" class="clone-div tabcontent <?php echo $hidden;?>" data-div-id="<?php echo $index;?>">
                        <label name="Subject" class=" formfield form-label" style='width: auto;margin-right: 20px;'>
                            <h4>Name</h4>
                            <input type="text" name="formfield[booking-details][<?php echo $index;?>][name]" class="subject-name formfield formfield-input" value="<?php echo $subject['name'];?>" placeholder="Enter subject name" style='width: unset;'>
                            <br>
                            <br>
                            <h4>Manager(s)</h4>
                            <?php
                            echo SIM\userSelect('', false, false, '', "formfield[booking-details][$index][managers][]", [], $subject['managers'], [], 'select', '', true);
                            ?>

                            <h4>Location Description</h4>
                            <?php
                            $settings = array(
                                'wpautop' => false,
                                'media_buttons' => false,
                                'forced_root_block' => true,
                                'convert_newlines_to_brs'=> true,
                                'textarea_name' => "formfield[booking_details][$index][description]",
                                'textarea_rows' => 10
                            );
                        
                            echo wp_editor(
                                $subject['description'],
                                "subjects-{$index}-description",
                                $settings
                            );
                            ?>

                            <h4>Enable Payments</h4>
                            <div class="info-box" name="info">
                                <div>
                                    <p class="info-icon" style='float:right'>
                                        <img draggable="false" role="img" class="emoji" alt="ℹ" src="<?php echo SIM\PICTURESURL."/info.png";?>" loading='lazy' >
                                    </p>
                                </div>
                                <span class="info-text">
                                    Enable to send payment reminders.<br>
                                    Make sure to set the payment options in the form settings.
                                </span>
                            </div>

                            <label>
                                <input type="radio" name="formfield[booking-details][<?php echo $index;?>][payments]" class=" formfield formfield-input" value="1" <?php if($subject['payments']){echo 'checked';}?>>
                                Yes
                            </label>
                            <label>
                                <input type="radio" name="formfield[booking-details][<?php echo $index;?>][payments]" class=" formfield formfield-input" value="0" <?php if(!$subject['payments']){echo 'checked';}?>>
                                No
                            </label>
                        </label>

                        <label class="formfield form-label">
                            <h4 class="label-text">Allow overlap</h4>
                            Allow new arrivals on the day the previous people leave<br>
                            <label>
                                <input type='radio' class='overlap' name='formfield[booking-details][<?php echo $index;?>][overlap]' value='yes' <?php if($subject['overlap'] == 'yes'){echo 'checked';}?>>
                                Yes
                            </label>    

                            <label>
                                <input type='radio' class='overlap' name='formfield[booking-details][<?php echo $index;?>][overlap]' value='no' <?php if($subject['overlap'] == 'no'){echo 'checked';}?>>
                                No
                            </label>
                            <br>
                            <br>
                            <div class='min-bookking-gap-time <?php if(!isset($subject['overlap']) || $subject['overlap'] == 'yes'){echo 'hidden';}?>'>
                                <label>
                                    Minimum time between two bookings in days
                                    <div class="info-box" name="info">
                                        <div>
                                            <p class="info-icon" style='float:right'>
                                                <img draggable="false" role="img" class="emoji" alt="ℹ" src="<?php echo SIM\PICTURESURL."/info.png";?>" loading='lazy' >
                                            </p>
                                        </div>
                                        <span class="info-text">
                                            Use 0 for allowing guests to arrive the next day.<br>
                                            1 means there is one full day between the previous and the next booking
                                        </span>
                                    </div>
                                    <input type='number' name='formfield[booking-details][<?php echo $index;?>][overlap-period]' value='<?php echo $subject['overlap-period'];?>' min='0'>
                                </label>
                            </div>
                        </label>

                        <label>
                            <input type='checkbox' name='formfield[booking-details][<?php echo $index;?>][oneday]' value='1' <?php if(isset($bookingDetails['oneday']) && $bookingDetails['oneday'] == 'yes'){echo 'checked';}?>>
                            Allow one day events
                        </label>

                        <label class="formfield form-label">
                            <h4>Default status for new bookings</h4>
                            <label>
                                <input type='radio' name='formfield[booking-details][<?php echo $index;?>][default-booking-state]' value='pending' <?php if($subject['default-booking-state'] == 'pending'){echo 'checked';}?>>
                                Pending
                            </label>
                            <br>
                            <label>
                                <input type='radio' name='formfield[booking-details][<?php echo $index;?>][default-booking-state]' value='confimed' <?php if($subject['default-booking-state'] == 'confimed'){echo 'checked';}?>>
                                Confimed
                            </label>
                            <br>
                            <br>
                            <button class="button sim small confirmed-roles-switcher <?php if($subject['default-booking-state'] != 'pending'){echo 'hidden';}?>" type="button" style='max-width: unset;'>Advanced</button>
                            <div class='confirmed-roles-wrapper hidden'>
                                <h4>Select roles for which bookings are confirmed by default</h4>
                                <div class="role-info">
                                    <?php
                                    foreach($userRoles as $key=>$roleName){
                                        if(!empty($subject['confirmed-booking-roles'][$key])){
                                            $checked = 'checked';
                                        }else{
                                            $checked = '';
                                        }
                                        echo "<label class='option-label'>";
                                            echo "<input type='checkbox' class='formbuilder form-element-setting' name='formfield[booking-details][$index][confirmed-booking-roles][$key]' value='$roleName' $checked>";
                                            echo $roleName;
                                        echo"</label><br>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </label>
                        
                        <br>
                        <label class="amount formfield form-label">
                            <h4>Room amount</h4>
                            <input type="number" name="formfield[booking-details][<?php echo $index;?>][amount]" class=" formfield formfield-input" value="<?php echo $subject['amount'];?>" placeholder="Enter subject amount" style='width: unset;'>
                        </label>  
                        <br>

                        <br>
                        <label class=" formfield form-label room-numbering <?php if($subject['amount'] == 1 || empty($subject['amount'])){echo 'hidden';}?>">
                            <h4>Room numbering type</h4>
                            <input type='radio' class='numbering-type' name='formfield[booking-details][<?php echo $index;?>][nrtype]' value='numbers' <?php if($subject['nrtype'] == 'numbers'){echo 'checked';}?>>
                            Numbers
                            <br>

                            <input type='radio' class='numbering-type' name='formfield[booking-details][<?php echo $index;?>][nrtype]' value='letters' <?php if($subject['nrtype'] == 'letters'){echo 'checked';}?>>
                            Letters
                            <br>

                            <input type='radio' class='numbering-type' name='formfield[booking-details][<?php echo $index;?>][nrtype]' value='custom' <?php if($subject['nrtype'] == 'custom'){echo 'checked';}?>>
                            Custom
                        </label>                          
                        <br>
                        <br>
                        <div class="rooms clone-divs-wrapper <?php if($subject['amount'] == 1 || empty($subject['amount'])){echo 'hidden';}?>" style='background: lightgrey;padding-bottom: 10px;padding-left: 10px;margin-right:10px'>
                            <?php
                            if(empty($subject['rooms'])){
                                $subject['rooms']   = ['1'];
                            }

                            ?>
                            <h3>Room details</h3>
                            <?php

                            foreach($subject['rooms'] as $i=>$room){
                                if(!is_array($room)){
                                    $room   = [
                                        "name"          => $room,
                                        "description"   => ''
                                    ];
                                }

                                if(!empty($room['name'])){
                                    $roomName   = $room['name'];
                                }elseif($subject['nrtype'] == 'letters'){
                                    $alphabet   = range('A', 'Z');
                                    $roomName   = $alphabet[$i];
                                }else{
                                    $roomName   =  $i+1;
                                }

                                ?>
                                <div class="clone-div" data-div-id="<?php echo $i;?>">
                                    <label name="roomname" class=" formfield form-label roomname">
                                        <h4>Room name</h4>
                                        <input type="text" name="formfield[booking-details][<?php echo $index;?>][rooms][<?php echo $i;?>][name]" class=" formfield formfield-input" value="<?php echo $roomName;?>" placeholder="Enter room name" style='width: unset;'>
                                    </label>
                                    <br>
                                    <br>
                                    <h4>Room Description</h4>
                                    <?php
                                    $settings = array(
                                        'wpautop' => false,
                                        'media_buttons' => false,
                                        'forced_root_block' => true,
                                        'convert_newlines_to_brs'=> true,
                                        'textarea_name' => "formfield[booking-details][$index][rooms][$i][description]",
                                        'textarea_rows' => 10
                                    );
                                
                                    echo wp_editor(
                                        $room['description'],
                                        "subjects-{$index}-rooms-{$i}-description",
                                        $settings
                                    );
                                    ?>
                                    
                                    <div class="button-wrapper" style="width:100%; display: flex;">
                                        <button type="button" class="add button" style="max-width: 130px; flex: 1;margin-right: 3px;margin-left: 3px;">Add a room</button>
                                        <?php
                                        if(count($subject['rooms']) > 1){
                                            ?>
                                            <button type="button" class="remove button" style="max-width: 190px;flex: 1;margin-right: 5px;">Remove this room</button>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                                <br>
                                <br>
                                <?php
                            }
                            ?>
                        </div>
                        <br>
                        <br>
                        <br>
                        <div class="button-wrapper" style="display: flex;">
                            <button type="button" class="add button" style="flex: 1; max-width: 150px; margin: 10px 5px 3px 0px;">Add a Subject</button>
                            <?php
                            if(count($bookingDetails) > 1){
                                ?>
                                <button type="button" class="remove button" style="flex: 1; max-width: 220px;margin-top: 10px">Remove this Subject</button>
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
    </div>
    <?php
}

// add extra elements for displaying in results table
add_filter('sim-forms-elements', __NAMESPACE__.'\formElements', 10, 3);
function formElements($elements, $displayFormResults, $force){
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
        if($el->type == 'booking-selector'){
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
}

// Display the date selector in the form
add_filter('sim-forms-element-html', __NAMESPACE__.'\elementHtml', 10, 3);
function elementHtml($html, $element, $object){
     // Check if the form has a booking selector
     if(empty($object->getElementByType('booking-selector'))){
        return $html;
    }

    if($element->type == 'booking-selector'){
        $bookings       = new Bookings($object);
        $bookingDetails = $bookings->getElementSubjects($element->id);

        if(!isset($bookingDetails)){
           return '<div class="warning">Please add one or more subjects</div>';
        }
        
        $details        = '';

        // Render tab buttons
        foreach($bookingDetails as $index => $subject){
            $subjectName    = strtolower(str_replace(' ', '-', $subject['name']));
            $active = '';
            if($index === 0 ){
                $active = 'active';
            }
            $details        .= "<button class='button tablink $active' type='button' id='show-{$subjectName}' data-target='$subjectName' style='margin-right:4px;'>
                {$subject['name']}
            </button>";
        }

        // Render tab contents
        foreach($bookingDetails as $index => $subject){
            $subjectName    = strtolower(str_replace(' ', '-', $subject['name']));
            $hidden = 'hidden';
            if($index === 0 ){
                $hidden = '';
            }

            $details        .= "<div id='$subjectName' class='tabcontent $hidden'>";
                // Make sure we have valid content, balanced and comments removed.
                $content    = force_balance_tags(do_shortcode($subject['description']));
                $content    = preg_replace('/<!--(.|\s)*?-->/', '', $content);
                
                if(empty($content)){
                    $manager        = get_userdata($subject['managers'][0]);
                    if($manager){
                        $details        .= "No details found, sorry.<br> Contact <a href='mailto:$manager->user_email?subject=Please add some description for {$subject['name']}&body=Dear $manager->display_name,'>the manager</a>";
                    }
                }else{
                    $details        .= $content;
                }
            $details        .= "</div>";
        }

        $html       = "
        <div name='location-details-modal' class='modal hidden'>
			<div class='modal-content'>
				<span class='close mobile-sticky'>&times;</span>
                $details
            </div>
		</div>
        ";
            
        $html       .= "<button type='button' class='small sim button location-details'>Show Location Descriptions</button><br>";
        $hidden     = 'hidden';
        $buttonText = 'Change';
        $required   = '';
        if($element->required){
            $required   = 'required';
        }

        if(empty($bookingDetails)){
            $hidden     = "";
            $buttonText = 'Select dates';
        }elseif(count($bookingDetails) < 6){
            foreach($bookingDetails as $subject){
                $cleanSubject    = trim($subject['name']);
                $checked    = '';
                if(isset($object->submission->formresults[$element->name]) && $object->submission->formresults[$element->name] == $cleanSubject){
                    $checked    = 'checked';
                }
                $html   .= "<label style='margin-right:5px;'>";
                    $html   .= "<input type='radio' class='booking-subject-selector' name='$element->name' value='$cleanSubject' $checked>";
                    $html   .= "$cleanSubject";
                $html   .= "</label>";
            }
        }else{
            $html   .= "<select class='booking-subject-selector' name='$element->name' $required>";
                foreach($bookingDetails as $subject){
                    $cleanSubject    = trim($subject['name']);
                    $html   .= "<option value='$cleanSubject'>$cleanSubject</option>";
                }
            $html   .= "</select>";
        }

        ob_start();

        ?>
        <div style='display:flex;align-items: center;'>
            <div class="clone-divs-wrapper selected-booking-dates <?php echo $hidden;?>">
                <div class="clone-div" data-div-id="0">
                    <div class="button-wrapper">
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

        $booking   = new Bookings($object);

        // Find the subject names
        foreach($bookingDetails as $subject){
            $html   .= $booking->dateSelectorModal($subject);
        }
    }

    elseif($element->name == 'booking-room'){
        $bookings       = new Bookings($object);

        $bookingDetails = maybe_unserialize($element->booking_details);

        if(empty($bookingDetails)){
            return 'Please add one or more subjects';
        }

        $elementName    = $object->getElementByType('booking-selector')[0]->name;

        foreach($bookingDetails as $subject){
            if($subject['name'] == $object->submission->formresults[$elementName]){
                break;
            }
        }

        $html   .= $bookings->roomSelector($subject, true);
    }

    // Display existing form entry element element
    elseif(!empty($object->submission)){
        global $wpdb;

        // Get the subject
        $subject    = $object->submission->formresults[$object->getElementByType('booking-selector')[0]->name];
            
        $startDates = (array) $object->submission->formresults['booking-startdate'];
        $endDates   = (array) $object->submission->formresults['booking-enddate'];

        $early      = array_values($startDates)[0];
        $late       = array_values($endDates)[0];

        foreach($startDates as $index=>$date){
            if($date < $early){
                $early  = $date;
            }

            if($endDates[$index] > $late){
                $late   = $endDates[$index];
            }
        }

        if(isset($_POST['booking-id']) && is_numeric($_POST['booking-id'])){
            $html   = str_replace('>', " data-booking-id='{$_POST['booking-id']}'>", $html);
        }

        if($element->name == 'booking-enddate'){
            // get the first event after this one
            $query  = "SELECT startdate FROM {$wpdb->prefix}sim_bookings WHERE subject = '$subject' AND startdate > '$late' ORDER BY startdate LIMIT 1";
            $max    = $wpdb->get_var($query);

            if(!empty($max)){
                $max    = "max='$max'";
            }

            $min    = "min='$early'";
        }elseif($element->name == 'booking-startdate'){
            // get the first event before this one
            $query  = "SELECT enddate FROM {$wpdb->prefix}sim_bookings WHERE subject = '$subject' AND enddate <= '$early' ORDER BY enddate LIMIT 1";
            $min    = $wpdb->get_var($query);

            if(!empty($min)){
                $min    = "min='$min'";
            }

            $max    = $late;
        }else{
            return $html;
        }

        $html   = str_replace('>', " $min max='$max'>", $html);
    }

    // Add a class for payment_amount_el
    elseif($element->id == $object->formData->payment_amount_el){
        $html   = str_replace("class='", "class='payment-amount ", $html);
    } 

    // Add a class for payment_details_el
    elseif($element->id == $object->formData->payment_details_el){
        $html   = str_replace("class='", "class='payment-details ", $html);
    } 

    // Add a class for payment_details_el
    elseif($element->id == $object->formData->price_per_night_el){
        $html   = str_replace("class='", "class='price-per-night ", $html);
    }

    return $html;

}

// Update the booking subjects name if the form name has changed
add_action('sim-after-formelement-updated', __NAMESPACE__.'\formElementUpdated', 10, 3);
function formElementUpdated($element, $instance, $oldElement){
    global $wpdb;

    if($element->type != 'booking-selector'){
        return;
    }

    // Check if a subject name is changed
    $oldBookingDetails  = maybe_unserialize($oldElement->booking_details);
    if(!$oldBookingDetails){
        $oldBookingDetails  = ['subjects' => []];
    }

    $newBookingDetails  = maybe_unserialize($element->booking_details);
    if(!$newBookingDetails){
        $newBookingDetails  = ['subjects' => []];
    }

    $oldSubjects        = array_map(__NAMESPACE__.'\getElementSubjectsNames', $oldBookingDetails);
    $newSubjects        = array_map(__NAMESPACE__.'\getElementSubjectsNames', $newBookingDetails);

    $changedNames       = array_diff($newSubjects, $oldSubjects);

    $bookings           = new Bookings($instance);

    foreach($changedNames as $index=>$newName){
        $oldName    = $oldSubjects[$index];

        // update existing bookings
        $query  = "UPDATE `$bookings->tableName` SET subject = REPLACE( `subject`, '$oldName', '$newName' ) WHERE `subject` LIKE '$oldName%'";
        
        $wpdb->query($query);
    }

    // Check if the payment option is changed
    $oldPayments        = array_map(__NAMESPACE__.'\getElementSubjectsPayments', $oldBookingDetails);
    $newPayments        = array_map(__NAMESPACE__.'\getElementSubjectsPayments', $newBookingDetails);

    foreach($oldPayments as $index => $old){
        // If we enable payments
        if($newPayments[$index] && $old != $newPayments[$index]){
            // mark old bookings as paid
            foreach($bookings->retrieveUnPaidBookings(true, true) as $unpaidBooking){
                $bookings->updateBooking($unpaidBooking, ['paid' => 1]);
            }
        }
    }
}

add_filter('forms-element-table-formats', __NAMESPACE__.'\addElementFormat', 10, 2);
function addElementFormat($formats, $object){
    $formats['booking_details']  = '%s'; // booking_details

    return $formats;
}

add_filter('forms-form-table-formats', __NAMESPACE__.'\addFormFormat', 10, 2);
function addFormFormat($formats, $object){
    $formats['payment_indicator']       = '%d'; // payment_indicator
    $formats['payment_amount_el']       = '%d'; // payment_amount_el
    $formats['payment_details_el']      = '%d'; // payment_details_el
    $formats['price_per_night_el']      = '%d'; // price_per_night_el
    $formats['default_booking_state']   = '%s'; // default_booking_state
    $formats['confirmed_booking_roles'] = '%s'; // confirmed_booking_roles

    return $formats;
}

function getElementSubjectsNames($v){
    if(is_array($v) && isset($v['name'])){
        return $v['name'];
    }
    return '';
}

function getElementSubjectsPayments($v){
    if(is_array($v) && isset($v['payments'])){
        return $v['payments'];
    }
    return '';
}