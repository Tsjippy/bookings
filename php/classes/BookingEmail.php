<?php
namespace SIM\BOOKINGS;
use SIM;
use SIM\ADMIN;

class BookingEmail extends ADMIN\MailSetting{

    public $user;

    public function __construct($user, object $booking) {
        // call parent constructor
		parent::__construct('payment_reminder', MODULE_SLUG);

        $this->addUser($user);

        if(isset($booking->submission_id)){
            $formSubmissionId                       = $booking->submission_id;
            $displayFormResults                     = new SIM\FORMS\DisplayFormResults();
            $submission                             = $displayFormResults->getSubmissions(null, $formSubmissionId)[0];

            // Load the formdata for this form
            $displayFormResults->getForm($submission->form_id);

            $amountName                             = $displayFormResults->getElementById('payment_amount_el');
            if($amountName){
                $this->replaceArray['%payable%']            = $submission[$amountName];
            }
            $detailsName                            = $displayFormResults->getElementById('payment_details_el');
            if($detailsName){
                $this->replaceArray['%payment_details%']    = $submission[$detailsName];
            }
        }

        $this->replaceArray['%first_name%']     = $user->first_name;   
        $this->replaceArray['%id%']             = $booking->id;  
        $this->replaceArray['%accomodation%']   = $booking->accomodation;
        $this->replaceArray['%startdate%']      = $booking->startdate; 
        $this->replaceArray['%enddate%']        = $booking->enddate; 

        $this->defaultSubject    = "Please pay for your booking with id %id%";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "Our records show you have not yet paid the amount of %payable% for your booking of %accomodation% from %startdate% till %enddate%.<br>";
		$this->defaultMessage 	.= 'Please do so immidiately.<br>';
        $this->defaultMessage 	.= '<b>Payment Details</b><br>';
        $this->defaultMessage 	.= '<b>%payment_details%<br>';
        
    }
}