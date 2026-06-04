<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;
use TSJIPPY\EVENTS;
use TSJIPPY\FORMS;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

class BookingPayments extends Bookings
{

    /**
     * Retrieve all the unpaid bookings
     *
     * @param   bool    $onlyFinished       True to only return bookings that are finished
     * @param   bool    $all                Whether to get unpaid bookings for all users. Default false;
     */
    public function retrieveUnPaidBookings($onlyFinished, $all = false)
    {
        global $wpdb;

        /**
         * Only show unpaid bookings this user has permissions for
         */
        if (wp_doing_cron()) {
            $userId = '';
        } else {
            $userId = $this->user->ID;
        }
        $this->getSubjectManagers($userId);

        if (empty($this->managers)) {
            return [];
        }

        $query            = "SELECT * FROM %i WHERE (`paid` != 1 OR `paid` IS NULL)";

        $values         = [$this->tableName];

        // only show finished bookings
        if ($onlyFinished) {
            $query    .= " AND end_date < %s";

            $values[]   = gmdate('Y-m-d');
        }

        if ($all) {
            $userId = '';
        } else {
            $userId = $this->user->ID;
        }

        $this->getSubjectManagers($userId, true);

        if (empty($this->payables)) {
            return [];
        }

        foreach ($this->payables as $index => $subject) {
            if ($index == 0) {
                $query    .= " AND (";
            } else {
                $query    .= " OR";
            }

            $query        .= " subject LIKE %s";
            $values[]    = "%" . $wpdb->esc_like($subject) . "%";
        }

        //sort on start_date
        $query    .= ") ORDER BY id ASC";

        // phpcs:disable
        return $wpdb->get_results(
            $wpdb->prepare($query, ...$values)
        );
        // phpcs:enable
    }

    /**
     * Retrieve all the pending bookings for the current user
     */
    public function retrievePendingBookings()
    {
        global $wpdb;

        /**
         * Only show unpaid bookings this user has permissions for
         */
        $this->getSubjectManagers($this->user->ID);

        if (empty($this->managers)) {
            return [];
        }

        $subjects       = array_keys($this->managers);

        $placeholders   = implode(', ', array_fill(0, count($subjects), '%s'));

        $query        = "SELECT * FROM %i WHERE pending = 1 AND start_date >= %s AND subject IN ($placeholders)";

        $values     = [
            $this->tableName,
            gmdate('Y-m-d'),
            ...$subjects
        ];

        foreach (array_keys($this->managers) as $index => $subject) {
            if ($index == 0) {
                $query    .= " AND (";
            } else {
                $query    .= " OR";
            }

            $query        .= " subject LIKE %s";
            $values[]    = "%" . $wpdb->esc_like($subject) . "%";
        }

        //sort on start_date
        $query    .= ") ORDER BY id ASC";

        // phpcs:disable
        return $wpdb->get_results(
            $wpdb->prepare($query, ...$values)
        );
        // phpcs:enable
    }

    /**
     * Creates an booking invoice pdf
     *
     * @param   object  $booking        The booking to create an invoice for
     * @param   object  $bookingEmail   The email to create the invoice for
     *
     * @return  string                  The path to the pdf invoice
     */
    protected function createInvoice($booking, $bookingEmail)
    {
        // Create a PDF invoice if possible
        if (!class_exists('TSJIPPY\PDF\PdfHtml')) {
            return [];
        }

        $pdf    = new TSJIPPY\PDF\PdfHtml();

        $pdf->skipFirstPage = false;

        $pdf->AddPage();

        $pdf->setHeaderTitle("Guesthouse Invoice INV" . sprintf("%06d", $booking->id));

        $pdf->Header();

        $pdf->SetFont('Arial', '', 10);

        $pdf->Write(10, "Invoice for {$bookingEmail->replaceArray['%subject%']} {$bookingEmail->replaceArray['%duration%']}");
        $pdf->Ln(10);

        $pdf->Write(10, "Payment Details");
        $pdf->Ln(10);

        // Calculate cell size
        $colWidths  = [0, 0];
        foreach ($bookingEmail->paymentDetailsRows as &$row) {

            $row    = str_replace(array_keys($bookingEmail->replaceArray), array_values($bookingEmail->replaceArray), $row);

            $cells  = explode(': ', $row);

            foreach ($cells as $index => $cell) {
                $width = round($pdf->GetStringWidth($cell)) + 5;

                if ($width > $colWidths[$index]) {
                    $colWidths[$index]  = $width;
                }
            }
        }
        unset($row);

        // Now write the rows
        $fill   = false;
        foreach ($bookingEmail->paymentDetailsRows as $row) {
            $cells  = explode(': ', $row);
            $pdf->writeTableRow($colWidths, $cells, $fill, []);
        }

        // Save the pdf
        $path   = get_temp_dir() . "Guesthouse Invoice INV{$booking->id}.pdf";

        wp_delete_file($path);

        $pdf->Output('F', $path);

        return $path;
    }

