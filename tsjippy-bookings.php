<?php

namespace TSJIPPY\BOOKINGS;

/**
 * Plugin Name:          Tsjippy Bookings
 * Description:          This plugin adds the possibility to book something via a form. It will display a calendar showing available dates
 * Version:              10.3.9
 * Author:               Ewald Harmsen
 * AuthorURI:            harmseninnigeria.nl
 * Requires at least:    6.3
 * Requires PHP:         8.3
 * Tested up to:         6.9
 * Plugin URI:            https://github.com/Tsjippy/bookings/
 * Tested:                6.9
 * TextDomain:            tsjippy
 * Requires Plugins:      tsjippy-forms, tsjippy-events
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if (! defined('ABSPATH')) {
    exit;
}

if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
    require_once(__DIR__  . '/shared-functionality/loader.php');
}

// Define constants
define(__NAMESPACE__ . '\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ . '\PLUGINPATH', __DIR__ . '/');
define(__NAMESPACE__ . '\PLUGINVERSION', get_plugin_data(__FILE__, false, false)['Version']);
define(__NAMESPACE__ . '\PLUGINSLUG', str_replace('tsjippy-', '', basename(__FILE__, '.php')));
define(__NAMESPACE__ . '\SETTINGS', get_option('tsjippy_' . PLUGINSLUG . '_settings', []));

// run right before activation
register_activation_hook(__FILE__, function () {
    if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
        require_once(__DIR__  . '/shared-functionality/loader.php');
    }

    // Create the table
    $bookings    = new Bookings();
    $bookings->createTables();

    // Add columns to forms element table
    $forms    = new \TSJIPPY\FORMS\Forms();

    // add columns to the forms table
    maybe_add_column($forms->tableName, 'payment_indicator', "ALTER TABLE $forms->tableName ADD COLUMN `payment_indicator` int");
    maybe_add_column($forms->tableName, 'payment_amount_el', "ALTER TABLE $forms->tableName ADD COLUMN `payment_amount_el` int");
    maybe_add_column($forms->tableName, 'payment_details_el', "ALTER TABLE $forms->tableName ADD COLUMN `payment_details_el` int");
    maybe_add_column($forms->tableName, 'price_per_night_el', "ALTER TABLE $forms->tableName ADD COLUMN `price_per_night_el` int");
    maybe_add_column($forms->tableName, 'default_booking_state', "ALTER TABLE $forms->tableName ADD COLUMN `default_booking_state` text");
    maybe_add_column($forms->tableName, 'confirmed_booking_roles', "ALTER TABLE $forms->tableName ADD COLUMN `confirmed_booking_roles` text");

    // Add column to the form element table
    //maybe_add_column($forms->elTableName, 'booking_details', "ALTER TABLE $forms->elTableName ADD COLUMN `booking_details` text");

    // Add column to the form email table
    maybe_add_column($forms->formEmailTable, 'days_before', "ALTER TABLE $forms->formEmailTable ADD COLUMN `days_before` int");
    maybe_add_column($forms->formEmailTable, 'days_after', "ALTER TABLE $forms->formEmailTable ADD COLUMN `days_after` int");
});