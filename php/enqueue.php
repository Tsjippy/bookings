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
    wp_register_script_module('@tsjippy/bookings', TSJIPPY\pathToUrl(PLUGINPATH . 'js/bookings' . TSJIPPY\JSEXTENSION), array('@tsjippy/formsubmit_script'), PLUGINVERSION);
}
