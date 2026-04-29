<?php
namespace TSJIPPY\BOOKINGS;
use TSJIPPY;


function displayLocationTax(){
    wp_enqueue_style('tsjippy_taxonomy_style');

    global $post;
    global $wp_query;

    if($wp_query->is_embed){
        $skipWrapper	= true;
    }

    if($skipWrapper){
        displayBooks();
    }else{
        if(!isset($skipHeader) || !$skipHeader){
            get_header(); 
        }

        ?>
        <div id="primary">
            <main id="main" class='taxonomy inside-article'>
                <button type='button' class='tsjippy button add-books' onclick='Main.showModal(`add-books`)'>Add books</button>
                <?php displayBooks();?>
            </main>
        </div>
        <?php
		if(function_exists('generate_construct_sidebars')){
			// @disregard P1010
        	generate_construct_sidebars();
		}

        if(!isset($skipFooter) || !$skipFooter){
            get_footer();
        }
    }
}

function displayBooks(){
	$name 				= get_queried_object()->name;
	if ( have_posts() ){
		do_action('tsjippy_before_archive', 'book');

		//only show the map if logged in
		if(is_user_logged_in() ){
			$mapName			= $name."_map";
			$mapId				= SETTINGS[$mapName] ?? '';

			if(is_numeric($mapId)){
				//Show the map of this category
				echo "<div style='margin-bottom:25px;'>";
					echo do_shortcode("[ultimate_maps id='$mapId']");
				echo '</div>';
			}
		}
	
		while ( have_posts() ) :
			the_post();
			include(__DIR__.'/content.php');
		endwhile;

		the_posts_pagination();
	}else{
		//No items with this category
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="no-results not-found">
			<div class="inside-article">
				<div class="entry-content">
					<?php echo apply_filters('tsjippy-empty-taxonomy', "There are no $name books yet", 'book'); ?>
				</div>
			</div>
		</div>
		</article>
		<?php
	}
}