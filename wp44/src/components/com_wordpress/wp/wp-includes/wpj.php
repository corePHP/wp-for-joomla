<?php

jimport( 'joomla.plugin.helper' );

/**
 * Authenticate the user using the username and password.
 */
add_filter( 'authenticate', 'wp_authenticate_joomla', 10, 3 );
function wp_authenticate_joomla( $user, $username, $password )
{
	global $mainframe;

	if ( is_a($user, 'WP_User') ) { return $user; }

	if ( empty($username) || empty($password) ) {
		$error = new WP_Error();

		if ( empty($username) )
			$error->add('empty_username', __('<strong>ERROR</strong>: The username field is empty.'));

		if ( empty($password) )
			$error->add('empty_password', __('<strong>ERROR</strong>: The password field is empty.'));

		return $error;
	}

	$credentials = compact( 'username', 'password' );
	$user = $mainframe->login( $credentials, array( 'silent' => true ) );

	if ( !$user ) {
		return new WP_Error('invalid_username', JText::_('E_LOGIN_AUTHENTICATE'));
	}

	$juser = JFactory::getUser();

	// Check to see if WP user exists
	$user =  new WP_User( $juser->id );
	if ( $user->ID ) {
		return $user;
	}

	$user_id = j_create_wp_user( $juser );

	$user =  new WP_User( $user_id );

	return $user;
}

/**
 * This function logs out Joomla user when WordPres logout is processed
 */
add_action( 'wp_logout', 'wp_logout_joomla' );
function wp_logout_joomla()
{
	$user = JFactory::getUser();
	if ( !$user->id || $user->get( 'guest' ) ) {
		return;
	}

	global $mainframe;

	$mainframe->logout();
}

/**
 * This function automatically authenticates Joomla user into WordPress,
 * when a Joomla admin access the WordPress dashboard from the backend of Joomla.
 */
add_filter( 'auth_redirect_scheme', 'wp_authenticate_joomla_admin', 10 );
function wp_authenticate_joomla_admin( $_var )
{
	global $wpdb;

	$mainframe = JFactory::getApplication();
	if ( !( $hash = $mainframe->input->getVar( 'h' ) ) ) {
		return $_var;
	}

	$db = JFactory::getDBO();

	$timestamp = strtotime( '-2 minutes' );
	$query = "SELECT user_id
		FROM {$wpdb->prefix}jauthenticate
			WHERE `hash` = '{$hash}'
			AND `timestamp` > {$timestamp}";
	$db->setQuery( $query );
	$user_id = $db->loadResult();

	if ( !$user_id ) {
		return $_var;
	}

	$juser = JFactory::getUser( $user_id );

	// Check to see if WP user exists
	$user = new WP_User( $juser->id );
	if ( !$user->ID ) {
		$user_id = j_create_wp_user( $juser );

		if ( is_a( $user_id, 'WP_Error' ) ) {
			return $_var;
		}
	}

	$credentials = array(
		'username' => $juser->username,
		'user_id' => $juser->id,
		'hash' => $hash
	);
	if ( !$mainframe->login( $credentials, array( 'silent' => true ) ) ) {
		return $_var;
	}

	$query = "DELETE FROM {$wpdb->prefix}jauthenticate WHERE `user_id` = {$juser->id}";
	$db->setQuery( $query );
	$db->query();

	// Process WordPress auto login
	$user = new WP_User( $juser->id );
	wp_set_current_user( $user->ID, $user->user_login );
	wp_set_auth_cookie( $user->ID );
	do_action( 'wp_login', $user->username );

	$uri = JFactory::getURI();
	$mainframe->redirect( $uri->toString(
		array( 'scheme', 'user', 'pass', 'host', 'port', 'path' ) )
	);

	die();
}

/**
 * Run Joomla content plugins on WordPress posts
 */
add_filter( 'the_content', 'wp_joomla_run_plugins' );
function wp_joomla_run_plugins( $text )
{
	global $post;
	static $front_end;

	jimport( 'joomla.html.parameter' );

	if ( !isset( $front_end ) ) {
		$japplication =& JFactory::getApplication();
		$front_end    = $japplication->isSite();
	}

	// Only run this filter if we are in the front-end
	if ( !$front_end ) { return $text; }

	// Go by our settings
	if ( !get_site_option( 'wpj_use_joomla_plugins', 0 ) ) { return $text; }

	JPluginHelper::importPlugin('content');
	$dispatcher	=& JDispatcher::getInstance();

	$item = (object) array(
		'title'			=> $post->post_title,
		'alias'			=> $post->post_name,
		'introtext'		=> $text,
		'fulltext'		=> '',
		'text'			=> $text,
		'state'			=> 1,
		'created'		=> $post->post_date,
		'created_by'	=> $post->post_author,
		'publish_up'	=> $post->post_date,
		'modified'		=> $post->post_modified,
		'access'		=> 0
	);

	$params	= new JRegistry( array() );

	$results = $dispatcher->trigger( 'onContentPrepare', array( 'com_content.article', &$item,
	 	&$params, 0 ) );

	return $item->text;
}

add_action( 'publish_post', 'wp_joomla_run_finderplugin' );
function wp_joomla_run_finderplugin( $post_id )
{
	global $post;
	static $front_end;
	
	if ( !get_site_option( 'wpj_use_joomla_plugins', 0 ) ) { return $text; }

	if(count($post)>0 && $post->post_status =='publish')
	{
		$post_title = get_the_title( $post_id );

		jimport( 'joomla.html.parameter' );

		if ( !isset( $front_end ) )
		{
			$japplication = JFactory::getApplication();
			$front_end    = $japplication->isSite();
		}

		JPluginHelper::importPlugin('content');
		$dispatcher	= JDispatcher::getInstance();

		$item = (object) array(
			'id'			=> $post->ID,
			'title'			=> $post_title,
			'alias'			=> $post->post_name,
			'introtext'		=> $post_title,
			'fulltext'		=> $post_title,
			'text'			=> $post_title,
			'state'			=> 1,
			'created'		=> $post->post_date,
			'created_by'	=> $post->post_author,
			'publish_up'	=> $post->post_date,
			'modified'		=> $post->post_modified,
			'access'		=> 1
		);

		$params	= new JRegistry( array() );

		$results = $dispatcher->trigger( 'onContentAfterSave', array( 'com_wordpress.wordpress_blog', &$item,
		 	&$params, 0 ) );

		return $item->text;
	}
}

add_action( 'trashed_post', 'wp_joomla_run_finderplugin_delete' );
add_action( 'deleted_post', 'wp_joomla_run_finderplugin_delete' );
function wp_joomla_run_finderplugin_delete( $post_id )
{
	global $post;
	static $front_end;
	
	if ( !get_site_option( 'wpj_use_joomla_plugins', 0 ) ) { return $text; }

	if(count($post)>0 && $post->post_status =='publish')
	{
		$post_title = get_the_title( $post_id );

		jimport( 'joomla.html.parameter' );

		if ( !isset( $front_end ) ) {
			$japplication = JFactory::getApplication();
			$front_end    = $japplication->isSite();
		}

		JPluginHelper::importPlugin('content');
		$dispatcher	= JDispatcher::getInstance();

		$item = (object) array(
			'id'			=> $post->ID,
			'title'			=> $post_title,
			'alias'			=> $post->post_name,
			'introtext'		=> $post_title,
			'fulltext'		=> $post_title,
			'text'			=> $post_title,
			'state'			=> 1,
			'created'		=> $post->post_date,
			'created_by'	=> $post->post_author,
			'publish_up'	=> $post->post_date,
			'modified'		=> $post->post_modified,
			'access'		=> 1
		);

		$params	= new JRegistry( array() );

		$results = $dispatcher->trigger( 'onContentAfterDelete', array( 'com_wordpress.wordpress_blog', &$item,
		 	&$params, 0 ) );

		return $item->text;
	}
}

