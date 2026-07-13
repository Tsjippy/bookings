<?php

namespace TSJIPPY\BOOKINGS;

use PHP_CodeSniffer\Standards\PSR2\Sniffs\Methods\FunctionCallSignatureSniff;
use TSJIPPY;
use TSJIPPY\ADMIN;

use function TSJIPPY\addElement;
use function TSJIPPY\addRawHtml;

if (! defined('ABSPATH')) {
    exit;
}

class AdminMenu extends ADMIN\SubAdminMenu
{

    /**
     * AdminMenu constructor.
     *
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name)
    {
        parent::__construct($settings, $name);
    }

    /**
     * Add the settings page to the admin menu
     *
     * @param \DOMElement $parent The parent menu slug
     * 
     * @return bool True if the settings page was added, false otherwise
     */
    public function settings($parent)
    {
        $this->recurrenceSelector('payment-reminder-freq', $this->settings['payment-reminder-freq'] ?? '', 'How often should people be reminded to pay?', $parent);

        return true;
    }

    /**
     * Add the emails page to the admin menu
     *
     * @param string $parent The parent menu slug
     * @return bool True if the emails page was added, false otherwise
     */
    public function emails($parent)
    {
        ob_start();

        ?>
        <h4>
            Define the e-mail people get when they still need to pay for some booking(s).
        </h4>
        <?php
        $emails    = new BookingEmail(
            (object)[
                "id"            => -1,
                "subject"        => "empty",
                "start_date"    => "2000-01-01",
                "end_date"        => "2000-01-01",
                "payable"        => "$23"
            ]
        );
        $emails->printPlaceholders();
        ?>

        <h4>
            Payment Reminder E-mail
        </h4>
        <?php

        $emails->printInputs();

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    /**
     * Add the data page to the admin menu
     *
     * @param string $parent The parent menu slug
     * @return bool True if the data page was added, false otherwise
     */
    public function data($parent = '')
    {

        return false;
    }

    /**
     * Add the functions page to the admin menu
     *
     * @param string $parent The parent menu slug
     * 
     * @return bool True if the functions page was added, false otherwise
     */
    public function functions($parent)
    {

        return false;
    }
}
