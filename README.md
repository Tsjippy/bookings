This plugin is dependend on the events and forms plugins.<br>
It adds the possibility to book accomodations via a form.<br>
It will display a calendar showing available dates

== Description ==
This plugin adds a 'booking selector' element to the available form elements of the tsjippy-forms plugin.

== Hooks ==
# FILTERS
- tsjippy-bookings-should-not-send-payment-reminder/**
* Filters whether we should send a payment reminder
* 
* @param   bool    $continue       Whether we should continue
* @param   object  $submission     The submission to be reminded about
* @param   object  $user           The user to be send an e-mail to
* @param   string  $email          The e-mail address
* @param   object  $instance       This instance of the booking class
*/

- tsjippy-bookings-payment-status
/**
* Filters whether we should mark a booking as paid based on the payment status
* By default a booking is marked as paid if the status is 'free' or 'paid'
* @param   bool    $paid       True is booking should be marked as paid
* @param   string  $value      The value of the payment indicator
* @param   object  $instance   The EditDormResults instance
*/

== Screenshots ==
1. An example form using the accommodation booking element
2. If an accommodation has rooms, this screen will show a room selector
3. Each selected room will show its description on the right and the agenda for that room at the bottom
4. A calendar with selected days
5. The form with selected dates
6. The configuration screen with the possibility to select users as managers for an accommodation, change the description of the location and for each room and other options

== Installation ==
This plugin is dependent on the tsjippy-forms plugin which will automatically installed.

== Frequently Asked Questions ==

= Is this plugin only for accomodation booking? =

No, you can use it for anything that is bookable for one or more days