/**
 * This function will allow us to change the default width of the WordPress page,
 * which is normally 940px
 */
// add_filter( 'twentyten_header_image_width', 'wpj_change_page_width' );
function wpj_change_page_width( $original )
{
	return $original;

	$default = 750;
	$user_setting = get_site_option( 'j_tt_page_width', $default );
	$difference = $original - $user_setting;

	$css = '#wp-access .menu-header,
div.menu,
#colophon,
#wp-branding,
#wp-main,
#wp-wrapper,
#wordpress,
.one-column #wp-content {
	width: '.$user_setting.'px !important;
}
#wp-access {
	width: '.$user_setting.'px !important;
}
#site-title {
	width: '.( 700 - $difference ).'px !important;
}
/* Experimental */
#wp-wrapper {
	margin-top: 5px !important;
	padding: 0 8px !important;
}';

	if ( !is_admin() ) {
		$doc = JFactory::getDocument();
		$doc->addStyleDeclaration( $css );
	}

	return $user_setting;
}

/**
 * Add leading class if post is sticky
 */
add_filter( 'post_class', 'wpj_post_class', 10, 3 );
function wpj_post_class( $classes, $class, $id )
{
	if ( in_array( 'sticky', $classes ) ) {
		$classes[] = 'leading';
	}

	return $classes;
}

/**
 * This function will allow us to change the default width of the WordPress page,
 * which is normally 940px
 */
add_filter( 'the_generator', 'wpj_the_generator', 10, 2 );
function wpj_the_generator( $generator, $type )
{
	if ( in_array( $type, array( 'html', 'xhtml', 'comment' ) ) ) {
		return '';
	}

	return $generator;
}

/**
 * Fix the non-sefed author link
 */
add_filter( 'author_link', 'wpj_author_link' );
function wpj_author_link( $link )
{
	return str_replace( '/?', '&', $link );
}

/**
 * WordPress admin hook function, contains all admin hooks
 */
