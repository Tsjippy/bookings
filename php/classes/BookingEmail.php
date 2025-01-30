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

        $this->replaceArray['%first_name%']     = $user->first_name;   
        $this->replaceArray['%id%']             = $booking->id;  
        $this->replaceArray['%accomodation%']   = $booking->accomodation;
        $this->replaceArray['%startdate%']      = $booking->startdate; 
        $this->replaceArray['%enddate%']        = $booking->enddate; 
        $this->replaceArray['%payable%']        = $booking->payable; 

        $this->defaultSubject    = "Please pay for your booking with id %id%";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "Our records show you have not yet paid the amount of %payable% for your booking of %accomodation% from %startdate% till %enddate%.<br>";
		$this->defaultMessage 	.= 'Please do so immidiately.';
    }
}