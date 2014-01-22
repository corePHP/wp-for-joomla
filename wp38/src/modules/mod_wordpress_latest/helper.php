<?php
/**
 * Create HTML dropdown list of Categories.
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
class Walker_CategoryDropdownElement extends Walker {
	/**
	 * @see Walker::$tree_type
	 * @since 2.1.0
	 * @var string
	 */
	var $tree_type = 'category';

	/**
	 * @see Walker::$db_fields
	 * @since 2.1.0
	 * @todo Decouple this
	 * @var array
	 */
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	/**
	 * @see Walker::start_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $category Category data object.
	 * @param int $depth Depth of category. Used for padding.
	 * @param array $args Uses 'selected', 'show_count', and 'show_last_update' keys, if they exist.
	 */
	function start_el(&$output, $category, $depth, $args) {
		$pad = str_repeat('&nbsp;', $depth * 3);

		$cat_name = apply_filters('list_cats', $category->name, $category);
		$output .= "\t<option class=\"level-$depth\" value=\"".$category->term_id."\"";
		if ( in_array($category->term_id, $args['selected']) )
			$output .= ' selected="selected"';
		$output .= '>';
		$output .= $pad.$cat_name;
		if ( $args['show_count'] )
			$output .= '&nbsp;&nbsp;('. $category->count .')';
		if ( $args['show_last_update'] ) {
			$format = 'Y-m-d';
			$output .= '&nbsp;&nbsp;' . gmdate($format, $category->last_update_timestamp);
		}
		$output .= "</option>\n";
	}
}

if(!function_exists('get_the_content_by_post')):
function get_the_content_by_post($post) {

	$output = '';

	if ( !empty($post->post_password) ) { // if there's a password
		if ( !isset($_COOKIE['wp-postpass_'.COOKIEHASH]) || stripslashes($_COOKIE['wp-postpass_'.COOKIEHASH]) != $post->post_password ) {	// and it doesn't match the cookie
			$output = get_the_password_form();
			return $output;
		}
	}

	$content = $post->post_content;
	if ( preg_match('/<!--more(.*?)?-->/', $content, $matches) ) {
		$content = explode('<!--more-->', $content, 2);
		$content = array($content[0]);
	} else {
		$content = array($content);
	}

	if ( count($content) > 1 ) {
		if ( $more ) {
			$output .= '<span id="more-'.$id.'"></span>'.$content[1];
		} else {
			$output = balanceTags($output);
		}
	}else $output = $content[0];

	return $output;
}
endif;
?>