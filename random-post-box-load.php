<?php
/**
 * @package Random Post Box
 * @author Mattias Wirf <mattias.wirf@gmail.com>
 * @license http://www.opensource.org/licenses/gpl-3.0.html
 */
 session_start();
// Get files needed for extrnal files
 define('WP_USE_THEMES', false);
 require('../../../wp-load.php');

// Check Nonce
 if (!check_ajax_referer('random-post-box-ajax-call')) {
	die();
 }

// Get options
 $rpb_options = rpb_get_options();
 

// --- Build query -------------------------
// 
	// Check for date limit by filter function, {@see random-post-box.php}
	if ($rpb_options['post_datelimit']) {
		add_filter('posts_where', 'rpb_date');
	}

	// One post and get it on random sorting
	if (is_array($wp_query->query)) {
		foreach($wp_query->query as $wp=>$q) {
			$args[$wp] = $q;	
		}
	}
	$args['showposts'] = 1;
	$args['orderby'] = 'rand';
	$args['post_type'] = 'post';
	unset($args['pagename']);
	
	// If option is to exclude posts from categories
	if ($rpb_options['cat_method'] == 'exclude' && !empty($rpb_options['cat_items'])) {
		
		// Could be changes to better var category__not_in
		$args['category__not_in'] = explode(',', str_replace(' ', '', $rpb_options['cat_items']));

	// or if it is to include posts from categories
	} elseif ($rpb_options['cat_method'] == 'include' && !empty($rpb_options['cat_items']))  {

		// Could be changes to better var category__not_in
		$args['category__in'] = explode(',', str_replace(' ', '', $rpb_options['cat_items']));
		
	}
	// If a post has been shown
	if (isset($_SESSION['rpb_last_post'])) {

		// Exclude previous post from showing
		$args['post__not_in'] = array($_SESSION['rpb_last_post']);
		unset($_SESSION['rpb_last_post']);
		
	}
// ---------------------------------------
	

// Load query for second loop
 $rpb_query = new WP_Query();
 $rpb_query->query($args);

// Check that there was a post returned
 if ($rpb_query->have_posts()) :
	 // The LOOP
	 while ($rpb_query->have_posts()) : $rpb_query->the_post();

		// Save id to not show next run
		$_SESSION['rpb_last_post'] = get_the_ID();

		// Should only titles be used?
		if ($rpb_options['title_only']) {
			
			?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php
			echo "\n";
			
		// Or the content to?
		} else {
			
			?>
			<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<div class="entry"><?php
			// Should excerpt be used and is there one
			if ($rpb_options['excerpt'] && strlen(get_the_excerpt()) > 5) {
				
				$excerpt = get_the_excerpt();
				if (substr($excerpt, strlen($excerpt)-1, 1) == '.') {
					$excerpt .= '..';
				} else {
					$excerpt .= '...';
				}
				echo $excerpt;
				
			// Otherwise use the whole post
			} else {
				
				if ($rpb_options['excerpt']) {
					
					echo rpb_cut_string(get_the_content(), 255, '...', true);

				} else {
					
					// Take away tags if settings say so
					if ($rpb_options['strip_tags']) {
						echo strip_tags(get_the_content(),'<br />');
					// Or just print
					} else {
						the_content();
					}
					
				}
				
			}
			?></div>
			<?php
			if ($rpb_options['show_meta']) {
					?>
					<p class="postmetadata"><?php the_tags(__("Tags: ", 'random-post-box'), ', ', '<br />'); ?> Posted in <?php the_category(', ') ?> <?php the_date(); ?> | <?php edit_post_link(__("Edit", 'random-post-box'), '', ' | '); ?>  <?php comments_popup_link(__("No Comments &#187;", 'random-post-box'), __("1 Comment &#187;", 'random-post-box'), __("% Comments &#187;", 'random-post-box')); ?></p>
					<?php
			}
			?>
			<?php
		}
	endwhile;
 endif;
?>
