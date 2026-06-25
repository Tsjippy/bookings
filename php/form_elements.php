<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

use function TSJIPPY\addElement as addElement;

if (! defined('ABSPATH')) {
    exit;
}

// Add a new type to the element choice dropdown
add_filter('tsjippy-forms-special-form-elements', __NAMESPACE__ . '\specialFormElements');
/**
 * Add booking selector to the list of special form elements
 *
 * @param array $options The current list of form elements
 *
 * @return array The updated list of form elements
 */
function specialFormElements($options)
{
    $options['booking-selector']    = 'Booking selector';

    return $options;
}

// add element options
add_filter('tsjippy-forms-element-form-content', __NAMESPACE__ . '\addFormElementOptions', 10, 3);
/**
 * Add form element options for the booking selector
 *
 * @param string $html The current HTML content
 * @param object $object The form object
 * @param object $element The form element
 *
 * @return string The updated HTML content
 */
function addFormElementOptions($html, $object, $element)
{
    global $wp_roles;


    if ($element == null || $element->type != 'booking-selector') {
        return $html;
    }

    //Get all available roles
    $userRoles      = $wp_roles->role_names;

    //Sort the roles
    asort($userRoles);

    $bookings       = new Bookings();

    $subjects       = $bookings->getElementSubjects($element->id);

    if (empty($subjects)) {
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
            foreach ($subjects as $index => $subject) {
                if (!is_array($subject)) {
                    $subject = $subjects[$index]    = [
                        'post-id'                   => -1,
                        'name'                      => $subject,
                        'amount'                    => 1,
                        'confirmed-booking-roles'   => [],
                    ];
                }

            ?>
                <button class='button tablink formbuilder-form' type='button' id='show-subject-<?php echo esc_attr($index); ?>' data-target='subject-<?php echo esc_attr($index); ?>' style='margin-right:4px;'>
                    <?php echo esc_html($subject['name']); ?>
                </button>
            <?php
            }

            // Render tab contents
            ?>
            <div id="element-settings" class="tabcontent">
                <?php echo wp_kses_post($html); ?>
            </div>
            <?php

            foreach ($subjects as $index => $subject) {
                $hidden    = 'hidden';
                if ($index === 0) {
                    //$hidden = '';
                }

            ?>
                <div id="subject-<?php echo esc_attr($index); ?>" class="clone-div tabcontent <?php echo esc_attr($hidden); ?>" data-div-id="<?php echo esc_attr($index); ?>">
                    <input type="hidden" class="no-reset" name="formfield[booking-details][<?php echo esc_attr($index); ?>][post-id]" value="<?php echo esc_attr($subject['post-id']); ?>">
                    <input type="hidden" class="no-reset" name="formfield[booking-details][<?php echo esc_attr($index); ?>][element-id]" value="<?php echo esc_attr($element->id); ?>">

                    <label name="Subject" class=" formfield form-label" style='width: auto;margin-right: 20px;'>
                        <h4>Name</h4>
                        <input type="text" name="formfield[booking-details][<?php echo esc_attr($index); ?>][name]" class="subject-name formfield formfield-input" value="<?php echo esc_attr($subject['name']); ?>" placeholder="Enter subject name" style='width: unset;'>
                    </label>
                    <br>
                    <br>
                    <label class=" formfield form-label" style='width: auto;margin-right: 20px;'>
                        <h4>Manager(s)</h4>
                        <?php
                        TSJIPPY\userSelect(id: "formfield[booking-details][$index][managers][]", userId: $subject['managers'], multiple: true, echo: true);
                        ?>
                    </label>

                    <h4>Location Description</h4>
                    <?php
                    $settings = array(
                        'wpautop'                   => false,
                        'media_buttons'             => false,
                        'forced_root_block'         => true,
                        'convert_newlines_to_brs'   => true,
                        'textarea_name'             => "formfield[booking-details][$index][description]",
                        'textarea_rows'             => 10
                    );

                    wp_editor(
                        $subject['description'],
                        "subjects-{$index}-description",
                        $settings
                    );
                    ?>

                    <label class=" formfield form-label" style='width: auto;margin-right: 20px;'>
                        <h4>Enable Payments</h4>
                        <?php
                        echo wp_kses_post($bookings->forms->infoBoxHtml("Enable to send payment reminders.<br>Make sure to set the payment options in the form settings. "));
                        ?>

                        <label>
                            <input type="radio" name="formfield[booking-details][<?php echo esc_attr($index); ?>][payments]" class=" formfield formfield-input" value="1" <?php if ($subject['payments']) {
                                                                                                                                                                                echo 'checked';
                                                                                                                                                                            } ?>>
                            Yes
                        </label>
                        <label>
                            <input type="radio" name="formfield[booking-details][<?php echo esc_attr($index); ?>][payments]" class=" formfield formfield-input" value="0" <?php if (!$subject['payments']) {
                                                                                                                                                                                echo 'checked';
                                                                                                                                                                            } ?>>
                            No
                        </label>
                    </label>

                    <label class="formfield form-label">
                        <h4 class="label-text">Allow overlap</h4>
                        Allow new arrivals on the day the previous people leave<br>
                        <label>
                            <input type='radio' class='overlap' name='formfield[booking-details][<?php echo esc_attr($index); ?>][overlap]' value='1' <?php if ($subject['overlap'] == '1') {
                                                                                                                                                            echo 'checked';
                                                                                                                                                        } ?>>
                            Yes
                        </label>

                        <label>
                            <input type='radio' class='overlap' name='formfield[booking-details][<?php echo esc_attr($index); ?>][overlap]' value='0' <?php if ($subject['overlap'] == '0') {
                                                                                                                                                            echo 'checked';
                                                                                                                                                        } ?>>
                            No
                        </label>
                        <br>
                        <br>
                        <div class='min-bookking-gap-time <?php if (($subject['overlap'] ?? 1) == '1') {
                                                                echo 'hidden';
                                                            } ?>'>
                            <label>
                                Minimum time between two bookings in days
                                <?php
                                echo wp_kses_post($bookings->forms->infoBoxHtml("Use 0 for allowing guests to arrive the next day.<br>1 means there is one full day between the previous and the next booking"));
                                ?>
                                <input type='number' name='formfield[booking-details][<?php echo esc_attr($index); ?>][overlap-period]' value='<?php echo esc_attr($subject['overlap-period']); ?>' min='0'>
                            </label>
                        </div>
                    </label>

                    <label>
                        <input type='checkbox' name='formfield[booking-details][<?php echo esc_attr($index); ?>][oneday]' value='1' <?php if (isset($subjects['oneday']) && $subjects['oneday'] == 'yes') {
                                                                                                                                        echo 'checked';
                                                                                                                                    } ?>>
                        Allow one day events
                    </label>

                    <label class="formfield form-label">
                        <h4>Default status for new bookings</h4>
                        <label>
                            <input type='radio' name='formfield[booking-details][<?php echo esc_attr($index); ?>][default-booking-state]' value='pending' <?php if ($subject['default-booking-state'] == 'pending') {
                                                                                                                                                                echo 'checked';
                                                                                                                                                            } ?>>
                            Pending
                        </label>
                        <br>
                        <label>
                            <input type='radio' name='formfield[booking-details][<?php echo esc_attr($index); ?>][default-booking-state]' value='confimed' <?php if ($subject['default-booking-state'] == 'confimed') {
                                                                                                                                                                echo 'checked';
                                                                                                                                                            } ?>>
                            Confimed
                        </label>
                        <br>
                        <br>
                        <button class="button tsjippy small confirmed-roles-switcher <?php if ($subject['default-booking-state'] != 'pending') {
                                                                                            echo 'hidden';
                                                                                        } ?>" type="button" style='max-width: unset;'>Advanced</button>
                        <div class='confirmed-roles-wrapper hidden'>
                            <h4>Select roles for which bookings are confirmed by default</h4>
                            <div class="role-info">
                                <?php
                                foreach ($userRoles as $key => $roleName) {
                                    if (in_array($key, $subject['confirmed-booking-roles'])) {
                                        $checked = 'checked';
                                    } else {
                                        $checked = '';
                                    }
                                ?>
                                    <label class='option-label'>
                                        <input type='checkbox' class='formbuilder form-element-setting' name='formfield[booking-details][<?php echo esc_attr($index); ?>][confirmed-booking-roles][]' value='<?php echo esc_attr($key); ?>' <?php if (in_array($key, $subject['confirmed-booking-roles'])) {
                                                                                                                                                                                                                                                echo 'checked=checked';
                                                                                                                                                                                                                                            } ?>>
                                        <?php echo esc_html($roleName); ?>
                                    </label><br>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </label>

                    <br>
                    <label class="amount formfield form-label">
                        <h4>Room amount</h4>
                        <input type="number" name="formfield[booking-details][<?php echo esc_attr($index); ?>][amount]" class=" formfield formfield-input" value="<?php echo esc_attr($subject['amount']); ?>" placeholder="Enter subject amount" style='width: unset;'>
                    </label>
                    <br>

                    <br>
                    <label class=" formfield form-label room-numbering <?php if ($subject['amount'] == 1 || empty($subject['amount'])) {
                                                                            echo 'hidden';
                                                                        } ?>">
                        <h4>Room numbering type</h4>
                        <input type='radio' class='numbering-type' name='formfield[booking-details][<?php echo esc_attr($index); ?>][nrtype]' value='numbers' <?php if ($subject['nrtype'] == 'numbers') {
                                                                                                                                                                    echo 'checked';
                                                                                                                                                                } ?>>
                        Numbers
                        <br>

                        <input type='radio' class='numbering-type' name='formfield[booking-details][<?php echo esc_attr($index); ?>][nrtype]' value='letters' <?php if ($subject['nrtype'] == 'letters') {
                                                                                                                                                                    echo 'checked';
                                                                                                                                                                } ?>>
                        Letters
                        <br>

                        <input type='radio' class='numbering-type' name='formfield[booking-details][<?php echo esc_attr($index); ?>][nrtype]' value='custom' <?php if ($subject['nrtype'] == 'custom') {
                                                                                                                                                                    echo 'checked';
                                                                                                                                                                } ?>>
                        Custom
                    </label>
                    <br>
                    <br>
                    <div class="rooms clone-divs-wrapper <?php if ($subject['amount'] == 1 || empty($subject['amount'])) {
                                                                echo 'hidden';
                                                            } ?>" style='background: lightgrey;padding-bottom: 10px;padding-left: 10px;margin-right:10px'>
                        <?php
                        if (empty($subject['rooms'])) {
                            $subject['rooms']   = ['0'];
                        }

                        ?>
                        <h3>Room details</h3>
                        <?php

                        // Tab buttons
                        foreach ($subject['rooms'] as $i => $room) {
                            if (empty($room['name'])) {
                                $room['name']   = "No Name " . $i + 1;
                            }

                            $subjectName    = strtolower(str_replace(' ', '-', $subject['name']));

                        ?>
                            <button class='button tablink formbuilder-form <?php if ($i === 0) {
                                                                                echo 'active';
                                                                            } ?>' type='button' id='<?php echo esc_attr("show-$subjectName-room-$i"); ?>' data-target='<?php echo esc_attr("$subjectName-room-$i"); ?>' style='margin-right:4px;max-width: 100px;'>
                                Room <?php echo esc_html($room['name']); ?>
                            </button>
                        <?php
                        }

                        // Tab contents
                        foreach ($subject['rooms'] as $i => $room) {
                            if (!is_array($room)) {
                                $room   = [
                                    "name"          => '',
                                    "description"   => ''
                                ];
                            }

                            if (!empty($room['name'])) {
                                $roomName   = $room['name'];
                            } elseif ($subject['nrtype'] == 'letters') {
                                $alphabet   = range('A', 'Z');
                                $roomName   = $alphabet[$i];
                            } else {
                                $roomName   =  $i + 1;
                            }

                            $subjectName    = strtolower(str_replace(' ', '-', $subject['name']));

                        ?>
                            <div id="<?php echo esc_attr($subjectName); ?>-room-<?php echo esc_attr($i); ?>" class="clone-div tabcontent <?php if ($i !== 0) {
                                                                                                                                                echo 'hidden';
                                                                                                                                            } ?>" data-div-id="<?php echo esc_attr($i); ?>">
                                <input type="hidden" name="formfield[booking-details][<?php echo esc_attr($index); ?>][rooms][<?php echo esc_attr($i); ?>][post-id]" value="<?php echo esc_attr($room['post-id']); ?>">
                                <label name="roomname" class=" formfield form-label roomname">
                                    <h4>Room name</h4>
                                    <input type="text" name="formfield[booking-details][<?php echo esc_attr($index); ?>][rooms][<?php echo esc_attr($i); ?>][name]" class=" formfield formfield-input" value="<?php echo esc_attr($roomName); ?>" placeholder="Enter room name" style='width: unset;'>
                                </label>
                                <br>
                                <br>
                                <h4>Room Description</h4>
                                <?php
                                $settings = array(
                                    'wpautop' => false,
                                    'media_buttons' => false,
                                    'forced_root_block' => true,
                                    'convert_newlines_to_brs' => true,
                                    'textarea_name' => "formfield[booking-details][$index][rooms][$i][description]",
                                    'textarea_rows' => 10
                                );

                                wp_editor(
                                    $room['description'],
                                    "subjects-{$index}-rooms-{$i}-description",
                                    $settings
                                );
                                ?>

                                <div class="button-wrapper" style="width:100%; display: flex;">
                                    <button type="button" class="add button" style="max-width: 130px; flex: 1;margin-right: 3px;margin-left: 3px;">
                                        Add a room
                                    </button>
                                    <?php
                                    $hidden = 'hidden';
                                    if (count($subject['rooms']) > 1) {
                                        $hidden = '';
                                    }
                                    ?>
                                    <button type="button" class="remove button <?php echo esc_attr($hidden); ?>" style="max-width: 190px;flex: 1;margin-right: 5px;">
                                        Remove this room
                                    </button>
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
                        if (count($subjects) > 1) {
                            $hidden = '';
                        }
                        ?>
                        <button type="button" class="remove button <?php echo esc_attr($hidden); ?>" style="flex: 1; max-width: 220px;margin-top: 10px">
                            Remove this Subject
                        </button>
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
add_filter('tsjippy-forms-elements', __NAMESPACE__ . '\formElements', 10, 3);
/**
 * Add extra form elements for the booking selector
 * These elements are used to store the booking details and to display them in the results table
 *
 * @param array $elements The current list of form elements
 * @param object $displayFormResults The current form results object
 * @param bool $force Whether to force to run the function again
 *
 * @return array The updated list of form elements
 */
