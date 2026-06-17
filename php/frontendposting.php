<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('tsjippy-frontend-content-post-content-title', __NAMESPACE__ . '\contentTitle');
/**
 * Sets the title for the booking subject content
 *
 * @param string $postType The post type
 */
function contentTitle($postType)
{
    // Book content title
    $class = 'property booking-subject';
    if ($postType != 'booking-subject') {
        $class .= ' hidden';
    }

?>
    <h4 class='<?php echo esc_attr($class); ?>' name='location-content-label'>
        Please describe the location
    </h4>
<?php
}

add_filter('tsjippy-frontend-content-posttype', __NAMESPACE__ . '\filterPostType');
/**
 * Filters the post type for frontend content
 *
 * @param string $postType The post type
 *
 * @return string The filtered post type
 */
function filterPostType($postType)
{
    if ($postType == 'booking-subject' || $postType == 'booking-room') {
        return 'page';
    }
    return $postType;
}

add_filter('tsjippy-frontend-post-types-and-tax', __NAMESPACE__ . '\filterPostTypes');
/**
 * Filters the post types for frontend content
 *
 * @param array $postTypes The post types
 *
 * @return array The filtered post types
 */
function filterPostTypes($postTypes)
{
    unset($postTypes['booking-subject']);
    unset($postTypes['booking-room']);

    return $postTypes;
}
