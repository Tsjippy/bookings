<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;
use function TSJIPPY\addElement as addElement;

if (! defined('ABSPATH')) {
    exit;
}

// the choice for table view or calendar view
add_action('tsjippy-forms-after-table-settings', __NAMESPACE__ . '\tableSettings');
/**
 * Add the option to choose between table view and calendar view in the form results
 * @param    object    $displayFormResults    The current instance of the form table class, can be used to get more information about the form and the user to decide which options to show
 */
function tableSettings($displayFormResults)
{
    // Check if it has an booking selector
    if (empty($displayFormResults->getElementByType('booking-selector'))) {
        return;
    }

    $setting    = '';
    if (isset($displayFormResults->tableSettings->booking_display)) {
        $setting    = $displayFormResults->tableSettings->booking_display;
    }

?>
    <div class="table-rights-wrapper">
        <label>
            Select if you want to see the bookings as table or as calendar
        </label>
        <br>
        <label>
            <input type='radio' name='table-settings[booking-display]' value='table' <?php if ($setting == 'table') echo 'checked'; ?>>
            Table
        </label>
        <label>
            <input type='radio' name='table-settings[booking-display]' value='calendar' <?php if ($setting == 'calendar') echo 'checked'; ?>>
            Calendar
        </label>
    </div>
<?php
}

// give table view permissions if we are a subject manager
add_filter('tsjippy-forms-table-edit-permissions', __NAMESPACE__ . '\changeTableViewPermissions', 10, 2);
/**
 * Give table view permissions if we are a subject manager
 * @param    bool    $tableViewPermissions    Whether or not the user has permissions to view the table, default false
 * @param    object    $object                    The current instance of the form table class, can be used to get more information about the form and the user to decide whether or not to give permissions
 */
function changeTableViewPermissions($tableViewPermissions, $object)
{
    if ($tableViewPermissions) {
        return $tableViewPermissions;
    }

    $bookings       = new Bookings($object);

    // get all booking selectors
    $elements       = $bookings->getBookingElements();

    if (empty($elements)) {
        return $tableViewPermissions;
    }

    // Loop over all subjects
    foreach ($elements as $element) {
        foreach ($bookings->getElementSubjects($element->id) as $subject) {
            // if we are the manager of one of the subjects
            if (isset($subject['managers'][$object->user->ID])) {
                return true;
            }
        }
    }

    return $tableViewPermissions;
}

// Display calendar instead of a table
add_filter('tsjippy-forms-table-should-show', __NAMESPACE__ . '\shouldShow', 10, 4);
/**
 * Filter whether or not to show the table, this can be used to for example show a message instead of the table when there are no submissions or when the user has no permissions
 * @param    bool           $shouldShow                Whether or not to show the table, default true
 * @param    object         $displayFormResults        The current instance of the form table class, can be used to get more information about the form and the user to decide whether or not to show the table
 * @param    string         $type                      The type of results that would be shown, either 'own', 'others' or 'all'
 * @param    \DOMElement    $parent                    The parent node to append to
 */
