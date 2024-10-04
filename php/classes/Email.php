<?php
namespace SIM\BOOKINGS;
use SIM;
use SIM\ADMIN;

class Email extends ADMIN\MailSetting{

    public $user;

    public function __construct(object $user) {
        // call parent constructor
		parent::__construct('adult_reminder', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%first_name%']  = $user->first_name;

        $this->defaultSubject   = "Please update your personal information on the %site_name% website";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "Some of your personal information on %site_name% needs to be updated.<br>";
		$this->defaultMessage 	.= 'Please click on the items below to update the data:';
    }
}