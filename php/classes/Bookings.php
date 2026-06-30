<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

use function TSJIPPY\addElement as addElement;
use function TSJIPPY\addRawHtml as addRawHtml;

use TSJIPPY\EVENTS;
use TSJIPPY\FORMS;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

class Bookings
{
    public array|false|\WP_Error $bookingElements;
    public array $bookings;
    public object $forms;
    public array $managers;
    public array $payables;
    public string $picturesUrl;
    public bool $showArchived;
    protected array $subjects;
    public bool $tableEditPermissions;
    public string $tableName;
    public array $unavailable;
    public object $user;
    public array $userRoles;

    public function __construct($formInstance = null)
    {
        global $wpdb;

        $this->bookingElements              = false;
        $this->bookings                     = [];
        $this->managers                     = [];
        $this->payables                     = [];
        $this->payables                     = [];
        $this->picturesUrl                    = TSJIPPY\pathToUrl(PLUGINPATH . 'pictures');
        $this->showArchived                 = false;
        $this->tableEditPermissions         = current_user_can('manage_options');
        $this->subjects                     = [];
        $this->tableName                    = $wpdb->prefix . 'tsjippy_bookings';
        $this->unavailable                  = [];
        $this->user                         = wp_get_current_user();
        $this->userRoles                    = array_flip($this->user->roles);

        if (getType($formInstance) == 'object') {
            $this->forms        = $formInstance;
        } else {
            $this->forms        = new TSJIPPY\FORMS\DisplayFormResults([]);
        }

        // Load the managers
        $this->getSubjectManagers();

        wp_enqueue_style('tsjippy_bookings_style', TSJIPPY\pathToUrl(PLUGINPATH . 'css/bookings.min.css'), array(), PLUGINVERSION);
    }

    /**
     * Retrieves all the subjects
     */
    public function getSubjects()
    {
        if (!empty($this->subjects)) {
            return;
        }

        $posts = get_posts([
            'post_type'         => 'booking-subject',
            'posts_per_page'    => -1,
            'post_status'       => 'publish',
            'orderby'           => 'title',
            'order'             => 'ASC',
        ]);

        foreach ($posts as $post) {
            $metas      = get_post_meta($post->ID);

            foreach ($metas as $key => $value) {
                $key    = str_replace('tsjippy_', '', $key);
                $value  = map_deep($value, 'maybe_unserialize');

                // single value not an array
                if (isset(['payments' => 1, 'overlap' => 1, 'overlap-period' => 1, 'default-booking-state' => 1, 'amount' => 1][$key])) {
                    $value  = $value[0];
                }
                $this->subjects[$post->post_title][$key] = $value;
            }
            $this->subjects[$post->post_title]['element-id']   = get_post_meta($post->ID, 'tsjippy_element-id', true);
            $this->subjects[$post->post_title]['post-id']      = $post->ID;
            $this->subjects[$post->post_title]['name']         = $post->post_title;
            $this->subjects[$post->post_title]['description']  = $post->post_content;

            $rooms       = get_children([
                'post_parent'   => $post->ID,
                'post_type'     => 'any',
                'numberposts'   => -1, // Get all children
                'post_status'   => 'publish',
                'orderby'       => 'title',
                'order'         => 'ASC',
            ]);

            // add the name to each room
            $this->subjects[$post->post_title]['rooms'] = [];
            foreach ($rooms as $roomPost) {
                $this->subjects[$post->post_title]['rooms'][] = [
                    'post-id'       => $roomPost->ID,
                    'name'          => get_post_meta($roomPost->ID, 'tsjippy_name', true),
                    'description'   => $roomPost->post_content
                ];
            }

            // add a dummy room if no rooms are found
            if (empty($rooms)) {
                $this->subjects[$post->post_title]['rooms'][] = [
                    'post-id'       => -1,
                    'name'          => ''
                ];
            }
        }
    }

    /**
     * Retrieves the subjects of a specific element from the database
     * @param   int     $elementId      The id of the booking element
     * @param   string  $subjectName    The optional name of a particular accomodation you want to retrieve the details of
     */
    public function getElementSubjects($elementId, $subjectName = '')
    {
        if (empty($this->subjects)) {
            $this->getSubjects();
        }

        $subjects   = [];
        foreach ($this->subjects as $subject) {
            if (($subject['element-id'] ?? '') == $elementId) {
                if (empty($subjectName)) {
                    $subjects[] = $subject;
                } elseif ($subject['name'] == $subjectName) {
                    return $subject;
                }
            }
        }

        return $subjects;
    }

    /**
     * Creates the table holding all bookings if it does not exist
     */
    public function createTables()
    {
        if (!function_exists('maybe_create_table')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->tableName}(
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            start_date date NOT NULL,
            end_date date NOT NULL,
            start_time varchar(80) NOT NULL,
            end_time varchar(80) NOT NULL,
            subject varchar(80) NOT NULL,
            room varchar(80),
            submission_id mediumint(9) NOT NULL,
            event_id mediumint(9),
            pending boolean DEFAULT true,
            paid boolean,
            payable longtext,
            PRIMARY KEY  (id)
       ) $charsetCollate;";

        maybe_create_table($this->tableName, $sql);
    }

    /**
     * @param   string  $date   The timestring of the first month to shown in the view
     *
     * @return  string          Html of the navigator
     */
    public function getNavigator($date)
    {
        $minusMonth     = strtotime("first day of 1 months ago", $date);
        $minusMonthStr  = gmdate('m', $minusMonth);
        $minusYearStr   = gmdate('Y', $minusMonth);

        $firstMonth     = strtotime("first day of next month", $minusMonth);

        $plusMonth      = strtotime("first day of 2 months", $date);
        $plusMonthStr   = gmdate('m', $plusMonth);
        $plusYearStr    = gmdate('Y', $plusMonth);

        ob_start();
        ?>
        <div class="navigator" data-month='<?php echo esc_attr(gmdate('m', $firstMonth)); ?>' data-year='<?php echo esc_attr(gmdate('Y', $firstMonth)); ?>'>
            <div class="prev <?php if (gmdate('ym', $minusMonth) < gmdate('ym')) echo 'hidden'; ?>">
                <a class="prevnext" data-month="<?php echo esc_attr($minusMonthStr); ?>" data-year="<?php echo esc_attr($minusYearStr); ?>">
                    <span>
                        << /span> <?php echo esc_html(gmdate('F', $minusMonth)); ?>
                </a>
            </div>
            <div class="next">
                <a class="prevnext" data-month="<?php echo esc_attr($plusMonthStr); ?>" data-year="<?php echo esc_attr($plusYearStr); ?>">
                    <?php echo esc_html(gmdate('F', $plusMonth)); ?> <span>></span>
                </a>
            </div>
        </div>

    <?php
        return ob_get_clean();
    }

