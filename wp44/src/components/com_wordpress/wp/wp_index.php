<?php
if ( ! (  defined( '_JEXEC' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress
 */

/* rc_corephp - This is for when we have multisite enabled */
if ( isset( $_SERVER['WPPRE_SCRIPT_FILENAME'] ) ) {
	// Store Joomla's
	$_SERVER['JPRE_SCRIPT_FILENAME'] = $_SERVER['SCRIPT_FILENAME'];
	$_SERVER['JPRE_REQUEST_URI'] = $_SERVER['REQUEST_URI'];
	$_SERVER['JPRE_SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'];
	$_SERVER['JPRE_PHP_SELF'] = $_SERVER['PHP_SELF'];

	// Set wordpress request
	$_SERVER['SCRIPT_FILENAME'] = $_SERVER['WPPRE_SCRIPT_FILENAME'];
	$_SERVER['REQUEST_URI'] = $_SERVER['WPPRE_REQUEST_URI'];
	$_SERVER['SCRIPT_NAME'] = $_SERVER['WPPRE_SCRIPT_NAME'];
	$_SERVER['PHP_SELF'] = $_SERVER['WPPRE_PHP_SELF'];
}

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
define('WP_USE_THEMES', true);

/** Loads the WordPress Environment and Template */
/* rc_removed ./ before the require */
require('wp-blog-header.php');

if ( isset( $_SERVER['JPRE_SCRIPT_FILENAME'] ) ) {
	$_SERVER['SCRIPT_FILENAME'] = $_SERVER['JPRE_SCRIPT_FILENAME'];
	$_SERVER['REQUEST_URI'] = dirname( dirname(  $_SERVER['JPRE_REQUEST_URI'] ) ) . 'index.php';
	$_SERVER['SCRIPT_NAME'] = $_SERVER['JPRE_SCRIPT_NAME'];
	$_SERVER['PHP_SELF'] = $_SERVER['JPRE_PHP_SELF'];

	// Set the original path - Experimental
	$uri = JURI::getInstance();
	$uri->setPath( trailingslashit( JURI::root( true ) ) );
}
?>