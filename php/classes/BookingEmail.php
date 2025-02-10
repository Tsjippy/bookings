<?php
namespace SIM\BOOKINGS;
use SIM;
use SIM\ADMIN;

class BookingEmail extends ADMIN\MailSetting{

    public function __construct(object $booking) {
        // call parent constructor
		parent::__construct('payment_reminder', MODULE_SLUG);

        $this->replaceArray['%name%']                       = '';
        $this->replaceArray['%payable%']                    = '';
        $this->replaceArray['%payment_details%']            = '';
        $this->replaceArray['%price_per_night%']            = '';

        if(isset($booking->submission_id)){
            $displayFormResults                             = new SIM\FORMS\DisplayFormResults();
            $displayFormResults->getSubmission($booking->submission_id);

            // Load the formdata for this form
            $displayFormResults->getForm($displayFormResults->submission->form_id);

            $name                                           = $displayFormResults->findUserNameElementName();
            if($name){
                $this->replaceArray['%name%']               = $displayFormResults->submission->formresults[$name];
            }

            $amountName                                     = $displayFormResults->getElementById($displayFormResults->formData->payment_amount_el, 'name');
            if($amountName){
                $this->replaceArray['%payable%']            = $displayFormResults->submission->formresults[$amountName];
            }

            $detailsName                                    = $displayFormResults->getElementById($displayFormResults->formData->payment_details_el, 'name');
            if($detailsName){
                $this->replaceArray['%payment_details%']    = $displayFormResults->submission->formresults[$detailsName];
            }

            $pricePerNightName                              = $displayFormResults->getElementById($displayFormResults->formData->price_per_night_el, 'name');
            if($pricePerNightName){
                $this->replaceArray['%price_per_night%']    = $displayFormResults->submission->formresults[$pricePerNightName];
            }
        }
  
        $this->replaceArray['%id%']             = $booking->id;  
        $this->replaceArray['%subject%']        = $booking->subject;
        if(!empty($booking->room)){
            $this->replaceArray['%subject%']   .= " room $booking->room";
        }
        $this->replaceArray['%startdate%']      = date('d-m-Y', strtotime($booking->startdate)); 
        $this->replaceArray['%enddate%']        = date('d-m-Y', strtotime($booking->enddate)); 

        $this->defaultSubject    = "Please pay for your booking with id %id%";

        $this->defaultMessage    = 'Hi %name%,<br><br>';
		$this->defaultMessage   .= "Our records show you have not yet paid the amount of %payable% for your booking of %subject% from %startdate% till %enddate%.<br>";
		$this->defaultMessage 	.= 'Please do so immidiately.<br>';
        $this->defaultMessage 	.= '<b>Payment Details</b><br>';
        $this->defaultMessage 	.= '<b>%payment_details%<br>';
        
    }
}