add_filter( 'admin_init', 'wpj_admin_hook' );
function wpj_admin_hook()
{
	global $blog_id, $current_user;

	$juser = JFactory::getUser();

	// Just to make sure we stay on top of our WordPress path, store it again only on dashboard page
	if ( 1 == $blog_id
		&& is_multisite()
		&& false !== strpos( $_SERVER['REQUEST_URI'], 'index.php' )
		&& false === strpos( $_SERVER['REQUEST_URI'], '?' )
		&& is_super_admin()
	) {
		// This has to be set here, otherwise WP does something with this option - wierd stuff
		// a:1:{s:9:"everyhome";i:1;}
		update_option( 'allowedthemes', array( 'everyhome' => 1 ) );

		// Once everything is good to go, lets store the WordPress blog path to the db
		if(0 === strpos($_SERVER['SCRIPT_FILENAME'], JPATH_BASE) ){
			$path = substr($_SERVER['SCRIPT_FILENAME'],strlen(JPATH_BASE));
			$path = current(explode('wp-admin',$path));
			$path = trim($path,DS);
		} else {
			$path = str_replace( JPATH_ROOT . DS, '', dirname( dirname( __FILE__ ) ) );
		}

		update_blog_option( 1, 'wpj_multisite_path', $path );
	}

	// Automatically redirect user to their primary blog
	if ( is_multisite()
		// && 1 == $blog_id
		&& false === strpos( $_SERVER['HTTP_REFERER'], '/wp-admin' )
		&& get_site_option( 'wpj_redirect_to_primary_blog', 1 )
		&& !isset( $_POST['Filename'] )
	) {
		get_currentuserinfo();
		if ( 1 != $current_user->primary_blog
			&& $current_user->primary_blog
			&& $current_user->primary_blog != $blog_id
		) {
			wp_redirect( get_admin_url( $current_user->primary_blog ) );
			die();
		}
	}

	// This may need to be here to set/update the role of the user if their Joomla status changes
	// if ( $juser->authorise( 'core.admin', '' ) ) {
	// 	$role = 'administrator';
	// } else {
	// 	$role = get_option( 'default_role' );
	// }

	if ( $juser->authorise( 'core.manage', '' ) ) {
		add_action( 'admin_bar_menu', 'wp_admin_bar_back_to_joomla', 10 );
	}
	function wp_admin_bar_back_to_joomla( $wp_admin_bar )
	{
		$wp_admin_bar->add_menu( array(
			'parent' => 'user-actions',
			'id'     => 'joomla',
			'title'  => 'Back to Joomla!',
			'href'   => trailingslashit( j_get_root_uri( true ) ) . 'administrator',
		) );
	}

	// This is changed if we are in multisite
	$general_settings_page_hook = 'general';
	if ( is_multisite() ) {
		$general_settings_page_hook = 'wpj_general_options';

		add_action( 'update_wpmu_options', 'wpj_update_wpmu_options' );
		function wpj_update_wpmu_options()
		{
			$options = array( 'j_tt_show_title_description', 'j_tt_show_header_image',
				'j_tt_show_blog_menu', 'wpj_template_club', 'wpj_wrap_in_small',
				'wpj_site_title_size', 'wpj_title_link_color', 'wpj_title_link_hover_color',
				'wpj_sticky_background', 'wpj_navigation_background', 'wpj_navigation_text_color',
				'wpj_navigation_hover_background', 'wpj_navigation_hover_text_color',
				'wpj_sub_navigation_background', 'wpj_sub_navigation_text_color',
				'wpj_sub_navigation_hover_background', 'wpj_sub_navigation_hover_text_color',
				'wpj_navigation_current_background', 'wpj_navigation_current_text_color',
				'wpj_sub_navigation_current_background', 'wpj_sub_navigation_current_text_color',
				'wpj_widget_h_tag_level', 'wpj_module_class_suffix',
				'wpj_module_class_suffix_space',
				'wpj_title_color', 'wpj_use_joomla_plugins',
				'wpj_use_wp_sidebar', 'wpj_enable_multisite', 'wpj_redirect_to_primary_blog'
				/*'j_tt_page_width'*/
			);

			foreach ( $options as $option ) {
				$value = null;
				if ( isset( $_POST[$option] ) ) {
					$value = stripslashes_deep( $_POST[$option] );
				}
				update_site_option( $option, $value );
			}
		}
	}

	// Add new whitelist_options
	add_filter( 'whitelist_options', 'wpj_whitelist_options' );
	function wpj_whitelist_options( $whitelist_options )
	{
		if ( ( $k = array_search( 'siteurl', $whitelist_options['general'] ) ) ) {
			unset( $whitelist_options['general'][$k] );
		}
		if ( ( $k = array_search( 'home', $whitelist_options['general'] ) ) ) {
			unset( $whitelist_options['general'][$k] );
		}

		$whitelist_options['general'][] = 'j_tt_show_title_description';
		$whitelist_options['general'][] = 'j_tt_show_header_image';
		$whitelist_options['general'][] = 'j_tt_show_blog_menu';
		// $whitelist_options['general'][] = 'j_tt_page_width';
		$whitelist_options['general'][] = 'wpj_template_club';
		$whitelist_options['general'][] = 'wpj_wrap_in_small';
		$whitelist_options['general'][] = 'wpj_site_title_size';
		$whitelist_options['general'][] = 'wpj_title_color';
		$whitelist_options['general'][] = 'wpj_title_link_color';
		$whitelist_options['general'][] = 'wpj_title_link_hover_color';
		$whitelist_options['general'][] = 'wpj_sticky_background';
		$whitelist_options['general'][] = 'wpj_navigation_background';
		$whitelist_options['general'][] = 'wpj_navigation_text_color';
		$whitelist_options['general'][] = 'wpj_navigation_current_background';
		$whitelist_options['general'][] = 'wpj_navigation_current_text_color';
		$whitelist_options['general'][] = 'wpj_sub_navigation_current_background';
		$whitelist_options['general'][] = 'wpj_sub_navigation_current_text_color';
		$whitelist_options['general'][] = 'wpj_navigation_hover_background';
		$whitelist_options['general'][] = 'wpj_navigation_hover_text_color';
		$whitelist_options['general'][] = 'wpj_sub_navigation_background';
		$whitelist_options['general'][] = 'wpj_sub_navigation_text_color';
		$whitelist_options['general'][] = 'wpj_sub_navigation_hover_background';
		$whitelist_options['general'][] = 'wpj_sub_navigation_hover_text_color';
		$whitelist_options['general'][] = 'wpj_widget_h_tag_level';
		$whitelist_options['general'][] = 'wpj_module_class_suffix';
		$whitelist_options['general'][] = 'wpj_module_class_suffix_space';
		$whitelist_options['general'][] = 'wpj_use_joomla_plugins';
		$whitelist_options['general'][] = 'wpj_use_wp_sidebar';
		$whitelist_options['general'][] = 'wpj_enable_multisite';
		$whitelist_options['general'][] = 'wpj_redirect_to_primary_blog';

		return $whitelist_options;
	}

	/**
	 * This section is to add new settings to different settings pages in WordPress
	 */
	if ( in_array( get_option('template'), array( 'twentyten', 'everyhome' ) )
		|| is_multisite()
	) {
		// Add new Twenty Ten theme settings section
		add_settings_section( 'tt_theme_settings', 'Twenty Ten theme settings',
			'wp_general_tt_settings', $general_settings_page_hook
		);
		function wp_general_tt_settings()
		{
			echo 'These settings will affect how the <strong>Twenty Ten theme</strong> displays site-wide.';
		}

		// Add all new settings
		add_settings_field( 'show_title_description', 'Display title and description',
			'wpj_header_show_title_description', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_header_show_title_description()
		{
			$s = get_site_option( 'j_tt_show_title_description', 1 );
			?>
			<select name="j_tt_show_title_description" id="j_tt_show_title_description">
				<option value="1" <?php echo ( ( $s ) ? 'selected="selected"' : '' ); ?>>Yes</option>
				<option value="0" <?php echo ( ( !$s ) ? 'selected="selected"' : '' ); ?>>No</option>
			</select>
			<?php
		}
		add_settings_field( 'show_header_image', 'Display header image',
			'wpj_header_show_image', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_header_show_image()
		{
			$s = get_site_option( 'j_tt_show_header_image', 1 );
			?>
			<select name="j_tt_show_header_image" id="j_tt_show_header_image">
				<option value="1" <?php echo ( ( $s ) ? 'selected="selected"' : '' ); ?>>Yes</option>
				<option value="0" <?php echo ( ( !$s ) ? 'selected="selected"' : '' ); ?>>No</option>
			</select>
			<?php
		}
		add_settings_field( 'show_blog_menu', 'Display blog menu',
			'wpj_header_show_blog_menu', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_header_show_blog_menu()
		{
			$s = get_site_option( 'j_tt_show_blog_menu', 1 );
			?>
			<select name="j_tt_show_blog_menu" id="j_tt_show_blog_menu">
				<option value="1" <?php echo ( ( $s ) ? 'selected="selected"' : '' ); ?>>Yes</option>
				<option value="0" <?php echo ( ( !$s ) ? 'selected="selected"' : '' ); ?>>No</option>
			</select>
			<?php
		}
		/* Deprecated * /
		add_settings_field( 'page_width', 'Blog page width (in pixels)',
			'wpj_page_width', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_page_width()
		{
			$s = get_site_option( 'j_tt_page_width', 750 );
			?>
			<input type="text" name="j_tt_page_width" id="j_tt_page_width" value="<?php echo $s; ?>" size="5" />
			<span class="description">px</span><br />
			<span class="description">Test to see which width best fits your Joomla template.</span>
			<?php
		}
		/* */

		// Use Joomla template club
		add_settings_field( 'wpj_joomla_template_club', 'Joomla Template Company',
			'wpj_template_club', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_template_club()
		{
			$s = get_site_option( 'wpj_template_club', '' );
			// Files that contain these options are 404.php, archive.php, attachment.php, author.php, category.php, functions.php, index.php, onecolumn-page.php, page.php, search.php, sidebar.php and single.php.
			?>
			<select name="wpj_template_club" id="wpj_template_club">
				<option value="" <?php echo ( ( '' == $s ) ? 'selected="selected"' : '' ); ?>>Joomla Default</option>
				<option value="joomlart" <?php echo ( ( 'joomlart' == $s ) ? 'selected="selected"' : '' ); ?>>JoomlArt</option>
				<option value="joomlapraise" <?php echo ( ( 'joomlapraise' == $s ) ? 'selected="selected"' : '' ); ?>>JoomlaPraise</option>
				<option value="joomlashack" <?php echo ( ( 'joomlashack' == $s ) ? 'selected="selected"' : '' ); ?>>JoomlaShack</option>
				<option value="rockettheme" <?php echo ( ( 'rockettheme' == $s ) ? 'selected="selected"' : '' ); ?>>RocketTheme</option>
				<option value="rockettheme_gantry_1" <?php echo ( ( 'rockettheme_gantry_1' == $s ) ? 'selected="selected"' : '' ); ?>>RocketTheme Gantry - Option 1</option>
				<option value="shape5" <?php echo ( ( 'shape5' == $s ) ? 'selected="selected"' : '' ); ?>>Shape5</option>
				<option value="yootheme" <?php echo ( ( 'yootheme' == $s ) ? 'selected="selected"' : '' ); ?>>YooTheme</option>
			</select>
			<span class="description">Pick the template company for your current Joomla template. We have special styles for each template company.</span>
			<?php
		}

		// Wrap metadata in small class
		add_settings_field( 'wrap_in_small', 'Wrap metadata in modifydate class',
			'wpj_wrap_in_small', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_wrap_in_small()
		{
			$s = get_site_option( 'wpj_wrap_in_small', 0 );
			?>
			<select name="wpj_wrap_in_small" id="wpj_wrap_in_small">
				<option value="1" <?php echo ( ( $s ) ? 'selected="selected"' : '' ); ?>>Yes</option>
				<option value="0" <?php echo ( ( !$s ) ? 'selected="selected"' : '' ); ?>>No</option>
			</select><br />
			<span class="description">Wrap Posted on ... by ... in small font wrapper. Needed for some templates (ie. JoomlaShack Cascada).</span>
			<?php
		}

		add_settings_field( 'wpj_widget_h_tag_level', 'H Tag level of widget titles (ie. H1 - H5)',
			'wpj_widget_h_tag_level', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_widget_h_tag_level()
		{
			$s = get_site_option( 'wpj_widget_h_tag_level', '2' );
			?>
			H<select name="wpj_widget_h_tag_level" id="wpj_widget_h_tag_level">
				<option value="1" <?php echo ( ( $s == 1 ) ? 'selected="selected"' : '' ); ?>>1</option>
				<option value="2" <?php echo ( ( $s == 2 ) ? 'selected="selected"' : '' ); ?>>2</option>
				<option value="3" <?php echo ( ( $s == 3 ) ? 'selected="selected"' : '' ); ?>>3</option>
				<option value="4" <?php echo ( ( $s == 4 ) ? 'selected="selected"' : '' ); ?>>4</option>
				<option value="5" <?php echo ( ( $s == 5 ) ? 'selected="selected"' : '' ); ?>>5</option>
			</select><br />
			<span class="description">H Tag level of widget titles (ie. H1 - H5). Should match your module titles.</span>
			<?php
		}

		add_settings_field( 'wpj_module_class_suffix', 'Add a module class suffix to widget to match styles',
			'wpj_module_class_suffix', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_module_class_suffix()
		{
			$s = get_site_option( 'wpj_module_class_suffix', '' );
			?>
			<input type="text" name="wpj_module_class_suffix" id="wpj_module_class_suffix" value="<?php echo $s; ?>" size="15" /><br />
			<span class="description">Add a module class suffix to widgets to match style of modules. Add space in front of name if it needs to be it's own class instead of adding to end of default module wrapper class.</span>
			<?php
		}

		add_settings_field( 'wpj_module_class_suffix_space', 'Add a space before the module class suffix to make it it\'s own class',
			'wpj_module_class_suffix_space', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_module_class_suffix_space()
		{
			$s = get_site_option( 'wpj_module_class_suffix_space', '0' );
			?>
			<select name="wpj_module_class_suffix_space" id="wpj_module_class_suffix_space">
				<option value="1" <?php echo ( ( $s ) ? 'selected="selected"' : '' ); ?>>Yes</option>
				<option value="0" <?php echo ( ( !$s ) ? 'selected="selected"' : '' ); ?>>No</option>
			</select><br />
			<span class="description">Add a space before the module class suffix to make it it's own class. Needed for templates like Gantry.</span>
			<?php
		}

		add_settings_field( 'site_title_size', 'Site title size (in pixels)',
			'wpj_site_title_size', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_site_title_size()
		{
			$s = get_site_option( 'wpj_site_title_size', 24 );
			?>
			<input type="text" name="wpj_site_title_size" id="wpj_site_title_size" value="<?php echo $s; ?>" size="5" />
			<span class="description">px</span><br />
			<span class="description">Set the size of your site title for inner pages of blog, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'title_color', 'Title color',
			'wpj_title_color', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_title_color()
		{
			$s = get_site_option( 'wpj_title_color', '' );
			?>
			<input type="text" name="wpj_title_color" id="wpj_title_color" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">Title color without link</span>
			<?php
		}

		add_settings_field( 'title_link_color', 'Site link color',
			'wpj_title_link_color', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_title_link_color()
		{
			$s = get_site_option( 'wpj_title_link_color', '' );
			?>
			<input type="text" name="wpj_title_link_color" id="wpj_title_link_color" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">Color for linked titles if you would want to override them, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'title_link_hover_color', 'Site link hover color',
			'wpj_title_link_hover_color', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_title_link_hover_color()
		{
			$s = get_site_option( 'wpj_title_link_hover_color', '' );
			?>
			<input type="text" name="wpj_title_link_hover_color" id="wpj_title_link_hover_color" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">Hover color for links if you would want to override them, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'sticky_background', 'Sticky post background color',
			'wpj_sticky_background', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_sticky_background()
		{
			$s = get_site_option( 'wpj_sticky_background', '' );
			?>
			<input type="text" name="wpj_sticky_background" id="wpj_sticky_background" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The background color for sticky posts, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'navigation_background', 'Navigation background color',
			'wpj_navigation_background', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_navigation_background()
		{
			$s = get_site_option( 'wpj_navigation_background', '#ccc' );
			?>
			<input type="text" name="wpj_navigation_background" id="wpj_navigation_background" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The menu navigation background color, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'navigation_text_color', 'Navigation text color',
			'wpj_navigation_text_color', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_navigation_text_color()
		{
			$s = get_site_option( 'wpj_navigation_text_color', '#000' );
			?>
			<input type="text" name="wpj_navigation_text_color" id="wpj_navigation_text_color" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The menu navigation text color, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'navigation_current_background', 'Navigation current background color',
			'wpj_navigation_current_background', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_navigation_current_background()
		{
			$s = get_site_option( 'wpj_navigation_current_background', '#ccc' );
			?>
			<input type="text" name="wpj_navigation_current_background" id="wpj_navigation_current_background" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The background color of the current menu item, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'navigation_current_text_color', 'Navigation current text color',
			'wpj_navigation_current_text_color', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_navigation_current_text_color()
		{
			$s = get_site_option( 'wpj_navigation_current_text_color', '#000' );
			?>
			<input type="text" name="wpj_navigation_current_text_color" id="wpj_navigation_current_text_color" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The text color of the current menu item, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'navigation_hover_background', 'Navigation hover background color',
			'wpj_navigation_hover_background', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_navigation_hover_background()
		{
			$s = get_site_option( 'wpj_navigation_hover_background', '#000' );
			?>
			<input type="text" name="wpj_navigation_hover_background" id="wpj_navigation_hover_background" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The menu navigation background hover color, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'navigation_hover_text_color', 'Navigation hover text color',
			'wpj_navigation_hover_text_color', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_navigation_hover_text_color()
		{
			$s = get_site_option( 'wpj_navigation_hover_text_color', '#fff' );
			?>
			<input type="text" name="wpj_navigation_hover_text_color" id="wpj_navigation_hover_text_color" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The menu navigation hover text color, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'sub_navigation_background', 'SubNavigation background color',
			'wpj_sub_navigation_background', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_sub_navigation_background()
		{
			$s = get_site_option( 'wpj_sub_navigation_background', '#ccc' );
			?>
			<input type="text" name="wpj_sub_navigation_background" id="wpj_sub_navigation_background" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The subnavigation menu background color, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'sub_navigation_text_color', 'SubNavigation text color',
			'wpj_sub_navigation_text_color', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_sub_navigation_text_color()
		{
			$s = get_site_option( 'wpj_sub_navigation_text_color', '#000' );
			?>
			<input type="text" name="wpj_sub_navigation_text_color" id="wpj_sub_navigation_text_color" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The subnavigation text color, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'sub_navigation_current_background', 'SubNavigation current background color',
			'wpj_sub_navigation_current_background', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_sub_navigation_current_background()
		{
			$s = get_site_option( 'wpj_sub_navigation_current_background', '#ccc' );
			?>
			<input type="text" name="wpj_sub_navigation_current_background" id="wpj_sub_navigation_current_background" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The background color of the current submenu item, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'sub_navigation_current_text_color', 'SubNavigation current text color',
			'wpj_sub_navigation_current_text_color', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_sub_navigation_current_text_color()
		{
			$s = get_site_option( 'wpj_sub_navigation_current_text_color', '#000' );
			?>
			<input type="text" name="wpj_sub_navigation_current_text_color" id="wpj_sub_navigation_current_text_color" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The text color of the current submenu item, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'sub_navigation_hover_background', 'SubNavigation hover background color',
			'wpj_sub_navigation_hover_background', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_sub_navigation_hover_background()
		{
			$s = get_site_option( 'wpj_sub_navigation_hover_background', '#000' );
			?>
			<input type="text" name="wpj_sub_navigation_hover_background" id="wpj_sub_navigation_hover_background" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The subnavigation hover background color, ie. #000, black...etc.</span>
			<?php
		}

		add_settings_field( 'sub_navigation_hover_text_color', 'SubNavigation hover text color',
			'wpj_sub_navigation_hover_text_color', $general_settings_page_hook, 'tt_theme_settings'
		);
		function wpj_sub_navigation_hover_text_color()
		{
			$s = get_site_option( 'wpj_sub_navigation_hover_text_color', '#fff' );
			?>
			<input type="text" name="wpj_sub_navigation_hover_text_color" id="wpj_sub_navigation_hover_text_color" value="<?php echo $s; ?>" size="5" /><br />
			<span class="description">The subnavigation hover text color, ie. #000, black...etc. </span>
			<?php
		}

	}

	// Add new settings section
	add_settings_section(
		'extra_settings', 'Extra settings', 'wp_general_extra_settings', $general_settings_page_hook
	);
	function wp_general_extra_settings() {}

	// Use Joomla content plugins
	add_settings_field( 'wpj_joomla_plugins', 'Use Joomla content plugins',
		'wpj_use_joomla_plugins', $general_settings_page_hook, 'extra_settings'
	);
	function wpj_use_joomla_plugins()
	{
		$s = get_site_option( 'wpj_use_joomla_plugins', 0 );
		?>
		<select name="wpj_use_joomla_plugins" id="wpj_use_joomla_plugins">
			<option value="1" <?php echo ( ( $s ) ? 'selected="selected"' : '' ); ?>>Yes</option>
			<option value="0" <?php echo ( ( !$s ) ? 'selected="selected"' : '' ); ?>>No</option>
		</select>
		<span class="description">Enabling this option will activate the Joomla content plugins when a WordPress post is displayed.</span>
		<?php
	}

	// Display sidebar within content
	add_settings_field( 'wpj_wp_sidebar', 'Use sidebar in content area',
		'wpj_use_wp_sidebar', $general_settings_page_hook, 'extra_settings'
	);
	function wpj_use_wp_sidebar()
	{
		$s = get_site_option( 'wpj_use_wp_sidebar', 1 );
		?>
		<select name="wpj_use_wp_sidebar" id="wpj_use_wp_sidebar">
			<option value="1" <?php echo ( ( $s ) ? 'selected="selected"' : '' ); ?>>Yes</option>
			<option value="0" <?php echo ( ( !$s ) ? 'selected="selected"' : '' ); ?>>No</option>
		</select>
		<span class="description">If enabled, the WordPress sidebar will be displayed within the WordPress content area. If you disable this option and would still like to display widgets, you can download the sidebar module at <a href="http://www.corephp.com/" target="_blank">www.corephp.com</a>.</span>
		<?php
	}

	// Enable Multisite
	add_settings_field( 'wpj_multisite', 'Enable Multisite',
		'wpj_enable_multisite_option', $general_settings_page_hook, 'extra_settings'
	);
	function wpj_enable_multisite_option()
	{
		global $JOOMLA_CONFIG;
		$s = get_site_option( 'wpj_enable_multisite', is_multisite() );

		$disable = '';
		if ( !$JOOMLA_CONFIG->sef || !$JOOMLA_CONFIG->sef_rewrite ) {
			$disable = 'disabled="disabled"';
		}
		?>
		<select name="wpj_enable_multisite" id="wpj_enable_multisite" <?php echo $disable; ?>>
			<option value="1" <?php echo ( ( $s ) ? 'selected="selected"' : '' ); ?>>Yes</option>
			<option value="0" <?php echo ( ( !$s ) ? 'selected="selected"' : '' ); ?>>No</option>
		</select>
		<?php if ( '' != $disable ) { ?>
		<span class="description" style="color:red;">Enable SEF URLs and Apache mod_rewrite in your Joomla configuration before enabling multisite</span><br />
		<?php } ?>
		<?php if ( is_multisite() ) { ?>
		<span class="description" style="color:red;">If you disable multisite, you must also change your menu item in Joomla to point to the new blog url!</span><br />
		<?php } ?>
		<span class="description">Enabling multisite will allow you to create a community of blogs.</span>
		<br />
		<span class="description">You can allow your users to create as many blogs as they want!</span>
		<?php
		wpj_enable_multisite( $s );
	}

	// Enable redirect primary blog
	add_settings_field( 'wpj_redirect_primary_blog', 'Redirect to Primary Blog',
		'wpj_redirect_to_primary_blog', $general_settings_page_hook, 'extra_settings'
	);
	function wpj_redirect_to_primary_blog()
	{
		$s = get_site_option( 'wpj_redirect_to_primary_blog', 1 );
		?>
		<select name="wpj_redirect_to_primary_blog" id="wpj_redirect_to_primary_blog">
			<option value="1" <?php echo ( ( $s ) ? 'selected="selected"' : '' ); ?>>Yes</option>
			<option value="0" <?php echo ( ( !$s ) ? 'selected="selected"' : '' ); ?>>No</option>
		</select>
		<span class="description">This option is only used when Multisite is enabled</span>
		<br />
		<span class="description">If enabled, a user will be automatically redirected to their primary blog when visiting any other WordPress dashboard for the first time.</span>
		<?php
	}

	// Add new permalinks settings section
	if ( !is_multisite() ) {
		add_settings_section(
			'permalinks_extra_settings', '', 'wp_permalinks_extra_settings', 'permalink'
		);
	}
	function wp_permalinks_extra_settings() {
		echo 'Before saving your new settings make sure that SEF URLs are turned on in Joomla!';
	}

	add_action( 'admin_print_styles', 'wpj_admin_print_styles', 30 );
	function wpj_admin_print_styles()
	{
		?>
<style type="text/css">
#wphead #site-visit-button{background-color:#585858;background-image:url(images/visit-site-button-grad.gif);color:#aaa;text-shadow:#3F3F3F 0 -1px 0;}#wphead a:hover #site-visit-button{color:#fff;}#wphead a:focus #site-visit-button,#wphead a:active #site-visit-button{background-position:0 -27px;}
#site-visit-button{background-repeat:repeat-x;background-position:0 0;-moz-border-radius:3px;-webkit-border-radius:3px;-khtml-border-radius:3px;border-radius:3px;cursor:pointer;display:-moz-inline-stack;display:inline-block;font-size:50%;font-style:normal;line-height:17px;margin-left:5px;padding:0 6px;vertical-align:middle;}
</style>
		<?php
	}

	// Dissallow adding users
	add_filter( 'show_network_site_users_add_new_form', 'wpj_add_new_form', 1 );
	function wpj_add_new_form( $default )
	{
		return false;
	}
}

// Commented out until Jonathan decides to use this awesome feature
// add_action('admin_menu', 'wpj_add_pages');

function wpj_add_pages()
{
	// Add color picker page
	if ( in_array( get_option('template'), array( 'twentyten', 'everyhome' ) ) ) {
		add_theme_page( 'Color Swatches', 'Color Swatches', 'edit_themes', 'wpj-color-swatches',
			'_color_swatches');
	}
}

function _color_swatches()
{
	// Save the colors
	if ( !empty( $_POST ) ) {
		update_option( 'wpj_cs_1', JFactory::getApplication()->input->getString( 'color_1', '' ) );
		update_option( 'wpj_cs_2', JFactory::getApplication()->input->getString( 'color_2', '' ) );
		update_option( 'wpj_cs_3', JFactory::getApplication()->input->getString( 'color_3', '' ) );
	}

	wp_enqueue_script( 'jquery' );
	?>
	<link rel="stylesheet"  href="<?php echo get_option('siteurl'); ?>/wp-includes/wpj/farbtastic/farbtastic.css" type="text/css" />
	<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-includes/wpj/farbtastic/farbtastic.js"></script>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery.farbtastic('#colorpicker1').linkTo(function(color){
			jQuery('#color_1').css('background-color', color).val(color);
		}).setColor('<?php echo get_option('wpj_cs_1'); ?>');
		jQuery.farbtastic('#colorpicker2').linkTo(function(color){
			jQuery('#color_2').css('background-color', color).val(color);
		}).setColor('<?php echo get_option('wpj_cs_2'); ?>');
		jQuery.farbtastic('#colorpicker3').linkTo(function(color){
			jQuery('#color_3').css('background-color', color).val(color);
		}).setColor('<?php echo get_option('wpj_cs_3'); ?>');
	});
	</script>

	<h2>Color Swatch Picker</h2>

	<form action="themes.php?page=wpj-color-swatches" method="post">
		<table border="0">
			<tr>
				<td>Swatch #1:</td>
				<td>Swatch #2:</td>
				<td>Swatch #3:</td>
			</tr>
			<tr>
				<td>
					<div id="colorpicker1"></div>
					<input type="text" id="color_1" name="color_1" value="" />
				</td>
				<td>
					<div id="colorpicker2"></div>
					<input type="text" id="color_2" name="color_2" value="" />
				</td>
				<td>
					<div id="colorpicker3"></div>
					<input type="text" id="color_3" name="color_3" value="" />
				</td>
			</tr>
		</table>
		<br />
		<input type="submit" name="submit" value="submit" class="button" />
	</form>
	<?php
}

// Function to modify new activated plugin to work inside Joomla
//add_action( 'activated_plugin', 'wpj_modify_activated_plugin' );
function wpj_modify_activated_plugin( $path )
{
	jimport( 'joomla.filesystem.file' );

	$plugin_dir = dirname( WP_PLUGIN_DIR . DS . $path );
	if ( !is_dir( $plugin_dir ) ) {
		return;
	}

	$files = wpj_get_files_from_dir( $plugin_dir, 'php', $_files = array() );
	if ( empty( $files ) ) {
		return;
	}

	// Parse through files
	foreach ( $files as $file ) {
		$file_contents = JFile::read( $file );

		if ( empty( $file_contents ) ) {
			continue;
		}

		// Check to see if file has already been parsed
		if ( false !== strpos( $file_contents, 'wp_for_joomla' ) ) {
			continue;
		}

		// Make sure we have some matches
		if ( !preg_match_all( '/global\s.*?;/im', $file_contents, $matches ) ) {
			continue;
		}

		$matches = array_unique( $matches[0] );

		$_globals = array();
		foreach ( $matches as $value ) {
			if ( preg_match( '/global\s\$.*?;/im', $value ) ) {
				$_globals[] = $value;
			}
		}

		if ( empty( $_globals ) ) {
			continue;
		}

		$replace = "\n// wp_for_joomla - Automatically added globals at beginning of file\n";
		$replace .= implode( "\n", $_globals );

		$count = 0;
		$_file_contents = preg_replace(
			'/<\?php|<\?/im', '$0' . $replace, $file_contents, 1, $count );

		// Plan 2 for injecting our new code to file
		if ( $count == 0 ) {
			// Find the first carriage return
			$pos = strpos( $file_contents, "\n" );
			if ( !$pos ) {
				$pos = strpos( $file_contents, "\r" );
			}
			$file_start = substr( $file_contents, 0, $pos );
			$file_end   = substr( $file_contents, $pos );

			$_file_contents = $file_start . $replace . $file_end;
		}

		JFile::write( $file, $_file_contents );
	}
}

/**
 * If the Everyhome Template is set then, remove RSS feed hook, and add our own
 */
if ( 'everyhome' == get_option('template') )
{
	remove_action( 'do_feed_rss2', 'do_feed_rss2', 10, 1 );
	add_action( 'do_feed_rss2', 'wpj_do_feed_rss2' );
	function wpj_do_feed_rss2( $for_comments )
	{
		if ( $for_comments )
			load_template( ABSPATH . WPINC . '/feed-rss2-comments.php' );
		else
			load_template( ABSPATH . WPINC . '/feed-rss2-everyhome.php' );
	}
}

/**
 * This function will return the URL for the home blog URL, which is always blog_id 1
 */
add_filter( 'blog_option_home', 'jpw_get_home_blog_url', 50, 2 );
add_filter( 'blog_option_siteurl', 'jpw_get_home_blog_url', 50, 2 );
function jpw_get_home_blog_url( $value, $blog_id )
{
	static $home_blog_url;

	if ( 1 != $blog_id ) {
		return $value;
	}

	if ( !$home_blog_url ) {
		$wp_component_path = '/components/com_wordpress/wp';
		if ( false === strpos( ABSPATH, $wp_component_path ) ) {
			$wp_component_path = '';
		}
		$home_blog_url = untrailingslashit( j_get_root_uri() ) . $wp_component_path;
	}

	return $home_blog_url;
}

/**
 * This function will return the URL for the home blog URL, which is always blog_id 1
 */
add_filter( 'show_adduser_fields', 'jwp_show_adduser_fields' );
function jwp_show_adduser_fields()
{
	return false;
}

/**
 * This function will either enable or disable the WordPress multisite functionality
 **/
function wpj_enable_multisite( $enabled )
{
	global $mainframe;

	if ( !$enabled && null !== $enabled && !defined('WP_ALLOW_MULTISITE') && is_multisite() ) {
		wpj_disable_multisite();
		return;
	}

	// If all settings are right, then we are gooood ;)
	if ( ( ( defined( 'WP_ALLOW_MULTISITE' ) || ( defined( 'MULTISITE' ) && MULTISITE ) )
			&& $enabled )
		|| ( !defined( 'WP_ALLOW_MULTISITE' ) && !$enabled )
	) {
		return;
	}

	jimport( 'joomla.filesystem.file' );

	ignore_user_abort( true );

	$config_file = ABSPATH . 'wp-config.php';
	$file_contents = JFile::read( $config_file );
	$multisite_code_line = "define('WP_ALLOW_MULTISITE', true);";
	$string_to_find = "/* That's all, stop editing! Happy blogging. */";

	ob_start();
	?>
	<div style="background-color: red;" class="error fade"><p>
		Unabled to read wp-config.php file.<br />
		To <?php echo ( ( $enabled ) ? 'enable' : 'disable' ); ?> multisite you must edit this file: <strong><?php echo $config_file ?></strong><br />
		You must <?php echo ( ( $enabled ) ? 'add' : 'remove' ); ?> the line that reads the following: <strong><?php echo $multisite_code_line; ?></strong> above where it says <strong>/* That's all, stop editing! Happy blogging. */</strong>.
		<br />
		<?php if ( $enabled ) {
			?>
			Finally follow step #5 on this page <a href="http://codex.wordpress.org/Create_A_Network#Step_5:_Enabling_the_Network" target="_blank">http://codex.wordpress.org/Create_A_Network</a>
			<?php
		}
		?>
	</p></div>
	<?php
	$error_text = ob_get_clean();

	if ( false === $file_contents
		&& ( ( !defined( 'WP_ALLOW_MULTISITE' ) && $enabled )
		|| ( defined( 'WP_ALLOW_MULTISITE' ) && !$enabled ) )
	) {
		echo $error_text;
		return;
	}

	if ( $enabled ) {
		$file_contents = str_replace(
			$string_to_find, "{$multisite_code_line}\n{$string_to_find}", $file_contents );
		update_option( 'allowedthemes', array( 'everyhome' => 1 ) );
	} else {
		$file_contents = str_replace( $multisite_code_line, '', $file_contents );
	}

	$wrote = JFile::write( $config_file, $file_contents );

	if ( !$wrote ) {
		echo $error_text;
		return;
	}

	if ( $enabled ) {
		$mainframe->redirect( 'network.php' );
	}
}

/**
 * Function to disable multisite
 *
 * @return void
 **/
function wpj_disable_multisite()
{
	global $mainframe;

	delete_blog_option( 1, 'allowedthemes' );
	delete_blog_option( 1, 'wpj_enable_multisite' );
	delete_blog_option( 1, 'wpj_multisite_path' );
	if ( 'everyhome' == get_blog_option( 1, 'template' ) ) {
		update_blog_option( 1, 'template', 'twentyten' );
		update_blog_option( 1, 'stylesheet', 'twentyten' );
	}
	delete_site_option( 'wpj_multisite_path' );

	$mainframe->redirect( untrailingslashit( dirname( dirname( JURI::root(true) ) ) )
		. '/components/com_wordpress/wp/wp-admin/' );
}

/**
 * Function will create a WordPress user from a given Joomla user
 *
 * @param object The instance of a JUser object
 * @return mixed If success the user_id of the WordPress created user
 */
function j_create_wp_user( $juser )
{
	global $wpdb;

	// Create new WP user
		// First insert the empty row
	$db = JFactory::getDBO();
	$query = "INSERT IGNORE INTO {$wpdb->users}
		( ID, user_login, user_email, user_registered )
		VALUES
		( {$juser->id}, '{$juser->username}', '{$juser->email}', '". gmdate('Y-m-d H:i:s')."' )";
	$db->setQuery( $query );
	try{
		$db->query();
	}catch(EXCEPTION $e){
		return new WP_Error( 'error', 'Error 74' );
	}

	list( $first_name, $last_name ) = explode( ' ', $juser->name, 2 );
	if ( is_callable( array( $juser, 'authorise' ) ) && $juser->authorise( 'core.admin', '' ) ) {
		$role = 'administrator';
	} else {
		$role = get_site_option( 'default_role', 'subscriber' );
	}

	$wp_user = get_userdata( $juser->id );

	// Insert new user
	//require_once( ABSPATH . WPINC . '/registration.php' );
	$_user = array(
		'ID' => $juser->id,
		'user_login' => $juser->username,
		'user_email' => $juser->email,
		'first_name' => trim( $first_name ),
		'last_name' => trim( $last_name )
	);

	// If no update, don't update some values
	if ( !$wp_user->ID || !$wp_user->user_pass ) {
		$_user['role'] = $role;
		$_user['user_pass'] = substr( md5( uniqid( rand(), true ) ), 0, 25 ); // Random password
		$_user['display_name'] = trim( $first_name );
	}

	$user_id = wp_insert_user( $_user );

	return $user_id;
}

/**
 * Function will return the Itemid for the current blog
 *
 * @param bool If true it will echo the itemid
 * @return int The found itemid
 */
function j_get_itemid( $echo = false )
{
	global $mainframe;

	$itemid = '';
	if ( !( $itemid = get_option( 'Itemid' ) ) && !$mainframe->isAdmin() && is_admin() ) {
		$menu = $mainframe->getMenu();
		if ( method_exists( $menu, 'getActive' ) ) {
			$active = $menu->getActive();
			if ( isset( $active->id ) && 'com_wordpress' == $active->component ) {
				$itemid = $active->id;
			}
		}
	}

	if ( 0 == $itemid ) {
		return '';
	}

	$itemid = intval( $itemid );

	if ( $echo ) {
		echo $itemid;
	} else {
		return $itemid;
	}
}

/**
 * Function will set the Itemid for the blog if possible
 *
 * @return void
 */
function j_set_itemid()
{
	global $mainframe;

	$option = JFactory::getApplication()->input->get( 'option' );

	$_wp_itemid	= get_option( 'Itemid' );
	$_j_itemid	= intval( @$_REQUEST['Itemid'] );

	if ( isset( $_REQUEST['WP_ENTRYPOINT'] )
		&& in_array( $_REQUEST['WP_ENTRYPOINT'], array( 'wp-login.php' ) )
	) {
		return;
	}

	// Lets get the itemid
	if ( 'com_wordpress' != $option || is_multisite() ) {
		if ( is_multisite() ) {
			$menu = $mainframe->getMenu();
			$items = $menu->getItems( 'component', 'com_wordpress' );
			$menu->setActive( $items[0]->id );
		}
		return;
	}

	// Experimental, if there is no Itemid and there is one stored in db
	if ( !$_j_itemid && $_wp_itemid && !is_admin() ) {
		// Redirect the user to proper URL?
		$uri = JURI::getInstance();

		$uri->setVar( 'Itemid', $_wp_itemid );
		$mainframe->redirect( $uri->toString( array( 'path', 'query', 'fragment' ) ) );
	}

	if ( $_j_itemid && $_j_itemid != $_wp_itemid ) {
		$menu    = &$mainframe->getMenu();
		if ( is_object( $menu ) ) {
		    if ( $item = $menu->getActive() ) {
		        $wpitemid = $item->id;
			} else {
				foreach( $menu->getMenu() as $menuItem ) {
					if ( $menuItem->component == $component_name ) {
						$wpitemid = $menuItem->id;
					}
				}
			}
		}

		if ( $wpitemid && !is_admin() ) {
			update_option( 'Itemid', $wpitemid );

			// Redirect the user to proper URL?
			$uri = JURI::getInstance();

			$uri->setVar( 'Itemid', $wpitemid );
			$mainframe->redirect( $uri->toString( array( 'path', 'query', 'fragment' ) ) );
		}
	}

	// Possibly useful in the future
	// $menu = &$mainframe->getMenu();
	// $menu->setActive( $_wp_itemid );
}
j_set_itemid();

/**
 * Function will return the best possible guess of what the site URL is
 *
 * @param bool If true will get the true root of the Joomla site
 * @return string The root URL of the site
 */
function j_get_root_uri( $true_root = false )
{
	global $JOOMLA_CONFIG;
	static $site_url;

	if ( $site_url && !$true_root && !MULTISITE ) {
		return $site_url;
	}

	if ( $true_root && $JOOMLA_CONFIG->live_site ) {
		$site_url = $JOOMLA_CONFIG->live_site;
		return $site_url;
	}

	$jurl = str_replace( 'administrator/', '', JURI::root() );
	if ( !$jurl ) {
		$site_url = get_option( 'jhome_url' );
		return $site_url;
	}

	// Get Joomla's URL without the path to the WordPress files
	if ( false !== ( $pos = strpos( $jurl, 'components/com_wordpress/wp' ) ) ) {
		$jurl = substr( $jurl, 0, $pos );
	}

	// Check to see if we are in the process of installing multisite
	$tmp_multisite = false;
	if ( defined( 'WP_ALLOW_MULTISITE' )
		&& file_exists( ABSPATH . '..' .DS. 'configuration.php' )
	) {
		$tmp_multisite = true;
	}

	// This is so that we avoid the next if Joomla is really not installed in a subdir
	if ( ( $dir = JURI::root(true) ) ) {
		if ( is_admin() && ( is_multisite() || $tmp_multisite )
			&& false !== strpos( $dir, 'wp-admin' )
		) {
			// This will remove something like this /blogs/wp-admin
			$dir = rtrim( dirname( dirname( $dir ) ) , '/' );
		}
	}

	// This is if we want to get the current blogs root URI, from the front-end only,
	// unless our install is on a directory
	if ( !$true_root && ( !is_admin() || $dir || $JOOMLA_CONFIG->live_site )
		&& ( is_multisite() || $tmp_multisite )
	) {
		// If we are in sub directory, lets do a replacement
		if ( $dir ) {
			$jurl = str_replace( JURI::root(true), $dir, $jurl );
		}

		if( !is_admin() ) {
			$jurl .= ltrim( str_replace( JPATH_ROOT, '', ABSPATH ), DS );
		}
	}

	// Remove wp-admin segment if it exists
	$site_url = str_replace( 'wp-admin/', '', $jurl );

	return $site_url;
}

/**
 * Function will return an array of files that match the $file_type argument.
 *
 * Files are return as absolute paths, in an associative array.
 * Files are found recursively through directories starting at base directory
 *
 * @param string The base directory to start searching for files
 * @param string The file type, this is simply the extension for files that we are searching
 * @param array (Optional) List of all found files
 */
function wpj_get_files_from_dir( $path, $file_type, &$found_files )
{
    if ( substr( $path, -1 ) !== DS){ $path .= DS; }

	$use_pathinfo = function_exists( 'pathinfo' );

    if ( $handle = opendir( $path ) ) {
        while ( false !== ( $file = readdir( $handle ) ) ) {
			$_file = $path . $file;
			$_filetype = filetype( $_file );

			if ( $_filetype === 'file' ) {
				// Get extension
				if ( $use_pathinfo ) {
					$ext = pathinfo( $_file );
					$ext = $ext['extension'];
				} else {
					$ext = substr( $_file, strrpos( $_file, '.' ) + 1 );
				}

				if ( $ext == $file_type ) {
                	$found_files[] = $_file;
				}
			}

            if ( $_filetype === 'dir' && $file != '.' && $file != '..'){
                clearstatcache();
                wpj_get_files_from_dir( $_file, $file_type, $found_files );
            }
        }
        closedir( $handle );
    }

    return $found_files;
}

/**
 * Used to find the community component installed on the site
 * this is so we can perform specific community actions.
 */
function findCommunityComponent()
{
	$community = array( 'iscomprofiler' => false, 'isjomsocial' => false, 'iseasysocial' => false,
		'isbuddypress' => false, 'nocommunity' => false );

	if( file_exists( JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php' ) ) {
		$community[ 'iseasysocial' ] = true;
	} elseif ( file_exists( JPATH_ROOT . '/components/com_community/community.php' ) ) {
		$community['isjomsocial'] = true;
	} elseif ( file_exists( JPATH_ROOT . '/components/com_comprofiler/comprofiler.php' ) ) {
		$community['iscomprofiler'] = true;
	} elseif ( file_exists( WP_PLUGIN_DIR . '/buddypress.php' ) ) {
		$community['isbuddypress'] = true;
	}else{
		$community['nocommunity'] = true;
	}

	return $community;
}

function getSocialAvatar( $id_or_email, $size = '96', $alt = false )
{
	$db = JFactory::getDBO();
	extract( findCommunityComponent() );
	if ( $size == 0 ) { return; }

	if ( is_numeric( $id_or_email ) ) {
		$user_id = (int) $id_or_email;
	} elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) ) {
		if ( !$id_or_email->user_id && $id_or_email->comment_author_email ) {
			$id_or_email = get_user_by('email', $id_or_email->comment_author_email);
			if ( is_object( $id_or_email ) ) {
				$user_id = $id_or_email->ID;
			} else {
				$user_id = 0;
			}
		}else{
			$user_id = (int) $id_or_email->user_id;
		}
	} elseif ( is_string( $id_or_email ) ) {
		$id_or_email = get_user_by( 'email', $id_or_email );
		if ( is_object( $id_or_email ) ) {
			$user_id = $id_or_email->ID;
		} else {
			$user_id = 0;
		}
	} else {
		$user_id = $id_or_email;
	}

	$query = '';
	if ( $iscomprofiler ) {
		$query = "SELECT cb.avatar AS thumbnail, cb.avatarapproved
					FROM `#__comprofiler` AS cb
							WHERE cb.user_id = {$user_id}";
	}
	if ( $query ) {
		$db->setQuery( $query );
		$row = $db->loadObject();
	}

	if ( false === $alt) {
		$safe_alt = '';
	} else {
		$safe_alt = esc_attr( $alt );
	}

	$img_attr = 'class="avatar avatar-'.$size.' photo alignright" height="'.$size.'" width="'.$size.'"';

	ob_start();

	if ( $iseasysocial ) {
		include_once( JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php' );

		$avatar_url 	= Foundry::user( $user_id )->getAvatar( SOCIAL_AVATAR_LARGE );
		?>
		<a href="<?php echo FRoute::profile( array( 'id' => $user_id , 'layout' => 'profile' ) );?>">
			<img alt="<?php echo $safe_alt; ?>" <?php echo $img_attr; ?> src="<?php echo $avatar_url; ?>" /></a>
	<?php
	} else if ( $iscomprofiler ) {
		if ( $row->avatarapproved && $row->thumbnail ) {
			$thumb = JURI::root(true) . '/images/comprofiler/tn' . $row->thumbnail;
		} else {
			$thumb = JURI::root(true) .
			'/components/com_comprofiler/plugin/templates/default/images/avatar/tnnophoto_n.png';
		}

		$img_attr = 'class="avatar avatar-'.$size.' photo alignright" height="85" width="61"';
		?>
		<a href="<?php echo JRoute::_(
			'index.php?option=com_comprofiler&task=userProfile&user=' . $user_id ) ?>">
			<img alt="<?php echo $safe_alt; ?>" <?php echo $img_attr; ?> src="<?php echo $thumb; ?>" /></a>
	<?php } elseif ( $isjomsocial ) {
			$jspath = JPATH_ROOT .DS. 'components' .DS. 'com_community';
			include_once( $jspath .DS. 'libraries' .DS. 'core.php' );

			// Get CUser object
			$user =& CFactory::getUser( $user_id );
			$avatar_url = $user->getThumbAvatar();

		?>
		<a href="<?php echo JRoute::_(
			"index.php?option=com_community&view=profile&userid={$user_id}" ); ?>">
			<img alt="<?php echo $safe_alt; ?>" <?php echo $img_attr; ?> src="<?php echo $avatar_url; ?>" /></a>
	<?php } else {
		echo get_avatar( $user_id, $size, $alt );
	}
	$avatar = ob_get_clean();

	return $avatar;
}

/* JomSocial hooks */
add_action( 'wp_insert_post', 'wpj_wp_insert_post', 10, 2 );
function wpj_wp_insert_post( $post_id, $post ) {
	static $done = false;

	if ( $done ) {
		return true;
	}

	if ( 'publish' != $post->post_status
		|| $post->post_date != $post->post_modified
		|| wp_is_post_revision( $post )
	) {
		return true;
	}

	$path = JPATH_ROOT . '/components/com_community/libraries/userpoints.php';

	if ( file_exists( $path ) ) {
		jimport( 'joomla.utilities.string' );
		jimport( 'joomla.filesystem.folder' );

		include_once(JPATH_ROOT . '/components/com_community/libraries/core.php');

		// Load language file
		$language = JFactory::getLanguage();
		$language->load( 'com_wordpress' );

		include_once( $path );

		// Activity Stream
		$title        = JString::substr( $post->post_title , 0 , 20 ) . '...';
		$link         = get_permalink( $post->ID );
		$act          = new stdClass();
		$act->cmd     = 'blog.create';
		$act->actor   = $post->post_author;
		$act->target  = 0;
		$act->title   = str_replace( 'actor', '{actor}',
			JText::sprintf( 'WPJ_NEW_POST' , $link , $title ) );
		$act->content = $post->post_content;
		$act->app     = 'wordpress';
		$act->cid     = $post->ID;

		// Add activity logging
		CFactory::load( 'libraries', 'activities' );
		CActivityStream::add($act);

		// Points add
		// CuserPoints::assignPoint( 'wordpress.post.add' );
	}

	$done = true;

	return $post_id;
}

add_action( 'comment_post', 'wpj_comment_post', 10, 2 );
function wpj_comment_post( $comment_id, $approved ) {
	static $done = false;

	if ( $done ) {
		return true;
	}

	if ( 1 != $approved ) {
		return true;
	}

	$path = JPATH_ROOT .DS. 'components' .DS. 'com_community' .DS. 'libraries'
		.DS. 'userpoints.php';

	if ( file_exists( $path ) ) {
		jimport( 'joomla.utilities.string' );
		jimport( 'joomla.filesystem.folder' );

		// Load language file
		$language =& JFactory::getLanguage();
		$language->load( 'com_wordpress' );

		include_once( $path );

		$comment = get_comment( $comment_id );

		// We need a user ID for this
		if ( !$comment->user_id ) {
			return true;
		}

		$post = get_post( $comment->comment_post_ID );

		// Activity Stream
		$title        = JString::substr( $post->post_title , 0 , 20 ) . '...';
		$link         = get_permalink( $post->ID ) . '#comment-' . $comment->comment_ID;
		$act          = new stdClass();
		$act->cmd     = 'blog.create';
		$act->actor   = $comment->user_id;
		$act->target  = 0;
		$act->title   = str_replace( 'actor', '{actor}',
			JText::sprintf( 'WPJ_NEW_COMMENT' , $link , $title ) );
		$act->content = $comment->comment_content;
		$act->app     = 'wordpress';
		$act->cid     = $post->ID;

		// Add activity logging
		CFactory::load ( 'libraries', 'activities' );
		CActivityStream::add($act);
	}

	$done = true;

	return $post_id;
}

function wpj_logger( $msg )
{
	$fp = fopen( JPATH_ROOT .DS. 'components' .DS. 'com_wordpress' .DS. 'log.txt', 'a+' );
	$date = gmdate( 'Y-m-d H:i:s ' );
	fwrite( $fp, "\n\n" . $date . $msg );
	fclose( $fp );

	return true;
}

?>