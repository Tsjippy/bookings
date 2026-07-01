<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

use function TSJIPPY\addElement as addElement;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Render the booking date elements on the form with the correct min and max attributes based on the existing bookings
 *
 * @param object $node   The current DOM node to render the element in
 * @param object $object The form object
 *
 * @return object The rendered element
 */
function bookingDateElementHtml(&$node, $object, $bookingId = false)
{
    global $wpdb;

    if (is_numeric($bookingId)) {
        $node->setAttribute('data-booking-id', $bookingId);
    }

    if ($object->element->slug != 'booking-start-date' && $object->element->slug != 'booking-end-date') {
        return;
    }

    // Get the subject
    $subject    = $object->submission->{$object->getElementByType('booking-selector')[0]->slug};

    $startDates = (array) $object->submission->{'booking-start-date'};
    $endDates   = (array) $object->submission->{'booking-end-date'};

    $early      = array_values($startDates)[0];
    $late       = array_values($endDates)[0];

    foreach ($startDates as $index => $date) {
        if ($date < $early) {
            $early  = $date;
        }

        if ($endDates[$index] > $late) {
            $late   = $endDates[$index];
        }
    }

    if ($object->element->slug == 'booking-start-date') {
        // get the first event after this one
        $max    = TSJIPPY\getFromDb(
            "get_start_date_for_{$subject}_after_$late",
            "bookings",
            "SELECT start_date FROM %i WHERE subject = %s AND start_date > %s ORDER BY start_date LIMIT 1",
            "{$wpdb->prefix}tsjippy_bookings",
            $subject,
            $late
        );

        if (!empty($max)) {
            $node->setAttribute('max', $max);
        }

        $node->setAttribute('min', $early);
    } elseif ($object->element->slug == 'booking-end-date') {
        // get the first event before this one
        $min    = TSJIPPY\getFromDb(
            "get_end_date_for_{$subject}_before_$early",
            "bookings",
            "SELECT end_date FROM %i WHERE subject = %s AND end_date <= %s ORDER BY end_date LIMIT 1",
            "{$wpdb->prefix}tsjippy_bookings",
            $subject,
            $early
        );

        if (!empty($min)) {
            $node->setAttribute('min', $min);
        }

        $node->setAttribute('max', $late);
    }
}

// Display the date selector in the form
add_filter('tsjippy-forms-element-html', __NAMESPACE__ . '\elementHtml', 10, 2);
/**
 * Render the form element HTML
 *
 * @param object $node The current DOM node to render the element in
 * @param object $object The form object
 *
 * @return object The rendered element
 */
function elementHtml($node, $object)
{
    // Check if the form has a booking selector
    if (empty($object->getElementByType('booking-selector'))) {
        return $node;
    }

    if ($object->element->slug == 'booking-rooms') {
        $bookings       = new Bookings($object);

        if (empty($subjects)) {
            return 'Please add one or more subjects';
        }

        $elementName    = $object->getElementByType('booking-selector')[0]->slug;

        foreach ($subjects as $subject) {
            if ($subject['name'] == $object->submission->{$elementName}) {
                break;
            }
        }

        $bookings->roomSelector($node, $subject, true);
    }

    // Display existing form entry element element
    elseif (!empty($object->submission)) {
        // phpcs:ignore
        bookingDateElementHtml($node, $object, (int) $_POST['booking-id']);
    }

    // Add a class for payment_amount_el
    elseif ($object->element->id == $object->formData->payment_amount_el) {
        $class  = $node->getAttribute('class');

        $class  .= ' payment-amount';

        $node->setAttribute('class', $class);
    }

    // Add a class for payment_details_el
    elseif ($object->element->id == $object->formData->payment_details_el) {
        $class  = $node->getAttribute('class');

        $class  .= ' payment-details';

        $node->setAttribute('class', $class);
    }

    // Add a class for payment_details_el
    elseif ($object->element->id == $object->formData->price_per_night_el) {
        $class  = $node->getAttribute('class');

        $class  .= ' price-per-night';

        $node->setAttribute('class', $class);
    }

    return $node;
}

// Display the date selector in the form
add_filter('tsjippy-forms-element-html-short-circuit', __NAMESPACE__ . '\bookingSelectorElementHtml', 10, 3);
/**
 * Render the booking selector element on the form
 *
 * @param object $override  default null, return a node to skip element html rendering
 * @param object $parent    The parent form element
 * @param object $object    The form object
 *
 * @return object The rendered element
 */
