# Changelog
## [Unreleased] - yyyy-mm-dd

### Added

### Changed

### Fixed
- accomodation selector

### Updated

## [10.4.8] - 2026-06-23


### Fixed
- hook names
- wrong table name

## [10.4.7] - 2026-06-23


## [10.4.6] - 2026-06-23


## [10.4.5] - 2026-06-23


### Fixed
- addElement code
- booking selector problems

## [10.4.4] - 2026-06-23


## [10.4.3] - 2026-06-23


### Changed
- payments html to dom elements
- implemented db caching
- implemented db caching
- implemented db caching
- replaced wpdb->update with updateDbFunction

## [10.4.1] - 2026-06-21


## [10.4.0.1] - 2026-06-21


## [10.4.0] - 2026-06-20


## [10.3.9] - 2026-06-19


### Added
- int sanitazion

## [10.3.8] - 2026-06-18


### Changed
- hook and filer name update
- hook and filter name update
- hook and filter name update
- prefix all hooks with plugin name

### Fixed
- load forms on scheduled task
- get subject managers

## [10.3.7] - 2026-06-15


## [10.3.6] - 2026-06-15


## [10.3.5] - 2026-06-15


## [10.3.4] - 2026-06-15


### Changed
- transform inputdata now requires element

## [10.3.3] - 2026-06-13


## [10.3.2] - 2026-06-13


### Fixed
- shared code loader
- activation hook
- payment index error

## [10.3.1] - 2026-06-11


### Changed
- prefixed post metas and shortcodes

## [10.3.0] - 2026-06-09


### Changed
- comply to coding standards
- code layout
- removed shared-functionality plugin dependency
- namespaced all constants
- sanitize all posts and get vars
- html to domelement

### Fixed
- spacing problem

## [10.2.9] - 2026-06-03


### Added
- more screenshots

### Fixed
- subject without rooms bug

## [10.2.8] - 2026-06-01


### Changed
- merged hooks.md into readme.md

### Fixed
- empty booking selector bug

## [10.2.7] - 2026-06-01


### Fixed
- update subject and room descriptions

## [10.2.6] - 2026-05-29


### Changed
- do not store get_plugin_data in global variable
- updated readme

## [10.2.5] - 2026-05-29


### Added
- wp_unslash

## [10.2.4] - 2026-05-28


### Changed
- only show pending bookings to the managers
- use esc funtions everywhere

## [10.2.3] - 2026-05-28


### Fixed
- empty form settings bug

## [10.2.2] - 2026-05-28


### Added
- inidicator for days that can only be booked as start or end of a booking

### Fixed
- get class error
- overlapping bookings query

## [10.2.1] - 2026-05-26


### Fixed
- index bug

## [10.2.0] - 2026-05-26


### Fixed
- non exiting array index bug

## [10.1.9] - 2026-05-25


### Fixed
- bug

## [10.1.8] - 2026-05-24


### Fixed
- array index bug

## [10.1.7] - 2026-05-23


### Fixed
- bug

## [10.1.6] - 2026-05-19


### Fixed
- typo

## [10.1.5] - 2026-05-17


### Fixed
- update check

## [10.1.4] - 2026-05-16


### Fixed
- after update

## [10.1.3] - 2026-05-16


### Fixed
- allow booking elements of type wp error

## [10.1.2] - 2026-05-14


### Changed
- prepare sql query

### Fixed
- load template files

## [10.1.1] - 2026-05-14