function shouldShow($shouldShow, $displayFormResults, $type, $parent)
{
    // Check if we should show the table view
    if (
        !isset($displayFormResults->tableSettings->booking_display)   ||          // no option choosen
        (
            isset($displayFormResults->tableSettings->booking_display) &&         // option chosen
            $displayFormResults->tableSettings->booking_display != 'calendar'     // but choose table view
        )      ||
        // phpcs:ignore
        isset($_REQUEST['export-xls'])  ||                                          // exporting an excel
        // phpcs:ignore
        isset($_REQUEST['export-pdf'])                                              // exporting a pdf
    ) {
        return $shouldShow;
    }

    /**
     * Data should always be splitted if we are in calendar view
     * So the type 'all' is not allowed.
     * We render our own submissions as a table, before continuing with the calendar view
     */
    if ($type == 'all') {
        $displayFormResults->renderTableButtons($parent);
        $displayFormResults->renderTable(type: 'own', parent: $parent);

        $type       = 'others';
    }

    // display the calendar instead of the table
    wp_enqueue_script('tsjippy-bookings');

    $bookings                   = new BookingPayments($displayFormResults);

    $elements                   = $bookings->getBookingElements();
    if (is_wp_error($elements)) {
        return $elements;
    }

    $targetDate                 = time();
    $bookedSubject              = '';

    // Show a specific booking
    // phpcs:ignore
    if (!empty($_REQUEST['id'])) {
        // phpcs:ignore
        $bookings->forms->submission    = $bookings->forms->getSubmissions('', (int) $_REQUEST['id'])[0];

        // Find the subject
        foreach ($elements as $element) {
            if (isset($bookings->forms->submission->{$element->id})) {
                $bookedSubject          = $bookings->forms->submission->{$element->id};
                break;
            }
        }

        $targetDate                     = $bookings->forms->submission->{'booking-start-date'};

        if (is_array($targetDate)) {
            array_values($bookings->forms->submission->{'booking-start-date'})[0];
        }

        $targetDate                     = strtotime($targetDate);
    }

    $div    = addElement('div', $parent, ['class' => "tables-wrapper"]);

    $subjects   = [];

    // Find the subject names
    foreach ($elements as $element) {
        foreach ($bookings->getElementSubjects($element->id) as $subject) {
            // Only show the subjects we are manager of
            if (!isset($subject['managers'][$bookings->user->ID])) {
                continue;
            }

            $subjects[]   = $subject;
        }
    }

    /**
     * Display a list of bookings
     */
    if ($type == 'own') {

        // Pending bookings
        $bookings->pendingBookingsHtml($div, 'approval');
        $bookings->pendingBookingsHtml($div, 'payment');

        // Get the bookings for the current user
        $userBookings    = $bookings->getUserBookingsByStartDate($displayFormResults->userId, gmdate("Y-m-d"));

        if (empty($userBookings)) {
            $div->append("You do not have any bookings. ");
            return false;
        }

        addElement('h4', $div, [], "Your Upcoming Bookings");

        $detailsWrapper = addElement('div', $div, ['class' => 'details-wrapper', 'style' => 'max-width:500px;display:flex;']);

        foreach ($userBookings as $booking) {
            $submission = $displayFormResults->getSubmissions(submissionId: $booking->submission_id)[0] ?? [];

            TSJIPPY\addRawHtml($bookings->submissionDetails($booking, $submission, false), $detailsWrapper);
        }

        return false;
    }

    // Only show subject selection if there is something to choose
    $formDataTable      = addElement('div', $div, ['class' => "form-data-table"]);
    $checkBoxWrapper    = addElement('div', $formDataTable, ['class' => 'checkbox-wrapper']);
    $calendarWrapper    = addElement('div', $formDataTable, ['class' => 'calendar-wrapper']);

    if (count($subjects) > 1) {
        addElement('h4', $checkBoxWrapper, [], 'Please select the calendar you like to see');
    }

    foreach ($subjects as $subject) {
        $bookings->bookings  = [];   // reset the bookings so they do not include the previous location

        $cleanSubject   = trim($subject['name']);

        $attributes = [
            'type'  => 'checkbox',
            'class' => 'admin-booking-subject-selector',
            'value' => $cleanSubject
        ];

        $hidden     = true;
        if ($subject['name'] == $bookedSubject || count($subjects) == 1) {
            $attributes['checked']    = 'checked';
            $hidden     = false;
        }

        if (count($subjects) > 1) {
            $label  = addElement('label', $checkBoxWrapper, [], $cleanSubject);
            addElement('input', $label, $attributes, '', 'afterBegin');
        }

        $bookings->modalContent($calendarWrapper, $subject, $targetDate, true, $hidden, true);
    }

    // Export buttons
    if (array_intersect($bookings->forms->userRoles, array_keys($bookings->forms->tableSettings->view_right_roles))) {
        $div    = addElement('div', $div);

        $form   = addElement('form', $div, ['method' => 'post', 'class' => 'export-form', 'id' => 'export-xls']);
        addElement('button', $form, ['class' => 'button button-primary', 'type' => 'submit', 'name' => 'export-xls'], 'Export data to excel');

        if (SETTINGS['pdf'] ?? false) {
            $form   = addElement('form', $div, ['method' => 'post', 'class' => 'export-form', 'id' => 'export-pdf']);
            addElement('button', $form, ['class' => 'button button-primary', 'type' => 'submit', 'name' => 'export-pdf'], 'Export data to pdf');
        }
    }

    return false;
}

// Change Archive button text
add_filter('tsjippy-forms-results-row-actions', __NAMESPACE__ . '\actionHtml', 10, 3);
/**
 * Change the Archive button text
 * @param    array    $attributes   Array of button attributes
 * @param    object   $submission   The submission for which the buttons are shown, can be used to decide whether or not to change the buttons
 * @param    object   $instance     The current instance of the form table class, can be used to get more information about the form and the user to decide whether or not to change the buttons
 */
function actionHtml($attributes, $submission, $instance)
{
    if (get_class($instance) != 'TSJIPPY\BOOKINGS\Bookings' || !isset($buttonsHtml['archive'])) {
        return $attributes;
    }

    $attributes['archive']['style'] = "width: max-content;";
    $attributes['archive']['text']  = 'Cancel booking';

    return $attributes;
}