function formElements($elements, $displayFormResults, $force)
{
    // do not show on the form itself, only on the results
    if (!$force && !in_array(get_class($displayFormResults), ["TSJIPPY\FORMS", "TSJIPPY\FORMS\DisplayFormResults", "TSJIPPY\FORMS\SubmitForm", "TSJIPPY\FORMS\EditFormResults"])) {
        return $elements;
    }

    /**
     * Check if this form has a booking-selector element
     */

    // We cannot use getElementByType here as we have not gotten all elements yet.
    $element    = false;
    foreach ($elements as $el) {
        if ($el->type == 'booking-selector') {
            $element    = $el;
            break;
        }
    }

    if ($element) {
        // Add the start_date and end_date
        $start_date                     = clone $element;
        $start_date->type               = 'date';
        $start_date->slug               = 'booking-start-date';
        $start_date->name               = 'Startdate';
        $start_date->id                 = -102;
        $start_date->booking_details    = '';

        $end_date                   = clone $element;
        $end_date->type             = 'date';
        $end_date->slug             = 'booking-end-date';
        $end_date->name             = 'Enddate';
        $end_date->id               = -103;
        $end_date->booking_details  = '';

        $room                   = clone $element;
        $room->type             = 'checkbox';
        $room->slug             = 'booking-rooms';
        $room->name             = 'Room';
        $room->id               = -104;
        $room->required         = false;
        $room->booking_details  = '';

        $elements[]         = $start_date;
        $elements[]         = $end_date;
        $elements[]         = $room;
    }

    return $elements;
}

