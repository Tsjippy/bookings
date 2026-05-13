<?php
namespace TSJIPPY\BOOKINGS;
use TSJIPPY;
use TSJIPPY\ADMIN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookingEmail extends ADMIN\MailSetting{
    public $booking;
    public $paymentDetailsRows;

    public function __construct(object $booking) {
        $this->booking    = $booking;

        // call parent constructor
		parent::__construct('payment-reminder', 'bookings');

        $this->replaceArray['%id%']                         = $this->booking->id;  
        $this->replaceArray['%subject%']                    = $this->booking->subject;
        $this->replaceArray['%duration%']                   = "from ".gmdate(DATEFORMAT, strtotime($this->booking->start_date))." till ".gmdate(DATEFORMAT, strtotime($this->booking->end_date));
        $this->replaceArray['%payable%']                    = '';
        $this->replaceArray['%payment_details%']            = '';
        $this->replaceArray['%price_per_night%']            = '';

        $this->loadBookings();

        $this->defaultSubject    = "Please pay for your booking with id %id%";

        $this->defaultMessage    = 'Hi %name%,<br><br>';
		$this->defaultMessage   .= "Our records show you have not yet paid the amount of %payable% for your booking of %subject% from %start_date% till %end_date%.<br>";
		$this->defaultMessage 	.= 'Please do so immidiately.<br>';
        $this->defaultMessage 	.= '<h4 style="font-weight: bold;color: #bd2919;margin: 15px 5px 5px;">Payment Details:</h4>';
        $this->defaultMessage 	.= '%payment_details%<br>';   
    }

    public function loadBookings(){
        if(!isset($this->booking->submission_id)){
            return;
        }

        $displayFormResults                             = new TSJIPPY\FORMS\DisplayFormResults([]);
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
            $startDates[]   = $this->booking->start_date;
            $endDates[]     = $this->booking->end_date;

            if(!empty($this->booking->room)){
                $rooms[]        = $this->booking->room;
            }
        }

        $this->replaceArray['%start_date%']  = gmdate(DATEFORMAT, strtotime($startDates[0]));

        $this->replaceArray['%end_date%']    = gmdate(DATEFORMAT, strtotime($endDates[0]));

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
        
        // only change the duration string if more than one unique start_date or end_date
        if(count(array_unique($startDates)) > 1 || count(array_unique($endDates)) > 1){
            $this->replaceArray['%duration%']   = '';
            foreach($startDates as $room => $d){
                $startDate  = gmdate(DATEFORMAT, strtotime($d));
                $endDate    = gmdate(DATEFORMAT, strtotime($endDates[$room]));

                if(!empty($this->replaceArray['%duration%'])){
                    $this->replaceArray['%duration%']   .= " and ";
                }
                $this->replaceArray['%duration%']   .= "from $startDate till $endDate (room $room)";
            }
        }

        $name                                           = $displayFormResults->findUserNameElementName();
        if($name){
            $elementId                                  = $displayFormResults->getElementBySlug($name, 'id');
            $this->replaceArray['%name%']               = $displayFormResults->submission->{$elementId};
        }

        $this->replaceArray['%payable%']                = $displayFormResults->submission->{$displayFormResults->formData->payment_amount_el};

        $this->replaceArray['%price_per_night%']        = $displayFormResults->submission->{$displayFormResults->formData->price_per_night_el};
        
        $paymentDetails                                 = $displayFormResults->submission->{$displayFormResults->formData->payment_details_el};

        // Convert details to table
        $this->paymentDetailsRows   = explode("\n",  $paymentDetails);

        $table  = '<table border="1" style="padding: 5px;border: none;">';
            foreach($this->paymentDetailsRows  as $row){
                $cols   = explode(":", $row);
                $table  .= '<tr>';
                    $table  .= "<td style='border: none;width: 120px;'>";
                        $table  .= "<b>".trim($cols[0])."</b>";
                    $table  .= "</td>";
                    $table  .= "<td style='border: none;'>";
                        $table  .= trim($cols[1]);
                    $table  .= "</td>";
                $table  .= "</tr>";
            }
        $table  .= '</table>';

        $this->replaceArray['%payment_details%']    = $table;
    }
}