    /**
     * Sends a reminder to the owner of a booking to pay for it
     */
    public function sendPaymentReminders()
    {
        // no form loaded, load them all, and send payment reminder for each of them
        if (empty($this->forms->formData)) {
            $this->forms->getForms();

            // Send payment reminder for each form
            foreach ($this->forms->forms as $form) {
                $this->forms->getForm($form->id);

                $result = $this->getBookingElements(true);

                // this form has booking selector in it
                if (!is_wp_error($result) && !empty($result)) {
                    $this->sendPaymentReminders();
                }
            }

            return;
        }

        $processed  =   [];
        foreach ($this->retrieveUnPaidBookings(true, true) as $booking) {

            // no subject set or this form submission is already processed
            if (empty($booking->subject) || in_array($booking->submission_id, $processed)) {
                continue;
            }

            $this->forms->parseSubmissions('', $booking->submission_id, false, true);

            if (!$this->forms->submissions) {
                continue;
            }

            $processed[]    = $booking->submission_id;

            // Load the form
            $this->forms->getForm($this->forms->submission->form_id);

            $accommodation  = $booking->subject;

            // check if payment is enabled for this subject
            foreach ($this->subjects as $subject) {
                // this is the current subject
                if ($subject['name'] == $accommodation) {
                    if (!$subject['payments']) {
                        // do not continue if disabled
                        continue 2;
                    }

                    break;
                }
            }

            $userId         = $this->forms->submission->user_id;
            $email          = false;

            // Not an user
            if (!is_numeric($userId) || $userId == 0) {
                $user       = '';

                $nameElName = $this->forms->findUserNameElementName();
                if ($nameElName) {
                    $slug   = $this->forms->submission->{$nameElName};
                    $user   = (object) ['display_name' => $slug];
                }

                // Find the phone number
                $phoneElName    = $this->forms->findPhoneNumberElementName();

                if ($phoneElName) {
                    foreach ($this->forms->submission->{$phoneElName} as $number) {
                        if (str_starts_with($number, '+')) {
                            $phonenumber = $number;
                            break;
                        }
                    }
                }

                // Find the e-mail
                $emailElName        = $this->forms->findEmailElementName();
                if ($emailElName) {
                    $elementId      = $this->forms->getElementBySlug($emailElName, 'id');
                    $email          = $this->forms->submission->{$elementId};
                }
            } else {
                $user   = get_user($userId);
                $email  = $user->user_email;
            }

            /**
             * Filters whether we should send a payment reminder
             *
             * @param   bool    $continue       Whether we should continue
             * @param   object  $submission     The submission to be reminded about
             * @param   object  $user           The user to be send an e-mail to
             * @param   string  $email          The e-mail address
             * @param   object  $instance       This instance of the booking class
             */
            if (!$email || apply_filters('tsjippy-bookings-should-not-send-payment-reminder', false, $this->forms->submission, $user, $email, $this)) {
                continue;
            }

            // Send an e-mail
            $bookingEmail    = new BookingEmail($booking);
            $bookingEmail->filterMail();

            $subject        = $bookingEmail->subject;
            $message        = $bookingEmail->message;
            $headers        = $bookingEmail->headers;

            // Add to the attachments
            $attachments[]  = $this->createInvoice($booking, $bookingEmail);

            add_filter('wp_mail', [$this->forms, 'addFormData'], 1);
            wp_mail($email, $subject, $message, $headers, $attachments);
            remove_filter('wp_mail', [$this->forms, 'addFormData'], 1);
        }
    }

