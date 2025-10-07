<?php
namespace SIM\BOOKINGS;
use SIM;
use SIM\EVENTS;
use SIM\FORMS;
use WP_Error;

class Bookings{
    public $tableName;
    public $bookings;
    public $forms;
    public $unavailable;
    public $showArchived;
    public $tableEditPermissions;
    public $user;
    public $userRoles;
    public $managers;
    public $payables;
    public $bookingElements;

    public function __construct($displayFormResults=''){
        global $wpdb;
		$this->tableName		= $wpdb->prefix.'sim_bookings';
        $this->bookings         = [];
        $this->user             = wp_get_current_user();
        $this->userRoles	    = $this->user->roles;
        $this->payables         = [];

        if(getType($displayFormResults) == 'object'){
            $this->forms        = $displayFormResults;
        }else{
            $this->forms        = new SIM\FORMS\DisplayFormResults();
        }

        // Load the managers
        $this->getSubjectManagers();

        wp_enqueue_style( 'sim_bookings_style');

        $this->addSplitEl();
    }

    /**
     * Adds the splitelements needed foor bookings
     */
    private function addSplitEl(){
        if(empty($this->forms->formData)){
            return;
        }
        
        if(empty($this->forms->formData->split)){
            $this->forms->formData->split   = [];
        }

        $splitId  = $this->forms->getElementByName('booking-startdate', 'id');
        if(!in_array($splitId, $this->forms->formData->split)){
            $this->forms->formData->split[] = $splitId;
        }

        $splitId  = $this->forms->getElementByName('booking-enddate', 'id');
        if(!in_array($splitId, $this->forms->formData->split)){
            $this->forms->formData->split[] = $splitId;
        }
    }

