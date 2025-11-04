# Changelog
## [Unreleased] - yyyy-mm-dd

### Added

### Changed

### Fixed

### Updated

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
