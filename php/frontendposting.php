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
    $class = 'property book';
    if($postType != 'book'){
        $class .= ' hidden';
    }

    echo "<h4 class='$class' name='location-content-label'>";
        echo 'Please describe the location';
    echo "</h4>";
}

add_action('sim_after_post_save', __NAMESPACE__.'\afterPostSave', 10, 2);
function afterPostSave($post, $frontEndPost){
    if($post->post_type != 'subject description'){
        return;
    }

    foreach(METAS as $meta=>$type){
        if(isset($_POST[$meta])){
            if(empty($_POST[$meta])){
                delete_post_meta($post->ID, $meta);
            }elseif($type == 'array'){
                $curValues = get_post_meta($post->ID, $meta);
                $newValues = array_map('sanitize_text_field', $_POST[$meta]);

                $deleted  = array_diff($curValues, $newValues);
                foreach($deleted as $value){
                    delete_metadata( 'post', $post->ID, $meta, $value);

                    if($meta == 'author' || $meta == 'location'){
                        $taxonomy = $meta == 'author' ? 'authors' : 'book-locations';
                        
                        //Remove author or location from the post
                        $term = get_term_by('name', $value, $taxonomy);

                        wp_remove_object_terms($post->ID, $term->term_id, $taxonomy);
                    }
                }

                $added    = array_diff($newValues, $curValues);
                foreach($added as $value){
                    if($meta == 'author'){
                        $library->processAuthor($value, $post->ID);
                    }else{
                        add_metadata( 'post', $post->ID, $meta, $value);

                        if($meta == 'location'){
                            wp_set_post_terms($post->ID, [$value], 'book-locations', true);
                        }
                    }
                }
            }else{
                //Store value
                update_metadata( 'post', $post->ID, $meta, sanitize_text_field($_POST[$meta]));
            }
        }
    }
}

//add meta data fields
add_action('sim_frontend_post_after_content', __NAMESPACE__.'\afterPostContent', 10, 2);
function afterPostContent($frontendcontend){

    if(!empty($frontendcontend->post) && $frontendcontend->post->post_type != 'book'){
        return;
    }

    //Load js
    wp_enqueue_script('sim_book_script');

    $postId     = $frontendcontend->postId;
    $postName   = $frontendcontend->postName;
    
    ?>
    <style>
        .form-table, .form-table th, .form-table, td{
            border: none;
        }
        .form-table{
            text-align: left;
        }
    </style>
    <div id="book-attributes" class="property book<?php if($postName != 'book'){echo ' hidden';} ?>">
        <input type='hidden' name='static-content' value='static-content'>
            
        <fieldset id="book" class="frontend-form">
            <legend>
                <h4>Book details</h4>
            </legend>

            <table class="form-table">
                <?php
                foreach(METAS as $meta=>$type){
                    if($type=='url'){
                        $type   = 'text';
                    }

                    switch ($meta){
                        default:
                            $text   = $meta;
                    }

                    ?>
                    <tr>
                        <th><label for="<?php echo $meta;?>"><?php echo ucfirst($text);?></label></th>
                        <td>
                            <?php
                            if($type == 'array'){
                                $type   = 'text';

                                $values = get_post_meta($postId, $meta);
                                if(empty($values)){
                                    $values[] = '';
                                }

                                ?>
                                <div class="clone-divs-wrapper">
                                    <?php
                                    foreach($values as $index=>$value){
                                        if(is_array($value)){
                                            $value = implode(',', $value);
                                        } 

                                        ?>
                                        <div id="<?php echo $meta;?>-div-<?php echo $index;?>" class="clone-div" data-div-id="<?php echo $index;?>">
                                            <div class='button-wrapper'>
                                                <input type='<?php echo $type;?>' class='formbuilder' name='<?php echo $meta;?>[]' value='<?php echo $value; ?>' style='width: calc(100% - 70px);'>
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
                                $value = get_post_meta($postId, $meta, true);
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