function bookingSelectorElementHtml($override, $parent, $object)
{
    // Check if the form has a booking selector
    if ($object->element->type != 'booking-selector') {
        return $override;
    }

    $bookings       = new Bookings($object);
    $subjects       = $bookings->getElementSubjects($object->element->id);

    if (empty($subjects)) {
        return addElement('div', $parent, ['class' => 'warning'], 'Please add one or more subjects');
    }

    /**
     * Build the modal
     */
    $modal      = addElement(
        'div',
        $parent,
        [
            'name'  => 'location-details-modal',
            'class' => 'modal hidden'
        ]
    );

    $modalContent   = addElement('div', $modal, ['class' => 'modal-content']);

    TSJIPPY\addCloseButtton($modalContent);

    // Render tab buttons
    foreach ($subjects as $index => $subject) {
        $subjectName    = strtolower(str_replace(' ', '-', $subject['name']));
        $attributes     = [
            'class'         => 'button tablink',
            'id'            => "show-{$subjectName}",
            'data-target'   => $subjectName,
            'style'         => 'margin-right:4px;',
            'type'          => 'button'
        ];

        if ($index === 0) {
            $attributes['class'] .= ' active';
        }

        addElement('button', $modalContent, $attributes, $subject['name']);
    }

    // Render tab contents
    foreach ($subjects as $index => $subject) {
        $attributes     = [
            'class'         => 'tabcontent lazy-post',
            'id'            => strtolower(str_replace(' ', '-', $subject['name'])),
            'data-post-id'  => $subject['post-id']
        ];

        if ($index !== 0) {
            $attributes['class'] .= ' hidden';
        }

        addElement('div', $modalContent, $attributes, $subject['name']);
    }

    /**
     * Build the element
     */
    addElement('button', $parent, ['class' => 'small tsjippy button location-details', 'type' => 'button'], 'Show Location Descriptions');
    addElement('br', $parent);

    $hidden     = 'hidden';
    $buttonText = 'Change';

    if (empty($subjects)) {
        $hidden     = "";
        $buttonText = 'Select dates';
    } elseif (count($subjects) < 6) {
        foreach ($subjects as $subject) {
            $attributes = [
                'type'  => 'radio',
                'class' =>  'booking-subject-selector',
                'name'  => $object->element->slug,
                'value' => trim($subject['name'])
            ];

            if (isset($object->submission->{$object->element->id}) && $object->submission->{$object->element->id} == trim($subject['name'])) {
                $attributes['checked']    = 'checked';
            }

            $label  = addElement('label', $parent, ['style' => 'margin-right:5px;']);
            addElement(
                'input',
                $label,
                $attributes
            );

            $label->append(trim($subject['name']));
        }
    } else {
        $attributes = [
            'class' =>  'booking-subject-selector',
            'name'  => $object->element->slug
        ];

        if ($object->element->required) {
            $attributes['required']    = 'required';
        }

        $select  = addElement('select', $parent, $attributes);

        foreach ($subjects as $subject) {
            addElement('option', $select, ['value' => trim($subject['name'])], trim($subject['name']));
        }
    }

    $flexDiv = addElement('div', $parent, ['style' => 'display:flex;align-items: center;']);

    $cloneDivsWrapper = addElement('div', $flexDiv, [
        'class' => "clone-divs-wrapper selected-booking-dates $hidden"
    ]);

    $cloneDiv       = addElement('div', $cloneDivsWrapper, ['class' => 'clone-div', 'data-div-id' => '0']);

    $buttonWrapper  = addElement('div', $cloneDiv, ['class' => 'button-wrapper']);

    $roomDiv        = addElement('div', $buttonWrapper, ['class' => 'hidden']);

    addElement('h4', $roomDiv, [], 'Room');

    $attributes = [
        'type'      => 'text',
        'name'      => 'booking-rooms[0]',
        'disabled'  => 'disabled'
    ];

    if ($object->element->required) {
        $attributes['required']   = 'required';
    }

    addElement('input', $roomDiv, $attributes);

    $arrivalDiv = addElement('div', $buttonWrapper);

    addElement('h4', $arrivalDiv, [], 'Arrival Date');

    $attributes = [
        'type'      => 'date',
        'name'      => 'booking-start-date[0]',
        'disabled'  => 'disabled'
    ];

    if ($object->element->required) {
        $attributes['required']   = 'required';
    }

    addElement('input', $arrivalDiv, $attributes);

    $departureDiv   = addElement('div', $buttonWrapper);

    addElement('h4', $departureDiv, [], 'Departure Date');

    $attributes = [
        'type'      => 'date',
        'name'      => 'booking-end-date[0]',
        'disabled'  => 'disabled'
    ];

    if ($object->element->required) {
        $attributes['required']   = 'required';
    }

    addElement('input', $departureDiv, $attributes);

    addElement('button', $flexDiv, [
        'class' => 'button change-booking-date hidden',
        'type'  => 'button',
        'style' => 'margin-left: 20px;'
    ], $buttonText);

    wp_enqueue_script('tsjippy-bookings');

    $day    = gmdate('d');
    // phpcs:ignore
    $month  = (int) ($_GET['month'] ?? '');
    // phpcs:ignore
    $year   = (int) ($_GET['yr'] ?? '');

    if (!is_numeric($month) || strlen($month) != 2) {
        $month  = gmdate('m');
    }
    if (!is_numeric($year) || strlen($year) != 4) {
        $year   = gmdate('Y');
    }

    // Find the subject names
    foreach ($subjects as $subject) {
        $bookings->dateSelectorModal($day, $month, $year, $parent, $subject);
    }

    return $flexDiv;
}