<?php
namespace SIM\BOOKINGS;
use SIM;

add_filter('sim_frontend_posting_modals', __NAMESPACE__.'\postingModals');
function postingModals($types){
    $types[]	= 'booking subject';
    return $types;
}

add_action('sim_frontend_post_content_title', __NAMESPACE__.'\contentTitle');
function contentTitle($postType){
    // Book content title
    $class = 'property booking subject';
    if($postType != 'booking subject'){
        $class .= ' hidden';
    }

    echo "<h4 class='$class' name='location-content-label'>";
        echo 'Please describe the location';
    echo "</h4>";
}

add_action('sim_after_post_save', __NAMESPACE__.'\afterPostSave', 10, 2);
function afterPostSave($post, $frontEndPost){
    if($post->post_type != 'booking subject'){
        return;
    }

    foreach($_POST as $meta => $value){
        if(empty($_POST[$meta])){
            delete_post_meta($post->ID, $meta);
        }elseif(gettype($value) == 'array'){
            $curValues = get_post_meta($post->ID, $meta);
            $newValues = array_map('sanitize_text_field', $value);

            $deleted  = array_diff($curValues, $newValues);
            foreach($deleted as $value){
                delete_metadata( 'post', $post->ID, $meta, $value);
            }

            $added    = array_diff($newValues, $curValues);
            foreach($added as $value){
                add_metadata( 'post', $post->ID, $meta, $value);
            }
        }else{
            //Store value
            update_metadata( 'post', $post->ID, $meta, sanitize_text_field($value));
        }
    }
}

//add meta data fields
add_action('sim_frontend_post_after_content', __NAMESPACE__.'\afterPostContent', 10, 2);
function afterPostContent($object){

    if(!empty($object->post) && $object->post->post_type != 'booking subject'){
        return;
    }

    //Load js
    //wp_enqueue_script('sim_book_script');

    $postId     = $object->postId;
    $postName   = $object->postName;
    
    ?>
    <style>
        .form-table, .form-table th, .form-table, td{
            border: none;
        }
        .form-table{
            text-align: left;
        }
    </style>
    <div id="booking-subject-attributes" class="property booking-subject<?php if($postName != 'booking subject'){echo ' hidden';} ?>">
        <input type='hidden' class='no-reset' class='no-reset' class='no-reset' name='static-content' value='static-content'>
            
        <fieldset id="booking-subject" class="frontend-form">
            <legend>
                <h4>Subject details</h4>
            </legend>

            <table class="form-table">
                <?php
                foreach(get_post_meta($postId) as $index => $meta){
                    $key    = $meta;
                    $values = $meta;
                    ?>
                    <tr>
                        <th><label><?php echo ucfirst($key);?></label></th>
                        <td>
                            <?php
                            if(is_array($values)){
                                ?>
                                <div class="clone-divs-wrapper">
                                    <?php
                                    foreach($values as $index => $value){
                                        if(is_array($value)){
                                            $value = implode(',', $value);
                                        } 

                                        ?>
                                        <div id="<?php echo $meta;?>-div-<?php echo $index;?>" class="clone-div" data-div-id="<?php echo $index;?>">
                                            <div class='button-wrapper'>
                                                <input type='text' class='formbuilder' name='<?php echo $meta;?>[]' value='<?php echo $value; ?>' style='width: calc(100% - 70px);'>
                                                <button type="button" class="add button" style="flex: 1;">+</button>
                                                <button type="button" class="remove button" style="flex: 1;">-</button>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <?php
                            }else{
                                $value = $values;
                                $type ='text';
                                if(is_numeric($value)){
                                    $type ='number';
                                }
                                ?>
                                <input type='<?php echo $type;?>' class='formbuilder' name='<?php echo $meta;?>' value='<?php echo $value; ?>'>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </table>
        </fieldset>
    </div>
    <?php
}