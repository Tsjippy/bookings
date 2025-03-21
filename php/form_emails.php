<?php
namespace SIM\BOOKINGS;
use SIM;

add_action('sim-forms-after-email-triggers', __NAMESPACE__.'\addBookingEmails', 10, 2);

function addBookingEmails($key, $email){
    ?>
    <label>
        <input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='before-stay' <?php if($email['emailtrigger'] == 'before-stay'){echo 'checked';}?>>
        <input type='number'name='emails[<?php echo $key;?>][days-before]' <?php if(!empty($email['days-before'])){echo "value='{$email['days-before']}'";}?> style='max-width: 70px;'> days before their booking starts
    </label>
    <br>
    <label>
        <input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='after-stay' <?php if($email['emailtrigger'] == 'after-stay'){echo 'checked';}?>>
        <input type='number'name='emails[<?php echo $key;?>][days-after]' <?php if(!empty($email['days-after'])){echo "value='{$email['days-after']}'";}?> style='max-width: 70px;'> days after their booking finished (0 means on the end date)
    </label>
    <br>
    <?php
}