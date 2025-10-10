<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('sim-forms-after-email-triggers', __NAMESPACE__.'\addBookingEmails', 10, 2);

function addBookingEmails($key, $email){
    ?>
    <label>
        <input type='radio' name='emails[<?php echo $key;?>][email-trigger]' class='email-trigger' value='before-stay' <?php if($email->email_trigger == 'before-stay'){echo 'checked';}?>>
        <input type='number'name='emails[<?php echo $key;?>][days-before]' <?php if(!empty($email->days_before)){echo "value='{$email->days_before}'";}?> style='max-width: 70px;'> days before their booking starts
    </label>
    <br>
    <label>
        <input type='radio' name='emails[<?php echo $key;?>][email-trigger]' class='email-trigger' value='after-stay' <?php if($email->email_trigger == 'after-stay'){echo 'checked';}?>>
        <input type='number'name='emails[<?php echo $key;?>][days-after]' <?php if(!empty($email->days_after)){echo "value='{$email->days_after}'";}?> style='max-width: 70px;'> days after their booking finished (0 means on the end date)
    </label>
    <br>
    <?php
}