<?php

/* rc_corephp - This is for when we have multisite enabled */
// Load Joomla
if ( !defined( '_JEXEC' ) ) {
	$_SERVER['WPPRE_SCRIPT_FILENAME'] = $_SERVER['SCRIPT_FILENAME'];
	$_SERVER['WPPRE_REQUEST_URI'] = $_SERVER['REQUEST_URI'];
	$_SERVER['WPPRE_SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'];
	$_SERVER['WPPRE_PHP_SELF'] = $_SERVER['PHP_SELF'];

	$_SERVER['SCRIPT_FILENAME'] = dirname( dirname( $_SERVER['SCRIPT_FILENAME'] ) )
		. DIRECTORY_SEPARATOR . basename( $_SERVER['SCRIPT_FILENAME'] );
	$_SERVER['REQUEST_URI'] = dirname( $_SERVER['REQUEST_URI'] );
	$_SERVER['SCRIPT_NAME'] = '/index.php';
	$_SERVER['PHP_SELF'] = '/index.php';

	$_REQUEST['option'] = 'com_wordpress';
	$_REQUEST['task'] = 'wp-signup.php';

	include( '../index.php' );
	return;
} else {
	/* rc_corephp - This is for when we have multisite enabled */
	$_SERVER['SCRIPT_FILENAME'] = $_SERVER['WPPRE_SCRIPT_FILENAME'];
	$_SERVER['REQUEST_URI'] = $_SERVER['WPPRE_REQUEST_URI'];
	$_SERVER['SCRIPT_NAME'] = $_SERVER['WPPRE_SCRIPT_NAME'];
	$_SERVER['PHP_SELF'] = $_SERVER['WPPRE_PHP_SELF'];

	require( 'wp-signup_real.php' );
}