/**
 * Render the booking selector element on the form
 *
 * @param object $parent   The current DOM parent to add the element to
 * @param object $object The form object
 *
 * @return object The rendered element
 */
function bookingSelectorHtml($parent, $object)
{
    $bookings       = new Bookings($object);
    $subjects       = $bookings->getElementSubjects($object->element->id);

    if (empty($subjects)) {
        return addElement('div', $parent, ['class' => 'warning'], 'Please add one or more subjects');
    }

    /**
     * Build the modal
     */
    $modal      = addElement(
        'div',
        $parent,
        [
            'name'  => 'location-details-modal',
            'class' => 'modal hidden'
        ]
    );

    $modalContent   = addElement('div', $modal, ['class' => 'modal-content']);

    TSJIPPY\addCloseButtton($modalContent);

    // Render tab buttons
    foreach ($subjects as $index => $subject) {
        $subjectName    = strtolower(str_replace(' ', '-', $subject['name']));
        $attributes     = [
            'class'         => 'button tablink',
            'id'            => "show-{$subjectName}",
            'data-target'   => $subjectName,
            'style'         => 'margin-right:4px;',
            'type'          => 'button'
        ];

        if ($index === 0) {
            $attributes['class'] .= ' active';
        }

        addElement('button', $modalContent, $attributes, $subject['name']);
    }

    // Render tab contents
    foreach ($subjects as $index => $subject) {
        $attributes     = [
            'class'         => 'tabcontent lazy-post',
            'id'            => strtolower(str_replace(' ', '-', $subject['name'])),
            'data-post-id'  => $subject['post-id']
        ];

        if ($index !== 0) {
            $attributes['class'] .= ' hidden';
        }

        addElement('div', $modalContent, $attributes, $subject['name']);
    }

    /**
     * Build the element
     */
    addElement('button', $parent, ['class' => 'small tsjippy button location-details', 'type' => 'button'], 'Show Location Descriptions');
    addElement('br', $parent);

    $hidden     = 'hidden';
    $buttonText = 'Change';

    if (empty($subjects)) {
        $hidden     = "";
        $buttonText = 'Select dates';
    } elseif (count($subjects) < 6) {
        foreach ($subjects as $subject) {
            $attributes = [
                'type'  => 'radio',
                'class' =>  'booking-subject-selector',
                'name'  => $object->element->slug,
                'value' => trim($subject['name'])
            ];

            if (isset($object->submission->{$object->element->id}) && $object->submission->{$object->element->id} == trim($subject['name'])) {
                $attributes['checked']    = 'checked';
            }

            $label  = addElement('label', $parent, ['style' => 'margin-right:5px;']);
            addElement(
                'input',
                $label,
                $attributes
            );

            $label->append(trim($subject['name']));
        }
    } else {
        $attributes = [
            'class' =>  'booking-subject-selector',
            'name'  => $object->element->slug
        ];

        if ($object->element->required) {
            $attributes['required']    = 'required';
        }

        $select  = addElement('select', $parent, $attributes);

        foreach ($subjects as $subject) {
            addElement('option', $select, ['value' => trim($subject['name'])], trim($subject['name']));
        }
    }

    $flexDiv = addElement('div', $parent, ['style' => 'display:flex;align-items: center;']);

    $cloneDivsWrapper = addElement('div', $flexDiv, [
        'class' => "clone-divs-wrapper selected-booking-dates $hidden"
    ]);

    $cloneDiv       = addElement('div', $cloneDivsWrapper, ['class' => 'clone-div', 'data-div-id' => '0']);

    $buttonWrapper  = addElement('div', $cloneDiv, ['class' => 'button-wrapper']);

    $roomDiv        = addElement('div', $buttonWrapper, ['class' => 'hidden']);

    addElement('h4', $roomDiv, [], 'Room');

    $attributes = [
        'type'      => 'text',
        'name'      => 'booking-rooms[0]',
        'disabled'  => 'disabled'
    ];

    if ($object->element->required) {
        $attributes['required']   = 'required';
    }

    addElement('input', $roomDiv, $attributes);

    $arrivalDiv = addElement('div', $buttonWrapper);

    addElement('h4', $arrivalDiv, [], 'Arrival Date');

    $attributes = [
        'type'      => 'date',
        'name'      => 'booking-start-date[0]',
        'disabled'  => 'disabled'
    ];

    if ($object->element->required) {
        $attributes['required']   = 'required';
    }

    addElement('input', $arrivalDiv, $attributes);

    $departureDiv   = addElement('div', $buttonWrapper);

    addElement('h4', $departureDiv, [], 'Departure Date');

    $attributes = [
        'type'      => 'date',
        'name'      => 'booking-end-date[0]',
        'disabled'  => 'disabled'
    ];

    if ($object->element->required) {
        $attributes['required']   = 'required';
    }

    addElement('input', $departureDiv, $attributes);

    addElement('button', $flexDiv, [
        'class' => 'button change-booking-date hidden',
        'type'  => 'button',
        'style' => 'margin-left: 20px;'
    ], $buttonText);

    wp_enqueue_script('tsjippy-bookings');

    $day    = gmdate('d');
    $month  = (int) ($_GET['month'] ?? '');
    $year   = (int) ($_GET['yr'] ?? '');

    if (!is_numeric($month) || strlen($month) != 2) {
        $month  = gmdate('m');
    }
    if (!is_numeric($year) || strlen($year) != 4) {
        $year   = gmdate('Y');
    }

    // Find the subject names
    foreach ($subjects as $subject) {
        $bookings->dateSelectorModal($day, $month, $year, $parent, $subject);
    }

    return $flexDiv;
}

