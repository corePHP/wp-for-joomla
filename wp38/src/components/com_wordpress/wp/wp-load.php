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

	if(!defined('JWP_BASE') && FALSE !== strpos(dirname($_SERVER['SCRIPT_FILENAME']), 'components'.DS.'com_wordpress')){
		$path = explode('components'.DS.'com_wordpress',dirname($_SERVER['SCRIPT_FILENAME'])); // single
		define('JPATH_BASE', $path[0]);
	} elseif (!is_link($_SERVER['SCRIPT_FILENAME'])) {
		define( 'JPATH_BASE', realpath( dirname(__FILE__) . DS.'..' ).DS ); // multi no sym
	} elseif ( !defined('JWP_BASE') && FALSE !== strpos($_SERVER['SCRIPT_FILENAME'],'wp-admin') ){
		$path = explode('wp-admin',$_SERVER['SCRIPT_FILENAME']);
		$path = array_pop($path);
		$path = trim($path,'/');
		$count = count(explode('/',$path)) + 2;
		$path = explode('/',$_SERVER['SCRIPT_FILENAME']);
		preg_match('/(.*)?(?:\/.*?){'.$count.'}$/',$_SERVER['SCRIPT_FILENAME'],$matches);
		define('JPATH_BASE', $matches[1].DS);
	} else {
		define('JPATH_BASE', dirname($_SERVER['SCRIPT_FILENAME']).DS);
	}

	require_once ( JPATH_BASE .'includes'.DS.'defines.php' );
	require_once ( JPATH_BASE .'includes'.DS.'framework.php' );
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

if ( file_exists( ABSPATH . 'wp-config.php') ) {

	/** The config file resides in ABSPATH */
	require_once( ABSPATH . 'wp-config.php' );

}
/* rc_corephp No need for these additional checks as we are using Joomla * /
} elseif ( file_exists( dirname(ABSPATH) . '/wp-config.php' ) && ! file_exists( dirname(ABSPATH) . '/wp-settings.php' ) ) {

	/** The config file resides one level above ABSPATH but is not part of another install * /
	require_once( dirname(ABSPATH) . '/wp-config.php' );

} else {

	// A config file doesn't exist

	// Set a path for the link to the installer
	if ( strpos($_SERVER['PHP_SELF'], 'wp-admin') !== false )
		$path = 'setup-config.php';
	else
		$path = 'wp-admin/setup-config.php';

	define( 'WPINC', 'wp-includes' );
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	require_once( ABSPATH . WPINC . '/load.php' );
	require_once( ABSPATH . WPINC . '/version.php' );

	wp_check_php_mysql_versions();
	wp_load_translations_early();

	// Standardize $_SERVER variables across setups.
	wp_fix_server_vars();

	require_once( ABSPATH . WPINC . '/functions.php' );

	$path = wp_guess_url() . '/wp-admin/setup-config.php';

	// Die with an error message
	$die  = __( "There doesn't seem to be a <code>wp-config.php</code> file. I need this before we can get started." ) . '</p>';
	$die .= '<p>' . __( "Need more help? <a href='http://codex.wordpress.org/Editing_wp-config.php'>We got it</a>." ) . '</p>';
	$die .= '<p>' . __( "You can create a <code>wp-config.php</code> file through a web interface, but this doesn't work for all server setups. The safest way is to manually create the file." ) . '</p>';
	$die .= '<p><a href="' . $path . '" class="button button-large">' . __( "Create a Configuration File" ) . '</a>';

	wp_die( $die, __( 'WordPress &rsaquo; Error' ) );
}
/* */
