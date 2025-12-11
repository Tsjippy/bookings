<?php
namespace SIM\BOOKINGS;
use SIM;
use SIM\ADMIN;

class BookingEmail extends ADMIN\MailSetting{
    public $booking;

    public function __construct(object $booking) {
        $this->booking    = $booking;

        // call parent constructor
		parent::__construct('payment-reminder', MODULE_SLUG);

        $this->replaceArray['%id%']                         = $this->booking->id;  
        $this->replaceArray['%subject%']                    = $this->booking->subject;
        $this->replaceArray['%duration%']                   = "from ".date(DATEFORMAT, strtotime($this->booking->startdate))." till ".date(DATEFORMAT, strtotime($this->booking->enddate));
        $this->replaceArray['%payable%']                    = '';
        $this->replaceArray['%payment_details%']            = '';
        $this->replaceArray['%price_per_night%']            = '';

        $this->loadBookings();

        $this->defaultSubject    = "Please pay for your booking with id %id%";

        $this->defaultMessage    = 'Hi %name%,<br><br>';
		$this->defaultMessage   .= "Our records show you have not yet paid the amount of %payable% for your booking of %subject% from %startdate% till %enddate%.<br>";
		$this->defaultMessage 	.= 'Please do so immidiately.<br>';
        $this->defaultMessage 	.= '<b>Payment Details</b><br>';
        $this->defaultMessage 	.= '<b>%payment_details%<br>';
        
    }

    public function loadBookings(){
        if(!isset($this->booking->submission_id)){
            return;
        }

        $displayFormResults                             = new SIM\FORMS\DisplayFormResults([]);
        $displayFormResults->parseSubmissions('', $this->booking->submission_id);

        if(!$displayFormResults->submission){
            return;
        }

        // Load the formdata for this form
        $displayFormResults->getForm($displayFormResults->submission->form_id);
        $booker = new Bookings($displayFormResults);

        $bookings   = $booker->getBookingsBySubmission($this->booking->submission_id);

        $startDates = [];
        $endDates   = [];
        $rooms      = [];

        // Store the dates
        foreach($bookings as $booking){
            $startDates[]   = $this->booking->startdate;
            $endDates[]     = $this->booking->enddate;
            $rooms[]        = $this->booking->room;
        }

        $this->replaceArray['%startdate%']  = date(DATEFORMAT, strtotime($startDates[0]));

        $this->replaceArray['%enddate%']    = date(DATEFORMAT, strtotime($endDates[0]));

        $this->replaceArray['%rooms%']      = $rooms[0];

        // Add rooms
        if(!empty($rooms)){
            if(count($rooms) == 1){
                $this->replaceArray['%subject%']   .= " room ". array_values($rooms)[0];
            }else{
                $rooms  = implode('&', $rooms);
                $this->replaceArray['%subject%']   .= " rooms $rooms";
            }
        }
        
        // only change the duration string if more than one unique startdate or enddate
        if(count(array_unique($startDates)) > 1 || count(array_unique($endDates)) > 1){
            $this->replaceArray['%duration%']   = '';
            foreach($startDates as $room => $d){
                $startDate  = date(DATEFORMAT, strtotime($d));
                $endDate    = date(DATEFORMAT, strtotime($endDates[$room]));

                if(!empty($this->replaceArray['%duration%'])){
                    $this->replaceArray['%duration%']   .= " and ";
                }
                $this->replaceArray['%duration%']   .= "from $startDate till $endDate (room $room)";
            }
        }

        $name                                           = $displayFormResults->findUserNameElementName();
        if($name){
            $elementId                                  = $displayFormResults->getElementByName($name, 'id');
            $this->replaceArray['%name%']               = $displayFormResults->submission->{$elementId};
        }

        $this->replaceArray['%payable%']                = $displayFormResults->submission->{$displayFormResults->formData->payment_amount_el};

        $this->replaceArray['%payment_details%']        = $displayFormResults->submission->{$displayFormResults->formData->payment_details_el};

        $this->replaceArray['%price_per_night%']        = $displayFormResults->submission->{$displayFormResults->formData->price_per_night_el};
    }
}