/**
 * Render the booking date elements on the form with the correct min and max attributes based on the existing bookings
 *
 * @param object $node The current DOM node to render the element in
 * @param object $object The form object
 *
 * @return object The rendered element
 */
function bookingDateElementHtml(&$node, $object, $bookingId = false)
{
    global $wpdb;

    if (is_numeric($bookingId)) {
        $node->setAttribute('data-booking-id', $bookingId);
    }

    if ($object->element->slug != 'booking-start-date' && $object->element->slug != 'booking-end-date') {
        return;
    }

    // Get the subject
    $subject    = $object->submission->{$object->getElementByType('booking-selector')[0]->slug};

    $startDates = (array) $object->submission->{'booking-start-date'};
    $endDates   = (array) $object->submission->{'booking-end-date'};

    $early      = array_values($startDates)[0];
    $late       = array_values($endDates)[0];

    foreach ($startDates as $index => $date) {
        if ($date < $early) {
            $early  = $date;
        }

        if ($endDates[$index] > $late) {
            $late   = $endDates[$index];
        }
    }


    if ($object->element->slug == 'booking-start-date') {
        // get the first event after this one
        $max    = TSJIPPY\getFromDb(
            "get_start_date_for_{$subject}_after_$late",
            "bookings",
            "SELECT start_date FROM %i WHERE subject = %s AND start_date > %s ORDER BY start_date LIMIT 1",
            "{$wpdb->prefix}tsjippy_bookings",
            $subject,
            $late
        );

        if (!empty($max)) {
            $node->setAttribute('max', $max);
        }

        $node->setAttribute('min', $early);
    } elseif ($object->element->slug == 'booking-end-date') {
        // get the first event before this one
        $min    = TSJIPPY\getFromDb(
            "get_end_date_for_{$subject}_before_$early",
            "bookings",
            "SELECT end_date FROM %i WHERE subject = %s AND end_date <= %s ORDER BY end_date LIMIT 1",
            "{$wpdb->prefix}tsjippy_bookings",
            $subject,
            $early
        );

        if (!empty($min)) {
            $node->setAttribute('min', $min);
        }

        $node->setAttribute('max', $late);
    }
}