    /**
     * Adds the buttons to approve or delete a pending booking
     *
     * @param   array   $buttonsHtml   The current html for the buttons
     * @param   object  $submission    The submission for which the buttons are shown
     * @param   int     $subId         The id of the submission
     * @param   object  $object        The bookings object
     *
     * @return  string                  The updated html for the buttons
     */
    public function pendingButtons($buttonsHtml, $submission, $subId, $object)
    {
        $buttonsHtml['approve'] = "<button class='button approve' type='button' data-submission-id='{$submission->id}' data-form-id='{$object->submission->form_id}'>Approve</button>";
        $buttonsHtml['delete']  = "<button class='button delete' type='button' data-submission-id='{$submission->id}' data-form-id='{$object->submission->form_id}'>Delete</button><br>";
        unset($buttonsHtml['archive']);

        return $buttonsHtml;
    }

    /**
     * Shows the html to list, approve and or delete pending bookings
     *
     * @param   string  $type       One of approval or payment to show bookings that are pending approval or pending payment
     */
    public function pendingBookingsHtml($type = 'approval')
    {
        /**
         * Only managers should see this
         */
        $this->getSubjectManagers($this->user->ID);

        if (count($this->managers) == 1) {
            return '';
        }

        wp_enqueue_script('tsjippy_forms_table_script');

        if ($type == 'approval') {
            $bookings    = $this->retrievePendingBookings();
        } else {
            $bookings    = $this->retrieveUnPaidBookings(true);
        }

        if (empty($bookings)) {
            return '';
        }

        $html   = "<h4>Bookings Pending " . ucfirst($type) . "</h4>";

        $submissions    = [];

        // Add a sub id to bookings which is equal to the booked room
        foreach ($bookings as $booking) {
            // one submission can have multiple bookings, only load the submission once
            if (empty($this->forms->submission) || $this->forms->submission->id != $booking->submission_id) {
                $submission         = $this->forms->getSubmissions('', $booking->submission_id)[0];

                // Submission not found
                if (!$submission) {
                    continue;
                }

                $submissions[]  = $submission;
            }
        }

        if (empty($submissions)) {
            return '';
        }

        if ($type == 'approval') {
            add_filter('tsjippy_form_actions_html', [$this, 'pendingButtons'], 10, 4);
        }

        ob_start();

        $this->forms->theTable('all', $submissions);

        $html   .= ob_get_clean();

        if ($type == 'approval') {
            remove_filter('tsjippy_form_actions_html',  [$this, 'pendingButtons'], 10);
        }

        return $html;
    }

    /**
     * Calculate the total amount due after booking update
     *
     * @param   array   $startDates    The start dates of the booking
     * @param   array   $endDates      The end dates of the booking
     *
     * @return  string                  The total amount due
     */
    public function calculatePaymentAmount($startDates, $endDates)
    {
        $startDates = TSJIPPY\cleanUpNestedArray($startDates);
        $endDates   = TSJIPPY\cleanUpNestedArray($endDates);

        $pricePerNightElId    = $this->forms->formData->price_per_night_el;

        if (empty($pricePerNightElId) || empty($startDates) || empty($endDates)) {
            return;
        }

        $nights = 0;
        foreach ($startDates as $index => $startDate) {
            if (empty($endDates[$index])) {
                $endDate    = array_values($endDates)[0]; // assume the end date is the same as the first given one
            } else {
                $endDate    = $endDates[$index];
            }
            $diff       = strtotime($endDate) - strtotime($startDate);

            $days       = round($diff / DAY_IN_SECONDS);

            $nights     = $nights + $days;
        }

        $pricePerNight      = $this->forms->submission->{$pricePerNightElId};
        if (empty($pricePerNight)) {
            TSJIPPY\printArray("Price per night not found in submission with id {$this->forms->submission->id}");
            return;
        }

        preg_match('/(.*?)([\d+|\.|,]+)/', "$pricePerNight", $matches);
        $amount             = $matches[2];
        $currency           = $matches[1];

        // Formatted number
        $payable            = $currency . number_format(intval(str_replace(",", "", $amount)) * $nights, 2);

        return $payable;
    }
}
