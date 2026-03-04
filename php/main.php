<?php
namespace SIM\BOOKINGS;
use SIM;

add_filter('sim-forms-split-element-ids', function($splitElementIds, $instance){
    if(!empty($instance->getElementByType('booking-selector'))){
        $splitElementIds[] = -102;
        $splitElementIds[] = -103;
        $splitElementIds[] = -104;
    }  
     
    return $splitElementIds;
}, 10, 2);