// Display the date selector in the form
add_filter('tsjippy-forms-element-html-short-circuit', __NAMESPACE__ . '\bookingSelectorElementHtml', 10, 3);
/**
 * Render the booking selector element on the form
 *
 * @param object $override  default null, return a node to skip element html rendering
 * @param object $parent    The parent form element
 * @param object $object    The form object
 *
 * @return object The rendered element
 */
function bookingSelectorElementHtml($override, $parent, $object)
{
    // Check if the form has a booking selector
    if ($object->element->type != 'booking-selector') {
        return $override;
    }

    return bookingSelectorHtml($parent, $object);
}

// Display the date selector in the form
add_filter('tsjippy-forms-element-html', __NAMESPACE__ . '\elementHtml', 10, 2);
/**
 * Render the form element HTML
 *
 * @param object $node The current DOM node to render the element in
 * @param object $object The form object
 *
 * @return object The rendered element
 */
function elementHtml($node, $object)
{
    // Check if the form has a booking selector
    if (empty($object->getElementByType('booking-selector'))) {
        return $node;
    }

    if ($object->element->slug == 'booking-rooms') {
        $bookings       = new Bookings($object);

        if (empty($subjects)) {
            return 'Please add one or more subjects';
        }

        $elementName    = $object->getElementByType('booking-selector')[0]->slug;

        foreach ($subjects as $subject) {
            if ($subject['name'] == $object->submission->{$elementName}) {
                break;
            }
        }

        $bookings->roomSelector($node, $subject, true);
    }

    // Display existing form entry element element
    elseif (!empty($object->submission)) {
        bookingDateElementHtml($node, $object, (int) $_POST['booking-id']);
    }

    // Add a class for payment_amount_el
    elseif ($object->element->id == $object->formData->payment_amount_el) {
        $class  = $node->getAttribute('class');

        $class  .= ' payment-amount';

        $node->setAttribute('class', $class);
    }

    // Add a class for payment_details_el
    elseif ($object->element->id == $object->formData->payment_details_el) {
        $class  = $node->getAttribute('class');

        $class  .= ' payment-details';

        $node->setAttribute('class', $class);
    }

    // Add a class for payment_details_el
    elseif ($object->element->id == $object->formData->price_per_night_el) {
        $class  = $node->getAttribute('class');

        $class  .= ' price-per-night';

        $node->setAttribute('class', $class);
    }

    return $node;
}

