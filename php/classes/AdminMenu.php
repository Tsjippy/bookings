<?php
namespace TSJIPPY\BOOKINGS;

use PHP_CodeSniffer\Standards\PSR2\Sniffs\Methods\FunctionCallSignatureSniff;
use TSJIPPY;
use TSJIPPY\ADMIN;

use function TSJIPPY\addElement;
use function TSJIPPY\addRawHtml;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu extends ADMIN\SubAdminMenu{

    public function __construct($settings, $name){
        parent::__construct($settings, $name);
    }

    public function settings($parent){
        $this->recurrenceSelector('payment-reminder-freq', $this->settings['payment-reminder-freq'] ?? '', 'How often should people be reminded to pay?', $parent);

        return true;
    }

    public function emails($parent){
        ob_start();

        ?>
        <h4>
            Define the e-mail people get when they still need to pay for some booking(s).
        </h4>
        <?php
        $emails    = new BookingEmail(
            (object)[
                "id"			=> -1,
                "subject"		=> "empty",
                "start_date"	=> "2000-01-01",
                "end_date"		=> "2000-01-01",
                "payable"		=> "$23"
            ]
        );
        $emails->printPlaceholders();
        ?>

        <h4>Payment Reminder E-mail</h4>
        <?php

        $emails->printInputs();

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function data($parent=''){

        return false;
    }

    public function functions($parent){

        return false;
    }

    /**
     * Schedules the tasks for this plugin
     *
    */
    public function postSettingsSave(){
        scheduleTasks();
    }
}