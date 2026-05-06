<?php
namespace TSJIPPY\BOOKINGS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

class AfterUpdate extends TSJIPPY\AfterPluginUpdate {

    public function afterPluginUpdate($oldVersion){
        global $wpdb;

        TSJIPPY\printArray('Running update actions');

        if(version_compare('10.0.5', $oldVersion)){
            /**
             * Rename tables to tsjippy_
             */
            $wpdb->query(
                "ALTER TABLE `{$wpdb->prefix}tsjippy_bookings`
                RENAME COLUMN `startdate` to `start_date`,
                RENAME COLUMN `enddate` to `end_date`,
                RENAME COLUMN `starttime` to `start_time`,
                RENAME COLUMN `endtime` to `end_time`;"
            );
        }
    }
}
