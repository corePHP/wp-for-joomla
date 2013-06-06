<?php
/**
 * Loads the WordPress environment and template.
 *
 * @package WordPress
 */

if ( !isset($wp_did_header) ) {

	$wp_did_header = true;

	require_once( 'wp-load.php' );

	wp();

	require_once( ABSPATH . WPINC . '/template-loader.php' );

}