// Update the booking-subjects name if the form name has changed
add_action('tsjippy-forms-after-formelement-updated', __NAMESPACE__ . '\formElementUpdated', 10, 3);
/**
 * Handle updates to form elements
 *
 * @param object $element The updated element
 * @param object $instance The form instance
 * @param object $oldElement The old element
 *
 * @return void
 */
function formElementUpdated($element, $instance, $oldElement)
{
    global $wpdb;

    if ($element->type != 'booking-selector') {
        return;
    }

    $bookings       = new BookingPayments($instance);
    $bookings->getSubjects();

    // Get the updated subject data
    $newSubjects    = TSJIPPY\sanitize($_POST['formfield']['booking-details'] ?? []);

    // index by post ids
    foreach ($newSubjects as $index => $subject) {
        unset($newSubjects[$index]);
        $newSubjects[$subject['post-id']]  = $subject;
    }

    // Previous subject data
    $oldSubjects        = $bookings->getElementSubjects($oldElement->id);

    // index by post ids
    foreach ($oldSubjects as $postId => $subject) {
        unset($oldSubjects[$postId]);
        $oldSubjects[$subject['post-id']]  = $subject;
    }

    // Loop over old subjects to see what changed
    foreach ($oldSubjects as $postId => $subject) {
        // This subject is removed
        if (!isset($newSubjects[$postId])) {
            $bookings->removeSubject($subject);
            continue;
        }

        $removed    = TSJIPPY\arrayDiffAssocRecursive($subject, $newSubjects[$postId]);
        $added      = TSJIPPY\arrayDiffAssocRecursive($newSubjects[$postId], $subject);
        $changed    = array_intersect_key($added, $removed);

        foreach ($changed as $key => $value) {
            unset($removed[$key]);
        }

        // See what is removed
        foreach ($removed as $key => $value) {
            delete_post_meta($postId, "tsjippy_$key");

            if ($key == 'payments') {
                // We disabled payments
                if ($value) {
                    // mark old bookings as paid
                    foreach ($bookings->retrieveUnPaidBookings(true, true) as $unpaidBooking) {
                        $bookings->updateBooking($unpaidBooking, ['paid' => 1]);
                    }
                }

                update_post_meta($postId, 'tsjippy_payments', $value);
            }
        }

        // Walk over the changes
        foreach ($added as $key => $value) {
            // Remove empty array values
            if (is_array($value)) {
                $value = array_filter($value);
            }

            // Subject detail changed
            if ($key == 'name') {
                // update existing bookings
                $wpdb->query($wpdb->prepare(
                    "UPDATE %i SET subject = REPLACE(`subject`, %s, %s) WHERE `subject` LIKE %s",
                    $bookings->tableName,
                    $subject[$key],
                    $value,
                    $wpdb->esc_like($value) . '%'
                )); 

                // Flush the cache to force new db queries
                if(wp_cache_supports( 'flush_group' )){
                    wp_cache_flush_group('bookings');
                }else{
                    wp_cache_flush();
                }

                // Update post title
                wp_update_post([
                    'ID'            => $postId,
                    'post_title'    => $value
                ]);
            } elseif ($key == 'description') {
                wp_update_post([
                    'ID'            => $postId,
                    'post_content'  => $value
                ]);
            } elseif ($key == 'rooms') {
                // index old rooms by post ids
                $oldRooms   = [];
                foreach ($subject['rooms'] as $index => $oldValue) {
                    unset($oldValue[$index]);

                    if (!empty($oldValue['post-id']) && $oldValue['post-id'] != -1) {
                        $oldRooms[$oldValue['post-id']]  = $oldValue;
                    }
                }

                // index new rooms by post ids
                $newRoomData    = [];
                $addedRooms     = [];
                foreach ($value as $index => $v) {
                    unset($value[$index]);

                    if (!isset($v['post-id'])) {
                        $v['post-id']   = $subject['rooms'][$index]['post-id'];
                    }

                    if (empty($v['post-id'])) {
                        $addedRooms[] = $v;
                    } elseif ($v['post-id'] != -1) {
                        $newRoomData[$v['post-id']]  = $v;
                    }
                }

                /**
                 * Use the unfiltered data to check which rooms are removed
                 * Cannot use the filtered data as rooms without change are also not present in there
                 */
                $submittedRooms = [];
                foreach ($newSubjects[$postId]['rooms'] as $room) {
                    $submittedRooms[$room['post-id']]   = $room;
                }

                $removedRooms   = array_diff_key($oldRooms, $submittedRooms);
                $changedRooms   = TSJIPPY\arrayDiffAssocRecursive($newRoomData, $oldRooms);

                $subjectName    = ucfirst($subject['name']);
                foreach ($addedRooms as $room) {
                    $name          = ucfirst($room['name']);
                    $description   = $room['description'] ?? '';

                    $roomId = wp_insert_post([
                        'post_title'    => "$subjectName Room $name",
                        'post_type'     => 'booking-room',
                        'post_status'   => 'publish',
                        'post_content'  => $description,
                        'post_parent'   => $postId
                    ]);

                    add_post_meta($postId, 'rooms', [$roomId => $name]);
                    add_post_meta($roomId, 'name', $name);
                }

                foreach ($changedRooms as $roomId => $room) {

                    $update = [
                        'ID'            => $roomId
                    ];

                    if (!empty($room['description'])) {
                        $update['post_content']  = $room['description'];
                    }

                    if (!empty($room['name'])) {
                        $update['post_title']    = "$subjectName Room {$room['name']}";

                        update_post_meta($postId, 'tsjippy_rooms', [$roomId => $room['name']], [$roomId => $oldRooms[$roomId]['name']]);
                        update_post_meta($roomId, 'tsjippy_name', $room['name']);
                    }

                    // Update room post
                    wp_update_post($update);
                }

                foreach ($removedRooms as $room) {
                    wp_delete_post($room['post-id']);

                    $name          = ucfirst($room['name']);
                    delete_post_meta($postId, 'tsjippy_rooms', [$room['post-id'] => $name]);
                }
            } elseif ($key == 'payments') {
                // We enabled payments
                if ($value) {
                    // mark old bookings as paid
                    foreach ($bookings->retrieveUnPaidBookings(true, true) as $unpaidBooking) {
                        $bookings->updateBooking($unpaidBooking, ['paid' => 1]);
                    }
                }

                update_post_meta($postId, 'tsjippy_payments', $value);
            } elseif ($value) {
                // first delete all
                delete_post_meta($postId, "tsjippy_$key");

                // Then add the new ones
                foreach ($value as $k => $v) {
                    add_post_meta($postId, $key, $v);
                }
            } else {
                update_post_meta($postId, "tsjippy_".$key, $value);
            }
        }
    }

    $addedSubjects      = array_diff_key($newSubjects, $oldSubjects);
    foreach ($addedSubjects as $newSubject) {
        $bookings->addSubject($newSubject);
    }
}

