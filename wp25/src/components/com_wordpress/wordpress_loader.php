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

class wpj_loader
{
	/**
	 * This static variable stores the working directory of Joomla
	 */
	static $working_directory;

	/**
	 * Contains the relative path to the WordPress installation
	 **/
	static $component_abs_path;

	function load()
	{
		static $css_added;

		// Check to see if WordPress has already been loaded
		if ( !defined( 'ABSPATH' ) ) {
			wpj_loader::load_wp();
		} else {
			@chdir( JPATH_ROOT .DS. wpj_loader::$component_abs_path );
		}

		if ( !$css_added ) {
			$doc = JFactory::getDocument();
			$doc->addStyleDeclaration('/* @group Module Widgets */
.wp_mod .module ul li{margin-bottom:3px}
.wp_mod #searchform{padding-left:10px}
.wp_mod #wp-latest-wrapper .entry-title{padding-bottom:0; margin-bottom:6px}
.wp_mod #wp-latest-wrapper .entry-meta{margin-left:0; padding-left:0; font-size:90%; margin-bottom:10px}
.wp_mod #wp-latest-wrapper .module_post_entry{margin-bottom:12px; padding-bottom:12px; border-bottom:solid 1px #ccc}
.wp_mod #wp-latest-wrapper li.categories{list-style:none; font-size:120%}
.wp_mod #wp-latest-wrapper li.categories ul{font-size:80%; padding-left:15%}
.wp_mod #wp-latest-cats ul:first-child, 
.wp_mod ul#recentcomments:first-child{padding-left:15%}
/* @end */

/* @group Module Widgets */
.wp-sidebar .module:first-child {padding: 0!important;}
.wp-sidebar .module:first-child ul:first-child {margin-left: 0!important;margin-bottom: 0!important;padding-left: 0!important;}
.wp-sidebar form#searchform {padding-top: 15px;padding-bottom: 15px;}
/* @end */');

			$css_added = true;
		}
	}

	function unload()
	{
		// Return back to the cwd if not multisite
		if ( wpj_loader::$working_directory ) {
			chdir( wpj_loader::$working_directory );
		}
	}

	function load_wp()
	{
		/* RC - Declare all variables that are ment to be globals, avoiding errors on some systems, but creating errors in others - fix is at the end of component */
		global $wp_taxonomies, $_wp_submenu_nopriv, $wp_local_package, $wp_registered_sidebars, $wp_version, $wp_dashboard_sidebars, $wp_user_roles, $wpdb, $wp_scripts;
		global $wp_registered_widget_controls, $wp_registered_widget_updates, $wp_registered_widgets, $_wp_deprecated_widgets_callbacks;
		global $wp_roles, $wp_registered_widget_controls, $wp_db_version, $_wp_admin_css_colors, $wp_http_referer, $_wp_real_parent_file, $admin_page_hooks, $taxonomy;
		global $menu, $submenu, $parent_file, $submenu_file, $current_user, $unique_filename_callback, $link, $cat_id, $action, $tag, $comment, $comment_status, $allowedposttags;
		global $pagenow, $user_email, $type, $post_mime_type, $tab, $revision, $diff, $left, $right, $listtag, $body_id, $user_id, $plugin_page, $tableposts;
		global $query_string, $posts, $post, $request, $more, $single, $post_ID, $temp_ID, $link_id, $tinymce_version, $manifest_version, $author_name, $author;
		global $file, $plugin_page, $mode, $user_identity, $sidebars_widgets, $in_comment_loop, $title, $is_trash, $required_php_version, $required_mysql_version;
		global $wp_embed, $theme, $text_direction, $wp_queries, $table_prefix, $wp_widget_factory, $wp;
		global $is_lynx, $is_gecko, $is_winIE, $is_macIE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone, $is_IE, $is_apache, $is_IIS, $is_iis7, $base, $blogname, $blog_title, $errors, $domain, $path, $multipage, $numpages, $page;
		global $wp_the_query, $wp_query, $wp_rewrite, $wp_locale, $allowedtags, $_wp_nav_menu_max_depth, $_nav_menu_placeholder, $wp_meta_boxes, $nav_menu_selected_id, $current_blog, $JOOMLA_CONFIG;

		if ( !defined( 'WP_MEMORY_LIMIT' ) ) {
			define( 'WP_MEMORY_LIMIT', '32M' );
		}

		global $mainframe, $option, $task, $component_name, $component_real_name, $wp_path;
		if ( !isset( $component_name ) ) {
			$component_name = 'com_wordpress';
		}
		$component_real_name = 'com_wordpress';

		if ( !$wp_path ) {
			// Check to see if we are using multisite
			$db = JFactory::getDBO();
			$query = "SELECT option_value
				FROM #__wp_options
					WHERE option_name = 'wpj_multisite_path'";
			$db->setQuery( $query );
			$wp_path = $db->loadResult();
		}

		// Check to see if we are in multisite or not
		if ( !$wp_path || 'components'.DS.'com_wordpress'.DS.'wp' == $wp_path ) {
			$component_abs_path = 'components' .DS. 'com_wordpress' .DS. 'wp';
		} else {
			$component_abs_path = $wp_path;
		}

		wpj_loader::$component_abs_path = $component_abs_path;
		wpj_loader::$working_directory = getcwd();

		chdir( JPATH_ROOT .DS. $component_abs_path );
		require_once( JPATH_ROOT .DS. $component_abs_path .DS. 'wp-load.php' );

		// Load language files
		$language =& JFactory::getLanguage();
		$language->load( 'com_wordpress' );

		// Make theme available for translation
		// Translations can be filed in the /languages/ directory
		load_theme_textdomain( 'twentyten', TEMPLATEPATH . '/languages' );

		$locale = get_locale();
		$locale_file = TEMPLATEPATH . "/languages/$locale.php";
		if ( is_readable( $locale_file ) )
			require_once( $locale_file );
	}
}