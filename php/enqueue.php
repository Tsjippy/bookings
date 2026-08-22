<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\loadAssets');
/**
 * Load assets for the bookings form.
 *
 * @return void
 */
function loadAssets()
{
    wp_register_script('tsjippy-bookings', TSJIPPY\pathToUrl(PLUGINPATH . 'js/bookings.min.js'), array('tsjippy_formsubmit_script'), PLUGINVERSION, true);
}
