<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

// Make mailtracker rest api url publicy available
add_filter('tsjippy_allowed_rest_api_urls', __NAMESPACE__ . '\allowedRestApiUrls');
/**
 * Allow additional REST API URLs
 *
 * @param array $urls    The list of allowed REST API URLs
 *
 * @return array    The updated list of allowed REST API URLs
 */
function allowedRestApiUrls($urls)
{
    $urls[]    = TSJIPPY\RESTAPIPREFIX . '/bookings/get_next_month';
    $urls[]    = TSJIPPY\RESTAPIPREFIX . '/bookings/remove';
    $urls[]    = TSJIPPY\RESTAPIPREFIX . '/bookings/load_post';

    return $urls;
}

add_action('rest_api_init', __NAMESPACE__ . '\restapiInit');
function restapiInit()
{
    // Next month
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/bookings',
        '/get_next_month',
        array(
            'methods'                 => 'POST',
            'callback'                 => __NAMESPACE__ . '\getNextMonth',
            'permission_callback'     => '__return_true',                    // Allow public access
            'args'                    => array(
                'month'    => array(
                    'required'    => true,
                    'validate_callback' => function ($month) {
                        return is_numeric($month);
                    }
                ),
                'year'        => array(
                    'required'    => true,
                    'validate_callback' => function ($year) {
                        return is_numeric($year);
                    }
                ),
                'subject'        => array(
                    'required'    => true
                )
            )
        )
    );

    // Approve pending booking
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/bookings',
        '/approve',
        array(
            'methods'                 => 'POST',
            'callback'                 => __NAMESPACE__ . '\approveBooking',
            'permission_callback'     => function ($rest) {
                // Get the bookings related to this submission
                $rest->bookingsObject    = new Bookings();

                $rest->bookingsObject->forms->formData->id    = $_POST['form-id'];

                $rest->bookings           = $rest->bookingsObject->getBookingsBySubmission((int) $_POST['id']);

                // Get the subject the current user is manager of
                $rest->bookingsObject->getSubjectManagers(get_current_user_id());

                // Return true if the user is manager of the subject related to the booking
                return in_array($rest->bookings[0]->subject, array_keys($rest->bookingsObject->managers));
            },
            'args'                    => array(
                'id'    => array(
                    'required'    => true,
                    'validate_callback' => function ($bookingId) {
                        return is_numeric($bookingId);
                    }
                )
            )
        )
    );

    // Delete a booking
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/bookings',
        '/remove',
        array(
            'methods'                 => 'POST',
            'callback'                 => __NAMESPACE__ . '\removeBooking',
            'permission_callback'     => function ($request) {
                // Get the bookings related to this submission
                $bookingsObject    = new Bookings();
                $bookings       = $bookingsObject->getBookingsBySubmission((int) $_POST['id']);

                // Get the subject the current user is manager of
                $bookingsObject->getSubjectManagers(get_current_user_id());

                // Return true if the user is manager of the subject related to the booking
                return in_array($bookings[0]->subject, array_keys($bookingsObject->managers));
            },
            'args'                    => array(
                'id'    => array(
                    'required'    => true,
                    'validate_callback' => function ($bookingId) {
                        return is_numeric($bookingId);
                    }
                )
            )
        )
    );

    // Load room and subject pages
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/bookings',
        '/load_post',
        array(
            'methods'                 => 'POST',
            'callback'                 => __NAMESPACE__ . '\loadPost',
            'permission_callback'     => '__return_true',                    // Allow public access
            'args'                    => array(
                'post-id'    => array(
                    'required'    => true,
                    'validate_callback' => function ($postId) {
                        return is_numeric($postId);
                    }
                )
            )
        )
    );
}


function getNextMonth()
{
    $bookings    = new Bookings();

    $bookings->forms->getForm((int) $_POST['form-id']);

    $bookings->forms->shortcodeId        = (int) $_POST['shortcode-id'];

    if (isset($_POST['element-id']) && is_numeric($_POST['element-id'])) {
        $element                        = $bookings->forms->getElementById((int) $_POST['element-id']);
    } else {
        foreach ($bookings->forms->formElements as $element) {
            if ($element->type == 'booking-selector') {
                break;
            }
        }
    }
    $bookings->forms->currentElement    = $element;

    $subjectName    = sanitize_text_field(wp_unslash($_POST['subject']));
    $date            = strtotime((int)$_POST['year'] . '-' . (int)$_POST['month'] . '-01');

    $months            = [];
    foreach ($bookings->getElementSubjects($element->id) as $subject) {
        if ($subject['name'] == $subjectName) {
            if ($subject['amount'] > 1) {
                if (isset($subject['nrtype']) && $subject['nrtype'] == 'letters') {
                    $alphabet = range('A', 'Z');
                    for ($x = 0; $x < $subject['amount']; $x++) {
                        $months[]    = $bookings->monthCalendar($subject['name'], $alphabet[$x], $date);
                    }
                } elseif (isset($subject['nrtype']) && $subject['nrtype'] == 'custom') {
                    foreach ($subject['rooms'] as $room) {
                        $months[]    = $bookings->monthCalendar($subject['name'], $room, $date);
                    }
                } else {
                    for ($x = 1; $x <= $subject['amount']; $x++) {
                        $months[]    = $bookings->monthCalendar($subject['name'], $x, $date);
                    }
                }
            } else {
                $months[]    = $bookings->monthCalendar($subject['name'], '', $date);
            }
        }
    }

    /**
     * date is the month we are requesting
     * the navigator expect the month given to be the first visible month
     * So if we are adding a new month the first visible month will be the month before that
     */
    if (isset($_POST['type']) && $_POST['type'] == 'prev') {
        $navDate    = $date;
    } else {
        $navDate    = strtotime('-1 month', $date);
    }
    $navigator    = $bookings->getNavigator($navDate);
    $detail        = '';
    if (!empty($_POST['shortcode-id'])) {
        $detail        = $bookings->detailHtml();
    }

    if (is_wp_error($detail)) {
        return $detail;
    }

    if (is_wp_error($navigator)) {
        return $navigator;
    }

    return [
        'months'    => $months,
        'navigator'    => $navigator,
        'details'    => $detail
    ];
}

/**
 * Approve a pending booking
 * This will set the pending status of the booking to 0, which means it is approved
 *
 * @param \WP_REST_Request $rest    The REST request object containing the booking ID and form ID
 *
 * @return bool|\WP_Error    True if the booking was approved successfully, WP_Error otherwise
 */
function approveBooking($rest)
{
    $result                        = false;
    foreach ($rest->bookings as $booking) {
        $result    = $rest->bookingsObject->updateBooking($booking, ['pending' => 0]);
    }

    if (is_wp_error($result)) {
        return $result;
    }

    return $result;
}

function removeBooking()
{
    $bookings    = new Bookings();

    $bookings->removeBooking((int) $_POST['id']);

    return 'Booking removed succesfully';
}

function loadPost()
{
    global $post;

    $post        = get_post($_POST['post-id']);

    // Make sure we have valid content, balanced and comments removed.
    $content    = get_the_content();
    $content    = apply_filters('the_content', $content);

    if (empty($content)) {
        $managers        = get_post_meta($post->ID, 'managers');
        if (!empty($managers[0])) {
            $manager    = get_user($managers[0]);
            if (empty($manager)) {
                return "No details found, sorry. ";
            }

            return "No details found, sorry.<br> Contact <a href='mailto:{$manager->user_email}?subject=Please add some description for {$post->title}}&body=Dear {$manager->display_name},'>the manager</a>";
        }

        return "No details found, sorry. ";
    } else {
        return $content;
    }
}
