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
add_filter('sim-forms-element-form-content', __NAMESPACE__.'\addFormElementOptions', 10, 3);
function addFormElementOptions($html, $object, $element){
    global $wp_roles;
    

    if($element == null || $element->type != 'booking-selector'){
        return $html;
    }
    
    //Get all available roles
    $userRoles = $wp_roles->role_names;
    
    //Sort the roles
    asort($userRoles);

    $bookings       = new Bookings();

    $subjects       = $bookings->getElementSubjects($element->id);

    if(empty($subjects)){
        $subjects = ['No Subjects defined yet'];
    }

    ob_start();

    ?>
    <div class='element-option booking-selector hidden'>      
        <div class="clone-divs-wrapper">
            <button class='button tablink formbuilder-form active' type='button' id='show-element-settings' data-target='element-settings' style='margin-right:4px;'>
                Element Settings
            </button>
            <?php
            // Render tab buttons
            foreach($subjects as $index => $subject){                    
                if(!is_array($subject)){
                    $subject = $subjects[$index]    = [
                        'post-id'                   => -1,
                        'name'                      => $subject,
                        'amount'                    => 1,
                        'confirmed-booking-roles'   => [],
                    ];
                }

                ?>
                <button class='button tablink formbuilder-form' type='button' id='show-subject-<?php echo $index;?>' data-target='subject-<?php echo $index;?>' style='margin-right:4px;'>
                    <?php echo $subject['name'];?>
                </button>
                <?php
            }
                
            // Render tab contents
            ?>
            <div id="element-settings" class="tabcontent">
                <?php echo $html;?>
            </div>
            <?php

            foreach($subjects as $index => $subject){
                $hidden	= 'hidden';
                if($index === 0){
                    //$hidden = '';
                }

                ?>
                <div id="subject-<?php echo $index;?>" class="clone-div tabcontent <?php echo $hidden;?>" data-div-id="<?php echo $index;?>">
                    <input type="hidden" class="no-reset" name="formfield[booking-details][<?php echo $index;?>][post-id]" value="<?php echo $subject['post-id'];?>" >
                    <input type="hidden" class="no-reset" name="formfield[booking-details][<?php echo $index;?>][element-id]" value="<?php echo $element->id;?>" >
                    
                    <label name="Subject" class=" formfield form-label" style='width: auto;margin-right: 20px;'>
                        <h4>Name</h4>
                        <input type="text" name="formfield[booking-details][<?php echo $index;?>][name]" class="subject-name formfield formfield-input" value="<?php echo $subject['name'];?>" placeholder="Enter subject name" style='width: unset;'>
                    </label>
                    <br>
                    <br>
                    <label class=" formfield form-label" style='width: auto;margin-right: 20px;'>
                        <h4>Manager(s)</h4>
                        <?php
                        echo SIM\userSelect('', false, false, '', "formfield[booking-details][$index][managers][]", [], $subject['managers'], [], 'select', '', true);
                        ?>
                    </label>

                    <h4>Location Description</h4>
                    <?php
                    $settings = array(
                        'wpautop' => false,
                        'media_buttons' => false,
                        'forced_root_block' => true,
                        'convert_newlines_to_brs'=> true,
                        'textarea_name' => "formfield[booking-details][$index][description]",
                        'textarea_rows' => 10
                    );
                
                    echo wp_editor(
                        $subject['description'],
                        "subjects-{$index}-description",
                        $settings
                    );
                    ?>

                    <label class=" formfield form-label" style='width: auto;margin-right: 20px;'>
                        <h4>Enable Payments</h4>
                        <?php
                        echo $bookings->forms->infoBoxHtml("Enable to send payment reminders.<br>Make sure to set the payment options in the form settings.");
                        ?>

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
                                <?php
                                echo $bookings->forms->infoBoxHtml("Use 0 for allowing guests to arrive the next day.<br>1 means there is one full day between the previous and the next booking");
                                ?>
                                <input type='number' name='formfield[booking-details][<?php echo $index;?>][overlap-period]' value='<?php echo $subject['overlap-period'];?>' min='0'>
                            </label>
                        </div>
                    </label>

                    <label>
                        <input type='checkbox' name='formfield[booking-details][<?php echo $index;?>][oneday]' value='1' <?php if(isset($subjects['oneday']) && $subjects['oneday'] == 'yes'){echo 'checked';}?>>
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
                                foreach($userRoles as $key => $roleName){
                                    if(in_array($key, $subject['confirmed-booking-roles'])){
                                        $checked = 'checked';
                                    }else{
                                        $checked = '';
                                    }
                                    echo "<label class='option-label'>";
                                        echo "<input type='checkbox' class='formbuilder form-element-setting' name='formfield[booking-details][$index][confirmed-booking-roles][]' value='$key' $checked>";
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
                            $subject['rooms']   = ['0'];
                        }

                        ?>
                        <h3>Room details</h3>
                        <?php

                        // Tab buttons
                        foreach($subject['rooms'] as $i => $room){
                            if(empty($room['name'])){
                                continue;
                            }

                            $active	= '';

                            if($i === 0){
                                $active = 'active';
                            }

                            $subjectName    = strtolower(str_replace(' ', '-', $subject['name']));

                            ?>
                            <button class='button tablink formbuilder-form <?php echo $active;?>' type='button' id='show-<?php echo $subjectName;?>-room-<?php echo $i;?>' data-target='<?php echo $subjectName;?>-room-<?php echo $i;?>' style='margin-right:4px;max-width: 100px;'>
                                Room <?php echo $room['name'];?>
                            </button>
                            <?php
                        }

                        // Tab contents
                        foreach($subject['rooms'] as $i => $room){
                            if(!is_array($room)){
                                $room   = [
                                    "name"          => '',
                                    "description"   => ''
                                ];
                            }

                            if(!empty($room['name'])){
                                $roomName   = $room['name'];
                            }elseif($subject['nrtype'] == 'letters'){
                                $alphabet   = range('A', 'Z');
                                $roomName   = $alphabet[$i];
                            }else{
                                $roomName   =  $i + 1;
                            }

                            $subjectName    = strtolower(str_replace(' ', '-', $subject['name']));

                            $hidden	= 'hidden';
                            if($i === 0){
                                $hidden = '';
                            }

                            ?>
                            <div id="<?php echo $subjectName;?>-room-<?php echo $i;?>" class="clone-div tabcontent <?php echo $hidden;?>" data-div-id="<?php echo $i;?>">
                                <input type="hidden" name="formfield[booking-details][<?php echo $index;?>][rooms][<?php echo $i;?>][post-id]" value="<?php echo $room['post-id'];?>">
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
                                    $hidden = 'hidden';
                                    if(count($subject['rooms']) > 1){
                                        $hidden = '';
                                    }
                                    ?>
                                    <button type="button" class="remove button <?php echo $hidden;?>" style="max-width: 190px;flex: 1;margin-right: 5px;">Remove this room</button>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>

                    <div class="button-wrapper" style="display: flex;">
                        <button type="button" class="add button" style="flex: 1; max-width: 150px; margin: 10px 5px 3px 0px;">Add a Subject</button>
                        <?php
                        $hidden = 'hidden';
                        if(count($subjects) > 1){
                            $hidden = '';
                        }
                        ?>
                        <button type="button" class="remove button <?php echo $hidden;?>" style="flex: 1; max-width: 220px;margin-top: 10px">Remove this Subject</button>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <br>
    </div>
    <?php

    return ob_get_clean();
}

// add extra elements for displaying in results table
add_filter('sim-forms-elements', __NAMESPACE__.'\formElements', 10, 3);
function formElements($elements, $displayFormResults, $force){
    // do not show on the form itself, only on the results
    if(!$force && !in_array(get_class($displayFormResults), ["SIM\FORMS\DisplayFormResults", "SIM\FORMS\SubmitForm", "SIM\FORMS\EditFormResults"])){
        return $elements;
    }

    // do not add to the formbuilder screen
    if(str_contains($_SERVER['QUERY_STRING'], 'formbuilder=true')){
        return $elements;
    }

    /**
     * Check if this form has a booking-selector element
     */
    
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
        $startdate->nicename= 'Startdate';
        $startdate->id      = -102;

        $enddate            = clone $element;
        $enddate->type      = 'date';
        $enddate->name      = 'booking-enddate';
        $enddate->nicename  = 'Enddate';
        $enddate->id        = -103;

        $room               = clone $element;
        $room->type         = 'checkbox';
        $room->name         = 'booking-rooms';
        $room->nicename     = 'Room';
        $room->id           = -104;
        
        $elements[]         = $startdate;
        $elements[]         = $enddate;
        $elements[]         = $room;
    }
    
    return $elements;
}

function bookingSelectorHtml($node, $object){
    $bookings       = new Bookings($object);
    $subjects       = $bookings->getElementSubjects($object->element->id);

    if(empty($subjects)){
        return $object->addElement('div', $node, ['class'=>'warning'], 'Please add one or more subjects');
    }

    /**
     * Build the modal
     */
    $modal      = $object->addElement(
        'div', 
        $node, 
        [
            'name'  => 'location-details-modal',
            'class' => 'modal hidden'
        ]
    );

    $modalContent   = $object->addElement('div', $modal, ['class' => 'modal-content']);

    $object->addElement('span', $modalContent, ['class' => 'close mobile-sticky'], '&times;');

    // Render tab buttons
    foreach($subjects as $index => $subject){
        $subjectName    = strtolower(str_replace(' ', '-', $subject['name']));
        $attributes     = [
            'class'         => 'button tablink',
            'id'            => "show-{$subjectName}",
            'data-target'   => $subjectName,
            'style'         => 'margin-right:4px;',
            'type'          => 'button'
        ];

        if($index === 0 ){
            $attributes['class'] .= ' active';
        }

        $object->addElement('button', $modalContent, $attributes, $subject['name']);
    }

    // Render tab contents
    foreach($subjects as $index => $subject){
        $attributes     = [
            'class'         => 'tabcontent lazy-post',
            'id'            => strtolower(str_replace(' ', '-', $subject['name'])),
            'data-post-id'  => $subject['post-id']
        ];

        if($index !== 0 ){
            $attributes['class'] .= ' hidden';
        }

        $object->addElement('div', $modalContent, $attributes, $subject['name']);
    }
    
    /**
     * Build the element 
     */   
    $object->addElement('button', $node, ['class' => 'small sim button location-details', 'type' => 'button'], 'Show Location Descriptions');
    $object->addElement('br', $node);

    $hidden     = 'hidden';
    $buttonText = 'Change';
    
    if(empty($subjects)){
        $hidden     = "";
        $buttonText = 'Select dates';
    }elseif(count($subjects) < 6){
        foreach($subjects as $subject){
            $attributes = [
                'type'  => 'radio',
                'class' =>  'booking-subject-selector',
                'name'  => $object->element->name,
                'value' => trim($subject['name'])
            ];

            if(isset($object->submission->{$object->element->id}) && $object->submission->{$object->element->id} == trim($subject['name'])){
                $attributes['checked']    = 'checked';
            }

            $label  = $object->addElement('label', $node, ['style' => 'margin-right:5px;']);
            $object->addElement(
                'input', 
                $label, 
                $attributes
            );

            $textNode = $object->dom->createTextNode(trim($subject['name']));

            $label->appendChild($textNode);
        }
    }else{
        $attributes = [
            'class' =>  'booking-subject-selector',
            'name'  => $object->element->name
        ];

        if($object->element->required){
            $attributes['required']    = 'required';
        }

        $select  = $object->addElement('select', $node, $attributes);

        foreach($subjects as $subject){
            $object->addElement('option', $select, ['value' => trim($subject['name'])], trim($subject['name']));
        }
    }

    $flexDiv = $object->addElement('div', $node, ['style' => 'display:flex;align-items: center;']);

        $cloneDivsWrapper = $object->addElement('div', $flexDiv, [
            'class' => "clone-divs-wrapper selected-booking-dates $hidden"
        ]);

            $cloneDiv       = $object->addElement('div', $cloneDivsWrapper, ['class' => 'clone-div', 'data-div-id' => '0']);

                $buttonWrapper  = $object->addElement('div', $cloneDiv, ['class' => 'button-wrapper']);

                    $roomDiv        = $object->addElement('div', $buttonWrapper, ['class' => 'hidden']);

                        $object->addElement('h4', $roomDiv, [], 'Room');

                        $attributes = [
                            'type'      => 'text',
                            'name'      => 'booking-rooms[0]',
                            'disabled'  => 'disabled'
                        ];

                        if($object->element->required){
                            $attributes['required']   = 'required';
                        }

                        $object->addElement('input', $roomDiv, $attributes);

                    $arrivalDiv = $object->addElement('div', $buttonWrapper);
                        
                        $object->addElement('h4', $arrivalDiv, [], 'Arrival Date');

                        $attributes = [
                            'type'      => 'date',
                            'name'      => 'booking-startdate[0]',
                            'disabled'  => 'disabled'
                        ];

                        if($object->element->required){
                            $attributes['required']   = 'required';
                        }

                        $object->addElement('input', $arrivalDiv, $attributes);

                    $departureDiv   = $object->addElement('div', $buttonWrapper);
                    
                        $object->addElement('h4', $departureDiv, [], 'Departure Date');

                        $attributes = [
                            'type'      => 'date',
                            'name'      => 'booking-enddate[0]',
                            'disabled'  => 'disabled'
                        ];

                        if($object->element->required){
                            $attributes['required']   = 'required';
                        }

                        $object->addElement('input', $departureDiv, $attributes);

        $object->addElement('button', $flexDiv, [
            'class' => 'button change-booking-date hidden',
            'type'  => 'button',
            'style' => 'margin-left: 20px;'
        ], $buttonText);

    wp_enqueue_script('sim-bookings');

    // Find the subject names
    foreach($subjects as $subject){
        $bookings->dateSelectorModal($node, $subject);
    }

    return $flexDiv;
}

function bookingDateElementHtml(&$node, $object){
    global $wpdb;

    if(isset($_POST['booking-id']) && is_numeric($_POST['booking-id'])){
        $node->setAttribute('data-booking-id', $_POST['booking-id']);
    }

    if($object->element->name != 'booking_enddate' && $object->element->name != 'booking_startdate'){
        return;
    }

    // Get the subject
    $subject    = $object->submission->{$object->getElementByType('booking-selector')[0]->name};
        
    $startDates = (array) $object->submission->booking_startdate;
    $endDates   = (array) $object->submission->booking_enddate;

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
    

    if($object->element->name == 'booking_enddate'){
        // get the first event after this one
        $query  = "SELECT startdate FROM {$wpdb->prefix}sim_bookings WHERE subject = '$subject' AND startdate > '$late' ORDER BY startdate LIMIT 1";
        $max    = $wpdb->get_var($query);

        if(!empty($max)){
            $node->setAttribute('max', $max);
        }

        $node->setAttribute('min', $early);
    }elseif($object->element->name == 'booking_startdate'){
        // get the first event before this one
        $query  = "SELECT enddate FROM {$wpdb->prefix}sim_bookings WHERE subject = '$subject' AND enddate <= '$early' ORDER BY enddate LIMIT 1";
        $min    = $wpdb->get_var($query);

        if(!empty($min)){
            $node->setAttribute('min', $min);
        }

        $node->setAttribute('max', $late);
    }
}

// Display the date selector in the form
add_filter('sim-form-element-html-short-circuit', __NAMESPACE__.'\bookingSelectorElementHtml', 10, 3);
function bookingSelectorElementHtml($node, $parent, $object){
     // Check if the form has a booking selector
    if($object->element->type != 'booking-selector'){
        return $node;
    }

    return bookingSelectorHtml($parent, $object);
}

// Display the date selector in the form
add_filter('sim-form-element-html', __NAMESPACE__.'\elementHtml', 10, 2);
function elementHtml($node, $object){
     // Check if the form has a booking selector
     if(empty($object->getElementByType('booking-selector'))){
        return $node;
    }

    if($object->element->name == 'booking_rooms'){
        $bookings       = new Bookings($object);

        //$subjects = maybe_unserialize($object->element->booking_details);

        if(empty($subjects)){
            return 'Please add one or more subjects';
        }

        $elementName    = $object->getElementByType('booking-selector')[0]->name;

        foreach($subjects as $subject){
            if($subject['name'] == $object->submission->{$elementName}){
                break;
            }
        }

        $node   = $bookings->roomSelector($node, $subject, true);
    }

    // Display existing form entry element element
    elseif(!empty($object->submission)){
        bookingDateElementHtml($node, $object);
    }

    // Add a class for payment_amount_el
    elseif($object->element->id == $object->formData->payment_amount_el){
        $class  = $node->getAttribute('class')->value;

        $class  .= ' payment-amount';

        $node->setAttribute('class', $class);
    } 

    // Add a class for payment_details_el
    elseif($object->element->id == $object->formData->payment_details_el){
        $class  = $node->getAttribute('class')->value;

        $class  .= ' payment-details';

        $node->setAttribute('class', $class);
    } 

    // Add a class for payment_details_el
    elseif($object->element->id == $object->formData->price_per_night_el){
        $class  = $node->getAttribute('class')->value;

        $class  .= ' price-per-night';

        $node->setAttribute('class', $class);
    }

    return $node;
}

// Update the booking-subjects name if the form name has changed
add_action('sim-after-formelement-updated', __NAMESPACE__.'\formElementUpdated', 10, 3);
function formElementUpdated($element, $instance, $oldElement){
    global $wpdb;

    if($element->type != 'booking-selector'){
        return;
    }

    $bookings       = new Bookings($instance);
    $bookings->getSubjects();

    // Get the updated subject data
    $newSubjects  = $_POST['formfield']['booking-details'];
    if(!$newSubjects){
        $newSubjects  = [];
    }

    // index by post ids
    foreach($newSubjects as $index => $subject){
        unset($newSubjects[$index]);
        $newSubjects[$subject['post-id']]  = $subject;
    }

    // Previous subject data
    $oldSubjects        = $bookings->getElementSubjects($oldElement->id);

    // index by post ids
    foreach($oldSubjects as $postId => $subject){
        unset($oldSubjects[$postId]);
        $oldSubjects[$subject['post-id']]  = $subject;
    }

    // Loop over old subjects to see what changed
    foreach($oldSubjects as $postId => $subject){
        // This subject is removed
        if(!isset($newSubjects[$postId])){
            $bookings->removeSubject($subject);
            continue;
        }

        // This subject is changed
        if($subject != $newSubjects[$postId]){

            // Check what changed
            foreach($subject as $key => $value){
                // Remove empty array values
                if(is_array($newSubjects[$postId][$key])){
                    $newSubjects[$postId][$key] = array_filter($newSubjects[$postId][$key]);
                }

                // Delete this one
                if(!isset($newSubjects[$postId][$key])){
                    delete_post_meta($postId, $key);

                    continue;
                }
                
                // Nothing has changed
                elseif( $newSubjects[$postId][$key] == $value){
                    continue;
                }

                // Subject detail changed
                if($key == 'name'){
                    // update existing bookings
                    $query  = "UPDATE `$bookings->tableName` SET subject = REPLACE( `subject`, '$value', '$newSubjects[$postId][$key]' ) WHERE `subject` LIKE '$value%'";
                    
                    $wpdb->query($query);

                    // Update post title
                    wp_update_post([
                        'ID'            => $postId,
                        'post_title'    => $newSubjects[$postId][$key]
                    ]);
                }elseif($key == 'description'){
                    wp_update_post([
                        'ID'            => $postId,
                        'post_content'  => $newSubjects[$postId][$key]
                    ]);
                }elseif($key == 'rooms'){

                    // index old rooms by post ids
                    $oldRooms   = [];
                    foreach($value as $index => $v){
                        unset($v[$index]);

                        if($v['post-id'] != -1){
                            $oldRooms[$v['post-id']]  = $v;
                        }
                    }

                    // index new rooms by post ids
                    $newRooms   = [];
                    foreach($newSubjects[$postId][$key] as $index => $v){
                        unset($newSubjects[$postId][$key][$index]);
                        
                        if($v['postid'] != -1){
                            $newRooms[$v['post-id']]  = $v;
                        }
                    }

                    $addedRooms     = array_diff_key($newRooms, $oldRooms);
                    $subjectName    = ucfirst($subject['name']);
                    foreach($addedRooms as $room){
                        $name          = ucfirst($room['name']);
                        $description   = isset($room['description']) ? $room['description'] : '';

                        $roomId = wp_insert_post([
                            'post_title'    => "$subjectName Room $name",
                            'post_type'     => 'booking-room',
                            'post_status'   => 'publish',
                            'post_content'  => $description,
                            'post_parent'   => $postId
                        ]);
                        
                        add_post_meta($postId, 'room', [$roomId => $name]);
                        add_post_meta($roomId, 'name', $name);
                    }

                    $removedRooms  = array_diff_key($oldRooms, $newRooms);
                    foreach($removedRooms as $room){
                        wp_delete_post($room['post-id']);

                        $name          = ucfirst($room['name']);
                        delete_post_meta($postId, 'room', [$room['post-id'] => $name]);
                    }
                }elseif($key == 'payments'){
                    // We enabled payments
                    if($newSubjects[$postId][$key]){
                        // mark old bookings as paid
                        foreach($bookings->retrieveUnPaidBookings(true, true) as $unpaidBooking){
                            $bookings->updateBooking($unpaidBooking, ['paid' => 1]);
                        }
                    }

                    update_post_meta($postId, $key, $newSubjects[$postId][$key]);
                }elseif(is_array($newSubjects[$postId][$key])){
                    // first delete all
                    delete_post_meta($postId, $key);

                    // Then add the new ones
                    foreach($newSubjects[$postId][$key] as $k => $v){
                        add_post_meta($postId, $key, $v);
                    }
                }else{
                    update_post_meta($postId, $key, $newSubjects[$postId][$key]);
                }
            }
        }
    }

    $addedSubjects      = array_diff_key($newSubjects, $oldSubjects);
    foreach($addedSubjects as $newSubject){
        $bookings->addSubject($newSubject);
    }
}

add_filter('forms-shortcode-table-formats', __NAMESPACE__.'\addShortcodeFormat', 10, 2);
function addShortcodeFormat($formats, $object){
    $formats['booking_display']       = '%s';

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

function getElementSubjectsPayments($v){
    if(is_array($v) && isset($v['payments'])){
        return $v['payments'];
    }
    return '';
}