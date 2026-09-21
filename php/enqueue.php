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
    $deps   = SCRIPT_DEBUG ? [
        '@tsjippy/form_submit_functions', 
        "@tsjippy/form_exports", 
        "@tsjippy/show_loader", 
        "@tsjippy/display_message", 
        "@tsjippy/modals"
    ] :
    [];

    wp_register_script_module('@tsjippy/bookings', TSJIPPY\pathToUrl(PLUGINPATH . 'js/bookings' . TSJIPPY\JSEXTENSION), $deps, PLUGINVERSION);
}