// Show the possible booking rooms
add_filter('tsjippy-forms-checkbox-options', function ($options, $object) {
    if (!isset($object->element) || $object->element->slug != 'booking-rooms[]') {
        return $options;
    }

    $bookingSelectors   = $object->getElementByType('booking-selector');
    if (!$bookingSelectors) {
        return $options;
    }

    // find the accomdation
    foreach ($bookingSelectors as $bookingSelector) {
        if (empty($object->submissions[0]->{$bookingSelector->slug})) {
            continue;
        }

        $accomodation   = $object->submissions[0]->{$bookingSelector->slug};

        // Get the rooms of this accomodation
        $bookings   = new Bookings($object);
        $details    = $bookings->getElementSubjects($bookingSelector->id, $accomodation);

        foreach ($details['rooms'] as $room) {
            $options[$room['name']]  = $room['name'];
        }

        break;
    }

    return $options;
}, 10, 2);

// Alter form results
add_filter('tsjippy-forms-retrieved-formdata', __NAMESPACE__ . '\formdataRetrieved', 10, 3);
/**
 * Alter the form results
 * @param    array    $submissions    The current form submissions retrieved, can be altered to change the data shown in the form results
 * @param    int|string    $userId        The ID of the user for which the data is retrieved, can be used to decide how to alter the data
 * @param    object    $object        The current instance of the form table class, can be
 * used to get more information about the form and the user to decide how to alter the data
 * @return array   The altered form submissions
 */
function formdataRetrieved($submissions, $userId, $object)
{
    $bookingSelectors   = $object->getElementByType('booking-selector');
    if (!$bookingSelectors) {
        return $submissions;
    }

    $booker   = new Bookings($object);

    $booker->getBookingElements();

    /**
     * Add the booking dates to the form results
     * Split on dates, add extra results if necessary
     */
    foreach ($submissions as $index => $submission) {
        // Get all the bookings belonging to this form submission
        $bookings   = $booker->getBookingsBySubmission($submission->id);

        $startDates = [];
        $endDates   = [];
        $rooms      = [];
        $bookingIds = [];

        // Store the dates
        foreach ($bookings as $booking) {
            $startDates[]   = $booking->start_date;
            $endDates[]     = $booking->end_date;

            if (!empty($booking->room)) {
                $rooms[]        = $booking->room;
            }

            $bookingIds[]   = $booking->id;
        }

        $newSubmissions      = [];

        // Add submissions for each room, using the room name as sub id
        foreach ($startDates as $i => $date) {
            // Add the dates to the form results
            $submission->{'booking-start-date'} = $date;
            $submission->{'booking-end-date'}   = $endDates[$i];
            $submission->booking_id             = $bookingIds[$i];

            if (!empty($rooms)) {
                $submission->{'booking-rooms'}  = $rooms[$i];
                $submission->sub_id             = $rooms[$i];
            }

            $newSubmissions[]                   = clone $submission;
        }

        // replace the original with the first
        $submissions[$index]    = $newSubmissions[0];

        // remove that one
        unset($newSubmissions[0]);

        // Add the extra submissions
        $submissions    = array_merge($submissions, $newSubmissions);
    }

    /**
     * Only show upcoming bookings for own bookings
     */

    // Do not filter if this is for a specific user
    if (is_numeric($userId)) {
        return $submissions;
    }

    // Get the subjects for the current user
    $booker->getSubjectManagers($booker->user->ID);

    // Loop over all booking selctors in the form
    foreach ($bookingSelectors as $bookingSelector) {
        if (empty($bookingSelector)) {
            TSJIPPY\printArray($bookingSelectors);
        } else {
            // loop over all submissions
            foreach ($submissions as $index => $submission) {
                // remove any submission not belonging to the $subjectsToKeep
                if (
                    !empty($submission->{$bookingSelector->slug})    &&
                    !isset($booker->managers[$submission->{$bookingSelector->slug}])    &&  // Not managed by us
                    $submission->user_id    != $booker->user->ID                      // Not our own sumissionn

                ) {
                    unset($submissions[$index]);
                }
            }
        }
    }

    return $submissions;
}

/**
 * Change the submission data retrieved
 */
add_filter('tsjippy-forms-formdata-retrieval-query', __NAMESPACE__ . '\alterQuery', 10, 4);
/**
 * Change the submission data retrieved
 * @param    array    $params        The current query params, can be altered to change the data retrieved from the database
 * @param    int|string    $userId        The ID of the user for which the data is retrieved, can be used to decide how to alter the query
 * @param    object    $instance    The current instance of the form table class, can be used
 * to get more information about the form and the user to decide how to alter the query
 * @return array   The altered query params
 */
