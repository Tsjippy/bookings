<?php
namespace SIM\BOOKINGS;
use SIM;

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\loadAssets');
function loadAssets(){
    wp_register_style( 'sim_bookings_style', SIM\pathToUrl(MODULE_PATH.'css/bookings.min.css'), array(), MODULE_VERSION);
    wp_register_script( 'sim-bookings', SIM\pathToUrl(MODULE_PATH.'js/bookings.min.js'), array('sim_formsubmit_script'), MODULE_VERSION, true);
}