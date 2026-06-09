<?php

namespace TSJIPPY\BOOKINGS;

use TSJIPPY;

/**
 * The content of a book shared between a single post, archive or the recipes page.
 **/

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$archive    = false;
if (is_tax() || is_archive()) {
    $archive    = true;
}

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="cat-card<?php if ($archive) {
                            echo ' inside-article';
                        } ?>">

        <?php
        if ($archive) {
            $url = get_permalink(get_the_ID());
            the_title("<h3 class='archivetitle'><a href='$url'>", '</a></h3>', true);
        } else {
            do_action('tsjippy_before_content');
        }
        ?>
        <div class="description">
            <?php
            //Only show summary on archive pages
            if ($archive) {
                $excerpt = force_balance_tags(wp_kses_post(get_the_excerpt()));
                if (empty($excerpt)) {
                    $url = get_permalink();
            ?>
                    <br>
                    <a href='<?php echo esc_url($url); ?>'>
                        View description »
                    </a>
            <?php
                } else {
                    echo wp_kses_post($excerpt);
                }
                //Show everything including category specific content
            } else {
                if (empty($post->post_content)) {
                    /** @disregard P1008 */
                    echo wp_kses_post(apply_filters('tsjippy_empty_description', 'No content found... ', $post));
                }

                the_content();
            }

            wp_link_pages(
                array(
                    'before' => '<div class="page-links">Pages:',
                    'after'  => '</div>',
                )
            );
            ?>
        </div>

        <div class='actions'>
            <?php
            global $wpdb;

            $elementId    = get_post_meta(get_the_ID(), 'tsjippy_element-id', true);
            if (empty($elementId)) {
                $parentId    = wp_get_post_parent_id();

                if ($parentId) {
                    $elementId    = get_post_meta($parentId, 'tsjippy_element-id', true);
                }
            }

            if (!empty($elementId)) {
                $bookings    = new Bookings();

                $bookings->forms->formData->id        = $wpdb->get_var($wpdb->prepare("SELECT form_id FROM {$bookings->forms->elTableName} WHERE ID=%d", $elementId));

                $bookings->forms->getForm();
            ?>
                <a href='<?php echo esc_url($bookings->forms->formData->form_url); ?>' class='tsjippy button' target='_blank'>
                    Book this accomodation
                </a>
            <?php
            }
            ?>
        </div>
    </div>
</article>