function alterQuery($params, $userId, $instance)
{
    if (empty($instance->getElementByType('booking-selector'))) {
        return $params;
    }

    $bookings   = new Bookings($instance);

    // We are requesting a submission value and the element index is negative, meaning a start- or end date or a room value
    if (
        isset($params['values'][2]) &&
        intval($params['values'][2]) < -101
    ) {
        $elementId      = $params['values'][2];
        // phpcs:ignore
        $submissionId   = (int) $_POST['submission-id'];
        if (!is_numeric($submissionId)) {
            return $params;
        }

        switch ($elementId) {
            case -102:
                $column = 'start_date';
                break;
            case -103:
                $column = 'end_date';
                break;
            case -104:
                $column = 'room';
                break;
            default:
                return $params;
        }

        $params['baseQuery']     = "select $column from %i WHERE ";

        // Unset the element index as it is not needed
        $params['where']    = [
            "submission_id = %d",
            "room = %d"
        ];

        $params['values']   = [
            $bookings->tableName,
            $submissionId,
            $params['values'][3]   // the original submission sub id (room number) where clause value
        ];
    }

    // only show future bookings in table view
    else{
        if (
            !in_array("S.id=%d", $params['where']) &&
            !in_array("submission_id = %d", $params['where'])
        ) {
            $params['where'][] .= "S.id IN(SELECT submission_id FROM %i WHERE end_date >= %s ORDER BY 'start_date')";
            $params['values'][] = $bookings->tableName;
            $params['values'][] = gmdate('Y-m-d');
        }
    }

    return $params;
}

// Store updated date or room
add_filter('tsjippy-forms-should-update-form-data', __NAMESPACE__ . '\updateBookingData', 10, 6);
/**
 * Change the submission data retrieved
 * @param    bool    $shouldContinue    Whether or not to continue with the default update process, return false if you have already updated the data yourself and do not want the default update to run
 * @param    int        $elementId        The id of the element for which the data is updated, can be used to decide whether or not to update the booking data
 * @param    int        $submissionId    The id of the submission for which the data is updated, can be used to update the correct booking
 * @param    string    $subId            The sub id of the submission for which the data is updated, can be used to update the correct booking when there are multiple bookings for one submission
 * @param    string    $value            The new value that is being updated, can be used to update the booking with the new value
 * @param    object    $instance        The current instance of the form table class, can be used to get more information about the form and the user to decide whether or not to update the booking data
 * @return bool    Return false if you have already updated the data yourself and do not want the default update to run, return true if you want the default update process to run after this function
 */
function updateBookingData($shouldContinue, $elementId, $submissionId, $subId, $value, $instance)
{
    // Change to paid / unpaid
    $paymentIndicatorElId    = $instance->formData->payment_indicator;

    if ($elementId > -102 && $elementId != $paymentIndicatorElId) {
        return $shouldContinue;
    }

    switch ($elementId) {
        // Mark as paid if the payment status changed to paid or free
        case $paymentIndicatorElId:
            $column = 'paid';

            /**
             * Filters whether we should mark a booking as paid based on the payment status
             * By default a booking is marked as paid if the status is 'free' or 'paid'
             * @param   bool    $paid       True is booking should be marked as paid
             * @param   string  $value      The value of the payment indicator
             * @param   object  $instance   The EditDormResults instance
             */
            $value  = apply_filters('tsjippy-bookings-payment-status', isset(['paid' => 1, 'free' => 1][$value]), $value, $instance);
            break;
        case -102:
            $column = 'start_date';
            break;
        case -103:
            $column = 'end_date';
            break;
        case -104:
            $column = 'room';
            break;
        default:
            return $shouldContinue;
    }

    $bookings   = new BookingPayments($instance);

    $startDates = [];
    $endDates   = [];
    $rooms      = [];

    // Update the booking data
    foreach ($bookings->getBookingsBySubmission($submissionId) as $booking) {
        // Only update the booking with the correct room if a sub id is given, otherwise update all bookings of this submission
        if (
            empty($subId) ||
            $booking->room == $subId &&
            $value != $booking->$column
        ) {
            $bookings->updateBooking($booking, [$column => $value], true);

            $booking->{$column} = $value;
        }

        $startDates[]   = $booking->start_date;
        $endDates[]     = $booking->end_date;
        $rooms[]        = $booking->room;
    }

    // Update the amount to be paid if start_date or end_date are changed
    if ($elementId == -102 || $elementId == -103) {
        $amount             = $bookings->calculatePaymentAmount($startDates, $endDates);

        $paymentAmountElId  = $bookings->forms->formData->payment_amount_el;

        if (!empty($paymentAmountElId)) {
            /**
             * @disregard P1013
             */
            $result = $bookings->forms->updateSubmission($paymentAmountElId, $amount);

            if (is_wp_error($result)) {
                TSJIPPY\printArray($result->get_error_message());
                return $result;
            }
        }
    }

    // Return false meaning the processing should not continue
    if ($elementId != $paymentIndicatorElId) {
        return false;
    }

    return true;
}
