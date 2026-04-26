<?php
namespace TSJIPPY\BOOKINGS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\loadAssets');
function loadAssets(){
    wp_register_script( 'tsjippy-bookings', TSJIPPY\pathToUrl(PLUGINPATH.'js/bookings.min.js'), array('tsjippy_formsubmit_script'), PLUGINVERSION, true);
}