    /**
     * Room description modals
     *
     * @param   array   $subject    The subject for which to show the room descriptions, including the room names and post ids
     *
     * @return  string              The html of the room description modal
     */
    public function roomDescription($subject)
    {
        ob_start();

        $subjectName    = strtolower(str_replace(' ', '_', $subject['name']));

        ?>
        <div name='<?php echo esc_attr($subjectName); ?>-room-modal' class="booking rooms modal hidden" style="display:unset; z-index: 999999999 !important;">
            <div class="modal-content">
                <?php TSJIPPY\addCloseButtton(); ?>

                <h4>Room descriptions</h4>
                <p>Select a room to see its description</p>
                <div class='tablink-wrapper'>
                    <?php
                    // Render tablink buttons
                    foreach ($subject['rooms'] as $index => $room) {
                    ?>
                        <button
                            class='button tablink formbuilder-form 
                        <?php if ($index === 0) echo 'active'; ?>'
                            type='button'
                            id='show-<?php echo esc_attr($subjectName); ?>-room-<?php echo esc_attr($index); ?>'
                            data-target='<?php echo esc_attr($subjectName); ?>-room-<?php echo esc_attr($index); ?>'
                            style='margin-right:4px;'>
                            Room <?php echo esc_html($room['name']); ?>
                        </button>
                    <?php
                    }
                    ?>
                </div>
                <?php

                // Room description
                $i = 0;
                foreach ($subject['rooms'] as $index => $room) {
                    $i++;
                    $name   = $room['name'];
                ?>
                    <div
                        id="<?php echo esc_attr($subjectName); ?>-room-<?php echo esc_attr($name); ?>"
                        class="tabcontent 
                        <?php if ($i > 1) echo 'hidden'; ?> lazy-post"
                        data-post-id='<?php echo esc_attr($room['post-id']); ?>'>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Function to get the room selector html
     *
     * @param   object  $node       The node to append the selector to
     * @param   array   $subject    The name of the subject
     * @param   boolean $isResult   Wheter we are looking at the form or the formresult
     * @param   bool    $radio      true for radio choice, false for checkboxes
     */
    public function roomSelector($node, $subject, $isResult, $radio = false)
    {
        if (empty($subject['amount']) || $subject['amount'] == 1) {
            return;
        }

        if ($radio) {
            $type   = 'radio';
        } else {
            $type   = 'checkbox';
        }

        // phpcs:ignore
        if (!empty($_REQUEST['id']) && $this->forms->submission->id != $_REQUEST['id']) {
            // phpcs:ignore
            $this->forms->submission = (object) $this->forms->getSubmissions('', $_REQUEST['id']);
        }

        $wrapper    = addElement('div', $node, ['class' => 'rooms']);
        $s  = 's';
        if ($radio) {
            $s  = '';
        }

        /**
         * Nodes for the form results vs the form itself
         */
        if ($isResult) {
            $wrapper->textContent = "Select the room$s you want to see the calendar for";
            addElement('br', $wrapper);
        } else {
            $subjectName            = strtolower(str_replace(' ', '_', $subject['name']));
            $wrapper->textContent   = "Select one or more room(s) you want to book";

            addElement(
                'button',
                $node,
                [
                    'class'         => 'button tsjippy small room-details',
                    'type'          => 'button',
                    'data-target'   => "{$subjectName}-room-modal"
                ],
                "Show room details"
            );

            addElement('br', $wrapper);
        }

        /**
         * Add inputs based on the room numbering
         */
        foreach ($subject['rooms'] as $room) {
            $attributes  = [
                'type'  => $type,
                'name'  => 'room',
                'class' => 'room-selector',
                'value' => $room['name'],
                'style' => 'margin: 5px;'
            ];

            if (
                // phpcs:ignore
                !empty($_REQUEST['id']) &&
                in_array($room['name'], $this->forms->submission->{'booking-rooms'} ?? [])
            ) {
                $attributes['checked']    = 'checked';
            }

            addElement('input', $wrapper, $attributes);

            $wrapper->append($room['name']);
        }
    }

    /**
     * Prints the calendar for each room of a subject
     *
     * @param   string  $subject    Subject name
     * @param   int     $date       Date the calendar should start
     */
    private function roomCalendars($subject, $date)
    {
        ob_start();
    ?>
        <div class='rooms-wrapper'>
            <?php
            foreach ($this->subjects[$subject]['rooms'] as $room) {
                $roomHidden = 'hidden';

                if (
                    // phpcs:ignore
                    isset($_REQUEST['id'])           &&              // We should display a specific submission
                    in_array($room['name'], $this->forms->submission->{'booking-rooms'} ?? [])    // and it is this room
                ) {
                    $roomHidden = '';
                }
            ?>
                <div class='room-wrapper <?php echo esc_attr($roomHidden); ?>' data-room='<?php echo esc_attr($room['name']); ?>'>
                    <h4>Room <?php echo esc_html($room['name']); ?></h4>
                    <div class='month-wrapper flex'>
                        <?php
                        $this->monthCalendar($subject, $room['name'], $date, true);
                        $this->monthCalendar($subject, $room['name'], strtotime('first day of next month', $date), true);
                        ?>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    <?php

        return ob_get_clean();
    }

    /**
     * Displays the booking calendars
     * @param   object      $parent     The node to append to
     * @param   array       $subject    The subject of the calendar
     * @param   int         $date       The date to retrieve the calendar for
     * @param   boolean     $isAdmin    Wheter to show for admin purposes
     * @param   boolean     $hidden     Wheter to hide the calendar by default
     *
     * @return  string                  The html
     */
    public function modalContent($parent, $subject, $date, $isAdmin = false, $hidden = false, $isResult = false)
    {
        $monthStr       = gmdate('m', $date);
        $yearStr        = gmdate('Y', $date);
        $cleanSubject   = trim($subject['name']);

        $attributes     = [
            'class'         => "bookings-wrap " . ($hidden ? 'hidden' : ''),
            'data-date'     => "$yearStr-$monthStr",
            'data-subject'  => $cleanSubject,
            'data-form-id'  => $this->forms->formData->id,
        ];

        if (isset($this->forms->currentElement->id)) {
            $attributes["data-element-id"]  = $this->forms->currentElement->id;
        }
        if (isset($this->forms->shortcodeId)) {
            $attributes["data-shortcode-id"] = $this->forms->shortcodeId;
        }

        $wrapper        = addElement('div', $parent, $attributes);

        $overview       = addElement('div', $wrapper, ['class' => "booking overview"]);

        $header         = addElement('div', $overview, ['class' => "header mobile-sticky"]);

        addElement('h4', $header, ['style' => 'text-align:center;'], ucfirst($cleanSubject) . ' Calendar');

        $this->roomSelector($header, $subject, $isResult);

        if (!$isAdmin) {
            $this->showSelectedModalDates($header, $subject['amount'] > 1);
        }

        $navigators = addElement('div', $header, ['class' => "navigators " . ($subject['amount'] > 1 ? 'hidden' : '')]);
        addRawHtml($this->getNavigator($date), $navigators);

        $attributes =  ['class' => "calendar table"];
        if (($subject['amount'] ?? []) > 1) {
            $attributes['style']    = "display:block;";
        }

        $calendarTable  = addElement('div', $overview, $attributes);

        // Show the month calendar if there are no rooms, otherwise show the room calendars
        if (empty($subject['nrtype']) || $subject['nrtype'] == 'none' || $subject['amount'] == 0) {
            $roomWrapper    = addElement('div', $calendarTable, ['class' => "room-wrapper"]); // needed for layout purposes
            $monthWrapper   = addElement('div', $roomWrapper, ['class' => "month-wrapper flex"]);

            addRawHtml($this->monthCalendar($cleanSubject, '', $date), $monthWrapper);
            addRawHtml($this->monthCalendar($cleanSubject, '', strtotime('first day of next month', $date)), $monthWrapper);
        } else {
            addRawHtml($this->roomCalendars($cleanSubject, $date), $calendarTable);
        }

        if (!$isAdmin) {

            $actions         = addElement('div', $overview, ['class' => "actions mobile-sticky bottom"]);

            addElement('button', $actions, ['class' => "button action reset disabled", "type" => 'button'], 'Reset');

            addElement('button', $actions, ['class' => "button action confirm disabled", "type" => 'button'], 'Confirm');
        } else {
            $details         = addElement('div', $wrapper, ['class' => "booking details-wrapper"]);

            addRawHtml($this->detailHtml(), $details);
        }

        // We don't need this on mobile devices
        if (!wp_is_mobile()) {
            $roomDetails         = addElement('div', $wrapper);
            // Room description
            foreach ($subject['rooms'] as $room) {
                if ($room['post-id'] == -1) {
                    continue;
                }

                $roomDescription        = addElement('div', $roomDetails, ['class' => 'hidden room-description', 'data-room-name' => $room['name']]);
                addElement('h4', $roomDescription, [], "Room " . $room['name']);
                addElement('div', $roomDescription, ['class' => 'lazy-post', 'data-post-id' => $room['post-id']]);
            }
        }

        if (empty($parent)) {
            return $wrapper->ownerDocument->saveHtml();
        }
    }

    /**
     * Displays the selected dates
     *
     * @param   \DOMElement  $node   The node to append to
     * @param   boolean $hide   Wheter to hide the selected dates by default (when there are multiple rooms)
     *
     * @return  string          The html
     */
    protected function showSelectedModalDates($node, $hide)
    {
        ob_start();
    ?>
        <div class="booking-date-wrapper 
            <?php if ($hide) echo 'hidden'; ?>">
            <div class="booking-dates-input-wrapper">
                <div class="-h0i9fjw">
                    <div class="booking-date-label-wrapper">
                        <label class="booking-date-label" for="booking-start-date">
                            <div class="booking-date-label-text">Arrival</div>
                            <div dir="ltr">
                                <div class="booking-date-label-input-wrapper">
                                    <input class="booking-date-label-input booking-start-date" placeholder="Select a date" type="text" value="" disabled>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div></div>
                    <div class="booking-date-label-wrapper disabled enddate">
                        <label class="booking-date-label" for="booking-end-date">
                            <div class="booking-date-label-text">
                                Departure
                            </div>
                            <div dir="ltr">
                                <div class="booking-date-label-input-wrapper">
                                    <input class="booking-date-label-input booking-end-date" placeholder="Select a date" type="text" value="" disabled>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="instructions-wrapper mobile-hidden">
                <div>
                    <div class="sewcpu6 dir dir-ltr" style="--spacingBottom:0;">
                        <div class="s1bh1tge dir dir-ltr">
                            <div class="-uxnsba" data-testid="availability-calendar-date-range">Select your arrival date.<br>Then click again to select your departure date.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php

        return addRawHtml(ob_get_clean(), $node);
    }

    /**
     *
     * Displays a date selector modal
     *
     * @param   object  $node       The node to append the modal to
     * @param   array   $subject    array with The name of the building/event and the amount of rooms
     */
    public function dateSelectorModal($day, $month, $year, $node, $subject)
    {
        $dateStr      = "$year-$month-$day";

        $date         = strtotime($dateStr);

        $cleanSubject = trim($subject['name']);

        /**
         * Create the modal
         */
        $modal = addElement(
            'div',
            $node,
            [
                'name'  => "{$cleanSubject}-modal",
                'class' => "booking modal hidden",
                'style' => "display:unset;"
            ]
        );

        $modalContent = addElement(
            'div',
            $modal,
            [
                'class' => "modal-content"
            ]
        );

        TSJIPPY\addCloseButtton($modalContent);

        // Append the modal content HTML
        $this->modalContent($modalContent, $subject, $date);
    }

    /**
     * Get the month calendar
     *
     * @param    string        $subject        The subject name
     * @param    string        $room            The subject room
     * @param   int         $date           The time
     * @param   boolean     $echo           Wheter to echo the result or return it
     *
     * @return    string                        Html of the calendar void if echo is true
     */
    public function monthCalendar($subject, $room, $date, $echo = false)
    {

        if (is_array($subject)) {
            $subject    = $subject['name'];
        }

        $month          = gmdate('m', $date);
        $year           = gmdate('Y', $date);
        $weekDay        = gmdate("w", strtotime(gmdate('Y-m-01', $date)));
        $workingDate    = strtotime("-$weekDay day", strtotime(gmdate('Y-m-01', $date)));

        // subject without optional room name
        $overlap        = false;
        $gapDays        = 0;

        foreach ($this->subjects as $s) {
            // check if overlap is enabled
            if ($s['name'] == $subject && !empty($s['overlap'])) {
                if ($s['overlap'] == 'yes') {
                    $overlap    = true;
                } elseif (!empty($s['overlap-period']) && is_numeric($s['overlap-period'])) {
                    $gapDays    = $s['overlap-period'];
                }

                break;
            }
        }

        //get the bookings for this month
        $this->retrieveMonthBookings($month, $year, $subject, $room, $gapDays);

        if (!$echo) {
            ob_start();
        }

    ?>
        <div class="month-container" data-month='<?php echo esc_attr(gmdate('m', $date)); ?>' data-year='<?php echo esc_attr(gmdate('Y', $date)); ?>'>
            <div class="current">
                <?php echo esc_html(gmdate('F Y', $date)); ?>
            </div>
            <dl>
                <?php
                for ($y = 0; $y <= 6; $y++) {
                    $name    = gmdate('D', $workingDate);
                ?>
                    <dt class='calendar day head'>
                        <?php echo esc_html($name); ?>
                    </dt>
                <?php
                    $workingDate    = strtotime("+1 days", $workingDate);
                }
                ?>
            </dl>
            <?php
            $this->writeCalendarRows($date, $overlap);
            ?>
        </div>

        <?php
        if (!$echo) {
            return ob_get_clean();
        }
    }

    /**
     * Writes calendar rows to screen
     *
     * @param   int     $date      The date to write the calendar for
     * @param   bool    $overlap   Whether to enable overlap for this calendar or not
     *
     * @return  void
     */
    public function writeCalendarRows($date, $overlap)
    {

        $month          = gmdate('m', $date);
        $weekDay        = gmdate("w", strtotime(gmdate('Y-m-01', $date)));
        $workingDate    = strtotime("-$weekDay day", strtotime(gmdate('Y-m-01', $date)));
        $curDate        = time();

        //loop over all weeks of a month
        while (true) {
            $hidden         = '';
            if ($month != gmdate('m', $date)) {
                $hidden = 'hidden';
            }

        ?>
            <dl class='calendar row <?php echo esc_attr($hidden); ?>' data-month='<?php echo esc_attr($month); ?>'>
                <?php
                //loop over all days of a week
                while (true) {
                    $workingDateStr        = gmdate('Y-m-d', $workingDate);
                    $workingMonth        = gmdate('m', $workingDate);
                    $workingDay            = gmdate('j', $workingDate);

                    $class              = '';

                    if ($workingMonth != $month) {
                ?>
                        <dt class='empty'></dt>
                    <?php
                    } else {
                        $data   = '';
                        // date is in the past, make it unavailable
                        if (gmdate('Ymd', $workingDate) < gmdate('Ymd', $curDate)) {
                            $class    = 'unavailable';
                            // not booked
                        } elseif (!isset($this->unavailable[$workingDateStr])) {
                            $class    = 'available';
                        }

                        // booked
                        if (isset($this->unavailable[$workingDateStr])) {
                            $bookingId  = $this->unavailable[$workingDateStr];

                            $dayBefore  = gmdate('Y-m-d', strtotime('-1 day', $workingDate));
                            $dayAfter   = gmdate('Y-m-d', strtotime('+1 day', $workingDate));
                            if (
                                $class    != 'unavailable' &&                                                                 // not in the past
                                $overlap &&                                                                                 // overlap enabled
                                get_class($this->forms) != 'TSJIPPY\FORMS\DisplayFormResults'   &&                          // we are not in the overview page
                                (
                                    !isset($this->unavailable[$dayBefore])    ||    // this is the first day of a booking
                                    !isset($this->unavailable[$dayAfter])           // or the last day of a booking
                                )
                            ) {
                                // First and last day of a reservation are available if overlap is enabled
                                $class    .= ' available';

                                // The day before this booking is available
                                if (!isset($this->unavailable[$dayBefore])) {
                                    $class    .= ' first';

                                    $data   .= "title='You can only book this as the last day of your stay'";
                                }

                                if (!isset($this->unavailable[$dayAfter])) {
                                    $class    .= ' last';
                                    $data   .= "title='You can only book this as the first day of your stay'";
                                }
                            } else {
                                $class    .= ' booked';
                            }

                            $data   .= "data-booking-id='$bookingId'";

                            if (method_exists($this->forms, 'getSubmissions')) {
                                // check if this is our own booking
                                foreach ($this->bookings as $booking) {
                                    if ($booking->id == $bookingId) {
                                        $submissionId   = $booking->submission_id;

                                        $submission     = $this->forms->getSubmissions(null, $submissionId)[0];

                                        $userId         = $submission->user_id;

                                        if ($userId == $this->forms->user->ID) {
                                            $class    .= ' own';
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                    ?>
                        <dt
                            class='calendar day <?php echo esc_attr($class); ?>'
                            data-date='<?php echo esc_attr(gmdate(TSJIPPY\DATEFORMAT, $workingDate)); ?>'
                            data-isodate='<?php echo esc_attr(gmdate('Y-m-d', $workingDate)); ?>'
                            <?php echo wp_kses_post($data); ?>>
                            <span class='day-nr'>
                                <?php echo esc_html($workingDay); ?>
                            </span>
                        </dt>
                <?php
                    }

                    //calculate the next week
                    $workingDate    = strtotime('+1 day', $workingDate);
                    //if the next day is the first day of a new week
                    if (gmdate('w', $workingDate) == 0) {
                        break;
                    }
                }
                ?>
            </dl>
        <?php

            // Break if next month
            if (gmdate('Ym', $workingDate) > gmdate('Ym', $date)) {
                break;
            }
        }
    }

    /**
     * Creates the html for a booking's submission data
     *
     * @param   object  $booking    The booking to display data for
     * @param   object  $submission The submissiondata of this booking
     * @param   bool    $hide       Whether to hide the details or not, default true
     * @param   bool    $echo       Whether to echo the result or return it, default false (echo)
     *
     * @return  string              HTML
     */
    public function submissionDetails($booking, $submission, $hide, $echo = false)
    {
        if (!empty($booking->room)) {
            $subId  = "data-subid='$booking->room'";
        } else {
            $subId  = '';
        }

        $hidden         = '';
        if (
            $hide   &&
            (
                // phpcs:ignore
                empty($_REQUEST['id']) ||
                // phpcs:ignore
                $_REQUEST['id'] != $this->forms->submission->id
            )
        ) {
            $hidden         = 'hidden';
        }

        if (!$echo) {
            ob_start();
        }

        ?>
        <div class='booking-detail-wrapper <?php echo esc_attr($hidden); ?>' data-booking-id='<?php echo esc_attr($booking->id); ?>'>
            <h6 class='booking-title'>
                Booking details
            </h6>

            <article class='booking'>
                <h4 class='booking-title'><?php echo esc_html($booking->subject ?? ''); ?></h4>
                <div class='booking-detail'>
                    <table data-form-id='<?php echo esc_attr($submission->form_id); ?>' style='width: unset;'>
                        <thead></thead>
                        <tbody>
                            <tr class='<?php echo esc_attr($this->bookingElements[0]->slug); ?>' data-submission-id='<?php echo esc_attr($submission->id); ?>'>
                                <td>
                                    <img src='<?php echo esc_url($this->picturesUrl); ?>/subject.png' loading='lazy' alt='<?php echo esc_attr($this->bookingElements[0]->name); ?>' class='booking-icon' title='<?php echo esc_attr($this->bookingElements[0]->name); ?>'>
                                </td>
                                <td class='booking-data-wrapper edit forms-table' data-element-id='<?php echo esc_attr($this->bookingElements[0]->id); ?>' data-name='<?php echo esc_attr($this->bookingElements[0]->slug); ?>' data-booking-id='<?php echo esc_attr($booking->id); ?>'>
                                    <?php echo esc_html($this->bookingElements[0]->name) ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <img src='<?php echo esc_url($this->picturesUrl); ?>/date.png' loading='lazy' alt='date' class='booking-icon'>
                                </td>
                                <td class='booking-data-wrapper edit forms-table'>
                                    <table data-form-id='<?php echo esc_attr($submission->form_id); ?>' data-shortcode-id='<?php echo esc_attr($this->forms->shortcodeId); ?>' style='margin-bottom: 0px; width:unset;'>
                                        <tr data-submission-id='<?php echo esc_attr($submission->id); ?>'>
                                            <td data-name='booking-start-date' data-element-id='<?php echo esc_attr($this->forms->getElementBySlug('booking-start-date')->id); ?>' <?php echo esc_attr($subId); ?> data-booking-id='<?php echo esc_attr($booking->id); ?>' class='edit forms-table'>
                                                <?php echo esc_html(gmdate(TSJIPPY\DATEFORMAT, strtotime($booking->start_date))); ?>
                                            </td>
                                        </tr>
                                        <tr data-submission-id='<?php echo esc_attr($submission->id); ?>'>
                                            <td data-name='booking-end-date' data-element-id='<?php echo esc_attr($this->forms->getElementBySlug('booking-end-date')->id); ?>' <?php echo esc_attr($subId); ?> data-booking-id='<?php echo esc_attr($booking->id); ?>' class='edit forms-table'>
                                                <?php echo esc_html(gmdate(TSJIPPY\DATEFORMAT, strtotime($booking->end_date))); ?>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <?php
                            if (!empty($booking->room)) {
                            ?>
                                <tr class='room' data-submission-id='<?php echo esc_attr($submission->id); ?>'>
                                    <td>
                                        <img src='<?php echo esc_url($this->picturesUrl); ?>/room.png' loading='lazy' alt='Room' class='booking-icon' title='Room'>
                                    </td>
                                    <td class='booking-data-wrapper edit forms-table' data-element-id='-104' <?php echo esc_attr($subId); ?> data-name='booking-rooms' data-booking-id='<?php echo esc_attr($booking->id); ?>'>
                                        <?php echo esc_attr($booking->room); ?>
                                    </td>
                                </tr>
                            <?php
                            }

                            foreach ($this->forms->columnSettings as $key => $setting) {
                                if (
                                    !$setting['show']     ||
                                    !is_numeric($key)   ||
                                    array_key_exists(
                                        $setting['slug'],
                                        [
                                            'form-id'                       => 1,
                                            'formurl'                       => 1,
                                            '_wpnonce'                      => 1,
                                            'id'                            => 1,
                                            'submissiontime'                => 1,
                                            'edittime'                      => 1,
                                            'timecreated'                   => 1,
                                            'timelastedited'                => 1,
                                            'time_created'                  => 1,
                                            'time_last_edited'              => 1,
                                            'startdate'                     => 1,
                                            'booking-startdate'             => 1,
                                            'booking-start-date'            => 1,
                                            'endate'                        => 1,
                                            'booking-enddate'               => 1,
                                            'booking-end-date'              => 1,
                                            'booking-room'                  => 1,
                                            'booking-rooms'                 => 1,
                                            'name'                          => 1,
                                            $this->bookingElements[0]->slug => 1
                                        ]
                                    )
                                ) {
                                    continue;
                                }

                                $slug       = $setting['slug'];
                                $name       = empty($setting['name']) ? $slug : $setting['name'];
                                $element    = $this->forms->getElementBySlug($slug);
                                $data       = $submission->{$element->id};

                                $transformedData   = $this->forms->transformInputData($data, $element, $submission);
                                if (empty($transformedData)) {
                                    $transformedData    = 'X';
                                }

                            ?>
                                <tr class='<?php echo esc_attr($slug); ?>' data-submission-id='<?php echo esc_attr($submission->id); ?>'>
                                    <?php
                                    if (file_exists(TSJIPPY\urlToPath("$this->picturesUrl/$slug.png"))) {
                                    ?>
                                        <td>
                                            <img src='<?php echo esc_url("$this->picturesUrl/$slug.png"); ?>' loading='lazy' alt='<?php echo esc_attr($name); ?>' class='booking-icon' title='<?php echo esc_attr($name); ?>'>
                                        </td>
                                    <?php
                                    } else {
                                    ?>
                                        <td>
                                            <?php echo esc_html($name); ?>:
                                        </td>
                                    <?php
                                    }
                                    ?>
                                    <td class='booking-data-wrapper edit forms-table' data-element-id='<?php echo esc_attr($element->id); ?>' data-name='<?php echo esc_attr($slug); ?>' data-booking-id='<?php echo esc_attr($booking->id); ?>'>
                                        <?php echo wp_kses_post($transformedData); ?>
                                    </td>
                                </tr>
                                <?php
                            }

                            //if there are actions
                            if (!empty($this->forms->formData->actions)) {
                                //loop over all the actions
                                $attributes    = [];
                                foreach ($this->forms->formData->actions as $action) {
                                    $editRoles  = $this->forms->columnSettings[$action]['edit_right_roles'] ?? [];

                                    // Use the table settings if no specific rights are set
                                    if (empty($editRoles)) {
                                        $editRoles  = $this->forms->tableSettings->edit_right_roles ?? [];
                                    }

                                    if (
                                        !$this->tableEditPermissions &&                       //if we are not allowed to do all actions
                                        $submission->user_id != $this->user->ID ||            //this is not our own entry
                                        !array_intersect_key($this->userRoles, $editRoles)        // we don't have permission for this specific button
                                    ) {
                                        continue;
                                    }

                                    if ($action == 'archive' && $this->showArchived && $this->forms->submissions->archived) {
                                        $action = 'unarchive';
                                    }
                                    $attributes[$action]    = [
                                        'class' => "$action button forms-table-action",
                                        'name'  => "{$action}-action",
                                        'value' => $action,
                                        'text'  => ucfirst($action),
                                        'type'  => 'button'
                                    ];
                                }

                                /**
                                 * Filters the avaiable buttons and their attributes
                                 * 
                                 * @param   array   $attributes Array of arrays with attributes
                                 * @param   object  $submission The current submission
                                 * @param   object  $object     The current DisplayFormResults object
                                 */
                                $attributes = apply_filters('tsjippy-forms-results-row-actions', $attributes, $submission, $this);

                                if (!empty($attributes)) {
                                ?>
                                    <tr class='actions' data-submission-id='<?php echo esc_attr($submission->id); ?>'>
                                        <td colspan='2'>
                                            <?php
                                            //we have the buttons now, check for which one we have permission
                                            foreach ($attributes as $action => $buttonAttributes) {
                                                $text   = $buttonAttributes['text'] ?? '';

                                                unset($buttonAttributes['text']);
                                            ?>
                                                <button
                                                    <?php
                                                    foreach ($buttonAttributes as $key => $value) {
                                                        echo esc_attr($key) . "='" . esc_attr($value) . "'";
                                                    }
                                                    ?>>
                                                    <?php echo esc_html($text); ?>
                                                </button>
                                            <?php
                                            }
                                            ?>
                                        </td>
                                    </tr>
                            <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
        <?php

        if (!$echo) {
            return ob_get_clean();
        }
    }

    /**
     * Build the detail html for the current month
     *
     * @param   bool    $hide   Whether to hide the details or not, default true
     */
    public function detailHtml($hide = true)
    {
        if (!method_exists($this->forms, 'parseSubmissions')) {
            return '';
        }

        $this->getBookingElements();

        if ($this->forms->columnSettings == null || empty($this->forms->tableSettings)) {
            if (method_exists($this->forms, 'loadShortcodeData')) {
                $result = $this->forms->loadShortcodeData();
                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }

        ob_start();

        $processed  = [];
        foreach ($this->bookings as $booking) {
            // do not process the same submission more than once
            if (isset($processed[$booking->submission_id])) {
                continue;
            }
            $processed[$booking->submission_id] = 1;

            // Retrieve booking details
            $this->forms->parseSubmissions(null, $booking->submission_id);

            $subject        = $this->forms->submission->{$this->bookingElements[0]->id};

            if (
                // we are not the manager of this subject
                !isset($this->managers[$subject][$this->user->ID]) &&

                // we do not have permissions
                !array_intersect($this->forms->userRoles, array_keys($this->forms->tableSettings->view_right_roles))  &&      // we do not have the right to see others submissions

                // This is not our own booking
                $this->forms->submission->user_id != $this->forms->user->ID
            ) {
                // no right to see this
        ?>
                <div class='booking-detail-wrapper warning hidden' data-booking-id='<?php echo esc_attr($booking->id); ?>'>
                    No permission to see this booking
                </div>
        <?php
                continue;
            }

            foreach ($this->forms->submissions as $submission) {
                $this->submissionDetails($booking, $submission, $hide, true);
            }
        }

        return ob_get_clean();
    }

    /**
     * Retrieve the subject data
     * @param   bool    $force      Do not send cached data, default false
     */
    public function getBookingElements($force = false)
    {
        if (!empty($this->bookingElements) && !$force) {
            return $this->bookingElements;
        }

        $this->bookingElements   = $this->forms->getElementByType('booking-selector');

        if (!$this->bookingElements || is_wp_error($this->bookingElements)) {
            $this->bookingElements  = [];
            return;
        }

        foreach ($this->bookingElements as &$element) {
            $this->getElementSubjects($element->id);
        }

        return $this->bookingElements;
    }

    /**
     * Check if a booking overlaps another booking
     *
     * @param   int     $startDate      The start_date epoch of a booking
     * @param   int     $endDate        The end_date epoch of a booking
     * @param   string  $subject        The subject  of a booking
     * @param   string  $room           The room of a booking
     * @param   int     $id             An booking id to ignore to exclude the booking itself
     *
     * @return  array                   An array with overlapping bookings
     */
    public function checkOverlap($startDate, $endDate, $subject, $room, $id = -1)
    {
        // First check if a booking on these dates doesn't exist
        $query        = "SELECT * FROM %i WHERE pending=0 AND subject = %s AND room = %s AND (%s BETWEEN start_date and end_date OR %s BETWEEN start_date and end_date)";
        $values     = [
            $this->tableName,
            $subject,
            $room,
            $startDate,
            $endDate
        ];

        $cacheKey   = "get_bookings_for_{$subject}_room_{$room}_startdate_{$startDate}_enddate_{$endDate}";

        if ($id != -1) {
            $query      .= " AND NOT id=%d";
            $values[]    = $id;

            $cacheKey   .= "_excluding{$id}";
        }

        //sort on start_date
        $query                .= " ORDER BY `start_date`, `start_time` ASC";

        // phpcs:ignore
        $bookings           = TSJIPPY\getFromDb(
            $cacheKey,
            "bookings",
            $query,
            $values
        );

        $overlap            = false;

        $bookingEls         = $this->getBookingElements();

        if (is_wp_error($bookingEls)) {
            return $bookingEls;
        }

        foreach ($this->subjects as $detail) {
            if (
                $detail['name'] == $subject &&
                !empty($detail['overlap']) &&
                $detail['overlap'] == 'yes'
            ) {
                $overlap    = true;
            }
        }

        // start and end_date may overlap so remove any of those
        if ($overlap) {
            foreach ($bookings as $index => $booking) {
                // this booking ends on the first day of the booking we are checking
                if ($booking->end_date == $startDate) {
                    unset($bookings[$index]);
                }

                // this booking starts on the last day of the booking we are checking
                if ($booking->start_date == $endDate) {
                    unset($bookings[$index]);
                }
            }
        }

        return $bookings;
    }

    /**
     * Checks wheter a booking to be inserted should be a pending booking
     *
     * @param   int|\WP_User     $user      the user or userId of the person for who the booking is done
     * @param   string          $subject    the subject of the booking
     *
     * @return  bool                        true if is should be pending, false otherwise
     */
    public function checkPending($user, $subject)
    {
        $els = $this->getBookingElements();
        if (!$els) {
            return true;
        }

        foreach ($this->subjects as $subjectSettings) {
            if (!str_contains($subject, $subjectSettings['name'])) {
                continue;
            }

            if (($subjectSettings['default_booking_state'] ?? '') == 'pending') {

                // user the boooking is for
                if (is_numeric($user)) {
                    $user       = get_userdata($user);
                }

                // user who submitted the form
                $submittingUser = get_userdata($this->forms->submission->user - id);

                if ( isset($subjectSettings['manager'][$user->ID]) || isset($subjectSettings['manager'][$submittingUser])) {
                    return    true;
                }
            }

            break;
        }

        return false;
    }

    /**
     * Insert a new booking
     *
     * @param   string      $startDate      The start_date string
     * @param   string      $endDate        The end_date string
     * @param   string      $subject        The subject the booking is for
     * @param   string      $room           The room the booking is for
     * @param   int         $submissionId   The form submission id
     */
    public function insertBooking($startDate, $endDate, $subject, $room, $submissionId)
    {
        $overlappingBookings    = $this->checkOverlap($startDate, $endDate, $subject, $room);
        if (!empty($overlappingBookings) && $overlappingBookings[0]->submission_id != $submissionId) {
            if (!empty($room)) {
                $subject    .= " room $room";
            }

            $startDateString    = gmdate(TSJIPPY\DATEFORMAT, strtotime($overlappingBookings[0]->start_date));
            $endDateString      = gmdate(TSJIPPY\DATEFORMAT, strtotime($overlappingBookings[0]->end_date));
            return new \WP_Error('booking', "The booking for $subject overlaps with an existing one from $startDateString till $endDateString, try again");
        }

        $userId             = $this->forms->submission->user_id;

        $subjectWithRoom    = $subject;
        if (!empty($room)) {
            $subjectWithRoom    = "$subject room $room";
        }

        $eventId    = -1;

        // create a personal event
        if (!empty($userId)) {
            $post = array(
                'post_type'     => 'event',
                'post_title'    => "Booking for $subjectWithRoom",
                'post_content'  => "Booking for $subjectWithRoom",
                'post_status'   => 'publish',
                'post_author'   => $userId
            );

            $eventId     = wp_insert_post($post, true, false);

            $event                 = [];
            $event['start_date']   = $startDate;
            $event['start_time']   = '14:00';
            $event['end_date']     = $endDate;
            $event['end_time']     = '12:00';
            $event['location']     = $subjectWithRoom;
            $event['organizer-id'] = $userId;
            $event['only_for']     = $userId;
            update_post_meta($eventId, 'tsjippy_eventdetails', json_encode($event));
            update_post_meta($eventId, 'tsjippy_only_for', $userId);
        }

        // Determine the pending state
        $pending    = $this->checkPending($userId, $subject);

        // Insert booking in db
        $result = TSJIPPY\insertInDb(
            $this->tableName,
            array(
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'subject'       => $subject,
                'room'          => $room,
                'submission_id' => $submissionId,
                'event_id'      => $eventId,
                'pending'       => $pending
            ),
            [],
            'bookings'
        );

        return $result;
    }

    /**
     * Validate a date change
     *
     * @param   object          $booking    The booking to validate
     * @param   array           $values     Reference to the values array
     *
     * @return  WP_error|bool               Error object if overlapping with another booking, true if ok.
     */
    protected function validateDates($booking, &$values)
    {
        if (!isset($values['start_date']) && !isset($values['end_date'])) {
            return true;
        }

        $startDate      = $booking->start_date;

        // Start date is updated
        if (isset($values['start_date'])) {
            $startDate  = &$values['start_date'];

            // get the relevant date
            if (is_array($startDate)) {
                // phpcs:ignore
                if (!empty($_POST['subid']) && isset($startDate[$_POST['subid']])) {
                    // phpcs:ignore
                    $startDate  = $startDate[TSJIPPY\sanitize($_POST['subid'] ?? '')];
                } else {
                    $startDate  = array_values($startDate)[0];
                }
            }
        }

        $endDate      = $booking->end_date;

        // End date is updated
        if (isset($values['end_date'])) {
            $endDate  = &$values['end_date'];

            // get the relevant date
            if (is_array($endDate)) {
                // phpcs:ignore
                if (!empty($_POST['subid']) && isset($endDate[$_POST['subid']])) {
                    // phpcs:ignore
                    $endDate  = $endDate[TSJIPPY\sanitize($_POST['subid'] ?? '')];
                } else {
                    $endDate  = array_values($endDate)[0];
                }
            }
        }

        $subject      = $booking->subject;
        if (isset($values['subject'])) {
            $subject  = $values['subject'];
        }

        $room      = $booking->room;
        if (isset($values['room'])) {
            $room  = $values['room'];
        }

        $overlappingBookings    = $this->checkOverlap($startDate, $endDate, $subject, $room, $booking->id);
        if (!empty($overlappingBookings)) {
            if (!empty($room)) {
                $subject    .= " room $room";
            }

            $startDateString    = gmdate(TSJIPPY\DATEFORMAT, strtotime($overlappingBookings[0]->start_date));
            $endDateString      = gmdate(TSJIPPY\DATEFORMAT, strtotime($overlappingBookings[0]->end_date));
            return new \WP_Error('booking', "The booking for $subject overlaps with an existing one from $startDateString till $endDateString, try again");
        }

        return true;
    }

    /**
     * Update an existing booking
     *
     * @param   int|object  $booking    The booking or booking id
     * @param   array       $values     The values to update in an a named array
     * @param   bool        $skipHtml   Whether to return new details html, default false
     */
    public function updateBooking($booking, $values, $skipHtml = false)
    {
        // Get the booking
        if (is_numeric($booking)) {
            $booking        = $this->getBookingById($booking);

            if (!$booking) {
                return new WP_Error('Invalid Booking id', 'Invalid Booking Id submitted');
            }
        }

        // only keep valid values
        $values         = array_filter($values, function ($val) {
            return isset(['start_date' => 1, 'end_date' => 1, 'start_time' => 1, 'end_time' => 1, 'subject' => 1, 'room' => 1, 'pending' => 1, 'paid' => 1][$val]);
        }, ARRAY_FILTER_USE_KEY);

        // Validate updated dates and adjusts the values array to only the relevant date for this booking
        $result         = $this->validateDates($booking, $values);

        // return the error if needed
        if ($result !== true) {
            return $result;
        }

        // update the booking
        $result = TSJIPPY\updateDbValue(
            $this->tableName,
            $values,
            array(
                'id'        => $booking->id
            ),
            [],
            ['%d'],
            'bookings'
        );

        if (!is_wp_error($result)) {
            $message            = 'Succesfully updated the booking';
        } else {
            $message            = $result;
        }

        // update event
        $event                  = json_decode(get_post_meta($booking->event_id, 'tsjippy_eventdetails', true), true);
        if (!empty($event)) {
            update_post_meta($booking->event_id, 'tsjippy_eventdetails', json_encode(array_merge($event, $values)));
        }

        if ($skipHtml) {
            return $message;
        }

        // Build the return array
        $monthsHtml     = [];
        $months         = [];
        $years          = [];
        $details        = '';

        // Get all the months
        $start    = (new \DateTime($booking->start_date))->modify('first day of this month');
        $end      = (new \DateTime($booking->end_date))->modify('first day of next month');
        $interval = \DateInterval::createFromDateString('1 month');
        $period   = new \DatePeriod($start, $interval, $end);

        foreach ($period as $dt) {
            $monthsHtml[]   = $this->monthCalendar($booking->subject, $booking->room, $dt->format("U"));
            $months[]       = $dt->format("m");
            $years[]        = $dt->format("Y");

            $details        = $this->detailHtml();
            if (is_wp_error($details)) {
                $details    = $details->get_error_message();
            }
        }

        return [
            'months'        => $months,
            'years'         => $years,
            'subject'       => $booking->subject,
            'html'          => $monthsHtml,
            'details'       => $details,
            'message'       => $message
        ];
    }

    /**
     * Updates, adds and/or removes the rooms of an existing submission
     *
     * @param   array   $newRooms           An array of the new rooms names
     * @param   array   $currentBookings    An array of the bookings to be updated
     */
    public function updateRooms($newRooms, $currentBookings)
    {
        $oldRooms   = [];
        foreach ($currentBookings as $booking) {
            $oldRooms[] = $booking->room;
        }

        $deleted    = array_flip(array_diff((array)$oldRooms, (array)$newRooms));
        $added      = array_diff((array)$newRooms, (array)$oldRooms);

        // we changed a room
        if (count($oldRooms) == count($newRooms)) {
            $deleted    = [];
            $added      = [];

            foreach ($oldRooms as $i => $oldRoom) {
                $newRoom    = $newRooms[$i];

                // Find the booking for this room and update it
                foreach ($currentBookings as $booking) {
                    if ($oldRoom == $booking->room) {
                        $result     = $this->updateBooking($booking, ['room' => $newRoom]);
                        break;
                    }
                }
            }
        }

        // add new ones
        if (!empty($added)) {
            $booking    = $currentBookings[0];
            foreach ($added as $room) {
                //Insert the new booking
                $result = $this->insertBooking($booking->start_date, $booking->end_date, $booking->subject, $room, $this->forms->submission->id);

                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }

        // remove any removed bookings
        if (!empty($deleted)) {
            foreach ($currentBookings as $booking) {
                $room   = $booking->room;

                // if this is the booking for the room
                if (isset($deleted[$room])) {
                    // Delete the booking
                    $this->removeBooking($booking);
                }
            }
        }

        return true;
    }

    /**
     * Update an existing booking
     *
     * @param   int|object     $booking  The booking or booking id
     */
    public function removeBooking($booking)
    {
        global $wpdb;

        // Get the booking
        if (is_numeric($booking)) {
            $booking        = $this->getBookingById($booking);
        }

        // Remove the event
        $events = new TSJIPPY\EVENTS\CreateEvents();
        $events->removeDbRows($booking->event_id, true);

        // Remove the booking
        TSJIPPY\removeFromDb(
            $this->tableName,
            ['id' => $booking->id],
            ['%d'],
            'bookings'
        );
    }

    /**
     * Remove a subject
     * @param   array  $subjectData    The subject data of the subject to remove
     */
    public function removeSubject($subjectData)
    {
        global $wpdb;

        // Delete potential existing bookings;
        $results    = TSJIPPY\getFromDb(
            "get_bookings_for_" . ($subjectData['name'] ?? ''),
            "bookings",
            "select * FROM %i WHERE `subject` LIKE %s",
            $this->tableName,
            $wpdb->esc_like($subjectData['name'] ?? '') . '%'
        );

        foreach ($results as $booking) {
            $this->removeBooking($booking);
        }

        // Delete potential existing rooms
        if (is_numeric($subjectData['post-id'])) {
            $postId = intval($subjectData['post-id']);
            $rooms       = get_children([
                'post_parent'   => $postId,
                'post_type'     => 'any',
                'numberposts'   => -1, // Get all children
                'post_status'   => 'publish',
                'orderby'       => 'title',
                'order'         => 'ASC',
            ]);

            foreach ($rooms as $room) {
                wp_delete_post($room->ID, true);
            }
        }

        // Delete the subject
        wp_delete_post($subjectData['post-id'], true);
    }

    /**
     * Stores a new subject
     * @param   array  $subjectData    The subject data of the subject to add
     */
    public function addSubject($subjectData)
    {
        $subjectName    = ucfirst($subjectData['name']);

        // insert a post for subject description
        $postId  = wp_insert_post([
            'post_title'    => $subjectName,
            'post_type'     => 'booking-subject',
            'post_status'   => 'publish',
            'post_content'  => isset($subjectData['description']) ? $subjectData['description'] : ''
        ]);

        if (isset($subjectData['rooms']) && is_array($subjectData['rooms'])) {
            foreach ($subjectData['rooms'] as $room) {
                $name          = ucfirst($room['name']);
                $description   = isset($room['description']) ? $room['description'] : '';

                $roomId = wp_insert_post([
                    'post_title'    => "$subjectName Room $name",
                    'post_type'     => 'booking-room',
                    'post_status'   => 'publish',
                    'post_content'  => $description,
                    'post_parent'   => $postId
                ]);

                add_post_meta($postId, "tsjippy_room", [$roomId => $name]);
                add_post_meta($roomId, "tsjippy_name", $name);
            }
        }

        unset($subjectData['description']);
        unset($subjectData['name']);
        unset($subjectData['rooms']);

        foreach ($subjectData as $key => $value) {
            update_post_meta($postId, "tsjippy_$key", $value);
        }
    }

    /**
     * Retrieve the bookings for a certain month
     *
     * @param   int     $month          The month to retrieve bookings for
     * @param   int     $year           The year to retrieve bookings for
     * @param   string  $subject        The subject to retrieve bookings for
     * @param   string  $room           The room to retrieve bookings for
     * @param   int     $extraDays      Extra days to block after each booking, default 0
     *
     * @return  void                    The bookings are stored in the $this->bookings property and the unavailable dates in the $this->unavailable property
     */
    protected function retrieveMonthBookings($month, $year, $subject, $room, $extraDays = 0)
    {
        global $wpdb;

        if (is_array($room)) {
            $room   = $room['name'];
        }

        //select all bookings of this month
        $startDate  = "$year-$month-01";
        $endDate    = gmdate("Y-m-t", strtotime($startDate));

        $result     = TSJIPPY\getFromDb(
            "get_bookings_for_{$subject}_room_{$room}_between_{$startDate}_and_$endDate",
            "bookings",
            "SELECT * FROM %i WHERE (`start_date` >= %s OR %s BETWEEN start_date and end_date) AND `start_date` <= %s AND subject = %s AND room = %s ORDER BY `start_date`, `start_time` ASC",
            $this->tableName,
            $startDate,
            $startDate,
            $endDate,
            $subject,
            $room
        );

        $this->bookings     =  array_merge($this->bookings, $result);

        $this->unavailable  = [];

        foreach ($result as $booking) {

            $current    = strtotime($booking->start_date);
            $last       = strtotime($booking->end_date);

            if ($extraDays > 0) {
                $last   = strtotime("+$extraDays days", $last);
            }

            while ($current <= $last) {
                $this->unavailable[gmdate('Y-m-d', $current)] = $booking->id;
                $current                = strtotime('+1 day', $current);
            }
        }
    }

    /**
     * Retrieve all the bookings of a certain end_date
     *
     *  @param  string|int  $date   The date in 'Y-m-d' format or unix timestamp
     */
    public function retrieveBookingsByEndDate($date)
    {
        if (is_numeric($date)) {
            $date   = gmdate('Y-m-d', $date);
        }

        return TSJIPPY\getFromDb(
            "get_booking_by_end_date_$date",
            "bookings",
            "SELECT * FROM %i WHERE end_date = %s",
            $this->tableName,
            $date
        );
    }

    /**
     * Retrieve all the bookings of a certain start_date
     *
     *  @param  string|int  $date   The date in 'Y-m-d' format or unix timestamp
     */
    public function retrieveBookingsByStartDate($date)
    {
        if (is_numeric($date)) {
            $date   = gmdate('Y-m-d', $date);
        }

        return TSJIPPY\getFromDb(
            "get_booking_by_start_date_$date",
            "bookings",
            "SELECT * FROM %i WHERE start_date = %s",
            $this->tableName,
            $date
        );
    }

    /**
     * Gets the bookings for a specific user after a specifice date
     * 
     * @param int           $userId The userid to get bookings for
     * @param int|string    $date   The date as a string (yyyy-mm-dd) or epoch
     * 
     * @return array                An array with all bookings
     */
    public function getUserBookingsByStartDate($userId, $date)
    {
        if (is_numeric($date)) {
            $date   = gmdate('Y-m-d', $date);
        }

        return TSJIPPY\getFromDb(
            "get_booking_for_user_{$userId}_by_start_date_$date",
            "bookings",
            "SELECT distinct submission_id, bookings.id as booking_id, start_date, end_date, subject, pending, event_id, paid, room FROM %i as bookings join %i as submission on bookings.submission_id = submission.id where bookings.start_date > %s and submission.user_id = %d",
            $this->tableName,
            $this->forms->submissionTableName,
            $date,
            $userId
        );
    }

    /** Get a booking by booking id
     *
     * @param   int $id         The booking id
     *
     * @return  object|false    The booking or false if not found
     */
    public function getBookingById($id)
    {

        $results = TSJIPPY\getFromDb(
            "booking_id_$id",
            'bookings',
            "SELECT * FROM %i WHERE id=%d",
            $this->tableName,
            $id
        );

        if (!empty($results)) {
            return $results[0];
        }

        return false;
    }

    /**
     * Get a booking by submission id
     *
     * @param   int             $id     The submission id
     *
     * @return  array|false             An array of bookings or false if no booking found
     * */
    public function getBookingsBySubmission($id)
    {
        $results    = TSJIPPY\getFromDb(
            "booking_by_submission_$id",
            'bookings',
            "SELECT * FROM %i WHERE submission_id=%d",
            $this->tableName,
            $id
        );

        if (!empty($results)) {
            return $results;
        }

        return false;
    }

    /**
     * Retrieves an array of start_date, end_date and room arrays
     *
     * @param   int     $submissionId    The submission id to retrieve the dates for
     *
     * @return  array                   An array with startDates, endDates and rooms arrays
     */
    function getBookingDates($submissionId)
    {
        // Get all the bookings belonging to this form submission
        $bookings   = $this->getBookingsBySubmission($submissionId);

        $startDates = [];
        $endDates   = [];
        $rooms      = [];

        // Store the dates
        foreach ($bookings as $booking) {
            $startDates[]   = $booking->start_date;
            $endDates[]     = $booking->end_date;
            $rooms[]        = $booking->room;
        }

        return [
            'startDates'    => $startDates,
            'endDates'      => $endDates,
            'rooms'         => $rooms
        ];
    }

    /**
     * Sends a reminder to the owner of a booking and to the managers
     */
    public function sendBookingEmails()
    {
        $this->forms->getForms();

        foreach ($this->forms->forms as $form) {

            $this->forms->formData  = $form;

            $this->forms->getForm($form->id);

            $this->getBookingElements(true);

            if (empty($this->bookingElements)) {
                continue;
            }

            $subjectKey = $this->bookingElements[0]->id;

            $this->forms->getEmailSettings();

            foreach ($this->forms->emailSettings as $mail) {

                if ($mail->email_trigger == 'before-stay' || $mail->email_trigger == 'after-stay') {
                    $processedSubmissionIds   = [];

                    $bookings   = [];
                    if ($mail->email_trigger == 'before-stay') {
                        $date       = gmdate('Y-m-d', strtotime("+{$mail->days_before} days", time()));
                        $bookings   = $this->retrieveBookingsByStartDate($date);
                    } elseif ($mail->email_trigger == 'after-stay') {
                        $date       = gmdate('Y-m-d', strtotime("-{$mail->days_after} days", time()));
                        $bookings   = $this->retrieveBookingsByEndDate($date);
                    }

                    foreach ($bookings as $booking) {
                        // Do not send multiple emails for the same submission
                        if (isset($processedSubmissionIds[$booking->submission_id])) {
                            continue;
                        }

                        $processedSubmissionIds[$booking->submission_id] = 1;

                        $bookingEmail    = new BookingEmail($booking);

                        $this->forms->parseSubmissions('', $booking->submission_id);

                        $replaceValues  = [];
                        foreach ($bookingEmail->replaceArray as $key => $value) {
                            $replaceValues[str_replace('%', '', $key)]  = $value;
                        }
                        $replaceValues  = (array)$this->forms->submission + $replaceValues;

                        $from       = $this->forms->processPlaceholders($mail->from, $replaceValues);

                        $to         = $this->forms->processPlaceholders($mail->to, $replaceValues);

                        $subject    = $this->forms->processPlaceholders($mail->subject, $replaceValues);

                        $message    = $this->forms->processPlaceholders($mail->message, $replaceValues);

                        $headers    = [];

                        if (!empty(trim($mail->headers))) {
                            $headers    = explode("\n", trim($mail->headers));
                        }

                        if (!empty($from)) {
                            $headers[]    = "Reply-To: $from";
                        }

                        add_filter('wp_mail', [$this->forms, 'addFormData'], 1);
                        wp_mail($to, $subject, $message, $headers);
                        remove_filter('wp_mail', [$this->forms, 'addFormData'], 1);

                        if ($mail->email_trigger == 'before-stay') {
                            $this->getSubjectManagers();

                            $bookingSubject    =  $this->forms->submission->{$subjectKey};

                            if (!isset($this->managers[$bookingSubject])) {
                                TSJIPPY\printArray("No manager found for $bookingSubject");
                                return;
                            }

                            $managers       = (array) $this->managers[$bookingSubject];

                            $elementName    = $this->forms->findUserNameElementName();

                            $elementId      = $this->forms->getElementBySlug($elementName, 'id');

                            $name           = $this->forms->submission->{$elementId};

                            foreach ($managers as $manager) {
                                // first repplace all occurences of the name for the manager name
                                $newSubject = str_replace($name, $manager->display_name, $subject);
                                $newMessage = str_replace($name, $manager->display_name, $message);

                                // Then replace your with the name
                                $newSubject = str_replace('your', $name . "'s", $newSubject);
                                $newMessage = str_replace('your', $name . "'s", $newMessage);

                                wp_mail($manager->user_email, $newSubject, $newMessage, $headers);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Get the booking managers
     *
     * @param   int     $userId     If supplied gets the subjects for this user only.
     */
    public function getSubjectManagers($userId = '', $force = false)
    {
        if (
            !$force &&
            !empty($this->managers) &&                          // the current list is not empty
            (
                (
                    is_numeric($userId)     &&                          // we only need the subjects for a certain user
                    ($this->managers['only_for'] ?? '')  == $userId     // the current list is for the current user
                ) ||
                (
                    empty($userId) &&                           // we want the generic list
                    empty($this->managers['only_for'])          // the current list is generic
                )
            )
        ) {
            // the current list is the list we need
            return;
        }

        $this->managers = [];

        if (is_numeric($userId)) {
            $this->managers['only_for']  = $userId;
        }

        // get the booking selector element
        $this->getSubjects();

        // Loop over all subjects
        foreach ($this->subjects as $subject) {
            if ($subject['payments'] ?? false) {
                $this->payables[]   = $subject['name'];
            }

            if (!isset($subject['managers'])) {
                $subject['managers']    = [];
            }

            if (!is_array($subject['managers'] ?? '')) {
                $subject['managers']    = [$subject['managers'] => 1];
            }

            // loop over all the managers of this subject
            foreach ($subject['managers'] as $managerId => $value) {

                if (!is_numeric($managerId)) {
                    continue;
                }

                // Check if this useraccount exists
                $manager    = get_userdata($managerId);

                // this manager is not the current user
                if ((is_numeric($userId) && $managerId != $userId) || !$manager) {
                    continue;
                }

                // create an empty array if needed for this subject
                if (empty($this->managers[$subject['name']])) {
                    $this->managers[$subject['name']]  = [];
                }

                // add the manager to the subject
                $this->managers[$subject['name']][$manager->ID]    = $manager;
            }
        }
    }
}
