<?php
/**
 * Plugin Name: Random Post Box
 * Plugin URI: http://www.open-source-editor.com/wordpress/random-post-box.html
 * Description: Displays a div which shows random posts in a slideshow.
 * Version: 1.0.3
 * Author: Mattias Wirf
 * Author URI: http://www.open-source-editor.com
 *
 * @package Random Post Box
 * @author Mattias Wirf <mattias.wirf@gmail.com>
 * @license http://www.opensource.org/licenses/gpl-3.0.html
 *
 * --------------------------------------------------------
 *   A plugin for Wordpress for loading a box with random post continously
 *   Copyright (C) 2010 Mattias Wirf
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------
 *
 * --- Future Development ---
 * @todo Make exclude/include categories to a list to choose from
 * @todo Exclude posts by offset number, not just date
 * @todo Exclude / Include posts per id
 * @todo Exclude / Include by tag
 * @todo Widget
 * @todo Exclude / Include by author
 * @todo A better way to enqueue the part in rpb_head() function
 * @todo Theme-template for box
 * @todo An approach to sticky posts
 * --------------------------
 * 
 */

// Activation of the plugin
 register_activation_hook(__FILE__, 'rpb_activate');
// Deactivation of this plugin
 register_deactivation_hook( __FILE__, 'rpb_deactivate');
// Runs when Wordpress is finished loading but before headers
 add_action('init', 'rpb_init');
// Add menu option (hook, function)
 add_action('admin_menu', 'rpb_manage');
// Add to theme header
 add_action('wp_head', 'rpb_head');
// Add shortcode
 add_shortcode('random-post-box', 'rpb_quick');



/**
 * Installation of this plugin. Not much is done,
 * just addition of the default options.
 */
 function rpb_activate() {

	// Check access
	if (!current_user_can('install_plugins')) {
		return new WP_Error('rpb-access-failure', __("Permission denied for activating Random Post Box.", 'random-post-box'));
	}

	// Build optionstring - get defaults optionsarray and make it to a string
	$rpb_options_string = rpb_make_options(rpb_default_options());
	
	// Add to db
	add_option('rpb-options', $rpb_options_string);
	
 }
 function rpb_deactivate() {
	 // Do something
 }


 
/**
 * Admin menu. This adds a page to the administration menus options section.
 * @return void
 */
 function rpb_manage() {

	 // Add options page ('Page title', 'Menu title', 'Access', 'File', 'Function)
	 add_options_page(__("Random Post Box Options", 'random-post-box'), __("Random Post Box", 'random-post-box'), 'manage_options', __FILE__, 'rpb_options');
	 
 }


 
/**
 * Options page
 * @return void
 */
 function rpb_options() {

	// Check access
	if (!current_user_can('manage_options')) {
		return new WP_Error('rpb-access-failure', __("Permission denied for updating Random Post Box options.", 'random-post-box'));
	}

	?>
	<div class="wrap">
		<div id="icon-edit" class="icon32"></div>  <h2><?php _e("Random Post Box Options", 'random-post-box'); ?></h2>
		<?php
		if ($_POST['rpb_submit']) {

			$update = rpb_options_update();

			if (is_wp_error($update)) {

				echo '<div class="error">' . $update->get_error_message() . '</div>';

			} elseif (!$update) {

				echo '<div class="error">' . __("Could not edit bonusoffer", 'random-post-box') . '</div>';

			} else {
				
				echo '<div class="ok">' . __("Thanks for updating the Random Post Box options!.", 'random-post-box') . '</div>';

			}
		}
		?>
		<?php rpb_options_form(); ?>
	</div>
	<?php

 }


 
