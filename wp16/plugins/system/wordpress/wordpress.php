<?php
if ( !defined('_JEXEC') ) { die( 'Direct Access to this location is not allowed.' ); }
/**
 * @version		$Id: wordpress.php 1 2009-10-27 20:56:04Z rafael $
 * @package		WordPress for Joomla!
 * @copyright	Copyright (C) 2010 'corePHP' / corephp.com. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt
 */

jimport('joomla.plugin.plugin');

class  plgSystemWordPress extends JPlugin
{
	function plgSystemShsef( &$subject, $config )
	{
		parent::__construct( $subject, $config );
	}

	function onAfterInitialise()
	{
		global $_wp_url_param;
		$_wp_url_param = explode( "\n", $this->params->get( 'url_path' ) );
		$current_url_path = '/';
		
		$_wp_url_param_tmp = array_reverse( $_wp_url_param );
		foreach ( $_wp_url_param_tmp as $tmp_value ) {
			$pos = strpos( $_SERVER['REQUEST_URI'], '/' . $tmp_value );
			$pos2 = strpos( $_SERVER['REQUEST_URI'], JURI::root(true) . '/' . $tmp_value );

			if ( false !== $pos || false !== $pos2 ) {
				$current_url_path = $tmp_value;
				break;
			}
		}

		if ( isset( $_SERVER['WPPRE_REQUEST_URI'] ) ) {
			$pos = 0;
		}

		// If blog is not on the homepage
		if ( !$this->params->get( 'is_homepage', 0 )
			&& ( ( 0 == $pos && false !== $pos ) || ( 0 == $pos2 && false !== $pos2 ) )
		) {
			$_SERVER['WP_REQUEST_URI'] = $_SERVER['REQUEST_URI'];
			$_SERVER['REQUEST_URI'] = '/' . $current_url_path
				. $this->params->get( 'request_uri_suffix' );
		}

		// If blog is on the homepagex
		if ( $this->params->get( 'is_homepage', 0 )
			&& ( !$_SERVER['REQUEST_URI'] || '/' == $_SERVER['REQUEST_URI']
				|| false !== strpos( $_SERVER['REQUEST_URI'],
					'/' . $this->params->get( 'menu_slug', '' ) )
				|| 0 === strpos( $_SERVER['REQUEST_URI'], '/page/' )
			)
		) {
			$_SERVER['WP_REQUEST_URI'] = $_SERVER['REQUEST_URI'];
			$_SERVER['REQUEST_URI'] = '/' . $current_url_path;

			if ( false !== strpos( $_SERVER['WP_REQUEST_URI'],
				'/' . $this->params->get( 'menu_slug' ) . '/feed' )
			) {
				$_SERVER['WP_REQUEST_URI'] = str_replace( '/' . $this->params->get( 'menu_slug' ),
				 	'', $_SERVER['WP_REQUEST_URI'] );
			}
		}
	}

	function onAfterRender()
	{
		if ( !$this->params->get( 'url_path' ) ) {
			return;
		}

		$buffer = JResponse::getBody();

		$buffer = str_replace( $this->params->get( 'url_path' ) . '.html',
			rtrim( $this->params->get( 'url_path' ), '/' ) . '/', $buffer );

		JResponse::setBody( $buffer );
	}
}