    /**
	 * Creates the table holding all bookings if it does not exist
	 */
	public function createBookingsTable(){
		if ( !function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . '/wp-admin/install-helper.php';
		}

		global $wpdb;
		
		$charsetCollate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->tableName}(
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			startdate date NOT NULL,
			enddate date NOT NULL,
			starttime varchar(80) NOT NULL,
			endtime varchar(80) NOT NULL,
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
		$minusMonthStr	= date('m', $minusMonth);
		$minusYearStr	= date('Y', $minusMonth);

        $firstMonth     = strtotime("first day of next month", $minusMonth);

		$plusMonth		= strtotime("first day of 2 months", $date);
		$plusMonthStr	= date('m', $plusMonth);
		$plusYearStr	= date('Y', $plusMonth);

        $hidden         = '';
        if(date('ym', $minusMonth) < date('ym')){
            //$hidden = 'hidden';
        }
        ob_start();
        ?>
        <div class="navigator" data-month='<?php echo date('m', $firstMonth);?>' data-year='<?php echo date('Y', $firstMonth);?>'>
            <div class="prev <?php echo $hidden;?>">
                <a class="prevnext" data-month="<?php echo $minusMonthStr;?>" data-year="<?php echo $minusYearStr;?>">
                    <span><</span> <?php echo date('F', $minusMonth);?>
                </a>
            </div>
            <div class="next">
                <a class="prevnext" data-month="<?php echo $plusMonthStr;?>" data-year="<?php echo $plusYearStr;?>">
                    <?php echo date('F', $plusMonth);?> <span>></span>
                </a>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Room description modals
     */
    public function roomDescription($subject){
        ob_start();

        $subjectName    = strtolower(str_replace(' ', '_', $subject['name']));

        ?>
        <div name='<?php echo $subjectName;?>-room-modal' class="booking rooms modal hidden" style="display:unset; z-index: 999999999 !important;">
            <div class="modal-content">
                <span class="close mobile-sticky">&times;</span>

                <h4>Room descriptions</h4>
                <p>Select a room to see its description</p>
                <div class='tablink-wrapper'>
                    <?php
                    // Render tablink buttons
                    foreach($subject['rooms'] as $index=>$room){
                        ?>
                        <button class='button tablink formbuilderform <?php if($index === 0){echo 'active';}?>' type='button' id='show_<?php echo $subjectName;?>_room_<?php echo $index;?>' data-target='<?php echo $subjectName;?>_room_<?php echo $index;?>' style='margin-right:4px;'>
                            <?php echo $room['name'];?>
                        </button>
                        <?php
                    }
                ?>
                </div>
                <?php

                // Room description
                foreach($subject['rooms'] as $index=>$room){
                    ?>
                    <div id="<?php echo $subjectName;?>_room_<?php echo $index;?>" class="tabcontent <?php if($index > 0){echo 'hidden';}?>">
                        <?php
                        $content    = force_balance_tags(do_shortcode($subject['description']));
                        $content    = preg_replace('/<!--(.|\s)*?-->/', '', $content);

                        if(empty($content)){
                            $manager        = get_userdata($subject['managers'][0]);
                            if($manager){
                                $content = "No details found, sorry.<br> Contact <a href='mailto:$manager->user_email?subject=Please add some description for {$subject['name']} room {$room['name']}&body=Dear $manager->display_name,'>the manager</a>";
                            }
                        }
                        echo $content;
                        ?>
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
     * @param   array   $subject   The name
     * @param   boolean $isResult   Wheter we are looking at the form or the formresult
     * @param   bool    $radio      true for radio choice, false for checkboxes
     */
    public function roomSelector($subject, $isResult, $radio=false){
        if(empty($subject['amount']) || $subject['amount'] == 1){
            return;
        }

        ob_start();
        
        if($radio){
            $type   = 'radio';
        }else{
            $type   = 'checkbox';
        }

        if(!empty($_REQUEST['id']) && $this->forms->submission->id != $_REQUEST['id']){
            $this->forms->submission = $this->forms->getSubmission($_REQUEST['id']);
        }

        ?>
        <div class="rooms">
            <?php
            $s  = 's';
            if($radio){
                $s  = '';
            }

            if($isResult){
                echo "Select the room$s you want to see the calendar for<br>";
            }else{
                $subjectName    = strtolower(str_replace(' ', '_', $subject['name']));
                echo "Select one or more room(s) you want to book <button class='button sim small room-details' type='button' data-target='{$subjectName}-room-modal'>Show room details</button><br>";
            }
            
            if(isset($subject['nrtype']) && $subject['nrtype'] == 'letters'){
                $alphabet = range('A', 'Z');
                for ($x = 0; $x < $subject['amount']; $x++) {
                    $checked    = '';
                    if(is_array($this->forms->submission->formresults['booking-room']) && in_array($alphabet[$x], $this->forms->submission->formresults['booking-room'])){
                        $checked    = 'checked';
                    }
                    ?>
                    <input type='<?php echo $type;?>' name='room' class='room-selector' value='<?php echo $alphabet[$x];?>' <?php echo $checked;?>>
                    <?php
                    echo $alphabet[$x];
                }
            }elseif(isset($subject['nrtype']) && $subject['nrtype'] == 'custom'){
                foreach($subject['rooms'] as $room){
                    if(!is_array($room)){
                        $room   = [
                            "name"          => $room,
                            "description"   => ''
                        ];
                    }

                    $checked    = '';
                    if(is_array($this->forms->submission->formresults['booking-room']) && in_array($room['name'], $this->forms->submission->formresults['booking-room'])){
                        $checked    = 'checked';
                    }
                    ?>
                    <input type='<?php echo $type;?>' name='room' class='room-selector' value='<?php echo $room['name'];?>' <?php echo $checked;?>>
                    <?php
                    echo $room['name'];
                }
            }else{
                for ($x = 1; $x <= $subject['amount']; $x++) {
                    $checked    = '';
                    if(isset($this->forms->submission->formresults['booking-room']) && in_array($x, $this->forms->submission->formresults['booking-room'])){
                        $checked    = 'checked';
                    }
                    ?>
                    <input type='<?php echo $type;?>' name='room' class='room-selector' value='<?php echo $x;?>' <?php echo $checked;?>>
                    <?php
                    echo $x;
                }
            }
            ?>
        </div>
        <br>
        <?php

        return ob_get_clean();
    }

    /**
     * Prints the calendar for each room of a subject
     *
     * @param   array   $rooms      Array of roomnames
     * @param   string  $subject    Subject name
     * @param   int     $date       Date the calendar should start
     */
    private function roomCalendars($rooms, $subject, $date){
        foreach($rooms as $room){
            $roomHidden = 'hidden';

            if(!is_array($room)){
                $room   = [
                    "name"          => $room,
                    "description"   => ''
                ];
            }

            if(
                isset($_REQUEST['id'])                                  &&              // We should display a specific submission
                is_array($this->forms->submission->formresults['booking-room'])   &&    // and a room is set
                in_array($room['name'], $this->forms->submission->formresults['booking-room'])  // and it is this room
            ){
                $roomHidden = '';
            }
            ?>
            <div class='room-wrapper <?php echo $roomHidden;?>'data-room='<?php echo $room['name'];?>'>
                <h4>Room <?php echo $room['name']?></h4>
                <div class='month-wrapper flex'>
                    <?php
                    echo $this->monthCalendar($subject, $room['name'], $date);
                    echo $this->monthCalendar($subject, $room['name'], strtotime('first day of next month', $date));
                    ?>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Displays the booking calendars
     * @param   array       $subject    The subject of the calendar
     * @param   int         $date       The date to retrieve the calendar for
     * @param   boolean     $isAdmin    Wheter to show for admin puposes
     * @param   boolean     $hidden     Wheter to hide the calendar by default
     *
     * @return  string                  The html
     */
    public function modalContent($subject, $date, $isAdmin = false, $hidden = false, $isResult=false){
		$monthStr		= date('m', $date);
		$yearStr		= date('Y', $date);
        $cleanSubject   = trim($subject['name']);

        ob_start();

        $extraString   = '';
        if(isset($this->forms->currentElement->id)){
            $extraString   = "data-elid='{$this->forms->currentElement->id}'";
        }
        if(isset($this->forms->shortcodeId)){
            $extraString   .= "data-shortcode_id='{$this->forms->shortcodeId}'";
        }

        ?>
        <div class="bookings-wrap <?php if($hidden){echo 'hidden';}?>" data-date="<?php echo "$yearStr-$monthStr";?>" data-subject="<?php echo $cleanSubject;?>" data-form-id="<?php echo $this->forms->formData->id;?>" <?php echo $extraString;?>>
            <div class="booking overview">
                <div class='header mobile-sticky'>
                    <h4 style='text-align:center;'><?php echo ucfirst($cleanSubject);?> Calendar</h4>

                    <?php
                    echo $this->roomSelector($subject, $isResult);

                    if(!$isAdmin){
                        echo $this->showSelectedModalDates($subject['amount'] > 1);
                    }
                    ?>
                    <div class="navigators <?php if($subject['amount'] > 1){echo 'hidden';}?>">
                        <?php
                        echo $this->getNavigator($date);
                        ?>
                    </div>
                </div>
                <div class="calendar table" <?php if(!empty($subject['amount']) && $subject['amount'] > 1){echo 'style="display:block;"';}?>>
                    <?php
                    if(empty($subject['nrtype']) || $subject['nrtype'] == 'none'){
                        ?>
                        <div class='room-wrapper'>
                            <div class='month-wrapper flex'>
                                <?php
                                echo $this->monthCalendar($cleanSubject, '', $date);
                                echo $this->monthCalendar($cleanSubject, '', strtotime('first day of next month', $date));
                                ?>
                            </div>
                        </div>
                        <?php
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

                        $this->roomCalendars($rooms, $cleanSubject, $date);
                    }
                    ?>
                </div>
                <?php
                if(!$isAdmin){
                    ?>
                    <div class="actions mobile-sticky bottom">
                        <button class="button action reset disabled" type='button'>Reset</button>
                        <button class="button action confirm disabled" type='button'>Confirm</button>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php

            if($isAdmin){
                ?>
                <div class="booking details-wrapper">
                    <?php
                    echo $this->detailHtml();
                    ?>
                </div>
                <?php
            }

            // We don't need this on mobile devices
            elseif(!wp_is_mobile()){
                ?>
                <div>
                    <?php
                    // Room description
                    foreach($subject['rooms'] as $index=>$room){
                        ?>
                        <div class="hidden room-description" data-room_name="<?php echo $room['name'];?>" >
                            <h4>Room <?php echo $room['name'];?></h4>
                            <?php
                            // Make sure we have valid content, balanced and comments removed.
                            $content    = force_balance_tags(do_shortcode($room['description']));
                            $content    = preg_replace('/<!--(.|\s)*?-->/', '', $content);
                            if(empty($content)){
                                $manager        = get_userdata($subject['managers'][0]);
                                if($manager){
                                    echo "No details found, sorry.<br> Contact <a href='mailto:$manager->user_email?subject=Please add some description for {$subject['name']} room {$room['name']}&body=Dear $manager->display_name,'>the manager</a>";
                                }
                            }else{
                                echo $content;
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
            }
            ?>
        </div>
        
        <?php
        return ob_get_clean();
    }

    /**
     * Displays the selected dates
     *
     * @param   bool    $hide   Wheter or not this should be hidden by default
     *
     * @return  string          The html
     */
    protected function showSelectedModalDates($hide){
        ob_start();

        ?>
        <div class="booking-date-wrapper <?php if($hide){echo 'hidden';}?>">
            <div class="booking-dates-input-wrapper">
                <div class="-h0i9fjw">
                    <div class="booking-date-label-wrapper">
                        <label class="booking-date-label" for="booking-startdate">
                            <div class="booking-date-label-text">Arrival</div>
                            <div dir="ltr">
                                <div class="booking-date-label-input-wrapper">
                                    <input class="booking-date-label-input booking-startdate" placeholder="Select a date" type="text" value="" disabled>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div></div>
                    <div class="booking-date-label-wrapper disabled enddate">
                        <label class="booking-date-label" for="booking-enddate">
                            <div class="booking-date-label-text">Departure</div>
                            <div dir="ltr">
                                <div class="booking-date-label-input-wrapper">
                                    <input class="booking-date-label-input booking-enddate" placeholder="Select a date" type="text" value="" disabled>
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
                            <div class="-uxnsba" data-testid="availability-calendar-date-range">Next, select your arrival date.<br>Then click again to select your departure date.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php

        return ob_get_clean();
    }

    /**
     *
     * Displays a date selector modal
     *
     * @param   array  $subject    array with The name of the building/event and the amount of rooms
     *
     * @return  string              The html
     */
    public function dateSelectorModal($subject){
        if(defined('REST_REQUEST') && isset($_POST['month']) && isset($_POST['year'])){
			$month		= $_POST['month'];
			$year		= $_POST['year'];
			$dateStr	= "$year-$month-01";
		}else{
			$day	= date('d');
			$month	= $_GET['month'];
			$year	= $_GET['yr'];
			if(!is_numeric($month) || strlen($month)!=2){
				$month	= date('m');
			}
			if(!is_numeric($year) || strlen($year)!=4){
				$year	= date('Y');
			}
			$dateStr	= "$year-$month-$day";
		}

        $date			= strtotime($dateStr);

        ob_start();
        $cleanSubject    = trim($subject['name']);

        echo $this->roomDescription($subject);

		?>
        <div name='<?php echo $cleanSubject;?>-modal' class="booking modal hidden" style="display:unset;">
			<div class="modal-content">
				<span class="close mobile-sticky">&times;</span>
                <?php echo $this->modalContent($subject, $date);?>
            </div>
		</div>
        <?php

        return ob_get_clean();
    }

    /**
	 * Get the month calendar
	 *
	 * @param	string		$subject		The subject name
     * @param	string		$room		    The subject room
     * @param   int         $date           The time
	 *
	 * @return	string				        Html of the calendar
	 */
	public function monthCalendar($subject, $room, $date){
		
        if(is_array($subject)){
            $subject    = $subject['name'];
        }

		ob_start();
		$curDate        = time();
        $month          = date('m', $date);
        $year           = date('Y', $date);
		$weekDay		= date("w", strtotime(date('Y-m-01', $date)));
		$workingDate	= strtotime("-$weekDay day", strtotime(date('Y-m-01', $date)));
		$calendarRows	= '';

        // subject without optional room name
        $overlap            = false;
        $gapDays            = 0;

        // get the details of all subjects
        $bookingDetails     = maybe_unserialize($this->forms->currentElement->booking_details);
        if(!empty($bookingDetails) && is_array($bookingDetails['subjects'])){
            // find the details of the current subject
            foreach($bookingDetails['subjects'] as $s){
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
        }

        //get the bookings for this month
		$this->retrieveMonthBookings($month, $year, $subject, $room, $gapDays);

		//loop over all weeks of a month
		while(true){
            $hidden         = '';
            if($month != date('m', $date)){
                $hidden = 'hidden';
            }

			$calendarRows .= "<dl class='calendar row $hidden' data-month='$month'>";
                //loop over all days of a week
                while(true){
                    $workingDateStr		= date('Y-m-d', $workingDate);
                    $workingMonth	    = date('m', $workingDate);
                    $workingDay			= date('j', $workingDate);

                    $class              = '';

                    if($workingMonth != $month){
                        $calendarRows .=  "<dt class='empty'></dt>";
                    }else{
                        $data   = '';
                        // date is in the past, make it unavailable
                        if(date('Ymd', $workingDate) < date('Ymd', $curDate)){
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
                                get_class($this->forms) != 'SIM\FORMS\DisplayFormResults'   &&                              // we are not in the overview page           
                                (
                                    !isset($this->unavailable[date('Y-m-d', strtotime('-1 day', $workingDate))])    ||      // this is the first day of a booking
                                    !isset($this->unavailable[date('Y-m-d', strtotime('+1 day', $workingDate))])            // or the last day of a booking
                                )
                            ){
                                $class	.= ' available';
                            }else{
                                $class	.= ' booked';
                            }

                            $data   .= "data-booking_id='$bookingId'";

                            if(method_exists($this->forms, 'getSubmissions')){
                                // check if this is our own booking
                                foreach($this->bookings as $booking){
                                    if($booking->id == $bookingId){
                                        $submissionId   = $booking->submission_id;

                                        $submission     = $this->forms->getSubmissions(null, $submissionId)[0];

                                        $userId         = $submission->userid;
                                        if(!empty($submission->formresults['user-id'])){
                                            $userId     = $submission->formresults['user-id'];
                                        }elseif(!empty($submission->formresults['user_id'])){
                                            $userId     = $submission->formresults['user_id'];
                                        }

                                        if($userId == $this->forms->user->ID){
                                            $class	.= ' own';
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        
                        $calendarRows .=  "<dt class='calendar day $class' data-date='".date(DATEFORMAT, $workingDate)."' data-isodate='".date('Y-m-d', $workingDate)."' $data>";
                            $calendarRows	.= "<span class='day-nr'>$workingDay</span>";
                        $calendarRows	.= "</dt>";
                    }
                    
                    //calculate the next week
                    $workingDate	= strtotime('+1 day', $workingDate);
                    //if the next day is the first day of a new week
                    if(date('w', $workingDate) == 0){
                        break;
                    }
                }
			$calendarRows .= '</dl>';

			// Break if next month
			if(date('Ym', $workingDate) > date('Ym', $date)){
				break;
			}
		}

        ?>
        <div class="month-container" data-month='<?php echo date('m', $date);?>' data-year='<?php echo date('Y', $date);?>'>
            <div class="current">
                <?php echo date('F Y', $date);?>
            </div>
            <dl>
                <?php
                $workingDate	= strtotime("-$weekDay day", strtotime(date('Y-m-01', $date)));
                for ($y = 0; $y <= 6; $y++) {
                    $name	= date('D', $workingDate);
                    echo "<dt class='calendar day head'>$name</dt>";
                    $workingDate	= strtotime("+1 days", $workingDate);
                }
                ?>
            </dl>
            <?php
            echo $calendarRows;
            ?>
        </div>

        <?php

		return ob_get_clean();
	}

    /**
     * Build the detail html for the current month
     */
    public function detailHtml(){
        if(!method_exists($this->forms, 'parseSubmissions')){
            return '';
        }

        $baseUrl	= SIM\pathToUrl(MODULE_PATH.'pictures');

        if($this->forms->columnSettings == null || empty($this->forms->tableSettings)){
            if(method_exists($this->forms, 'loadShortcodeData')){
                $result = $this->forms->loadShortcodeData();
                if(is_wp_error($result)){
                    return $result;
                }
            }
        }

        ob_start();

        foreach($this->bookings as $booking){
            // Retrieve booking details
            $this->forms->parseSubmissions(null, $booking->submission_id);
            $submission     = $this->forms->submission->formresults;

            $userIdElName   = $this->forms->findUserIdElementName();

            $subject        = $submission[$this->bookingElements[0]->name];

            if(
                // we are not the manager of this subject
                !in_array($this->user, (array) $this->managers[$subject]) &&
                // we do not have permissions
                !array_intersect($this->forms->userRoles, array_keys($this->forms->tableSettings['view-right-roles']))  &&      // we do not have the right to see others submissions
                // This is not our own booking
                (
                    isset($submission[$userIdElName])                      &&
                    $submission[$userIdElName] != $this->forms->user->ID
                )
            ){
                // no right to see this
                echo "<div class='booking-detail-wrapper warning hidden' data-booking_id='$booking->id'>";
                    echo "No Permission to see this booking";
                echo "</div>";
                continue;
            }

            $hidden         = 'hidden';
            if(!empty($_REQUEST['id']) && $_REQUEST['id'] == $submission['id']){
                $hidden = '';
            }

            foreach((array)$submission['booking-room'] as $room){
                if($room == $booking->room){
                    $subId  = $room;
                    break;
                }
            }            

            ?>
            <div class='booking-detail-wrapper <?php echo $hidden;?>' data-booking_id='<?php echo $booking->id;?>'>
                <h6 class='booking-title'>
                    Booking details
                </h6>

                <article class='booking'>
                    <h4 class='booking-title'><?php echo $submission['name'];?></h4>
                    <div class='booking-detail'>
                        <table data-form-id='<?php echo $submission['form-id'];?>' style='width: unset;'>
                            <thead></thead>
                            <tbody>
                                <tr class='<?php $this->bookingElements[0]->name;?>' data-id='<?php echo $submission['id'];?>'>
                                    <td>
                                        <img src='<?php echo $baseUrl;?>/subject.png' loading='lazy' alt='<?php echo $this->bookingElements[0]->nicename;?>' class='booking-icon' title='<?php echo $this->bookingElements[0]->nicename;?>'>
                                    </td>
                                    <td class='booking-data-wrapper edit-forms-table' data-id='<?php echo $this->bookingElements[0]->id;?>' data-name='<?php echo $this->bookingElements[0]->name;?>' data-oldvalue='<?php echo json_encode($submission[$this->bookingElements[0]->name]);?>' data-booking_id='<?php echo $booking->id;?>'>
                                        <?php echo $submission[$this->bookingElements[0]->name];?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <img src='<?php echo $baseUrl;?>/date.png' loading='lazy' alt='date' class='booking-icon'>
                                    </td>
                                    <td class='booking-data-wrapper edit-forms-table'>
                                        <table data-form-id='<?php echo $submission['form-id'];?>' style='margin-bottom: 0px; width:unset;'>
                                            <tr data-id='<?php echo $submission['id'];?>'>
                                                <td data-name='booking-startdate' data-id='<?php echo $this->forms->getElementByName('booking-startdate')->id;?>' data-subid='<?php echo $subId;?>' data-oldvalue='<?php echo json_encode($booking->startdate);?>' data-booking_id='<?php echo $booking->id;?>' class='edit-forms-table'>
                                                    <?php echo date(DATEFORMAT, strtotime($booking->startdate));?>
                                                </td>
                                            </tr>
                                            <tr data-id='<?php echo $submission['id'];?>'>
                                                <td data-name='booking-enddate' data-id='<?php echo  $this->forms->getElementByName('booking-enddate')->id;?>' data-subid='<?php echo $subId;?>' data-oldvalue='<?php echo json_encode($booking->enddate);?>' data-booking_id='<?php echo $booking->id;?>' class='edit-forms-table'>
                                                    <?php echo date(DATEFORMAT, strtotime($booking->enddate));?>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr class='room' data-id='<?php echo $submission['id'];?>'>
                                    <td>
                                        <img src='<?php echo $baseUrl;?>/room.png' loading='lazy' alt='Room' class='booking-icon' title='Room'>
                                    </td>
                                    <td class='booking-data-wrapper edit-forms-table' data-id='-104' data-subid='<?php echo $subId;?>' data-name='booking-room' data-oldvalue='<?php echo json_encode($submission['booking-room']);?>' data-booking_id='<?php echo $booking->id;?>'>
                                        <?php echo $booking->room;?>
                                    </td>
                                </tr>

                                <?php
                                foreach($this->forms->columnSettings as $key=>$setting){
                                    if(
                                        $setting['show'] == 'hide' || 
                                        !is_numeric($key) || 
                                        in_array($setting['name'], ['form-id', 'formurl', '_wpnonce', 'id', 'submissiontime', 'edittime', 'booking-startdate', 'booking-enddate', 'booking-room', 'name', $this->bookingElements[0]->name])
                                    ){
                                        continue;
                                    }

                                    $name       = $setting['name'];
                                    $element    = $this->forms->getElementByName($name);
                                    $data       = $submission[$name];

                                    $transformedData   = $this->forms->transformInputData($data, $name, $submission);
                                    if(empty($transformedData)){
                                        $transformedData    = 'X';
                                    }

                                    echo "<tr class='$name' data-id='{$submission['id']}'>";
                                        if(file_exists(SIM\urlToPath("$baseUrl/$name.png"))){
                                            echo "<td><img src='$baseUrl/$name.png' loading='lazy' alt='{$setting['nice-name']}' class='booking-icon' title='{$setting['nice-name']}'></td>";
                                        }else{
                                            echo "<td>{$setting['nice-name']}:</td>";
                                        }
                                        echo "<td class='booking-data-wrapper edit-forms-table' data-id='$element->id' data-name='$name' data-oldvalue='".json_encode($data)."' data-booking_id='$booking->id'>";
                                            echo $transformedData;
                                        echo "</td>";
                                    echo "</tr>";
                                }

                                //if there are actions
                                if(!empty($this->forms->formData->actions)){
                                    //loop over all the actions
                                    $buttonsHtml	= [];
                                    $buttons		= '';
                                    foreach($this->forms->formData->actions as $action){
                                        if($action == 'archive' && $this->showArchived == 'true' && $this->forms->submissions->archived){
                                            $action = 'unarchive';
                                        }
                                        $buttonsHtml[$action]	= "<button class='$action button forms-table-action' name='{$action}-action' value='$action'>".ucfirst($action)."</button>";
                                    }
                                    $buttonsHtml = apply_filters('sim_form_actions_html', $buttonsHtml, $submission, $name, $this, $this->forms->submission);
                                    
                                    //we have te html now, check for which one we have permission
                                    foreach($buttonsHtml as $action=>$button){
                                        if(
                                            $this->tableEditPermissions || 																			//if we are allowed to do all actions
                                            $submission['user-id'] == $this->user->ID || 															//or this is our own entry
                                            array_intersect($this->userRoles, (array)$this->forms->columnSettings[$action]['edit-right-roles'])		//or we have permission for this specific button
                                        ){
                                            $buttons .= $button;
                                        }
                                    }
                                    if(!empty($buttons)){
                                        echo "<tr class='actions' data-id='{$submission['id']}'>";
                                            echo "<td  colspan='2'>$buttons</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
            <?php
        }

        return ob_get_clean();
    }

    /**
     * Retrieve the subject data
     * @param   bool    $force      Do not send cacheg data, default false
     */
    public function getSubjectData($force = false){
        if(!empty($this->bookingElements) && !$force){
            return $this->bookingElements;
        }

        $this->bookingElements   = $this->forms->getElementByType('booking-selector');

        if(!$this->bookingElements || is_wp_error($this->bookingElements)){
            $this->bookingElements  = [];
            return;
        }

        foreach($this->bookingElements as $element){
            $element->booking_details = maybe_unserialize($element->booking_details);

            if(!isset($element->booking_details['subjects'])){
                return new WP_Error('no subject', 'Please add one or more booking subjects');
            }
        }

        return $this->bookingElements;
    }

    /**
     * Check if a booking overlaps another booking
     *
     * @param   int     $startDate      The startdate epoch of a booking
     * @param   int     $endDate        The enddate epoch of a booking
     * @param   string  $subject        The subject  of a booking
     * @param   int     $id             An booking id to ignore to check exclude the the booking itself
     */
    public function checkOverlap($startDate, $endDate, $subject, $room, $id=-1){
        global $wpdb;

        // First check if a booking on these dates doesn't exist
        $query	    = "SELECT * FROM $this->tableName WHERE pending=0 AND subject = '$subject' AND room = '$room' AND ('$startDate' BETWEEN startdate and enddate OR '$endDate' BETWEEN startdate and enddate)";

        if($id != -1){
            $query  .= " AND NOT id=$id";
        }
        
        //sort on startdate
		$query	            .= " ORDER BY `startdate`, `starttime` ASC";

		$bookings           = $wpdb->get_results($query);

        $overlap            = false;

        $bookingEls         = $this->getSubjectData();

        if(is_wp_error($bookingEls)){
            return $bookingEls;
        }

        $bookingDetails     = $bookingEls[0]->booking_details;
        if(!empty($bookingDetails) && is_array($bookingDetails['subjects'])){
            foreach($bookingDetails['subjects'] as $detail){
                if(
                    $detail['name'] == $subject && 
                    !empty($detail['overlap']) && 
                    $detail['overlap'] == 'yes'
                ){
                    $overlap    = true;
                }
            }
        }

        // start and enddate may overlap so remove any of those
        if($overlap){
            foreach($bookings as $index=>$booking){
                // this booking ends on the first day of the booking we are checking
                if($booking->enddate == $startDate){
                    unset($bookings[$index]);
                }

                // this booking starts on the last day of the booking we are checking
                if($booking->startdate == $endDate){
                    unset($bookings[$index]);
                }
            }
        }

        return $bookings;
    }

    /**
     * Checks wheter a booking to be inserted should be a pending booking
     *
     * @param   int     $user       the user or userId of the person for who the booking is done
     * 
     * @return  bool                true if is should be pending, false otherwise
     */
    public function checkPending($user, $subject){
        $els = $this->getSubjectData();
        if(!$els){
            return true;
        }

        foreach($els[0]->booking_details['subjects'] as $subjectSettings){
            if(!str_contains($subject, $subjectSettings['name'])){
                continue;
            }

            if(isset($subjectSettings['default_booking_state']) && $subjectSettings['default_booking_state'] == 'pending'){
                SIM\cleanUpNestedArray($subjectSettings['confirmed_booking_roles']);

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
     * @param   string      $startdate      The startdate string
     * @param   string      $enddate        The enddate string
     * @param   string      $subject        The subject the booking is for
     * @param   string      $room           The room the booking is for
     * @param   int         $submissionId   The form submission id
     */
    public function insertBooking($startDate, $endDate, $subject, $room, $submissionId){
        global $wpdb;

        $overlappingBookings    = $this->checkOverlap($startDate, $endDate, $subject, $room);
		if(!empty($overlappingBookings)){
            if(!empty($room)){
                $subject    .= " room $room";
            }

            $startDateString    = date(DATEFORMAT, strtotime($overlappingBookings[0]->startdate));
            $endDateString      = date(DATEFORMAT, strtotime($overlappingBookings[0]->enddate));
            return new \WP_Error('booking', "The booking for $subject overlaps with an existing one from $startDateString till $endDateString, try again");
        }

        $userIdKey	        = $this->forms->findUserIdElementName();

        $userId             = $this->forms->submission->formresults[$userIdKey];

        $subjectWithRoom    = $subject;
        if(!empty($room)){
            $subjectWithRoom    = "$subject room $room";
        }

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
            $event['startdate']				= $startDate;
            $event['starttime']				= '14:00';
            $event['enddate']				= $endDate;
            $event['endtime']				= '12:00';
            $event['location']				= $subjectWithRoom;
            $event['organizer-id']			= $userId;
            $event['onlyfor']               = $userId;
            update_post_meta($eventId, 'eventdetails', json_encode($event));
            update_post_meta($eventId, 'onlyfor', $userId);
        }

        // Determine the pending state
        $pending    = $this->checkPending($userId, $subject);

        // Insert booking in db
        $wpdb->insert(
            $this->tableName,
            array(
                'startdate'			=> $startDate,
                'enddate'			=> $endDate,
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

		$bookingId   = $wpdb->insert_id;
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
        if(!isset($values['startdate']) && !isset($values['enddate'])){
            return true;
        }

        $startdate      = $booking->startdate;
        
        // Start date is updated
        if(isset($values['startdate'])){
            $startdate  = &$values['startdate'];

            // get the relevant date
            if(is_array($startdate)){
                if(!empty($_POST['subid']) && isset($startdate[$_POST['subid']])){
                    $startdate  = $startdate[$_POST['subid']];
                }else{
                    $startdate  = array_values($startdate)[0];
                }
            }
        }

        $enddate      = $booking->enddate;

        // End date is updated
        if(isset($values['enddate'])){
            $enddate  = &$values['enddate'];

            // get the relevant date
            if(is_array($enddate)){
                if(!empty($_POST['subid']) && isset($enddate[$_POST['subid']])){
                    $enddate  = $enddate[$_POST['subid']];
                }else{
                    $enddate  = array_values($enddate)[0];
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
        
        $overlappingBookings    = $this->checkOverlap($startdate, $enddate, $subject, $room, $booking->id);
		if(!empty($overlappingBookings)){
            if(!empty($room)){
                $subject    .= " room $room";
            }

            $startDateString    = date(DATEFORMAT, strtotime($overlappingBookings[0]->startdate));
            $endDateString      = date(DATEFORMAT, strtotime($overlappingBookings[0]->enddate));
            return new \WP_Error('booking', "The booking for $subject overlaps with an existing one from $startDateString till $endDateString, try again");
        }

        return true;
    }

    /**
     * Update the formresults of a booking
     *
     * @param   object          $booking    The booking to update
     * @param   array           $values     Values array
     */
    protected function updateBookingSubmission($booking, $values){
        global $wpdb;

        // update submission
        if(empty($this->forms->submission)){
            $this->forms->submission = $this->forms->getSubmission($booking->submission_id);
        }

        $formResults    = &$this->forms->submission->formresults;

        $shouldUpdate   = false;
        foreach($values as $key => $value){
            // We updated a booking value
            if(!isset($formResults[$key]) && isset($formResults['booking-'.$key])){
                $key    = 'booking-'.$key;
            }

            if(isset($formResults[$key])){
                // We are updating a subid
                if(
                    is_array($formResults[$key]) &&                 // the target formresult is an array
                    !empty($_POST['subid']) &&                      // We have submitted an subid
                    isset($formResults[$key][$_POST['subid']])      // and the sub id is an index of the array
                ){
                    // It is an updated value
                    if($formResults[$key][$_POST['subid']] != $value){   
                        $shouldUpdate   = true;
                        $formResults[$key][$_POST['subid']] = $value;
                    }
                }

                // value needs to be updated in the db
                elseif($formResults[$key] != $value){
                    $shouldUpdate        = true;
                    $formResults[$key]   = $value;
                }
            }
        }

        if($shouldUpdate){
            $this->updateSubmissionData();
        }
    }

    /**
     * Updates the submission data of a booking
     */
    public function updateSubmissionData(){
        global $wpdb;

        $wpdb->update(
            $this->forms->submissionTableName,
            [
                'formresults'   => serialize($this->forms->submission->formresults)
            ],
            array(
                'id'		    => $this->forms->submission->id
            ),
        );

        if(empty($wpdb->last_error)){
            return true;
        }else{
            return $wpdb->last_error;
        }
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
        $values         = array_filter( $values, function($val){return in_array($val, ['startdate', 'enddate', 'starttime', 'endtime', 'subject', 'room', 'pending', 'paid']);}, ARRAY_FILTER_USE_KEY);

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

        // booking submission contains possibly the data of multiple bookings so make sure we use the original data
        $this->updateBookingSubmission($booking, $values);

        // update event
        $event                          = json_decode(get_post_meta($booking->event_id, 'eventdetails', true), true);
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
        $start    = (new \DateTime($booking->startdate))->modify('first day of this month');
        $end      = (new \DateTime($booking->enddate))->modify('first day of next month');
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
        $events = new SIM\EVENTS\CreateEvents();
        $events->removeDbRows($booking->event_id, true);

        // Remove the booking
        $wpdb->delete(
			$this->tableName,
			['id' => $booking->id],
			['%d'],
		);
    }

    /**
     * Retrieve the bookings for a certain month
     *
     * @param   int     $month          The month to retrieve bookings for
     * @param   int     $year           The year to retrieve bookings for
     * @param   string  $subject        The subject to retrieve bookings for
     * @param   int     $extraDays      Extra days to block after each booking, default 0
     *
     */
    protected function retrieveMonthBookings($month, $year, $subject, $room, $extraDays=0){
        global $wpdb;

        if(is_array($room)){
            $room   = $room['name'];
        }

		//select all bookings of this month
        $startDate  = "$year-$month-01";
        $endDate    = date("Y-m-t", strtotime($startDate));
		$query	    = "SELECT * FROM $this->tableName WHERE (`startdate` >= '$startDate' OR '$startDate' BETWEEN startdate and enddate) AND `startdate` <= '$endDate' AND subject = '$subject' AND room = '$room'";

        //sort on startdate
		$query	.= " ORDER BY `startdate`, `starttime` ASC";

        $result             = $wpdb->get_results($query);
		$this->bookings 	=  array_merge($this->bookings, $result);

        $this->unavailable  = [];

        foreach($result as $booking){

            $current    = strtotime($booking->startdate);
            $last       = strtotime($booking->enddate);

            if($extraDays > 0){
                $last   = strtotime("+$extraDays days", $last);
            }

            while( $current <= $last ) {
                $this->unavailable[date('Y-m-d', $current)] = $booking->id;
                $current                = strtotime('+1 day', $current);
            }
        }
    }

    /**
     * Retrieve all the pending bookings for the current user
     *
     */
    public function retrievePendingBookings(){
        global $wpdb;

        $query	    = "SELECT * FROM $this->tableName WHERE pending=1 AND startdate >= '".date('Y-m-d')."'";

        $this->getSubjectManagers($this->user->ID);

        if(empty($this->managers)){
            return [];
        }

        foreach(array_keys($this->managers) as $index => $subject){
            if($index == 0){
                $query	.= " AND (";
            }else{
                $query	.= " OR";
            }

            $query	.= " subject LIKE '%$subject%'";
        }

        //sort on startdate
		$query	.= ") ORDER BY id ASC";

		return $wpdb->get_results($query);
    }

    /**
     * Retrieve all the unpaid bookings
     *
     * @param   bool    $onlyFinished       True to only return bookings that are finished 
     * @param   bool    $all                Whether to get unpaid bookings for the current user only. Default true;
     */
    public function retrieveUnPaidBookings($onlyFinished, $all=false){
        global $wpdb;

        $query	    = "SELECT * FROM $this->tableName WHERE (`paid` IS NULL OR `paid` = 0)";

        // only show finished bookings
        if($onlyFinished){
            $query	.= " AND enddate < '".date('Y-m-d')."'";
        }

        if($all){
            $userId = '';
        }else{
            $userId = $this->user->ID;
        }

        $this->getSubjectManagers($userId, true);

        if(empty($this->payables)){
            return [];
        }

        foreach($this->payables as $index => $subject){
            if($index == 0){
                $query	.= " AND (";
            }else{
                $query	.= " OR";
            }

            $query	.= " subject LIKE '%$subject%'";
        }

        //sort on startdate
		$query	.= ") ORDER BY id ASC";

		return $wpdb->get_results($query);
    }

    /**
     * Retrieve all the bookings of a certain startdate
     *
     *  @param  string|int  $date   The date in 'Y-m-d' format or unix timestamp
     */
    public function retrieveBookingsByEndDate($date){
        global $wpdb;

        if(is_numeric($date)){
            $date   = date('Y-m-d', $date);
        }

        $query	    = "SELECT * FROM $this->tableName WHERE enddate = '$date'";

		return $wpdb->get_results($query);
    }

    /**
     * Retrieve all the bookings of a certain startdate
     *
     *  @param  string|int  $date   The date in 'Y-m-d' format or unix timestamp
     */
    public function retrieveBookingsByStartDate($date){
        global $wpdb;

        if(is_numeric($date)){
            $date   = date('Y-m-d', $date);
        }

        $query	    = "SELECT * FROM $this->tableName WHERE startdate = '$date'";

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

		$query	    = "SELECT * FROM $this->tableName WHERE submission_id=$id";

        $results    = $wpdb->get_results($query);
        if(!empty($results)){
            return $results;
        }
		
        return false;
    }

    /**
     * Sends a reminder to the owner of a booking and to the managers
     */
    public function sendBookingEmails(){
        $this->forms->getForms();

        foreach($this->forms->forms as $form){

            $this->forms->formId    = $form->id;

            $this->forms->formData  = $form;

            $this->getSubjectData(true);

            if(empty($this->bookingElements)){
                continue;
            }

            $subjectKey = $this->bookingElements[0]->name;

            $emails     = $form->emails;
		
            foreach($emails as $mail){
                if($mail['emailtrigger'] == 'before-stay' || $mail['emailtrigger'] == 'after-stay'){
                    if($mail['emailtrigger'] == 'before-stay'){
                        $date       = date('Y-m-d', strtotime("+{$mail['days-before']} days", time()));
                        $bookings   = $this->retrieveBookingsByStartDate($date);
                    }

                    elseif($mail['emailtrigger'] == 'after-stay'){
                        $date       = date('Y-m-d', strtotime("-{$mail['days-after']} days", time()));
                        $bookings   = $this->retrieveBookingsByEndDate($date);
                    }

                    foreach($bookings as $booking){

                        $this->forms->getSubmission($booking->submission_id);
    
                        $from       = $this->forms->processPlaceholders($mail['from']);
        
                        $to         = $this->forms->processPlaceholders($mail['to']);
        
                        $subject    = $this->forms->processPlaceholders($mail['subject']);
        
                        $message    = $this->forms->processPlaceholders($mail['message']);
        
                        $headers	= [];
        
                        if(!empty(trim($mail['headers']))){
                            $headers	= explode("\n", trim($mail['headers']));
                        }

                        if(!empty($from)){
                            $headers[]	= "Reply-To: $from";
                        }                    

                        add_filter('wp_mail', [$this->forms, 'addFormData'], 1);
                        wp_mail($to , $subject, $message, $headers);
                        remove_filter('wp_mail', [$this->forms, 'addFormData'], 1);

                        if($mail['emailtrigger'] == 'before-stay'){
                            $this->getSubjectManagers();

                            $bookingSubject    =  $this->forms->submission->formresults[$subjectKey];

                            if(!isset($this->managers[$bookingSubject])){
                                SIM\printArray("No manager found for $bookingSubject");
                                return;
                            }

                            $managers    = (array) $this->managers[$bookingSubject];

                            foreach($managers as $manager){
                                $name       = $this->forms->submission->formresults[$this->forms->findUserNameElementName()];

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
                    isset($this->managers['onlyfor'])   &&      // the current manager list is only for a certain user
                    $this->managers['onlyfor']  == $userId      // the current list is for the current user
                ) ||
                (
                    empty($userId) &&                           // we want the generic list
                    empty($this->managers['onlyfor'] )          // the current list is generic
                )
            )
        ){
            // the current list is the list we need
            return;
        }

        if(is_numeric($userId)){
            $this->managers['onlyfor']  = $userId;
        }

        // get the booking selector element
        $els     = $this->getSubjectData();
        if(!$els || is_wp_error($els)){
            return;
        }

        $this->managers = [];
        $this->payables = [];

        // loop over all booking selector elements of this form
        foreach($els as $el){
            // find the subject
            if($el->booking_details && is_array($el->booking_details['subjects'])){
                // Loop over all subjects
                foreach($el->booking_details['subjects'] as $subject){
                    $managerIds  = $subject['managers'];

                    // loop over all the managers of this subject
                    foreach($managerIds as $managerId){

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

                        if($subject['payments']){
                            $this->payables[]   = $subject['name'];
                        }
                    }
                }
            }
        }
    }

    /**
     * Sends a reminder to the owner of a booking to pay for it
     */
    public function sendPaymentReminders(){
        // no form loaded, load them all, and send payment reminder for each of them
        if(empty($this->forms->formData)){
            $this->forms->getForms();

            // Send payment reminder for each form
            foreach($this->forms->forms as $form){
                $this->forms->getForm($form->id);

                $result = $this->getSubjectData(true);

                // this form has booking selector in it
                if(!is_wp_error($result) && !empty($result)){
                    $this->sendPaymentReminders();
                }
            }

            return;
        }

        $processed  =   [];
        foreach($this->retrieveUnPaidBookings(true, true) as $booking){

            // no subject set or this form submission is already processed
            if(empty($booking->subject) || in_array($booking->submission_id, $processed)){
                continue;
            }

            $submission = $this->forms->getSubmission($booking->submission_id);

            if(!$submission){
                continue;
            }

            $processed[]    = $booking->submission_id;

            // Load the form
            $this->forms->getForm($submission->form_id);

            $el             = $this->getSubjectData()[0];

            $accommodation  = $booking->subject;

            // check if payment is enabled for this subject
            foreach($el->booking_details['subjects'] as $subject){
                // this is the current subject
                if($subject['name'] == $accommodation){
                    if(!$subject['payments']){
                        // do not continue if disabled
                        continue 2;
                    }

                    break;
                }
            }

            $userIdElName    = $this->forms->findUserIdElementName();
            if(is_wp_error($userIdElName)){
                continue;
            }

            if($userIdElName){
                $userId     = $submission->formresults[$userIdElName];
            }else{
                $userId     = $submission->user-id;
            }

            $phonenumber    = $userId;
            $email          = false;
            
            // Not an user
            if(!is_numeric($userId)){
                $user       = '';

                $nameElName = $this->forms->findUserNameElementName();
                if($nameElName){
                    $name   = $submission->formresults[$nameElName];
                    $user   = (object) ['display_name' => $name ];
                }

                // Find the phone number
                $phoneElName    = $this->forms->findPhoneNumberElementName();

                if($phoneElName){
                    foreach($submission->formresults[$phoneElName] as $number){
                        if (str_starts_with($number, '+')) {
                            $phonenumber = $number;
                            break;
                        }
                    }
                }

                // Find the e-mail
                $emailElName        = $this->forms->findEmailElementName();
                if($emailElName){
                    $email          = $submission->formresults[$emailElName];
                }
            }else{
                $user   = get_user($userId);
                $email  = $user->user_email;
            }

            if(apply_filters('sim-bookings-should-not-send-payment-reminder', false, $submission, $user, $email, $this)){
                continue;
            }

            // Send an e-mail
            $bookingEmail    = new BookingEmail($booking);
            $bookingEmail->filterMail();
                
            $subject        = $bookingEmail->subject;
            $message        = $bookingEmail->message;
            $headers        = $bookingEmail->headers;

            if(!$email){
                continue;
            }

            add_filter('wp_mail', [$this->forms, 'addFormData'], 1);
            wp_mail( $email, $subject, $message, $headers);
            remove_filter('wp_mail', [$this->forms, 'addFormData'], 1);
        }
    }

    /**
     * Adds the buttons to approve or delete a pending booking
     */
    public function pendingButtons($buttonsHtml, $values, $subId, $object){
        $buttonsHtml['approve'] = "<button class='button approve' type='button' data-id='{$values['id']}' data-form-id='{$object->submission->form_id}'>Approve</button>";
        $buttonsHtml['delete']  = "<button class='button delete' type='button' data-id='{$values['id']}' data-form-id='{$object->submission->form_id}'>Delete</button><br>";
        unset($buttonsHtml['archive']);

        return $buttonsHtml;
    }

    /**
     * shows the html to list, approve and or delete pending bookings
     * 
     * @param   string  $type       One of approval or payment to show bookings that are pending approval or pending payment
     */
    public function pendingBookingsHtml($type='approval'){
        if($type == 'approval'){
            $bookings    = $this->retrievePendingBookings();
        }else{
            $bookings    = $this->retrieveUnPaidBookings(true);
        }

        if(empty($bookings)){
            return '';
        }

        $html   = "<h4>Bookings Pending ".ucfirst($type)."</h4>";

        $submissions    = [];

        // Add a sub id to bookings which is equal to the booked room
        foreach($bookings as $booking){
            // one submission can have multiple bookings, only load the submission once
            if(empty($submission) || $submission->id != $booking->submission_id){
                $submission         = $this->forms->getSubmission($booking->submission_id);   
            }

            if(!empty($booking->room)){
                $submission->subId  = $booking->room;
            }

            $submissions[]                                  = clone $submission;
        }

        if(empty($submissions)){
            return '';
        }

        if($type == 'approval'){
            add_filter('sim_form_actions_html', [$this, 'pendingButtons'], 10, 4);
        }

        ob_start();

        $this->forms->theTable('all', $submissions);

        $html   .= ob_get_clean();

        if($type == 'approval'){
            remove_filter('sim_form_actions_html',  [$this, 'pendingButtons'], 10);
        }

        return $html;
    }

    /**
     * Calculate the total amount due after booking update
     */
    public function calculatePaymentAmount(){
        $pricePerNightEl    = $this->forms->formData->price_per_night_el;
        $pricePerNightName  = $this->forms->getElementById($pricePerNightEl, 'name');

        $paymentAmountEl    = $this->forms->formData->payment_amount_el;
        $paymentAmountName  = $this->forms->getElementById($paymentAmountEl, 'name');

        if(empty($pricePerNightEl) || empty($paymentAmountEl)){
            return;
        }

        if(!is_array($this->forms->submission->formresults['booking-startdate'])){
            $this->forms->submission->formresults['booking-startdate']  = [$this->forms->submission->formresults['booking-startdate']];
        }

        if(!is_array($this->forms->submission->formresults['booking-enddate'])){
            $this->forms->submission->formresults['booking-enddate']  = [$this->forms->submission->formresults['booking-enddate']];
        }

        $nights = 0;
        foreach($this->forms->submission->formresults['booking-startdate'] as $index=>$startDate){
            if(empty($this->forms->submission->formresults['booking-enddate'][$index])){
                $endDate    = array_values($this->forms->submission->formresults['booking-enddate'])[0]; // assume the end date is the same as the first given one
            }else{
                $endDate    = $this->forms->submission->formresults['booking-enddate'][$index];
            }
            $diff       = strtotime($endDate) - strtotime($startDate);

            $days       = round($diff / (60 * 60 * 24));

            $nights     = $nights + $days;
        }

        $pricePerNight      = $this->forms->submission->formresults[$pricePerNightName];
        preg_match('/(.*?)([\d+|\.|,]+)/', "$pricePerNight", $matches);
        $amount             = $matches[2];
        $currency           = $matches[1];

        // Formatted number
        $payable            = $currency.number_format(intval(str_replace(",","",$amount)) * $nights, 2);

        $this->forms->submission->formresults[$paymentAmountName] = $payable;

        // Update in db
        $this->updateSubmissionData();

        return $payable;
    }
}
