<?php
namespace TSJIPPY\BOOKINGS;
use TSJIPPY;
use TSJIPPY\EVENTS;
use TSJIPPY\FORMS;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bookings{
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

    public function __construct($formInstance=null){
        global $wpdb;

        $this->bookingElements              = false;
        $this->bookings                     = [];
        $this->managers                     = [];
        $this->payables                     = [];
        $this->payables                     = [];
        $this->picturesUrl	                = TSJIPPY\pathToUrl(PLUGINPATH.'pictures');
        $this->showArchived                 = false;
        $this->tableEditPermissions         = current_user_can('manage_options');
        $this->subjects                     = [];
        $this->tableName		            = $wpdb->prefix.'tsjippy_bookings';
        $this->unavailable                  = [];
        $this->user                         = wp_get_current_user();
        $this->userRoles	                = $this->user->roles;

        if(getType($formInstance) == 'object'){
            $this->forms        = $formInstance;
        }else{
            $this->forms        = new TSJIPPY\FORMS\DisplayFormResults([]);
        }

        // Load the managers
        $this->getSubjectManagers();

        wp_enqueue_style( 'tsjippy_bookings_style', TSJIPPY\pathToUrl(PLUGINPATH.'css/bookings.min.css'), array(), PLUGINVERSION);
    }

    /**
     * Retrieves the subjects of a specific element from the database
     */
    public function getSubjects(){
        if(!empty($this->subjects)){
            return;
        }

        $posts = get_posts([
            'post_type'         => 'booking-subject', 
            'posts_per_page'    => -1, 
            'post_status'       => 'publish',
            'orderby'           => 'title',
            'order'             => 'ASC',
        ]);
        
        foreach($posts as $post){
            $metas                                              = get_post_meta($post->ID);

            foreach($metas as $key => $value){
                if(count($value) == 1 && $key != 'managers'){
                    $this->subjects[$post->post_title][$key]    = maybe_unserialize($value[0]);
                }else{
                    $this->subjects[$post->post_title][$key]    = array_map('maybe_unserialize', $value);
                }
            }
            $this->subjects[$post->post_title]['element-id']   = get_post_meta($post->ID, 'element-id', true);
            $this->subjects[$post->post_title]['post-id']      = $post->ID;
            $this->subjects[$post->post_title]['name']         = $post->post_title;
            $rooms       = get_children( [
                'post_parent'   => $post->ID,
                'post_type'     => 'any',
                'numberposts'   => -1, // Get all children
                'post_status'   => 'publish',
                'orderby'       => 'title',
                'order'         => 'ASC',
            ]);
            
            // add the name to each room
            $this->subjects[$post->post_title]['rooms'] = [];
            foreach($rooms as $roomPost){
                $this->subjects[$post->post_title]['rooms'][] = [
                    'post-id'       => $roomPost->ID,
                    'name'          => get_post_meta($roomPost->ID, 'name', true)
                ];
            }

            // add a dummy room if no rooms are found
            if(empty($rooms)){
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
    public function getElementSubjects($elementId, $subjectName=''){
        if(empty($this->subjects)){
            $this->getSubjects();
        }

        $subjects   = [];
        foreach($this->subjects as $subject){
            if(isset($subject['element-id']) && $subject['element-id'] == $elementId){
                if(empty($subjectName)){
                    $subjects[] = $subject;
                }elseif($subject['name'] == $subjectName){
                    return $subject;
                }
            }
        }

        return $subjects;
    }

    /**
	 * Creates the table holding all bookings if it does not exist
	 */
	public function createTables(){
		if ( !function_exists( 'maybe_create_table' ) ) {
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

		maybe_create_table($this->tableName, $sql );
	}

    /**
     * @param   string  $date   The timestring of the first month to shown in the view
     *
     * @return  string          Html of the navigator 
     */
    public function getNavigator($date){
        $minusMonth		= strtotime("first day of 1 months ago", $date);
		$minusMonthStr	= gmdate('m', $minusMonth);
		$minusYearStr	= gmdate('Y', $minusMonth);

        $firstMonth     = strtotime("first day of next month", $minusMonth);

		$plusMonth		= strtotime("first day of 2 months", $date);
		$plusMonthStr	= gmdate('m', $plusMonth);
		$plusYearStr	= gmdate('Y', $plusMonth);
        
        ob_start();
        ?>
        <div class="navigator" data-month='<?php echo esc_attr(gmdate('m', $firstMonth));?>' data-year='<?php echo esc_attr(gmdate('Y', $firstMonth));?>'>
            <div class="prev <?php if(gmdate('ym', $minusMonth) < gmdate('ym')){ echo 'hidden';}?>">
                <a class="prevnext" data-month="<?php echo esc_attr($minusMonthStr);?>" data-year="<?php echo esc_attr($minusYearStr);?>">
                    <span><</span> <?php echo esc_html(gmdate('F', $minusMonth));?>
                </a>
            </div>
            <div class="next">
                <a class="prevnext" data-month="<?php echo esc_attr($plusMonthStr);?>" data-year="<?php echo esc_attr($plusYearStr);?>">
                    <?php echo esc_html(gmdate('F', $plusMonth));?> <span>></span>
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
    public function roomDescription($subject){
        ob_start();

        $subjectName    = strtolower(str_replace(' ', '_', $subject['name']));

        ?>
        <div name='<?php echo esc_attr($subjectName);?>-room-modal' class="booking rooms modal hidden" style="display:unset; z-index: 999999999 !important;">
            <div class="modal-content">
                <span class="close mobile-sticky">&times;</span>

                <h4>Room descriptions</h4>
                <p>Select a room to see its description</p>
                <div class='tablink-wrapper'>
                    <?php
                    // Render tablink buttons
                    foreach($subject['rooms'] as $index => $room){
                        ?>
                        <button class='button tablink formbuilder-form <?php if($index === 0){echo 'active';}?>' type='button' id='show-<?php echo esc_attr($subjectName);?>-room-<?php echo esc_attr($index);?>' data-target='<?php echo esc_attr($subjectName);?>-room-<?php echo esc_attr($index);?>' style='margin-right:4px;'>
                            Room <?php echo esc_html($room['name']) ;?>
                        </button>
                        <?php
                    }
                ?>
                </div>
                <?php

                // Room description
                $i = 0;
                foreach($subject['rooms'] as $index => $room){
                    $i++;
                    $name   = $room['name'];
                    ?>
                    <div id="<?php echo esc_attr($subjectName);?>-room-<?php echo esc_attr($name);?>" class="tabcontent <?php if($i > 1){echo 'hidden';}?> lazy-post" data-post-id='<?php echo esc_attr($room['post-id']);?>' >
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
    public function roomSelector($node, $subject, $isResult, $radio=false){
        if(empty($subject['amount']) || $subject['amount'] == 1){
            return;
        }
        
        if($radio){
            $type   = 'radio';
        }else{
            $type   = 'checkbox';
        }

        if(!empty($_REQUEST['id']) && $this->forms->submission->id != $_REQUEST['id']){
            $this->forms->submission = (object) $this->forms->getSubmissions('', $_REQUEST['id']);
        }

        $wrapper    = $this->forms->addElement('div', $node, ['class' => 'rooms']);
        $s  = 's';
        if($radio){
            $s  = '';
        }

        /**
         * Nodes for the form results vs the form itself
         */
        if($isResult){
            $wrapper->textContent = "Select the room$s you want to see the calendar for";
            $this->forms->addElement('br', $wrapper);            
        }else{
            $subjectName            = strtolower(str_replace(' ', '_', $subject['name']));
            $wrapper->textContent   = "Select one or more room(s) you want to book";
            
            $this->forms->addElement(
                'button', 
                $node,
                [
                    'class'         => 'button tsjippy small room-details', 
                    'type'          => 'button', 
                    'data-target'   => "{$subjectName}-room-modal"
                ],
                "Show room details"
            );

            $this->forms->addElement('br', $wrapper); 
        }
            
        /**
         * Add inputs based on the room numbering
         */
        if(isset($subject['nrtype']) && $subject['nrtype'] == 'letters'){
            $alphabet = range('A', 'Z');

            for ($x = 0; $x < $subject['amount']; $x++) {
                $attributes  = [
                    'type'  => $type, 
                    'name'  => 'room', 
                    'class' => 'room-selector', 
                    'value' => $alphabet[$x],
                    'style' => 'margin-left: 5px;'
                ];

                if(
                    !empty($_REQUEST['id']) &&
                    is_array($this->forms->submission->{'booking-rooms'}) && 
                    in_array($alphabet[$x], $this->forms->submission->{'booking-rooms'})
                ){
                    $attributes['checked']    = 'checked';
                }

                $this->forms->addElement('input', $wrapper, $attributes); 

                // Create a text node
                $textNode = $this->forms->dom->createTextNode($alphabet[$x]);

                // Append the text node to the element
                $wrapper->appendChild($textNode);
            }
        }elseif(isset($subject['nrtype']) && $subject['nrtype'] == 'custom'){
            foreach($subject['rooms'] as $room){
                $attributes  = [
                    'type'  => $type, 
                    'name'  => 'room', 
                    'class' => 'room-selector', 
                    'value' => $room['name'],
                    'style' => 'margin-left: 5px;'
                ];

                if(
                    !empty($_REQUEST['id']) &&
                    is_array($this->forms->submission->{'booking-rooms'}) && 
                    in_array($room['name'], $this->forms->submission->{'booking-rooms'})
                ){
                    $attributes['checked']    = 'checked';
                }

                $this->forms->addElement('input', $wrapper, $attributes); 

                // Create a text node
                $textNode = $this->forms->dom->createTextNode($room['name']);

                // Append the text node to the element
                $wrapper->appendChild($textNode);
            }
        }else{
            for ($x = 1; $x <= $subject['amount']; $x++) {
                $attributes  = [
                    'type'  => $type, 
                    'name'  => 'room', 
                    'class' => 'room-selector', 
                    'value' => $x,
                    'style' => 'margin-left: 5px;'
                ];

                if(
                    !empty($_REQUEST['id']) &&
                    isset($this->forms->submission->{'booking-rooms'}) && 
                    in_array($x, $this->forms->submission->{'booking-rooms'})
                ){
                    $attributes['checked']    = 'checked';
                }

                $this->forms->addElement('input', $wrapper, $attributes); 

                // Create a text node
                $textNode = $this->forms->dom->createTextNode($x);

                // Append the text node to the element
                $wrapper->appendChild($textNode);
            }
        }
    }

    /**
     * Prints the calendar for each room of a subject
     *
     * @param   array   $rooms      Array of roomnames
     * @param   string  $subject    Subject name
     * @param   int     $date       Date the calendar should start
     */
    private function roomCalendars($rooms, $subject, $date){
        ob_start();
        ?>
        <div class='rooms-wrapper'>
            <?php
            foreach($rooms as $room){
                $roomHidden = 'hidden';

                if(
                    isset($_REQUEST['id'])                                  &&              // We should display a specific submission
                    is_array($this->forms->submission->{'booking-rooms'})   &&    // and a room is set
                    in_array($room['name'], $this->forms->submission->{'booking-rooms'})  // and it is this room
                ){
                    $roomHidden = '';
                }
                ?>
                <div class='room-wrapper <?php echo esc_attr($roomHidden);?>' data-room='<?php echo esc_attr($room['name']);?>'>
                    <h4>Room <?php echo esc_html($room['name']);?></h4>
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
     * @param   object      $node       The node to append to
     * @param   array       $subject    The subject of the calendar
     * @param   int         $date       The date to retrieve the calendar for
     * @param   boolean     $isAdmin    Wheter to show for admin purposes
     * @param   boolean     $hidden     Wheter to hide the calendar by default
     *
     * @return  string                  The html
     */
    public function modalContent($node, $subject, $date, $isAdmin = false, $hidden = false, $isResult=false){
        $returnHtml = false;
        if(empty($node)){
			// Create a new DOMDocument object
			$node 	    = $this->forms->dom;
			
   			$returnHtml = true;
		}

        $monthStr		= gmdate('m', $date);
		$yearStr		= gmdate('Y', $date);
        $cleanSubject   = trim($subject['name']);

        $attributes     = [
            'class'         => "bookings-wrap " . ($hidden ? 'hidden' : ''),
            'data-date'     => "$yearStr-$monthStr",
            'data-subject'  => $cleanSubject,
            'data-form-id'  => $this->forms->formData->id,
        ];

        if(isset($this->forms->currentElement->id)){
            $attributes["data-element-id"]  = $this->forms->currentElement->id;
        }
        if(isset($this->forms->shortcodeId)){
            $attributes["data-shortcode-id"] = $this->forms->shortcodeId;
        }

        $wrapper        = $this->forms->addElement('div', $node, $attributes );

        $overview       = $this->forms->addElement('div', $wrapper, ['class' => "booking overview"] );

            $header         = $this->forms->addElement('div', $overview, ['class' => "header mobile-sticky"] );

                $this->forms->addElement('h4', $header, ['style' => 'text-align:center;'], ucfirst($cleanSubject) . ' Calendar');

                $this->roomSelector($header, $subject, $isResult);

                if(!$isAdmin){
                    $this->showSelectedModalDates($header, $subject['amount'] > 1);
                }

                $navigators = $this->forms->addElement('div', $header, ['class' => "navigators ".( $subject['amount'] > 1 ? 'hidden': '')]);
                
                    $this->forms->addRawHtml($this->getNavigator($date), $navigators);

            $calendarTable  = $this->forms->addElement('div', $overview, ['class' => "calendar table ".( !empty($subject['amount']) && $subject['amount'] > 1 ? 'style="display:block;"': '')]);

                if(empty($subject['nrtype']) || $subject['nrtype'] == 'none'){
                    $roomWrapper    = $this->forms->addElement('div', $calendarTable, ['class' => "room-wrapper"]);
                        $monthWrapper   = $this->forms->addElement('div', $roomWrapper, ['class' => "month-wrapper flex"]);

                            $this->forms->addRawHtml($this->monthCalendar($cleanSubject, '', $date), $monthWrapper);
                            $this->forms->addRawHtml($this->monthCalendar($cleanSubject, '', strtotime('first day of next month', $date)), $monthWrapper);
                }else{
                    $rooms  = [];

                    if(isset($subject['nrtype']) && $subject['nrtype'] == 'letters'){
                        $alphabet = range('A', 'Z');
                        for ($x = 0; $x < $subject['amount']; $x++) {
                            $rooms[]    = $alphabet[$x];
                        }
                    }elseif(isset($subject['nrtype']) && $subject['nrtype'] == 'custom'){
                        $rooms  = $subject['rooms'];
                    }else{
                        for ($x = 1; $x <= $subject['amount']; $x++) {
                            $rooms[]    = $x;
                        }
                    }

                    $this->forms->addRawHtml($this->roomCalendars($rooms, $cleanSubject, $date), $calendarTable);
                }

            if(!$isAdmin){

                $actions         = $this->forms->addElement('div', $overview, ['class' => "actions mobile-sticky bottom"] );

                    $this->forms->addElement('button', $actions, ['class' => "button action reset disabled", "type" => 'button'], 'Reset' );

                    $this->forms->addElement('button', $actions, ['class' => "button action confirm disabled", "type" => 'button'], 'Confirm' );
            }else{
                $details         = $this->forms->addElement('div', $wrapper, ['class' => "booking details-wrapper"] );

                    $this->forms->addRawHtml($this->detailHtml(), $details);
            }

            // We don't need this on mobile devices
            if(!wp_is_mobile()){
                $roomDetails         = $this->forms->addElement('div', $wrapper);
                    // Room description
                    foreach($subject['rooms'] as $room){
                        if($room['post-id'] == -1){
                            continue;
                        }

                        $roomDescription        = $this->forms->addElement('div', $roomDetails, ['class' => 'hidden room-description', 'data-room-name' => $room['name']]);
                            $this->forms->addElement('h4', $roomDescription, [], "Room ".$room['name']);
                            $this->forms->addElement('div', $roomDescription, ['class' => 'lazy-post', 'data-post-id' => $room['post-id']]);
                    }
            }
        
        if($returnHtml){
			return $this->forms->dom->saveHtml();
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
    protected function showSelectedModalDates($node, $hide){
        ob_start();
        ?>
        <div class="booking-date-wrapper <?php if($hide){echo 'hidden';}?>">
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
                    <div class="booking-date-label-wrapper disabled end_date">
                        <label class="booking-date-label" for="booking-end-date">
                            <div class="booking-date-label-text">Departure</div>
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

        return $this->forms->addRawHtml(ob_get_clean(), $node);
    }

    /**
     *
     * Displays a date selector modal
     *
     * @param   object  $node       The node to append the modal to
     * @param   array   $subject    array with The name of the building/event and the amount of rooms
     */
    public function dateSelectorModal($node, $subject){
        if(defined('REST_REQUEST') && isset($_POST['month']) && isset($_POST['year'])){
			$month		= $_POST['month'];
			$year		= $_POST['year'];
			$dateStr	= "$year-$month-01";
		}else{
			$day	= gmdate('d');
			$month	= $_GET['month'];
			$year	= $_GET['yr'];
			if(!is_numeric($month) || strlen($month)!=2){
				$month	= gmdate('m');
			}
			if(!is_numeric($year) || strlen($year)!=4){
				$year	= gmdate('Y');
			}
			$dateStr	= "$year-$month-$day";
		}

        $date	        = strtotime($dateStr);

        $cleanSubject   = trim($subject['name']);

        /** 
         * Create the modal
         */
        $modal = $this->forms->addElement('div', $node, [
            'name'  => "{$cleanSubject}-modal",
            'class' => "booking modal hidden",
            'style' => "display:unset;"
        ]);

        $modalContent = $this->forms->addElement('div', $modal, ['class' => "modal-content"]);

        $this->forms->addElement('span', $modalContent, ['class' => "close mobile-sticky"], '&times;');

        // Append the modal content HTML
        $this->modalContent($modalContent, $subject, $date);
    }

    /**
	 * Get the month calendar
	 *
	 * @param	string		$subject		The subject name
     * @param	string		$room		    The subject room
     * @param   int         $date           The time
     * @param   boolean     $echo           Wheter to echo the result or return it
	 *
	 * @return	string				        Html of the calendar void if echo is true
	 */
	public function monthCalendar($subject, $room, $date, $echo = false){
		
        if(is_array($subject)){
            $subject    = $subject['name'];
        }

        $month          = gmdate('m', $date);
        $year           = gmdate('Y', $date);
		$weekDay		= gmdate("w", strtotime(gmdate('Y-m-01', $date)));
		$workingDate	= strtotime("-$weekDay day", strtotime(gmdate('Y-m-01', $date)));

        // subject without optional room name
        $overlap            = false;
        $gapDays            = 0;

        foreach($this->subjects as $s){
            // check if overlap is enabled
            if($s['name'] == $subject && !empty($s['overlap'])){
                if($s['overlap'] == 'yes'){
                    $overlap    = true;
                }elseif(!empty($s['overlap-period']) && is_numeric($s['overlap-period'])){
                    $gapDays    = $s['overlap-period'];
                }

                break;
            } 
        }

        //get the bookings for this month
		$this->retrieveMonthBookings($month, $year, $subject, $room, $gapDays);

        if(!$echo ){
		    ob_start();
        }

        ?>
        <div class="month-container" data-month='<?php echo esc_attr(gmdate('m', $date));?>' data-year='<?php echo esc_attr(gmdate('Y', $date));?>'>
            <div class="current">
                <?php echo esc_html(gmdate('F Y', $date));?>
            </div>
            <dl>
                <?php
                for ($y = 0; $y <= 6; $y++) {
                    $name	= gmdate('D', $workingDate);
                    ?>
                    <dt class='calendar day head'>
                        <?php echo esc_html($name);?>
                    </dt>
                    <?php
                    $workingDate	= strtotime("+1 days", $workingDate);
                }
                ?>
            </dl>
            <?php
            $this->writeCalendarRows($date, $overlap);
            ?>
        </div>

        <?php
        if(!$echo ){
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
    public function writeCalendarRows($date, $overlap){

        $month          = gmdate('m', $date);
		$weekDay		= gmdate("w", strtotime(gmdate('Y-m-01', $date)));
		$workingDate	= strtotime("-$weekDay day", strtotime(gmdate('Y-m-01', $date)));
        $curDate        = time();

        //loop over all weeks of a month
		while(true){
            $hidden         = '';
            if($month != gmdate('m', $date)){
                $hidden = 'hidden';
            }

			?>
            <dl class='calendar row <?php echo esc_attr($hidden);?>' data-month='<?php echo esc_attr($month);?>'>
                <?php
                //loop over all days of a week
                while(true){
                    $workingDateStr		= gmdate('Y-m-d', $workingDate);
                    $workingMonth	    = gmdate('m', $workingDate);
                    $workingDay			= gmdate('j', $workingDate);

                    $class              = '';

                    if($workingMonth != $month){
                        ?>
                        <dt class='empty'></dt>
                        <?php
                    }else{
                        $data   = '';
                        // date is in the past, make it unavailable
                        if(gmdate('Ymd', $workingDate) < gmdate('Ymd', $curDate)){
                            $class	= 'unavailable';
                        // not booked
                        }elseif(!isset($this->unavailable[$workingDateStr])){
                            $class	= 'available';
                        }
                        
                        // booked
                        if(isset($this->unavailable[$workingDateStr])){
                            $bookingId  = $this->unavailable[$workingDateStr];

                            // First and last day of a reservation are available if overlap is enabled
                            if(
                                $class	!= 'unavailable' &&                                                                 // not in the past
                                $overlap &&                                                                                 // overlap enabled
                                get_class($this->forms) != 'TSJIPPY\FORMS\DisplayFormResults'   &&                              // we are not in the overview page           
                                (
                                    !isset($this->unavailable[gmdate('Y-m-d', strtotime('-1 day', $workingDate))])    ||      // this is the first day of a booking
                                    !isset($this->unavailable[gmdate('Y-m-d', strtotime('+1 day', $workingDate))])            // or the last day of a booking
                                )
                            ){
                                $class	.= ' available';
                            }else{
                                $class	.= ' booked';
                            }

                            $data   .= "data-booking-id='$bookingId'";

                            if(method_exists($this->forms, 'getSubmissions')){
                                // check if this is our own booking
                                foreach($this->bookings as $booking){
                                    if($booking->id == $bookingId){
                                        $submissionId   = $booking->submission_id;

                                        $submission     = $this->forms->getSubmissions(null, $submissionId)[0];

                                        $userId         = $submission->user_id;

                                        if($userId == $this->forms->user->ID){
                                            $class	.= ' own';
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        
                        ?>
                        <dt 
                            class='calendar day <?php echo esc_attr($class);?>' 
                            data-date='<?php echo esc_attr(gmdate(DATEFORMAT, $workingDate));?>' 
                            data-isodate='<?php echo esc_attr(gmdate('Y-m-d', $workingDate));?>' 
                            <?php echo esc_html($data);?>
                        >
                            <span class='day-nr'>
                                <?php echo esc_html($workingDay);?>
                            </span>
                        </dt>
                        <?php
                    }
                    
                    //calculate the next week
                    $workingDate	= strtotime('+1 day', $workingDate);
                    //if the next day is the first day of a new week
                    if(gmdate('w', $workingDate) == 0){
                        break;
                    }
                }
			?>
            </dl>
            <?php

			// Break if next month
			if(gmdate('Ym', $workingDate) > gmdate('Ym', $date)){
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
    public function submissionDetails($booking, $submission, $hide, $echo = false){
        $subId          = $submission->subId;
        
        $hidden         = '';
        if(
            $hide   &&
            (
                empty($_REQUEST['id']) || 
                $_REQUEST['id'] != $this->forms->submission->id
            )
        ){
            $hidden         = 'hidden';
        }

        if(!$echo){
            ob_start();
        }

        ?>
        <div class='booking-detail-wrapper <?php echo esc_attr($hidden);?>' data-booking-id='<?php echo esc_attr($submission->booking_id);?>'>
            <h6 class='booking-title'>
                Booking details
            </h6>

            <article class='booking'>
                <h4 class='booking-title'><?php echo esc_html($submission->slug);?></h4>
                <div class='booking-detail'>
                    <table data-form-id='<?php echo esc_attr($submission->form_id);?>' style='width: unset;'>
                        <thead></thead>
                        <tbody>
                            <tr class='<?php echo esc_attr($this->bookingElements[0]->slug);?>' data-submission-id='<?php echo esc_attr($submission->id);?>'>
                                <td>
                                    <img src='<?php echo esc_url($this->picturesUrl);?>/subject.png' loading='lazy' alt='<?php echo esc_attr($this->bookingElements[0]->name);?>' class='booking-icon' title='<?php echo esc_attr($this->bookingElements[0]->name);?>'>
                                </td>
                                <td class='booking-data-wrapper edit forms-table' data-element-id='<?php echo esc_attr($this->bookingElements[0]->id);?>' data-name='<?php echo esc_attr($this->bookingElements[0]->slug);?>' data-booking-id='<?php echo esc_attr($submission->booking_id);?>'>
                                    <?php echo esc_html($submission->{$this->bookingElements[0]->id})?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <img src='<?php echo esc_url($this->picturesUrl);?>/date.png' loading='lazy' alt='date' class='booking-icon'>
                                </td>
                                <td class='booking-data-wrapper edit forms-table'>
                                    <table data-form-id='<?php echo esc_attr($submission->form_id);?>' data-shortcode-id='<?php echo esc_attr($this->forms->shortcodeId);?>' style='margin-bottom: 0px; width:unset;'>
                                        <tr data-submission-id='<?php echo esc_attr($submission->id);?>'>
                                            <td data-name='booking-start-date' data-element-id='<?php echo esc_attr($this->forms->getElementBySlug('booking-start-date')->id);?>' data-subid='<?php echo esc_attr($subId);?>' data-booking-id='<?php echo esc_attr($submission->booking_id);?>' class='edit forms-table'>
                                                <?php echo esc_html(gmdate(DATEFORMAT, strtotime($submission->{'booking-start-date'})));?>
                                            </td>
                                        </tr>
                                        <tr data-submission-id='<?php echo esc_attr($submission->id);?>'>
                                            <td data-name='booking-end-date' data-element-id='<?php echo esc_attr($this->forms->getElementBySlug('booking-end-date')->id);?>' data-subid='<?php echo esc_attr($subId);?>' data-booking-id='<?php echo esc_attr($submission->booking_id);?>' class='edit forms-table'>
                                                <?php echo esc_html(gmdate(DATEFORMAT, strtotime($submission->{'booking-end-date'})));?>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <?php 
                            if(!empty($submission->{'booking-rooms'})){
                                ?>
                                <tr class='room' data-submission-id='<?php echo esc_attr($submission->id);?>'>
                                    <td>
                                        <img src='<?php echo esc_url($this->picturesUrl);?>/room.png' loading='lazy' alt='Room' class='booking-icon' title='Room'>
                                    </td>
                                    <td class='booking-data-wrapper edit forms-table' data-element-id='-104' data-subid='<?php echo esc_attr($subId);?>' data-name='booking-rooms' data-booking-id='<?php echo esc_attr($submission->booking_id);?>'>
                                        <?php echo esc_attr($submission->{'booking-rooms'});?>
                                    </td>
                                </tr>
                                <?php
                            }

                            foreach($this->forms->columnSettings as $key => $setting){
                                if(
                                    !$setting['show']     || 
                                    !is_numeric($key)   || 
                                    in_array(
                                        $setting['slug'], 
                                        [
                                            'form-id',
									        'formurl',
									        '_wpnonce',
										    'id',
											'submissiontime',
											'edittime',
											'timecreated',
											'timelastedited',
											'time_created',
											'time_last_edited',
											'startdate',
											'booking-startdate',
											'booking-start-date',
											'endate',
											'booking-enddate',
											'booking-end-date',
											'booking-room',
											'booking-rooms',
											'name',
											$this->bookingElements[0]->slug
                                        ]
                                    )
                                ){
                                    continue;
                                }

                                $slug       = $setting['slug'];
                                $name       = empty($setting['name']) ? $slug : $setting['name'];
                                $element    = $this->forms->getElementBySlug($slug);
                                $data       = $submission->{$element->id};

                                $transformedData   = $this->forms->transformInputData($data, $slug, $submission);
                                if(empty($transformedData)){
                                    $transformedData    = 'X';
                                }

                                ?>
                                <tr class='<?php echo esc_attr($slug);?>' data-submission-id='<?php echo esc_attr($submission->id);?>'>
                                    <?php
                                    if(file_exists(TSJIPPY\urlToPath("$this->picturesUrl/$slug.png"))){
                                        ?>
                                        <td>
                                            <img src='<?php echo esc_url("$this->picturesUrl/$slug.png");?>' loading='lazy' alt='<?php echo esc_attr($name);?>' class='booking-icon' title='<?php echo esc_attr($name);?>'>
                                        </td>
                                        <?php
                                    }else{
                                        ?>
                                        <td>
                                            <?php echo esc_html($name);?>:
                                        </td>
                                        <?php
                                    }
                                    ?>
                                    <td class='booking-data-wrapper edit forms-table' data-element-id='<?php echo esc_attr($element->id);?>' data-name='<?php echo esc_attr($slug);?>' data-booking-id='<?php echo esc_attr($booking->id);?>'>
                                        <?php echo wp_kses_post($transformedData);?>
                                    </td>
                                </tr>
                                <?php
                            }

                            //if there are actions
                            if(!empty($this->forms->formData->actions)){
                                //loop over all the actions
                                $buttonsHtml	= [];
                                $buttons		= '';
                                foreach($this->forms->formData->actions as $action){
                                    if($action == 'archive' && $this->showArchived && $this->forms->submissions->archived){
                                        $action = 'unarchive';
                                    }
                                    $buttonsHtml[$action]	= "<button class='$action button forms-table-action' name='{$action}-action' value='$action'>".ucfirst($action)."</button>";
                                }
                                $buttonsHtml = apply_filters('tsjippy_form_actions_html', $buttonsHtml, $submission, $slug, $this, $this->forms->submission);
                                
                                //we have te html now, check for which one we have permission
                                foreach($buttonsHtml as $action => $button){
                                    $editRoles  = (array)$this->forms->columnSettings[$action]['edit_right_roles'];
                                    // Use the table settings if no specific rights are set
                                    if(empty($editRoles)){
                                        $editRoles  = $this->forms->tableSettings->edit_right_roles;
                                    }

                                    if(
                                        $this->tableEditPermissions || 																			//if we are allowed to do all actions
                                        $submission->user_id == $this->user->ID || 															//or this is our own entry
                                        array_intersect($this->userRoles, $editRoles)		//or we have permission for this specific button
                                    ){
                                        $buttons .= $button;
                                    }
                                }

                                if(!empty($buttons)){
                                    ?>
                                    <tr class='actions' data-submission-id='<?php echo esc_attr($submission->id);?>'>
                                        <td  colspan='2'><?php echo $buttons;?></td>
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

        if(!$echo){
            return ob_get_clean();
        }
    }

    /**
     * Build the detail html for the current month
     * 
     * @param   bool    $hide   Whether to hide the details or not, default true
     */
    public function detailHtml($hide=true){
        if(!method_exists($this->forms, 'parseSubmissions')){
            return '';
        }

        $this->getBookingElements();

        if($this->forms->columnSettings == null || empty($this->forms->tableSettings)){
            if(method_exists($this->forms, 'loadShortcodeData')){
                $result = $this->forms->loadShortcodeData();
                if(is_wp_error($result)){
                    return $result;
                }
            }
        }

        ob_start();

        $processed  = [];
        foreach($this->bookings as $booking){
            // do not process the same submission more than once
            if(in_array($booking->submission_id, $processed )){
                continue;
            }
            $processed[]    = $booking->submission_id;

            // Retrieve booking details
            $this->forms->parseSubmissions(null, $booking->submission_id);

            $subject        = $this->forms->submission->{$this->bookingElements[0]->id};

            if(
                // we are not the manager of this subject
                !in_array($this->user->ID, array_keys((array) $this->managers[$subject])) &&

                // we do not have permissions
                !array_intersect($this->forms->userRoles, array_keys($this->forms->tableSettings->view_right_roles))  &&      // we do not have the right to see others submissions
                
                // This is not our own booking
                $this->forms->submission->user_id != $this->forms->user->ID
            ){
                // no right to see this
                ?>
                <div class='booking-detail-wrapper warning hidden' data-booking-id='<?php echo esc_attr($booking->id);?>'>
                    No permission to see this booking
                </div>
                <?php
                continue;
            }

            foreach($this->forms->submissions as $submission){
                $this->submissionDetails($booking, $submission, $hide, true);                
            }
        }

        return ob_get_clean();
    }

    /**
     * Retrieve the subject data
     * @param   bool    $force      Do not send cached data, default false
     */
    public function getBookingElements($force = false){
        if(!empty($this->bookingElements) && !$force){
            return $this->bookingElements;
        }

        $this->bookingElements   = $this->forms->getElementByType('booking-selector');

        if(!$this->bookingElements || is_wp_error($this->bookingElements)){
            $this->bookingElements  = [];
            return;
        }

        foreach($this->bookingElements as &$element){
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
     * @param   int     $id             An booking id to ignore to check exclude the the booking itself
     * 
     * @return  array                   An array with overlapping bookings
     */
    public function checkOverlap($startDate, $endDate, $subject, $room, $id=-1){
        global $wpdb;

        // First check if a booking on these dates doesn't exist
        $query	    = "SELECT * FROM %i WHERE pending=0 AND subject = %s AND room = %s AND (%d BETWEEN start_date and end_date OR %d BETWEEN start_date and end_date)";
        $values     = [
            $this->tableName,
            $subject, 
            $room, 
            $startDate, 
            $endDate
        ];

        if($id != -1){
            $query  .= " AND NOT id=%d";
            $values[] = $id;
        }
        
        //sort on start_date
		$query	            .= " ORDER BY `start_date`, `start_time` ASC";

		$bookings           = $wpdb->get_results($wpdb->prepare($query, $values));

        $overlap            = false;

        $bookingEls         = $this->getBookingElements();

        if(is_wp_error($bookingEls)){
            return $bookingEls;
        }

        foreach($this->subjects as $detail){
            if(
                $detail['name'] == $subject && 
                !empty($detail['overlap']) && 
                $detail['overlap'] == 'yes'
            ){
                $overlap    = true;
            }
        }

        // start and end_date may overlap so remove any of those
        if($overlap){
            foreach($bookings as $index=>$booking){
                // this booking ends on the first day of the booking we are checking
                if($booking->end_date == $startDate){
                    unset($bookings[$index]);
                }

                // this booking starts on the last day of the booking we are checking
                if($booking->start_date == $endDate){
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
    public function checkPending($user, $subject){
        $els = $this->getBookingElements();
        if(!$els){
            return true;
        }

        foreach($this->subjects as $subjectSettings){
            if(!str_contains($subject, $subjectSettings['name'])){
                continue;
            }

            if(isset($subjectSettings['default_booking_state']) && $subjectSettings['default_booking_state'] == 'pending'){
                array_filter($subjectSettings['confirmed_booking_roles']);

                $confirmRoles   = array_keys($subjectSettings['confirmed_booking_roles']);

                // user the boooking is for
                if(is_numeric($user)){
                    $user       = get_userdata($user);
                }

                // user who submitted the form
                $submittingUser = get_userdata($this->forms->submission->user-id);

                if(
                    (
                        $user  &&          //user found
                        array_intersect($user->roles, $confirmRoles) // and allowed
                    )   ||
                    (
                        $submittingUser  &&          // user found
                        array_intersect($submittingUser->roles, $confirmRoles) // and allowed
                    )
                ){
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
    public function insertBooking($startDate, $endDate, $subject, $room, $submissionId){
        global $wpdb;

        $overlappingBookings    = $this->checkOverlap($startDate, $endDate, $subject, $room);
		if(!empty($overlappingBookings) && $overlappingBookings[0]->submission_id != $submissionId){
            if(!empty($room)){
                $subject    .= " room $room";
            }

            $startDateString    = gmdate(DATEFORMAT, strtotime($overlappingBookings[0]->start_date));
            $endDateString      = gmdate(DATEFORMAT, strtotime($overlappingBookings[0]->end_date));
            return new \WP_Error('booking', "The booking for $subject overlaps with an existing one from $startDateString till $endDateString, try again");
        }

        $userId             = $this->forms->submission->user_id;

        $subjectWithRoom    = $subject;
        if(!empty($room)){
            $subjectWithRoom    = "$subject room $room";
        }

        $eventId    = -1;

        // create a personal event
        if(!empty($userId)){
            $post = array(
                'post_type'		=> 'event',
                'post_title'    => "Booking for $subjectWithRoom",
                'post_content'  => "Booking for $subjectWithRoom",
                'post_status'   => 'publish',
                'post_author'   => $userId
            );

            $eventId 	= wp_insert_post( $post, true, false);

            $event							= [];
            $event['start_date']				= $startDate;
            $event['start_time']				= '14:00';
            $event['end_date']				= $endDate;
            $event['end_time']				= '12:00';
            $event['location']				= $subjectWithRoom;
            $event['organizer-id']			= $userId;
            $event['only_for']               = $userId;
            update_post_meta($eventId, 'eventdetails', json_encode($event));
            update_post_meta($eventId, 'only_for', $userId);
        }

        // Determine the pending state
        $pending    = $this->checkPending($userId, $subject);

        // Insert booking in db
        $wpdb->insert(
            $this->tableName,
            array(
                'start_date'		=> $startDate,
                'end_date'			=> $endDate,
                'subject'			=> $subject,
                'room'			    => $room,
                'submission_id'	    => $submissionId,
                'event_id'          => $eventId,
                'pending'           => $pending
            )
        );
		
		if(!empty($wpdb->last_error)){
			return new \WP_Error('bookings', $wpdb->last_error);
		}

		return $wpdb->insert_id;
    }

    /**
     * Validate a date change
     *
     * @param   object          $booking    The booking to validate
     * @param   array           $values     Reference to the values array
     *
     * @return  WP_error|bool               Error object if overlapping with another booking, true if ok.
     */
    protected function validateDates($booking, &$values){
        if(!isset($values['start_date']) && !isset($values['end_date'])){
            return true;
        }

        $start_date      = $booking->start_date;
        
        // Start date is updated
        if(isset($values['start_date'])){
            $start_date  = &$values['start_date'];

            // get the relevant date
            if(is_array($start_date)){
                if(!empty($_POST['subid']) && isset($start_date[$_POST['subid']])){
                    $start_date  = $start_date[$_POST['subid']];
                }else{
                    $start_date  = array_values($start_date)[0];
                }
            }
        }

        $end_date      = $booking->end_date;

        // End date is updated
        if(isset($values['end_date'])){
            $end_date  = &$values['end_date'];

            // get the relevant date
            if(is_array($end_date)){
                if(!empty($_POST['subid']) && isset($end_date[$_POST['subid']])){
                    $end_date  = $end_date[$_POST['subid']];
                }else{
                    $end_date  = array_values($end_date)[0];
                }
            }
        }

        $subject      = $booking->subject;
        if(isset($values['subject'])){
            $subject  = $values['subject'];
        }

        $room      = $booking->room;
        if(isset($values['room'])){
            $room  = $values['room'];
        }
        
        $overlappingBookings    = $this->checkOverlap($start_date, $end_date, $subject, $room, $booking->id);
		if(!empty($overlappingBookings)){
            if(!empty($room)){
                $subject    .= " room $room";
            }

            $startDateString    = gmdate(DATEFORMAT, strtotime($overlappingBookings[0]->start_date));
            $endDateString      = gmdate(DATEFORMAT, strtotime($overlappingBookings[0]->end_date));
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
    public function updateBooking($booking, $values, $skipHtml=false){
        global $wpdb;
        
        // Get the booking
        if(is_numeric($booking)){
            $booking        = $this->getBookingById($booking);

            if(!$booking){
                return new WP_Error('Invalid Booking id', 'Invalid Booking Id submitted');
            }
        }

        // only keep valid values
        $values         = array_filter( $values, function($val){return in_array($val, ['start_date', 'end_date', 'start_time', 'end_time', 'subject', 'room', 'pending', 'paid']);}, ARRAY_FILTER_USE_KEY);

        // Validate updated dates and adjusts the values array to only the relevant date for this booking
        $result         = $this->validateDates($booking, $values);

        // return the error if needed
        if($result !== true){
            return $result;
        }

        // update the booking
        $wpdb->update(
            $this->tableName,
            $values,
            array(
                'id'		=> $booking->id
            ),
        );

        if(empty($wpdb->last_error)){
            $message            = 'Succesfully updated the booking';
        }else{
            $message            = $wpdb->last_error;
        }

        // update event
        $event                  = json_decode(get_post_meta($booking->event_id, 'eventdetails', true), true);
        if(!empty($event)){
            update_post_meta($booking->event_id, 'eventdetails', json_encode(array_merge($event, $values)));
        }

        if($skipHtml){
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
            if(is_wp_error($details)){
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
    function updateRooms($newRooms, $currentBookings){
        $oldRooms   = [];
        foreach($currentBookings as $booking){
            $oldRooms[] = $booking->room;
        }

        $deleted    = array_diff((array) $oldRooms, (array)$newRooms);
        $added      = array_diff((array)$newRooms, (array)$oldRooms);

        // we changed a room
        if(count($oldRooms) == count($newRooms)){
            $deleted    = [];
            $added      = [];

            foreach($oldRooms as $i => $oldRoom){
                $newRoom    = $newRooms[$i];

                // Find the booking for this room and update it
                foreach($currentBookings as $booking){
                    if($oldRoom == $booking->room){
                        $result     = $this->updateBooking($booking, ['room' => $newRoom]);
                        break;
                    }
                }
            }
        }

        // add new ones
        if(!empty($added)){
            $booking    = $currentBookings[0];
            foreach($added as $room){
                //Insert the new booking
                $result = $this->insertBooking($booking->start_date, $booking->end_date, $booking->subject, $room, $this->forms->submission->id);

                if(is_wp_error($result )){
                    return $result;
                }
            }
        }

        // remove any removed bookings
        if(!empty($deleted)){
            foreach($currentBookings as $booking){
                $room   = $booking->room;

                // if this is the booking for the room
                if(in_array($room, $deleted)){
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
    public function removeBooking($booking){
        global $wpdb;

        // Get the booking
        if(is_numeric($booking)){
            $booking        = $this->getBookingById($booking);
        }

        // Remove the event
        $events = new TSJIPPY\EVENTS\CreateEvents();
        $events->removeDbRows($booking->event_id, true);

        // Remove the booking
        $wpdb->delete(
			$this->tableName,
			['id' => $booking->id],
			['%d'],
		);
    }

    /**
     * Remove a subject
     * @param   array  $subjectData    The subject data of the subject to remove
     */
    public function removeSubject($subjectData){
        global $wpdb;

        // Delete potential existing bookings;
        $results    = $wpdb->get_results($wpdb->prepare(
            "Select * FROM %i WHERE `subject` LIKE '{$subjectData['name']}%'",
            $this->tableName,
        ));

        foreach($results as $booking){
            $this->removeBooking($booking);
        }

        // Delete potential existing rooms
        if(is_numeric($subjectData['post-id'])){
            $postId = intval($subjectData['post-id']);
            $rooms       = get_children( [
                'post_parent'   => $postId,
                'post_type'     => 'any',
                'numberposts'   => -1, // Get all children
                'post_status'   => 'publish',
                'orderby'       => 'title',
                'order'         => 'ASC',
            ]);

            foreach($rooms as $room){
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
    public function addSubject($subjectData){
        $subjectName    = ucfirst($subjectData['name']);

        // insert a post for subject description
        $postId  = wp_insert_post([
            'post_title'    => $subjectName,
            'post_type'     => 'booking-subject',
            'post_status'   => 'publish',
            'post_content'  => isset($subjectData['description']) ? $subjectData['description'] : ''
        ]);

        if(isset($subjectData['rooms']) && is_array($subjectData['rooms'])){
            foreach($subjectData['rooms'] as $room){
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
        }

        unset($subjectData['description']);
        unset($subjectData['name']);
        unset($subjectData['rooms']);

        foreach($subjectData as $key => $value){
            update_post_meta($postId, $key, $value);
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
    protected function retrieveMonthBookings($month, $year, $subject, $room, $extraDays=0){
        global $wpdb;

        if(is_array($room)){
            $room   = $room['name'];
        }

		//select all bookings of this month
        $startDate  = "$year-$month-01";
        $endDate    = gmdate("Y-m-t", strtotime($startDate));

        $result             = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM %i WHERE (`start_date` >= %s OR %s BETWEEN start_date and end_date) AND `start_date` <= %s AND subject = %s AND room = %s ORDER BY `start_date`, `start_time` ASC",
            $this->tableName,
            $startDate,
            $startDate,
            $endDate,
            $subject,
            $room
        ));
        
		$this->bookings 	=  array_merge($this->bookings, $result);

        $this->unavailable  = [];

        foreach($result as $booking){

            $current    = strtotime($booking->start_date);
            $last       = strtotime($booking->end_date);

            if($extraDays > 0){
                $last   = strtotime("+$extraDays days", $last);
            }

            while( $current <= $last ) {
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
    public function retrieveBookingsByEndgmdate($date){
        global $wpdb;

        if(is_numeric($date)){
            $date   = gmdate('Y-m-d', $date);
        }

		return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM %i WHERE end_date = %s", $this->tableName, $date)
        );
    }

    /**
     * Retrieve all the bookings of a certain start_date
     *
     *  @param  string|int  $date   The date in 'Y-m-d' format or unix timestamp
     */
    public function retrieveBookingsByStartgmdate($date){
        global $wpdb;

        if(is_numeric($date)){
            $date   = gmdate('Y-m-d', $date);
        }

        $query	    = "SELECT * FROM $this->tableName WHERE start_date = '$date'";

		return $wpdb->get_results($query);
    }

    /** Get a booking by booking id 
     *
     * @param   int $id         The booking id
     *
     * @return  object|false    The booking or false if not found
     */
    public function getBookingById($id){
        global $wpdb;

		$query	    = "SELECT * FROM $this->tableName WHERE id=$id ";

		$results    =  $wpdb->get_results($query);

        if(!empty($results)){
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
    public function getBookingsBySubmission($id){
        global $wpdb;

        $results    = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM %i WHERE submission_id=%d", $this->tableName, $id)
        );

        if(!empty($results)){
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
    function getBookingDates($submissionId){
        // Get all the bookings belonging to this form submission
        $bookings   = $this->getBookingsBySubmission($submissionId);

        $startDates = [];
        $endDates   = [];
        $rooms      = [];

        // Store the dates
        foreach($bookings as $booking){
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
    public function sendBookingEmails(){
        $this->forms->getForms();

        foreach($this->forms->forms as $form){

            $this->forms->formData  = $form;

            $this->getBookingElements(true);

            if(empty($this->bookingElements)){
                continue;
            }

            $subjectKey = $this->bookingElements[0]->id;

            $this->forms->getEmailSettings();
		
            foreach($this->forms->emailSettings as $mail){

                if($mail->email_trigger == 'before-stay' || $mail->email_trigger == 'after-stay'){
                    $processedSubmissionIds   = [];

                    if($mail->email_trigger == 'before-stay'){
                        $date       = gmdate('Y-m-d', strtotime("+{$mail->days_before} days", time()));
                        $bookings   = $this->retrieveBookingsByStartgmdate($date);
                    }

                    elseif($mail->email_trigger == 'after-stay'){
                        $date       = gmdate('Y-m-d', strtotime("-{$mail->days_after} days", time()));
                        $bookings   = $this->retrieveBookingsByEndgmdate($date);
                    }

                    foreach($bookings as $booking){
                        // Do not send multiple emails for the same submission
                        if(in_array($booking->submission_id, $processedSubmissionIds)){
                            continue;
                        }

                        $processedSubmissionIds[] = $booking->submission_id;

                        $bookingEmail    = new BookingEmail($booking);

                        $this->forms->parseSubmissions('', $booking->submission_id);

                        $replaceValues  = [];
                        foreach($bookingEmail->replaceArray as $key => $value){
                            $replaceValues[str_replace('%', '', $key)]  = $value;
                        }
                        $replaceValues  = (array)$this->forms->submission + $replaceValues;
    
                        $from       = $this->forms->processPlaceholders($mail->from, $replaceValues);
        
                        $to         = $this->forms->processPlaceholders($mail->to, $replaceValues);
        
                        $subject    = $this->forms->processPlaceholders($mail->subject, $replaceValues);
        
                        $message    = $this->forms->processPlaceholders($mail->message, $replaceValues);
        
                        $headers	= [];
        
                        if(!empty(trim($mail->headers))){
                            $headers	= explode("\n", trim($mail->headers));
                        }

                        if(!empty($from)){
                            $headers[]	= "Reply-To: $from";
                        }                    

                        add_filter('wp_mail', [$this->forms, 'addFormData'], 1);
                        wp_mail($to , $subject, $message, $headers);
                        remove_filter('wp_mail', [$this->forms, 'addFormData'], 1);

                        if($mail->email_trigger == 'before-stay'){
                            $this->getSubjectManagers();

                            $bookingSubject    =  $this->forms->submission->{$subjectKey};

                            if(!isset($this->managers[$bookingSubject])){
                                TSJIPPY\printArray("No manager found for $bookingSubject");
                                return;
                            }

                            $managers       = (array) $this->managers[$bookingSubject];

                            $elementName    = $this->forms->findUserNameElementName();

                            $elementId      = $this->forms->getElementBySlug($elementName, 'id');

                            $name           = $this->forms->submission->{$elementId};

                            foreach($managers as $manager){
                                // first repplace all occurences of the name for the manager name
                                $newSubject = str_replace($name, $manager->display_name, $subject);
                                $newMessage = str_replace($name, $manager->display_name, $message);

                                // Then replace your with the name
                                $newSubject = str_replace('your', $name."'s", $newSubject);
                                $newMessage = str_replace('your', $name."'s", $newMessage);

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
    public function getSubjectManagers($userId = '', $force=false){
        if(
            !$force &&
            !empty($this->managers) &&                          // the current list is not empty
            (
                (
                    is_numeric($userId)     &&                  // we only need the subjects for a certain user
                    isset($this->managers['only_for'])   &&      // the current manager list is only for a certain user
                    $this->managers['only_for']  == $userId      // the current list is for the current user
                ) ||
                (
                    empty($userId) &&                           // we want the generic list
                    empty($this->managers['only_for'] )          // the current list is generic
                )
            )
        ){
            // the current list is the list we need
            return;
        }

        if(is_numeric($userId)){
            $this->managers['only_for']  = $userId;
        }

        // get the booking selector element
        $this->getSubjects();

        // Loop over all subjects
        foreach($this->subjects as $subject){
            if($subject['payments']){
                $this->payables[]   = $subject['name'];
            }

            if(!is_array($subject['managers'])){
                $subject['managers']    = [$subject['managers']];
            }

            // loop over all the managers of this subject
            foreach($subject['managers'] as $managerId){

                if(!is_numeric($managerId)){
                    continue;
                }

                // Check if this useraccount exists
                $manager    = get_userdata($managerId);

                // this manager is not the current user
                if(( is_numeric($userId) && $managerId != $userId) || !$manager ){
                    continue;
                }

                // create an empty array if needed for this subject 
                if(empty($this->managers[$subject['name'] ] )){
                    $this->managers[$subject['name'] ]  = [];
                }

                // add the manager to the subject
                $this->managers[$subject['name'] ][$manager->ID]    = $manager;
            }
        }
    }
}