add_filter('tsjippy-forms-shortcode-table-formats', __NAMESPACE__ . '\addShortcodeFormat', 10, 2);
/**
 * Add extra formats for the booking selector shortcode
 *
 * @param array $formats The current list of formats
 * @param object $object The form results object
 *
 * @return array The updated list of formats
 */
function addShortcodeFormat($formats, $object)
{
    $formats['booking_display']       = '%s';

    return $formats;
}

add_filter('tsjippy-forms-form-table-formats', __NAMESPACE__ . '\addFormFormat', 10, 2);
/**
 * Add extra formats for the form table
 *
 * @param array $formats The current list of formats
 * @param object $object The form results object
 *
 * @return array The updated list of formats
 */
function addFormFormat($formats, $object)
{
    $formats['payment_indicator']       = '%d'; // payment_indicator
    $formats['payment_amount_el']       = '%d'; // payment_amount_el
    $formats['payment_details_el']      = '%d'; // payment_details_el
    $formats['price_per_night_el']      = '%d'; // price_per_night_el
    $formats['default_booking_state']   = '%s'; // default_booking_state
    $formats['confirmed_booking_roles'] = '%s'; // confirmed_booking_roles

    return $formats;
}

/**
 * Get the payment information for a given element
 *
 * @param mixed $v The element to check
 *
 * @return string The payment information or an empty string
 */
function getElementSubjectsPayments($v)
{
    if (is_array($v) && isset($v['payments'])) {
        return $v['payments'];
    }
    return '';
}
