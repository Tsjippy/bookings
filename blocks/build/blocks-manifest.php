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
				'meta' => 'managers'
			),
			'payments' => array(
				'type' => 'boolean',
				'source' => 'meta',
				'meta' => 'payments',
				'default' => false
			),
			'overlap' => array(
				'type' => 'boolean',
				'source' => 'meta',
				'meta' => 'overlap',
				'default' => false
			),
			'overlapPeriod' => array(
				'type' => 'number',
				'source' => 'meta',
				'meta' => 'overlap_period',
				'default' => 0
			),
			'oneday' => array(
				'type' => 'boolean',
				'source' => 'meta',
				'meta' => 'oneday',
				'default' => false
			),
			'defaultBookingState' => array(
				'type' => 'string',
				'source' => 'meta',
				'meta' => 'default_booking_state',
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
		'attributes' => array(
			'text' => array(
				'type' => 'string',
				'default' => ''
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