/**
 * Print the form with options
 * @return void
 */
 function rpb_options_form() {

	// Get options
	$options = rpb_get_options();
	
	?>
	<form method="post" action="">
		<?php wp_nonce_field('random-post-options-form'); ?>
		<fieldset>
			<legend><h3><?php echo _e("Timing", 'random-post-box'); ?></h3></legend>
			<ul>
				<li><select name="rpb_delay_out" id="rpb_delay_out" class="rpb-admin-select">
					<?php
					for ($i = 0; $i <= 5000; $i += 500) {
						?>
						<option value="<?php echo $i; ?>"<?php
						// Check if it is previous chosen value
						if ($options['delay_out'] == $i) {
							echo ' selected="selected"';
						}
						?>><?php
						// Turn into seconds, people don't get milliseconds ;)
						$sec = round($i / 1000, 1);
						// Make it look nice and add a zero to even seconds, the print
						echo strlen($sec) == 1 ? $sec . '.0' : $sec;
						// Print suffix
						echo ' ' . __('seconds');
						?>
						</option>
						<?php
					}
					?>
				</select> <label for="rpb_delay_out"><?php _e("delay fading message out", 'random-post-box'); ?></label></li>
			
				<li><select name="rpb_delay_in" id="rpb_delay_in" class="rpb-admin-select">
					<?php
					for ($i = 0; $i <= 5000; $i += 500) {
						?>
						<option value="<?php echo $i; ?>"<?php
						// Check if it is previous chosen value
						if ($options['delay_in'] == $i) {
							echo ' selected="selected"';
						}
						?>><?php
						// Turn into seconds, people don't get milliseconds ;)
						$sec = round($i / 1000, 1);
						// Make it look nice and add a zero to even seconds, the print
						echo strlen($sec) == 1 ? $sec . '.0' : $sec;
						// Print suffix
						echo ' ' . __('seconds') . ' ';
						?>
						</option>
						<?php
					}
					?>
				</select> <label for="rpb_delay_in"><?php _e("delay fading message in", 'random-post-box'); ?></label></li>

				<li><select name="rpb_delay_interval" id="rpb_delay_interval" class="rpb-admin-select">
					<?php
					for ($i = 1000; $i <= 20000; $i += 1000) {
						?>
						<option value="<?php echo $i; ?>"<?php
						// Check if it is previous chosen value
						if ($options['delay_interval'] == $i) {
							echo ' selected="selected"';
						}
						?>><?php
						// Turn into seconds, people don't get milliseconds ;)
						$sec = round($i / 1000, 1);
						// Print suffix
						echo $sec . ' ' . __("seconds", 'random-post-box') . '&nbsp;';
						?>
						</option>
						<?php
					}
					for ($i = 60000; $i <= 600000; $i += 60000) {
						?>
						<option value="<?php echo $i; ?>"<?php
						// Check if it is previous chosen value
						if ($options['delay_interval'] == $i) {
							echo ' selected="selected"';
						}
						?>><?php
						// Turn into seconds, people don't get milliseconds ;)
						$sec = round($i / 1000, 1);
						// Print suffix
						echo $sec . ' ' . __("seconds", 'random-post-box') . '&nbsp;';
						?>
						</option>
						<?php
					}
					?>
				</select> <label for="rpb_delay_interval"><?php _e(" message is displayed in the box", 'random-post-box'); ?></label></li>
			</ul>
		</fieldset>

		<fieldset>

			<legend><h3><?php  _e("Display in post", 'random-post-box'); ?></h3></legend>
			<ul>
				<li><input type="checkbox" id="rpb_title_only" name="rpb_title_only"<?php echo $options['title_only'] ? ' checked="checked"' : ''; ?> />
				<label for="rpb_title_only"><?php _e("Use titles only", 'random-post-box'); ?></label></li>
				
				<li><input type="checkbox" id="rpb_excerpt" name="rpb_excerpt"<?php echo $options['excerpt'] ? ' checked="checked"' : ''; ?> />
				<label for="rpb_excerpt"><?php _e("Use excerpt for display", 'random-post-box'); ?></label></li>

				<li><input type="checkbox" id="rpb_strip_tags" name="rpb_strip_tags"<?php echo $options['strip_tags'] ? ' checked="checked"' : ''; ?> />
				<label for="rpb_strip_tags"><?php _e("Strip tags", 'random-post-box'); ?></label>
				<small>(<?php _e("Not used if \"Use excerpt...\" is set to true)", 'random-post-box'); ?></small></li>

				<li><input type="checkbox" id="rpb_show_meta" name="rpb_show_meta"<?php echo $options['show_meta'] ? ' checked="checked"' : ''; ?> />
				<label for="rpb_show_meta"><?php echo __("Show post meta", 'random-post-box') . ' <small>' . __("(category, tags, author)", 'random-post-box') . '</small>'; ?></label></li>
			</ul>
			
		</fieldset>

		<fieldset>
			
			<legend><h3><?php _e("Posts selection", 'random-post-box'); ?></h3></legend>
			<p><em><?php _e("Some advice: To guarantee that something will be shown in the box, you need to set these
							options so the script can find more then two posts.<br />If you only have 1 post older then 30
							days, don't set it to exclude everything from the last 30 days.", 'random-post-box'); ?></em></p>
			<ul>
				
				<li><select id="rpb_cat_method" name="rpb_cat_method">
						<option value="exclude"<?php echo $options['cat_method'] == 'exclude' ? ' selected="selected"': ''; ?>><?php _e("Exclude", 'random-post-box'); ?> </option>
						<option value="include"<?php echo $options['cat_method'] == 'include' ? ' selected="selected"': ''; ?>><?php _e("Include", 'random-post-box'); ?> </option>
				</select>
				<label for="rpb_cat_items"><?php _e("posts from these categories", 'random-post-box'); ?></label>
				<input type="text" value="<?php echo $options['cat_items']; ?>" name="rpb_cat_items" id="rpb_cat_items" />
				<small>(<?php _e("use category id's and seperated with comma", 'random-post-box'); ?>)</small></li>

				<li><label for="rpb_post_datelimit"><?php _e("Posts older then", 'random-post-box'); ?>
					<select name="rpb_post_datelimit" id="rpb_post_datelimit">
						<?php
						for ($i = 0; $i <= 9; $i++) {
							?><option value="<?php echo $i; ?>"<?php echo $options['post_datelimit'] == $i ? ' selected="selected"' : ''; ?>><?php echo $i; ?></option><?php
							echo "\n";
						}
						for ($i = 10; $i <= 60; $i += 10) {
							?><option value="<?php echo $i; ?>"<?php echo $options['post_datelimit'] == $i ? ' selected="selected"' : ''; ?>><?php echo $i; ?></option><?php
							echo "\n";
						}
						?>
					</select>
					<?php _e("days can be selected.", 'random-post-box'); ?></label></li>
				
			</ul>

		</fieldset>

		<fieldset>			
			<p class="submit"><input name="rpb_submit" id="rpb_submit" class="button-primary" value="<?php _e("Save Changes", 'random-post-box'); ?>" type="submit" /></p>
		</fieldset>

	</form>
	<?php
	
 }


 
/**
 * Update options sent from form
 */
 function rpb_options_update() {

	// Check referer
	check_admin_referer('random-post-options-form');

	// Prepare array
	$options = array();

	// Get values
	if (isset($_POST['rpb_delay_out'])) {
		$options['delay_out'] = intval($_POST['rpb_delay_out']);
	}
	if (isset($_POST['rpb_delay_in'])) {
		$options['delay_in'] = intval($_POST['rpb_delay_in']);
	}
	if (isset($_POST['rpb_delay_interval'])) {
		$options['delay_interval'] = intval($_POST['rpb_delay_interval']);
	}
	// Post display
	if (isset($_POST['rpb_title_only']) && ($_POST['rpb_title_only'] == 'on')) {
		$options['title_only'] = 1;
	} else {
		$options['title_only'] = 0;
	}
	if (isset($_POST['rpb_excerpt']) && ($_POST['rpb_excerpt'] == 'on')) {
		$options['excerpt'] = 1;
	} else {
		$options['excerpt'] = 0;
	}
	if (isset($_POST['rpb_strip_tags']) && ($_POST['rpb_strip_tags'] == 'on')) {
		$options['strip_tags'] = 1;
	} else {
		$options['strip_tags'] = 0;
	}
	if (isset($_POST['rpb_show_meta']) && ($_POST['rpb_show_meta'] == 'on')) {
		$options['show_meta'] = 1;
	} else {
		$options['show_meta'] = 0;
	}
	// Exclude or include
	if (isset($_POST['rpb_cat_method']) && ($_POST['rpb_cat_method'] == 'include')) {
		$options['cat_method']	= 'include';
	} else {
		$options['cat_method']	= 'exclude';
	}
	if (isset($_POST['rpb_cat_items'])) {
		$options['cat_items'] = $_POST['rpb_cat_items'];
	}
	if (isset($_POST['rpb_post_datelimit']) && is_numeric($_POST['rpb_post_datelimit'])) {
		$options['post_datelimit'] = intval($_POST['rpb_post_datelimit']);
	}

	// Make options-string
	$options_string = rpb_make_options($options);

	// Update databasw with change
	if (get_option('rpb-options') != $options_string) {
		if (update_option('rpb-options', $options_string)) {
			return true;
		} else {
			return new WP_Error('rpb-update-failure', __("Could not update Random Post Box options", 'random-post-box'));
		}
	} else {
		return true;
	}
	
 }


 
/**
 * Get options. Stored as array in db (no fancy stuff),
 * this is just a way to get them easy.
 * @return array
 */
 function rpb_get_options() {

	 // Get data
	 $tmp = get_option('rpb-options');

	 // The options
	 $options = explode(';', $tmp);

	 // Prepare an array variable
	 $return = array();
	 
	 // Loop and fill array with assoc keys and values
	 foreach ($options as $o) {

		$key_and_value = explode('=', $o);
		$return[$key_and_value[0]] = $key_and_value[1];
		unset($key_and_value);
		
	 }

	 // Send back
	 return $return;

 }


 
/**
 * Check and make options string before
 * adding to database.
 * @param arr $options		The assoc array to inplode.
 * @return str
 */
 function rpb_make_options($options) {

	 // Check that it's an array
	 if (!is_array($options)) {
		 return WP_Error('random-post-box', __("Input to <code>rpb_make_options()</code> was incorrect.", 'random-post-box'));
	 }

	 // Loop array
	 $return = '';
	 foreach($options as $k=>$o) {
		 $return .= $k . '=' . $o . ';';
	 }

	 // Return string without trailing ";"
	 return substr($return, 0, (strlen($return) - 1));
	 
 }


 
/**
 * Make a list of default options
 * This is basically used when installing/activating the
 * plugin to get the plugin working.
 */
 function rpb_default_options() {

	 $options = array();

	// for JQuery
	 $options['delay_out']		= 1000;
	 $options['delay_in']		= 1500;
	 $options['delay_interval'] = 5000;

	// For postdisplay
	 $options['title_only']		= 0;
	 $options['excerpt']		= 0;
	 $options['strip_tags']		= 0;
	 $options['show_meta']		= 0;

	// For post inclution/exclution
	 $options['cat_method']		= 'exclude';
	 $options['cat_items']		= '';
	 $options['post_datelimit'] = 14;

	 return $options;

 }


 
/**
 * Template tag. Just a way
 * to print the result som
 * @see get_random_post()
 * @return void
 */
 function random_post_box() {

	echo get_random_post_box();
	
 }


 
/**
 * Get tag which is filled with posts.
 * Pretty easy realy. Just a div with
 * an id.
 * @return str
 */
 function get_random_post_box() {
	 
	 return '<div id="random-post-box-frame"><div id="random-post-box"></div></div>';
	 
 }


 
/**
 * Quick tag handler
 * By writing [rpb_quick] in
 * a post or page the user can
 * call on this function.
 */
 function rpb_quick() {

	 // Do something
	 return get_random_post_box();
	 
 }


 
/**
 * Initialize, load language and some more.
 * @return void
 */
 function rpb_init() {

	// Get the path to languages
	$plugin_lang_path = plugin_basename(dirname( __FILE__ ) . '/lang');
	
	// Load language-functions
	load_plugin_textdomain('random-post-box', $plugin_lang_path);
	
	// Make sure JQuery is loaded
	wp_enqueue_script('jquery');
	
 }


 
/**
 * Prints to template header.
 * Some JS-code relying on jQuery. This
 * fades the posts in and out by an
 * AJAX-call.
 * @todo Get fadeout to work
 * @return mixed			String is returned if not printed
 */
 function rpb_head() {

	// Get options
	$rpb_options = rpb_get_options();
	
	// Check for vital options
	$rpb_options['delay_out'] = is_numeric($rpb_options['delay_out']) && ($rpb_options['delay_out'] > 499) ? $rpb_options['delay_out'] : '1500';
	$rpb_options['delay_in'] = is_numeric($rpb_options['delay_in']) && ($rpb_options['delay_in'] > 499) ? $rpb_options['delay_in'] : '1500';
	$rpb_options['delay_interval'] = is_numeric($rpb_options['delay_interval']) && ($rpb_options['delay_interval'] > 499) ? $rpb_options['delay_interval'] : '6000';
	
	// Build the link
	$link = WP_PLUGIN_URL . '/random-post-box/random-post-box-load.php';
	$link = function_exists('wp_nonce_url') ? wp_nonce_url($link, 'random-post-box-ajax-call') : $link;

	// Make JS for head
	$headjs = '
		<script type="text/javascript">
		/**
		 * Load content in to
		 * Random Post Box Plugin
		 */
		 function rpb_update() {
			var $j = jQuery.noConflict();
			$j(\'#random-post-box\').fadeOut(' . $rpb_options['delay_out'] . ', function() {
				$j(\'#random-post-box\').load(\'' . $link . '\', function () {
					$j(\'#random-post-box\').fadeIn(' . $rpb_options['delay_in'] . ');
				});
			});
		 }
		 setInterval(rpb_update, ' . $rpb_options['delay_interval'] . ');
		 window.onload = rpb_update;
		 </script>';
	
	// Print the js-code
	echo $headjs;

	
 }


 
/**
 * Cut a string without breaking words.
 * @author Mattias Wirf
 * @param str $txt			The string to cut
 * @param int $length		The length of the string
 * @param str $trail		A string to add when it is cut, default "..."
 * @param bool $strip_tags	Should tags be stripped?
 * @return str				A cut string or an uncut string.
 */
 function rpb_cut_string($txt, $length, $trail = '...', $strip_tags = true) {

	// Should tags be stripped?
	if ($strip_tags) {
		$txt = strip_tags($txt);
 	}

	// Cut the string if needed
	if (strlen($txt) > $length) {
		// Get last space
		$last_space = strrpos(substr($txt, 0, $length), ' ');
		return substr($txt, 0, $last_space) . $trail;
	} else {
		// Return original
		return $txt;
	}
 }



/**
 * This function is used to
 * limit the query in random-post-box-load.php
 * @param str $where		Prepared sql-string
 * @return str
 */
 function rpb_date($where = '') {
	global $rpb_options;
	$where .= ' AND post_date < \'' . date('Y-m-d', strtotime('-' . $rpb_options['post_datelimit'] . ' days')) . '\'';
	//die($where);
	return $where;
 }
?>
