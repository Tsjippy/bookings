<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_filter('tsjippy-forms-split-block-ids', function ($splitBlockIds, $instance) {
    if (!empty($instance->getBlockByType('accomodation'))) {
        $splitBlockIds[] = -102;
        $splitBlockIds[] = -103;
        $splitBlockIds[] = -104;
    }

    return $splitBlockIds;
}, 10, 2);
