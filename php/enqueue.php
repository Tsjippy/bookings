<?php
namespace SIM\BOOKINGS;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    wp_register_style( 'sim_bookings_style', plugins_url('css/bookings.min.css', __DIR__), array(), MODULE_VERSION);
    wp_register_script( 'sim-bookings', plugins_url('js/bookings.min.js', __DIR__), array('sim_formsubmit_script'), MODULE_VERSION, true);
});