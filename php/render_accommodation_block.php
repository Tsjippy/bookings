<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;
use function TSJIPPY\addElement as addElement;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Render the booking selector element on the form
 *
 * @param object $blockAttributes    Block attributes
 *
 * @return object The rendered element
 */
function bookingSelectorHtml($blockAttributes)
{

    if (empty($blockAttributes['bookingSubjects'])) {
        ?>
        <div class='warning'>
            Please add one or more subjects
        </div>
        <?php
        return;
    }

    $bookings = new Bookings();

    $bookings->getSubjects($blockAttributes['bookingSubjects']);

    /**
     * Build the modal
     */
    $modal      = addElement(
        'div',
        '',
        [
            'name'  => 'location-details-modal',
            'class' => 'modal hidden'
        ]
    );

    $parent = $modal->ownerDocument;

    $modalContent   = addElement('div', $modal, ['class' => 'modal-content']);

    TSJIPPY\addCloseButtton($modalContent);

    $buttonWrapper  = addElement('div', $modalContent, ['class' => 'button-wrapper']);

    foreach ($bookings->subjects as $index => $subject) {
        /**
         * Render tab buttons
         */
        $subjectSlug    = strtolower(str_replace(' ', '-', trim($subject['name'])));
        $attributes     = [
            'class'         => 'button tablink',
            'id'            => "show-{$subjectSlug}",
            'data-target'   => $subjectSlug,
            'style'         => 'margin-right:4px;',
            'type'          => 'button'
        ];

        if ($index === 0) {
            $attributes['class'] .= ' active';
        }

        addElement('button', $buttonWrapper, $attributes, $subject['name']);

        /**
         * Render tab contents
         */
        $attributes     = [
            'class'         => 'tabcontent lazy-post',
            'id'            => $subjectSlug,
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

    if (empty($bookings->subjects)) {
        $hidden     = "";
        $buttonText = 'Select dates';
    } 
    
    /**
     * Loop over all subjects to create radio selectors
     */
    elseif (count($bookings->subjects) < 6) {
        foreach ($bookings->subjects as $subject) {
            $subjectSlug    = strtolower(str_replace(' ', '-', trim($subject['name'])));

            $attributes = [
                'type'      => 'radio',
                'class'     => 'booking-subject-selector',
                'name'      => $blockAttributes['name'] ?? 'booking-subject-selector',
                'value'     => trim($subject['name']),
                'data-slug' => $subjectSlug
            ];

            $label  = addElement('label', $parent, ['style' => 'margin-right:5px;']);
            addElement(
                'input',
                $label,
                $attributes
            );

            $label->append(trim($subject['name']));
        }
    } 
    
    /**
     * Loop over all subjects to create radio selectors
     */
    else {
        $subjectSlug    = strtolower(str_replace(' ', '-', trim($subject['name'])));

        $attributes = [
            'class' => 'booking-subject-selector',
            'name'  => "accommodation-selector"
        ];

        if ($blockAttributes['required']) {
            $attributes['required']    = 'required';
        }

        $select  = addElement('select', $parent, $attributes);

        foreach ($bookings->subjects as $subject) {
            addElement('option', $select, ['value' => $subjectSlug], trim($subject['name']));
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

    if ($blockAttributes['required']) {
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

    if ($blockAttributes['required']) {
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

    if ($blockAttributes['required']) {
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
    foreach ($bookings->subjects as $subject) {
        $bookings->dateSelectorModal($day, $month, $year, $parent, $subject);
    }

    echo $parent->saveHTML();
}