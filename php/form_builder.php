<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

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
add_filter('tsjippy-forms-element-form-content', __NAMESPACE__ . '\addFormFromBuilderBookingSelectorOptions', 10, 3);
/**
 * Add form element options for the booking selector
 *
 * @param string $html    The current HTML content
 * @param object $object  The form object
 * @param object $element The form element
 *
 * @return string         The updated HTML content
 */
function addFormFromBuilderBookingSelectorOptions($html, $object, $element)
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

    ob_start();

    ?>
    <div class='element-option booking-selector hidden'>
        <div class="clone-divs-wrapper">
            <button class='button tablink formbuilder-form active' type='button' id='show-element-settings' data-target='element-settings' style='margin-right:4px;'>
                Element Settings
            </button>
            <?php
            // Render tab buttons
            /**
             * We need one empty subject to be able set it up
             */
            if(empty($subjects)){
                $subjects[] = [
                    'post-id'                   => -1,
                ];
            }
            foreach ($subjects as $index => $subject) {
                ?>
                <button class='button tablink formbuilder-form<?php if ($index === 0) echo ' active'; if($subject['post-id'] == -1){ echo ' dummy hidden'; } ?>' type='button' id='show-subject-<?php echo esc_attr($index); ?>' data-target='subject-<?php echo esc_attr($index); ?>' style='margin-right:4px;'>
                    <?php echo esc_html($subject['name'] ?? 'Name'); ?>
                </button>
            <?php
            }
            ?>
            <button type="button" class="add button" style="flex: 1; max-width: 150px; margin: 10px 5px 3px 0px;">
                Add a Subject
            </button>

            <div id="element-settings" class="tabcontent">
                <?php echo wp_kses_post($html); ?>
            </div>
            <?php

        
            foreach ($subjects as $index => $subject) {
                ?>
                <div id="subject-<?php echo esc_attr($index); ?>" class="clone-div tabcontent <?php if($index !== 0 || $subject['post-id'] == -1){ echo 'hidden'; } ?>" data-div-id="<?php echo esc_attr($index); ?>">
                    <input type="hidden" class="no-reset" name="formfield[booking-details][<?php echo esc_attr($index); ?>][post-id]" value="<?php echo esc_attr($subject['post-id']); ?>">
                    <input type="hidden" class="no-reset" name="formfield[booking-details][<?php echo esc_attr($index); ?>][element-id]" value="<?php echo esc_attr($element->id); ?>">
                    <?php
                    $hidden = 'hidden';
                    if (count($subjects) > 1) {
                        $hidden = '';
                    }
                    ?>
                    <button type="button" class="remove button <?php echo esc_attr($hidden); ?>" style="flex: 1; max-width: 220px;margin-top: 10px">
                        Remove this Subject
                    </button>

                    <label name="Subject" class=" formfield form-label" style='width: auto;margin-right: 20px;'>
                        <h4>
                            Name
                        </h4>
                        <input type="text" name="formfield[booking-details][<?php echo esc_attr($index); ?>][name]" class="subject-name formfield formfield-input" value="<?php echo esc_attr($subject['name']); ?>" placeholder="Enter subject name" style='width: unset;'>
                    </label>
                    <br>
                    <br>
                    <label class=" formfield form-label" style='width: auto;margin-right: 20px;'>
                        <h4>
                            Manager(s)
                        </h4>
                        <?php
                        TSJIPPY\userSelect(id: "formfield[booking-details][$index][managers][]", userId: $subject['managers'] ?? [], multiple: true, echo: true);
                        ?>
                    </label>

                    <h4>
                        Location Description
                    </h4>
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
                        $subject['description'] ?? '',
                        "subjects-{$index}-description",
                        $settings
                    );
                    ?>

                    <label class=" formfield form-label" style='width: auto;margin-right: 20px;'>
                        <h4>
                            Enable Payments
                        </h4>
                        <?php
                        $bookings->forms->infoBoxHtml("Enable to send payment reminders.<br>Make sure to set the payment options in the form settings. ");
                        ?>

                        <label>
                            <input
                                type="radio"
                                name="formfield[booking-details][<?php echo esc_attr($index); ?>][payments]"
                                class="formfield formfield-input"
                                value="1"
                                <?php if ($subject['payments']) echo 'checked'; ?>>
                            Yes
                        </label>
                        <label>
                            <input
                                type="radio"
                                name="formfield[booking-details][<?php echo esc_attr($index); ?>][payments]"
                                class=" formfield formfield-input"
                                value="0"
                                <?php if (!$subject['payments']) echo 'checked'; ?>>
                            No
                        </label>
                    </label>

                    <label class="formfield form-label">
                        <h4 class="label-text">Allow overlap</h4>
                        Allow new arrivals on the day the previous people leave<br>
                        <label>
                            <input
                                type='radio'
                                class='overlap'
                                name='formfield[booking-details][<?php echo esc_attr($index); ?>][overlap]'
                                value='1'
                                <?php if ($subject['overlap'] == '1') echo 'checked'; ?>>
                            Yes
                        </label>

                        <label>
                            <input
                                type='radio'
                                class='overlap'
                                name='formfield[booking-details][<?php echo esc_attr($index); ?>][overlap]'
                                value='0'
                                <?php if ($subject['overlap'] == '0') echo 'checked'; ?>>
                            No
                        </label>
                        <br>
                        <br>
                        <div
                            class='min-bookking-gap-time 
                        <?php if (($subject['overlap'] ?? 1) == '1') echo 'hidden'; ?>'>
                            <label>
                                Minimum time between two bookings in days
                                <?php
                                $bookings->forms->infoBoxHtml("Use 0 for allowing guests to arrive the next day.<br>1 means there is one full day between the previous and the next booking");
                                ?>
                                <input type='number' name='formfield[booking-details][<?php echo esc_attr($index); ?>][overlap-period]' value='<?php echo esc_attr($subject['overlap-period']); ?>' min='0'>
                            </label>
                        </div>
                    </label>

                    <label>
                        <input
                            type='checkbox'
                            name='formfield[booking-details][<?php echo esc_attr($index); ?>][oneday]'
                            value='1'
                            <?php if (isset($subjects['oneday']) && $subjects['oneday'] == 'yes') echo 'checked'; ?>>
                        Allow one day events
                    </label>

                    <label class="formfield form-label">
                        <h4>
                            Default status for new bookings
                        </h4>
                        <label>
                            <input
                                type='radio'
                                name='formfield[booking-details][<?php echo esc_attr($index); ?>][default-booking-state]'
                                value='pending'
                                <?php if ($subject['default-booking-state'] == 'pending') echo 'checked'; ?>>
                            Pending
                        </label>
                        <br>
                        <label>
                            <input
                                type='radio'
                                name='formfield[booking-details][<?php echo esc_attr($index); ?>][default-booking-state]'
                                value='confimed'
                                <?php if ($subject['default-booking-state'] == 'confimed') echo 'checked'; ?>>
                            Confimed
                        </label>
                        <br>
                        <br>
                    </label>

                    <br>
    
                    <div class="rooms clone-divs-wrapper">
                        <button type="button" class="add button room" style="max-width: 130px; flex: 1;margin-right: 3px;margin-left: 3px;">
                            Add a room
                        </button>
                        <br>

                        <div class="room-details-wrapper<?php if (count($subject['rooms'] ?? []) < 2 ) echo ' hidden'; ?>" style='background: lightgrey;padding-bottom: 10px;padding-left: 10px;margin-right:10px'>
                            
                            <h3>
                                Room details
                            </h3>
                            <?php
                            /**
                             * We need one empty room to be able set it up
                             */
                            if(empty($subject['rooms'])){
                                $subject['rooms'] = [
                                    [
                                        'post-id'                   => -1
                                    ]
                                ];
                            }

                            // Tab buttons
                            foreach (($subject['rooms']) as $i => $room) {

                                $subjectName    = strtolower(str_replace(' ', '-', $subject['name'] ?? ''));

                                ?>
                                <button
                                    class='button tablink formbuilder-form <?php if ($i === 0) echo 'active';  if ($room['post-id'] == -1) echo 'dummy hidden'; ?>'
                                    type='button' 
                                    id='<?php echo esc_attr("show-$subjectName-room-$i"); ?>'
                                    data-target='<?php echo esc_attr("$subjectName-room-$i"); ?>'
                                    style='margin-right:4px;max-width: 100px;'>
                                    Room <?php echo esc_html($room['name']); ?>
                                </button>
                                <?php
                            }

                            // Tab contents
                            foreach (($subject['rooms'] ?? []) as $i => $room) {
                                if (!is_array($room)) {
                                    $room   = [
                                        "name"          => '',
                                        "description"   => ''
                                    ];
                                }

                                if (!empty($room['name'])) {
                                    $roomName   = $room['name'];
                                } else {
                                    $roomName   =  $i + 1;
                                }

                                $subjectName    = strtolower(str_replace(' ', '-', $subject['name']));

                            ?>
                                <div
                                    id="<?php echo esc_attr($subjectName); ?>-room-<?php echo esc_attr($i); ?>"
                                    class="clone-div tabcontent <?php if ($i !== 0 || $room['post-id'] == -1) echo 'hidden'; ?>"
                                    data-div-id="<?php echo esc_attr($i); ?>">
                                    <input type="hidden" name="formfield[booking-details][<?php echo esc_attr($index); ?>][rooms][<?php echo esc_attr($i); ?>][post-id]" value="<?php echo esc_attr($room['post-id']); ?>">
                                    <label name="roomname" class=" formfield form-label roomname">
                                        <h4>
                                            Room name
                                        </h4>
                                        <input type="text" name="formfield[booking-details][<?php echo esc_attr($index); ?>][rooms][<?php echo esc_attr($i); ?>][name]" class=" formfield formfield-input" value="<?php echo esc_attr($roomName); ?>" placeholder="Enter room name" style='width: unset;'>
                                    </label>
                                    <br>
                                    <br>
                                    <h4>
                                        Room Description
                                    </h4>
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

// Update the booking-subjects name if the form name has changed
add_action('tsjippy-forms-after-formelement-updated', __NAMESPACE__ . '\formElementUpdated', 10, 3);
/**
 * Handle updates to form elements
 *
 * @param object $element    The updated element
 * @param object $instance   The form instance
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
    // phpcs:ignore
    $updatedSubjectData    = TSJIPPY\sanitize($_POST['formfield']['booking-details'] ?? []);

    /**
     * Loop over the updated subject data and index by post ids
     * This is done to make it easier to compare the old and new data
     */
    foreach ($updatedSubjectData as $index => $subject) {
        unset($updatedSubjectData[$index]);

        // Do not keep subjects with no name
        if (empty($subject['name'])) {
            continue;
        }

        // flip the managers array so we can use the faster isset vs in_array
        if (!empty($subject['managers'])) {
            $subject['managers'] = array_flip($subject['managers']);
        }

        /**
         * Create a new booking-subject post if the post-id is -1
         */
        if($subject['post-id'] == -1){
            $subject['post-id'] = $bookings->addSubject($subject);
        }

        $updatedSubjectData[$subject['post-id']]  = $subject;
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
        if (!isset($updatedSubjectData[$postId])) {
            $bookings->removeSubject($subject);
            continue;
        }

        $removed    = TSJIPPY\arrayDiffAssocRecursive($subject, $updatedSubjectData[$postId]);
        $added      = TSJIPPY\arrayDiffAssocRecursive($updatedSubjectData[$postId], $subject);
        $changed    = array_intersect_key($added, $removed);

        foreach ($changed as $key => $value) {
            unset($removed[$key]);
        }

        // See what is removed
        foreach ($removed as $key => $value) {
            // Only delete a specific post meta
            if (is_array($value)) {
                foreach ($value as $v) {
                    delete_post_meta($postId, "tsjippy_$key", $v);
                }
            } else {
                delete_post_meta($postId, "tsjippy_$key");
            }

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

            /**
             * Name changed, update the subject name in the bookings table and the post title of the booking-subject post
             */
            if ($key == 'name') {
                // update existing bookings
                // phpcs:disable
                $wpdb->query($wpdb->prepare(
                    "UPDATE %i SET subject = REPLACE(`subject`, %s, %s) WHERE `subject` LIKE %s",
                    $bookings->tableName,
                    $subject[$key],
                    $value,
                    $wpdb->esc_like($value) . '%'
                ));
                // phpcs:enable

                // Flush the cache to force new db queries
                if (wp_cache_supports('flush_group')) {
                    wp_cache_flush_group('bookings');
                } else {
                    wp_cache_flush();
                }

                // Update post title
                wp_update_post([
                    'ID'            => $postId,
                    'post_title'    => $value
                ]);
            } 
            
            /**
             * Description changed, update the post content of the booking-subject post
             */
            elseif ($key == 'description') {
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
                foreach ($updatedSubjectData[$postId]['rooms'] as $room) {
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

                    add_post_meta($postId, "tsjippy_rooms", [$roomId => $name]);
                    add_post_meta($roomId, "tsjippy_name", $name);
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
            } elseif (is_array($value)) {
                foreach ($value as $k => $v) {
                    add_post_meta($postId, "tsjippy_$key", $v);
                }
            } else {
                update_post_meta($postId, "tsjippy_$key", $value);
            }
        }
    }
}