### Changed
- date( to gmdate(

### Fixed
- do not map end dates as start dates

## [10.1.0] - 2026-05-13


### Changed
- removed spaces from file names
- escape before echoing

### Fixed
- select start and end date

## [10.0.8] - 2026-05-12


### Changed
- code relocation
- more efficient rest functions

## [10.0.7] - 2026-05-11


### Added
- rest api permission callbacks

### Changed
- fixed several bugs
- moved css to css file

## [10.0.6.1] - 2026-05-06


## [10.0.6] - 2026-05-06


## [10.0.5] - 2026-05-06


## [10.0.1] - 2026-05-03


### Changed
- removed the redirection at activation as it is done by the share plugin
- use shared workflow  

## [10.0.0] - 2026-05-01


### Added
- redirection to settings page on plugin activation

### Changed
- main plugin name from sim-base to tsjippy-shared-functionality
- base namespace to TSJIPPY
- filternames to include tsjippy
- function name
- PLUGINCONSTANT value
- table columns
- recurrence selector code
- exclude .vscode from releases
- updated github workflow versions

## [8.7.4] - 2026-04-16


### Fixed
- sub ids

## [8.7.3] - 2026-03-17


### Fixed
- bug whith only one date

## [8.7.2] - 2026-03-05


### Added
- bookingpayments class

### Fixed
- unpaid bookings query

## [8.7.1] - 2026-03-04


### Changed
- split in multi files

## [8.7.0] - 2026-03-04


### Changed
- use wpdb prepare
- replaced _ with -

### Fixed
- query
- update payment amount on date change
- do not allow to load the same month twice

## [8.6.9] - 2026-01-30


### Fixed
- payment reminders

## [8.6.8] - 2026-01-24


### Changed
- implemented new SQL query from forms module

## [8.6.7] - 2026-01-09


### Fixed
- bug when retrieving bookings over ajax

## [8.6.6] - 2026-01-01


### Fixed
- display of own bookings

## [8.6.5] - 2025-12-12


### Fixed
- reminder e-mails

## [8.6.4] - 2025-12-12


### Changed
- layout of payment reminder e-mail

### Fixed
- changing payment status

## [8.6.3] - 2025-12-11


### Fixed
- bug
- booking-details paceholder

## [8.6.1] - 2025-11-27


### Fixed
- room selectors

## [8.6.0] - 2025-11-21


### Changed
- formresults to submission
- getSuubmissions
- get form results by element id
- node based html
- implemented forms module changes

### Fixed
- booking without room
- element id datset value
- booking e-mails
- update bookings
- some bugs

## [8.5.3] - 2025-11-07


### Changed
- alter query from form results
- prevent data malfunction

### Fixed
- bugs

## [8.5.2] - 2025-11-06


### Changed
- cleaner layout when adding booking selector

## [8.5.1] - 2025-11-04


### Changed
- data-id to data-submission-id

### Fixed
- updating booking dates

## [8.4.9] - 2025-11-03


### Changed
- js import removed
- stop listening to events if we have a match

### Fixed
- bug whith pres electing rooms

## [8.4.8] - 2025-10-30


### Changed
- new format for frontendcontent

### Fixed
- display bookings when manager
- booking details
- booking actions default permissions
- store email settings

## [8.4.6] - 2025-10-20


### Changed
- using array_filter

## [8.4.5] - 2025-10-17


### Changed
- get info box from code

## [8.4.3] - 2025-10-16


## [8.4.2] - 2025-10-14


### Added
- lazy load subject and room descriptions

## [8.4.1] - 2025-10-13


### Added
- template for single subjects
- booking link on bottom of subject and room page

### Changed
- classname
- more classnames
- bump
- data attribute names
- array to object
- dataset names
- store booking subjects in seperate table
- add and remove rooms

### Fixed
- error with displaying reservations
- bugs

## [8.3.9] - 2025-09-26


### Changed
- classnames replace _ with -

## [8.3.8] - 2025-09-25


### Changed
- js generated loader

## [8.3.7] - 2025-09-24


### Fixed
- issue when non-booking selector had booking details

## [8.3.6] - 2025-08-27


### Changed
- only show payment settings when a booking element is present

## [8.3.5] - 2025-08-13


### Changed
- darkmode css

## [8.3.4] - 2025-07-25


### Fixed
- issue with updaing bookings

## [8.3.3] - 2025-07-24


## [8.3.2] - 2025-07-04


## [8.3.1] - 2025-07-02


### Added
- location and room descriptions

### Fixed
- room problem
- potential bugs in location descriptions

## [8.2.9] - 2025-04-28


## [8.2.8] - 2025-04-09


### Fixed
- show personal bookings
- display issue with payable bookings

## [8.2.7] - 2025-04-05


### Changed
- only send a copy to booking manager on before-stay

### Fixed
- after stay e-mails
- only export bookings for the current logged in manager

## [8.2.6] - 2025-03-27


### Changed
- adjust sub id if needed
- show booked dates in results page

## [8.2.5] - 2025-03-27


### Fixed
- bookings in table view

## [8.2.4] - 2025-03-27


### Fixed
- %booking-details% dates
- details view of specific booking by id

## [8.2.3] - 2025-03-21


### Fixed
- typo

## [8.2.2] - 2025-03-21


### Added
- booking e-mails

## [8.2.1] - 2025-03-17


### Fixed
- only process payment when needed

## [8.2.0] - 2025-02-20


### Fixed
- edit booking details if booked multiple rooms

## [8.1.8] - 2025-02-13


### Changed
- module hooks now include module slug

### Fixed
- updating existing booking

## [8.1.6] - 2025-02-12


### Fixed
- error when invalid json form result
- run sim-booking-paid only once ber submission

## [8.1.5] - 2025-02-11


### Fixed
- do not sent an e-mail after an incorrect booking

## [8.1.4] - 2025-02-11


### Changed
- use site date and time format

## [8.1.3] - 2025-02-11


### Changed
- sim_module_updated filter to new format

## [8.1.2] - 2025-02-10


### Added
- sim-bookings-should-not-send-payment-reminder filter

### Fixed
- placeholders in payment reminder e-mail

## [8.1.1] - 2025-02-10


### Added
- rooms to seperate db column

### Changed
- update message contains payable amount

### Fixed
- bug in room calendars
- bug in element html filter

## [8.1.0] - 2025-02-10


### Fixed
- bug in other forms

## [8.0.9] - 2025-02-07


## [8.0.8] - 2025-02-07


### Added
- multipe booking managers

### Fixed
- change dates

## [8.0.7] - 2025-02-03


### Added
- price per night element
- payable calculation

### Fixed
- payment selectors
- payment updates

## [8.0.6] - 2025-01-31


### Added
- payments icon

### Changed
- unpaid bookings only bookings from the past

### Fixed
- retrieveUnpaidBookings query
- pending payments table

## [8.0.4] - 2025-01-30


### Added
- paid column
- payment reminders

## [8.0.3] - 2024-11-18


### Changed
- removed anonymous functions

### Fixed
- double functionname

## [8.0.2] - 2024-10-17


### Changed
- readme

### Fixed
- global css

## [8.0.1] - 2024-10-11


### Changed
- hooks

### Fixed
- pending status

## [8.0.0] - 2024-10-04


## [8.0.0] - 2024-10-03
