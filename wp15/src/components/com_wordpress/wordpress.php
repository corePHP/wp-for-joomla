<?php
if ( ! (  defined( '_JEXEC' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }
/**
* @version $Id: 1  2008-11-15 19:34 rafael $
* @package WordPress Integration
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see license.txt
* WordPress is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* 
* This version of WordPress has originally been modified by corePHP to work
* within the Joomla 1.5.x environment.
* For any support visit: http://www.corephp.com/wordpress/support
* 
* http://www.corephp.com
*/

if ( isset( $_SERVER['WP_REQUEST_URI'] ) ) {
	$_SERVER['REQUEST_URI'] = $_SERVER['WP_REQUEST_URI'];
}

/* RC - Declare all variables that are ment to be globals, avoiding errors on some systems, but creating errors in others - fix is at the end of component */
global $wp_taxonomies, $_wp_submenu_nopriv, $wp_local_package, $wp_registered_sidebars, $wp_version, $wp_dashboard_sidebars, $wp_user_roles, $wpdb, $wp_scripts;
global $wp_registered_widget_controls, $wp_registered_widget_updates, $wp_registered_widgets, $_wp_deprecated_widgets_callbacks;
global $wp_roles, $wp_registered_widget_controls, $wp_db_version, $_wp_admin_css_colors, $wp_http_referer, $_wp_real_parent_file, $admin_page_hooks, $taxonomy;
global $menu, $submenu, $parent_file, $submenu_file, $current_user, $unique_filename_callback, $link, $cat_id, $action, $tag, $comment, $comment_status, $allowedposttags;
global $pagenow, $user_email, $type, $post_mime_type, $tab, $revision, $diff, $left, $right, $listtag, $body_id, $user_id, $plugin_page, $tableposts;
global $query_string, $posts, $post, $request, $more, $single, $post_ID, $temp_ID, $link_id, $tinymce_version, $manifest_version, $author_name, $author;
global $file, $plugin_page, $mode, $user_identity, $sidebars_widgets, $in_comment_loop, $title, $is_trash, $required_php_version, $required_mysql_version;
global $wp_embed, $theme, $text_direction, $wp_queries, $table_prefix, $wp_widget_factory, $wp, $blog_id;
global $is_lynx, $is_gecko, $is_winIE, $is_macIE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone, $is_IE, $is_apache, $is_IIS, $is_iis7, $base, $blogname, $blog_title, $errors, $domain, $path, $multipage, $numpages, $page;
global $wp_the_query, $wp_query, $wp_rewrite, $wp_locale, $allowedtags, $_wp_nav_menu_max_depth, $_nav_menu_placeholder, $wp_meta_boxes, $nav_menu_selected_id, $current_blog, $JOOMLA_CONFIG;

if ( !defined( 'WP_MEMORY_LIMIT' ) ) {
	define( 'WP_MEMORY_LIMIT', '32M' );
}

global $mainframe, $option, $task, $component_name, $component_real_name, $wp_path;

if ( !$wp_path ) {
	// Check to see if we are using multisite
	$db = JFactory::getDBO();
	$query = "SELECT option_value
		FROM #__wp_options
			WHERE option_name = 'wpj_multisite_path'";
	$db->setQuery( $query );
	$wp_path = $db->loadResult();
}

// Change directory to blog directory if not multisite
if ( !$wp_path || 'components'.DS.'com_wordpress'.DS.'wp' == $wp_path ) {
	$component_abs_path = 'components' .DS. 'com_wordpress' .DS. 'wp';

	$working_directory = getcwd();
	chdir( $component_abs_path );
} else { // Is multisite
	$component_abs_path = $wp_path;
}

if ( !isset( $component_name ) ) {
	$component_name = 'com_wordpress';
}
$component_real_name = 'com_wordpress';

switch( JRequest::getVar( 'task' ) ) {
	case 'wp-signup.php':
		require( JPATH_ROOT .DS. $component_abs_path .DS. 'wp-signup.php' );
		break;
	default:
		require_once( JPATH_ROOT .DS. $component_abs_path .DS. 'wp_index.php' );
		break;
}

if ( is_feed() ) { die(); }

$allowed_themes = array( 'twentyeleven', 'twentyten', 'everyhome', 'default' );
if ( !in_array( get_stylesheet(), apply_filters( 'wpj_allowed_themes', $allowed_themes ) ) ) {
	die();
}

// Return back to the cwd if not multisite
if ( !$wp_path || 'components'.DS.'com_wordpress'.DS.'wp' == $wp_path ) {
	chdir( $working_directory );
}
unset( $JOOMLA_CONFIG );