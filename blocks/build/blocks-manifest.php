<?php
// This file is generated. Do not modify it manually.
return array(
	'accommodation-metadata' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'tsjippy-booking-subjects/meta',
		'version' => '0.1.0',
		'title' => 'Booking Subjects meta data',
		'category' => 'widgets',
		'description' => 'All Booking Subjects Meta',
		'textdomain' => 'tsjippy',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'move' => true,
					'remove' => true
				)
			),
			'managers' => array(
				'type' => 'array',
				'source' => 'meta',
				'meta' => 'tsjippy_managers'
			),
			'payments' => array(
				'type' => 'boolean',
				'source' => 'meta',
				'meta' => 'tsjippy_payments',
				'default' => false
			),
			'overlap' => array(
				'type' => 'boolean',
				'source' => 'meta',
				'meta' => 'tsjippy_overlap',
				'default' => false
			),
			'overlapPeriod' => array(
				'type' => 'number',
				'source' => 'meta',
				'meta' => 'tsjippy_overlap_period',
				'default' => 0
			),
			'oneday' => array(
				'type' => 'boolean',
				'source' => 'meta',
				'meta' => 'tsjippy_oneday',
				'default' => false
			),
			'defaultBookingState' => array(
				'type' => 'string',
				'source' => 'meta',
				'meta' => 'tsjippy_default_booking_state',
				'default' => 'pending'
			)
		)
	),
	'accomodation' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'tsjippy-bookings/accomodation',
		'version' => '0.1.0',
		'title' => 'Accomodation Block',
		'category' => 'form-elements',
		'icon' => 'forms',
		'description' => 'Accomodation Description',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'tsjippy',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php',
		'attributes' => array(
			'bookingSubjects' => array(
				'type' => 'array',
				'items' => array(
					'type' => 'number'
				),
				'default' => array(
					
				)
			)
		)
	),
	'room-metadata' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'tsjippy-booking-rooms/meta',
		'version' => '0.1.0',
		'title' => 'Booking Rooms meta data',
		'category' => 'widgets',
		'description' => 'All Booking Rooms Meta',
		'textdomain' => 'tsjippy',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'move' => true,
					'remove' => true
				)
			)
		)
	)
);
