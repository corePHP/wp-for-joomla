<?php
if ( !defined('_JEXEC') ) { die( 'Direct Access to this location is not allowed.' ); }
/**
 * @version		$Id: wordpress.php 1 2009-10-27 20:56:04Z rafael $
 * @package		WordPress for Joomla!
 * @copyright	Copyright (C) 2010 'corePHP' / corephp.com. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt
 */

jimport('joomla.plugin.plugin');

class plgUserWordPress extends JPlugin
{
	/**
	 * Keeps track to see if the logout function has been called already
	 */
	static $logout_called = false;

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since 1.5
	 */
	function plgUserWordPress(& $subject, $config)
	{
		global $wp_path;

		parent::__construct($subject, $config);

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
		} else { // Is multisite
			$component_abs_path = $wp_path;
		}

		$this->path_to_wp = JPATH_ROOT .DS. $component_abs_path;
	}

	/**
	 * Function will create new WordPress user upon the creation of Joomla user
	 */
	function onUserAfterSave( $user, $isnew, $success, $msg )
	{
		global $mainframe;

		$this->load_wp();

		$user_id = j_create_wp_user( (object) $user );

		$this->unload_wp();
	}

	/**
	 * Function will delete WordPress user upon deletion of Joomla user
	 */
	function onUserAfterDelete( $user, $succes, $msg )
	{
		global $mainframe;

		if ( !$this->load_wp() ) {
			return;
		}

		$user_id = (int) $user['id'];
		$user =  new WP_User( $user_id );
		if ( $user->ID ) {
			require_once( $this->path_to_wp .DS.'wp-admin'.DS.'includes'.DS.'user.php' );
			wp_delete_user( $user_id );
		}

		$this->unload_wp();
	}

	/**
	 * This function will log user into WordPress when sucessful Joomla login is triggered
	 */
	function onUserLogin( $user, $options = array() )
	{
		global $mainframe;

		jimport('joomla.user.helper');

		if ( !$this->load_wp() ) {
			return;
		}

		if ( $mainframe->isAdmin() ) {
			// If we are in the admin, then lets update our jhome_url option to keep accurate
			update_option( 'jhome_url', JURI::root() );

			return true;
		}

		// Try to get user_id from joomla, if fail then try and get it from WordPress
		if ( !( $id = intval( JUserHelper::getUserId( $user['username'] ) ) ) ) {
			$user = get_userdatabylogin( $user['username'] );
			$id = $user->ID;
		} else {
			// Lets check that user exists in WP
			$wp_user = new WP_User( $id );
			if ( !$wp_user->ID ) {
				$juser = JFactory::getUser( $id );
				$user_id = j_create_wp_user( $juser );

				if ( is_a( $user_id, 'WP_Error' ) ) {
					return $_var;
				}
			}
		}

		if ( !$id ) {
			return true;
		}

		// Process wp auto-login
		wp_set_current_user( $id, $user['username'] );
		wp_set_auth_cookie( $id );
		do_action( 'wp_login', $user['username'] );

		$this->unload_wp();

		return true;
	}

	/**
	 * Function will logout WordPress user, when Joomla logout is triggered
	 */
	function onUserLogout( $user )
	{
		$app = JFactory::getApplication();

		if ( plgUserWordPress::$logout_called || $app->isAdmin() ) {
			return true;
		}

		plgUserWordPress::$logout_called = true;

		if ( !$this->load_wp() ) {
			return;
		}

		wp_clear_auth_cookie();
		do_action( 'wp_logout' );

		$this->unload_wp();

		return true;
	}

	/**
	 * Function used to load WordPress framework
	 */
	function load_wp()
	{
		/* RC - Declare all variables that are ment to be globals, avoiding errors on some systems, but creating errors in others - fix is at the end of component */
		global $wp_taxonomies, $_wp_submenu_nopriv, $wp_local_package, $wp_registered_sidebars, $wp_version, $wp_dashboard_sidebars, $wp_user_roles, $wpdb, $wp_scripts;
		global $wp_roles, $wp_registered_widget_controls, $wp_db_version, $_wp_admin_css_colors, $wp_http_referer, $_wp_real_parent_file, $admin_page_hooks, $taxonomy;
		global $menu, $submenu, $parent_file, $submenu_file, $current_user, $unique_filename_callback, $link, $cat_id, $action, $tag, $comment, $comment_status, $allowedposttags;
		global $pagenow, $user_email, $type, $post_mime_type, $tab, $revision, $diff, $left, $right, $listtag, $body_id, $user_id, $plugin_page, $tableposts;
		global $query_string, $posts, $post, $request, $more, $single, $post_ID, $temp_ID, $link_id, $tinymce_version, $manifest_version, $author_name, $author;
		global $file, $plugin_page, $mode, $user_identity, $sidebars_widgets, $in_comment_loop, $title, $is_trash, $required_php_version, $required_mysql_version;
		global $wp_embed, $theme, $text_direction, $wp_queries, $table_prefix, $wp_widget_factory, $wp;
		global $is_lynx, $is_gecko, $is_winIE, $is_macIE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone, $is_IE, $is_apache, $is_IIS, $is_iis7;
		global $wp_the_query, $wp_query, $wp_rewrite, $wp_locale;

		if ( !defined( 'WP_MEMORY_LIMIT' ) ) {
			define( 'WP_MEMORY_LIMIT', '32M' );
		}

		// Change directory to blog directory
		global $working_directory;
		$working_directory = getcwd();
		chdir( $this->path_to_wp );

		global $mainframe, $option, $task, $component_name, $component_real_name, $admin_comp_url;
		if ( !isset( $component_name ) ) {
			$component_name = 'com_wordpress';
		}
		$component_real_name = 'com_wordpress';

		$path = $this->path_to_wp.DS.'wp-load.php';

		if ( !file_exists( $this->path_to_wp.DS.'wp-load.php' ) ) {
			return false;
		}

		require_once( $this->path_to_wp.DS.'wp-load.php' );

		return true;
	}

	/**
	 * Function to unload WordPress framework
	 */
	function unload_wp()
	{
		global $working_directory;
		// Return back to the cwd
		chdir( $working_directory );
	}
}
