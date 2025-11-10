<?php
namespace SIM\BOOKINGS;
use SIM;
use SIM\ADMIN;

class BookingEmail extends ADMIN\MailSetting{

    public function __construct(object $booking) {
        // call parent constructor
		parent::__construct('payment-reminder', MODULE_SLUG);

        $this->replaceArray['%id%']                         = $booking->id;  
        $this->replaceArray['%subject%']                    = $booking->subject;
        $this->replaceArray['%duration%']                   = "from ".date(DATEFORMAT, strtotime($booking->startdate))." till ".date(DATEFORMAT, strtotime($booking->enddate));
        $this->replaceArray['%payable%']                    = '';
        $this->replaceArray['%payment_details%']            = '';
        $this->replaceArray['%price_per_night%']            = '';

        if(isset($booking->submission_id)){
            $displayFormResults                             = new SIM\FORMS\DisplayFormResults([]);
            $displayFormResults->getSubmission($booking->submission_id);

            if(!$displayFormResults->submission){
                return;
            }

            // Load the formdata for this form
            $displayFormResults->getForm($displayFormResults->submission->form_id);
            $booker = new Bookings($displayFormResults);

            $bookings   = $booker->getBookingsBySubmission($booking->submission_id);

            $startDates = [];
            $endDates   = [];
            $rooms      = [];

            // Store the dates
            foreach($bookings as $booking){
                $startDates[]   = $booking->startdate;
                $endDates[]     = $booking->enddate;
                $rooms[]        = $booking->room;
            }

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
                $this->replaceArray['%name%']               = $displayFormResults->submission->$name;
            }

            $amountName                                     = $displayFormResults->getElementById($displayFormResults->formData->payment_amount_el, 'name');
            if($amountName){
                $this->replaceArray['%payable%']            = $displayFormResults->submission->$amountName;
            }

            $detailsName                                    = $displayFormResults->getElementById($displayFormResults->formData->payment_details_el, 'name');
            if($detailsName){
                $this->replaceArray['%payment_details%']    = $displayFormResults->submission->$detailsName;
            }

            $pricePerNightName                              = $displayFormResults->getElementById($displayFormResults->formData->price_per_night_el, 'name');
            if($pricePerNightName){
                $this->replaceArray['%price_per_night%']    = $displayFormResults->submission->{$pricePerNightName};
            }
        }

        $this->defaultSubject    = "Please pay for your booking with id %id%";

        $this->defaultMessage    = 'Hi %name%,<br><br>';
		$this->defaultMessage   .= "Our records show you have not yet paid the amount of %payable% for your booking of %subject% from %startdate% till %enddate%.<br>";
		$this->defaultMessage 	.= 'Please do so immidiately.<br>';
        $this->defaultMessage 	.= '<b>Payment Details</b><br>';
        $this->defaultMessage 	.= '<b>%payment_details%<br>';
        
    }
}