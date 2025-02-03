<?php
namespace SIM\BOOKINGS;
use SIM;

// Add a new type to the element choice dropdown
add_filter('sim-special-form-elements', __NAMESPACE__.'\specialFormElements');
function specialFormElements($options){
    $options['booking_selector']    = 'Booking selector';

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
                            <h5 style='margin-bottom:2px;'><strong>Enable Payments</strong></h5>
                            <div class="infobox" name="info">
                                <div>
                                    <p class="info_icon" style='float:right'>
                                        <img draggable="false" role="img" class="emoji" alt="ℹ" src="<?php echo SIM\PICTURESURL."/info.png";?>" loading='lazy' >
                                    </p>
                                </div>
                                <span class="info_text">
                                    Enable to send payment reminders.<br>
                                    Make sure to set the payment options in the form settings.
                                </span>
                            </div>

                            <label>
                                <input type="radio" name="formfield[booking_details][subjects][<?php echo $index;?>][payments]" id="payments" class=" formfield formfieldinput" value="1" <?php if($subject['payments']){echo 'checked';}?>>
                                Yes
                            </label>
                            <label>
                                <input type="radio" name="formfield[booking_details][subjects][<?php echo $index;?>][payments]" id="payments" class=" formfield formfieldinput" value="0" <?php if(!$subject['payments']){echo 'checked';}?>>
                                No
                            </label>
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
}

// Display the date selector in the form
add_filter('sim-forms-element-html', __NAMESPACE__.'\elementHtml', 10, 3);
function elementHtml($html, $element, $object){
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
                if(isset($object->submission->formresults['accomodation']) && $object->submission->formresults['accomodation'] == $cleanSubject){
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

        $booking   = new Bookings($object);

        // Find the accomodation names
        foreach($subjects as $subject){
            $html   .= $booking->dateSelectorModal($subject);
        }
    }

    elseif($element->name == 'booking-room'){
        $bookings       = new Bookings($object);

        $bookingDetails = maybe_unserialize($element->booking_details);

        if(!isset($bookingDetails['subjects'])){
            return 'Please add one or more subjects';
        }else{
            $subjects   = $bookingDetails['subjects'];
        }

        $elementName    = $object->getElementByType('booking_selector')[0]->name;

        foreach($subjects as $subject){
            if($subject['name'] == $object->submission->formresults[$elementName]){
                break;
            }
        }

        $html   .= $bookings->roomSelector($subject, true);
    }

    // Display existing form entry element element
    elseif(!empty(object->submission)){
        global $wpdb;

        // Get the subject
        $subject    = $object->submission->formresults[$object->getElementByType('booking_selector')[0]->name];
            
        $startDate  = $object->submission->formresults['booking-startdate'];
        $endDate    = $object->submission->formresults['booking-enddate'];

        if(isset($_POST['booking_id']) && is_numeric($_POST['booking_id'])){
            $html   = str_replace('>', " data-booking_id='{$_POST['booking_id']}'>", $html);
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

    if($element->type != 'booking_selector'){
        return;
    }

    // Check if a subject name is changed
    $oldBookingDetails  = maybe_unserialize($oldElement->booking_details);
    $newBookingDetails  = maybe_unserialize($element->booking_details);

    $oldSubjects        = array_map(__NAMESPACE__.'\getSubjectNames', $oldBookingDetails['subjects']);
    $newSubjects        = array_map(__NAMESPACE__.'\getSubjectNames', $newBookingDetails['subjects']);

    $changedNames       = array_diff($newSubjects, $oldSubjects);

    $bookings           = new Bookings($instance);

    foreach($changedNames as $index=>$newName){
        $oldName    = $oldSubjects[$index];

        // update existing bookings
        $query  = "UPDATE `$bookings->tableName` SET subject = REPLACE( `subject`, '$oldName', '$newName' ) WHERE `subject` LIKE '$oldName%'";
        
        $wpdb->query($query);
    }

    // Check if the payment option is changed
    $oldPayments        = array_map(__NAMESPACE__.'\getSubjectPayments', $oldBookingDetails['subjects']);
    $newPayments        = array_map(__NAMESPACE__.'\getSubjectPayments', $newBookingDetails['subjects']);

    foreach($oldPayments as $index => $old){
        // If we enable payments
        if($newPayments[$index] && $old != $newPayments[$index]){
            // mark old bookings as paid
            foreach($bookings->retrieveUnPaidBookings(true) as $unpaidBooking){
                $bookings->updateBooking($unpaidBooking, ['paid' => 1]);
            }
        }
    }
}

function getSubjectNames($v){
    if(is_array($v) && isset($v['name'])){
        return $v['name'];
    }
    return '';
}

function getSubjectPayments($v){
    if(is_array($v) && isset($v['payments'])){
        return $v['payments'];
    }
    return '';
}