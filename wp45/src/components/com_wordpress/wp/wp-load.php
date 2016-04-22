<?php
global $mainframe;
// Not through Joomla entrance

/* rc_corephp */
if ( !defined( '_JEXEC' ) ) {
	global $option;

	define( '_JEXEC', 1 );
	define( '_WP_INCLUDED_J', 1 );
	if ( !defined( 'DS' ) ) {
		define( 'DS', DIRECTORY_SEPARATOR );
	}

	if(!defined('JWP_BASE') && FALSE !== strpos(dirname($_SERVER['SCRIPT_FILENAME']), 'components/com_wordpress')){
		$path = explode('components/com_wordpress',dirname($_SERVER['SCRIPT_FILENAME'])); // single
		define('JPATH_BASE', $path[0].'/');
	} elseif (!is_link($_SERVER['SCRIPT_FILENAME'])) {
		define( 'JPATH_BASE', realpath( dirname(__FILE__) . '/../' ) .'/' ); // multi no sym
	} elseif ( !defined('JWP_BASE') && FALSE !== strpos($_SERVER['SCRIPT_FILENAME'],'wp-admin') ){
		$path = explode('wp-admin',$_SERVER['SCRIPT_FILENAME']);
		$path = array_pop($path);
		$path = trim($path,'/');
		$count = count(explode('/',$path)) + 2;
		$path = explode('/',$_SERVER['SCRIPT_FILENAME']);
		preg_match('/(.*)?(?:\/.*?){'.$count.'}$/',$_SERVER['SCRIPT_FILENAME'],$matches);
		define('JPATH_BASE', $matches[1].'/');
	} else {
		define('JPATH_BASE', dirname($_SERVER['SCRIPT_FILENAME']).'/');
	}

	require_once ( JPATH_BASE .'includes/defines.php' );
	require_once ( JPATH_BASE .'includes/framework.php' );
	$mainframe	= JFactory::getApplication( 'site' );
	$mainframe->initialise();
} else {
	if ( !$mainframe ) {
		$mainframe = JFactory::getApplication( 'site' );
	}
}
/* rc_corephp end */
/**
 * Bootstrap file for setting the ABSPATH constant
 * and loading the wp-config.php file. The wp-config.php
 * file will then load the wp-settings.php file, which
 * will then set up the WordPress environment.
 *
 * If the wp-config.php file is not found then an error
 * will be displayed asking the visitor to set up the
 * wp-config.php file.
 *
 * Will also search for wp-config.php in WordPress' parent
 * directory to allow the WordPress directory to remain
 * untouched.
 *
 * @internal This file must be parsable by PHP4.
 *
 * @package WordPress
 */

/** Define ABSPATH as this file's directory */
define( 'ABSPATH', dirname(__FILE__) . '/' );

error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );

/*
 * If wp-config.php exists in the WordPress root, or if it exists in the root and wp-settings.php
 * doesn't, load wp-config.php. The secondary check for wp-settings.php has the added benefit
 * of avoiding cases where the current directory is a nested installation, e.g. / is WordPress(a)
 * and /blog/ is WordPress(b).
 *
 * If neither set of conditions is true, initiate loading the setup process.
 */
if ( file_exists( ABSPATH . 'wp-config.php') ) {

	/** The config file resides in ABSPATH */
	require_once( ABSPATH . 'wp-config.php' );

} 
/* rc_corephp No need for these additional checks as we are using Joomla * /

elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {

	/** The config file resides one level above ABSPATH but is not part of another install */
	/*require_once( dirname( ABSPATH ) . '/wp-config.php' );

} else {

	// A config file doesn't exist

	define( 'WPINC', 'wp-includes' );
	require_once( ABSPATH . WPINC . '/load.php' );

	// Standardize $_SERVER variables across setups.
	wp_fix_server_vars();

	require_once( ABSPATH . WPINC . '/functions.php' );

	$path = wp_guess_url() . '/wp-admin/setup-config.php';

	/*
	 * We're going to redirect to setup-config.php. While this shouldn't result
	 * in an infinite loop, that's a silly thing to assume, don't you think? If
	 * we're traveling in circles, our last-ditch effort is "Need more help?"
	 */
/*	if ( false === strpos( $_SERVER['REQUEST_URI'], 'setup-config' ) ) {
		header( 'Location: ' . $path );
		exit;
	}

	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	require_once( ABSPATH . WPINC . '/version.php' );

	wp_check_php_mysql_versions();
	wp_load_translations_early();

	// Die with an error message
	$die  = sprintf(
		/* translators: %s: wp-config.php */
	/*	__( "There doesn't seem to be a %s file. I need this before we can get started." ),
		'<code>wp-config.php</code>'
	) . '</p>';
	$die .= '<p>' . sprintf(
		/* translators: %s: Codex URL */
	/*	__( "Need more help? <a href='%s'>We got it</a>." ),
		__( 'https://codex.wordpress.org/Editing_wp-config.php' )
	) . '</p>';
	$die .= '<p>' . sprintf(
		/* translators: %s: wp-config.php */
	/*	__( "You can create a %s file through a web interface, but this doesn't work for all server setups. The safest way is to manually create the file." ),
		'<code>wp-config.php</code>'
	) . '</p>';
	$die .= '<p><a href="' . $path . '" class="button button-large">' . __( "Create a Configuration File" ) . '</a>';

	wp_die( $die, __( 'WordPress &rsaquo; Error' ) );
}
/* */