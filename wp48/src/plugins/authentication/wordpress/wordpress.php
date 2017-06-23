<?php
if ( !defined('_JEXEC') ) { die( 'Direct Access to this location is not allowed.' ); }
/**
 * @version		$Id: wordpress.php 1 2009-10-27 20:56:04Z rafael $
 * @package		WordPress for Joomla!
 * @copyright	Copyright (C) 2010 'corePHP' / corephp.com. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt
 */

jimport( 'joomla.plugin.plugin' );

/**
 * WordPress Authentication Plugin
 *
 * @package		WordPress for Joomla
 * @since 1.0
 */
class plgAuthenticationWordPress extends JPlugin
{
	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param	object	$subject	The object to observe
	 * @param	array	$config		An array that holds the plugin configuration
	 * @since	1.5
	 */
	function plgAuthenticationWordPress( &$subject, $config )
	{
		parent::__construct( $subject, $config );
	}

	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @access	public
	 * @param	array	$credentials	Array holding the user credentials
	 * @param	array	$options		Array of extra options
	 * @param	object	$response		Authentication response object
	 * @return	boolean
	 * @since	1.5
	 */
	function onUserAuthenticate( $credentials, $options, &$response )
	{
		global $wpdb;

		if ( !isset( $credentials['hash'] ) ) {
			return;
		}

		$db = JFactory::getDBO();

		// Lets double check that the request is valid
		$query = "SELECT `timestamp`
			FROM {$wpdb->prefix}jauthenticate
				WHERE `hash` = '{$credentials['hash']}'
				AND `user_id` = {$credentials['user_id']}";
		$db->setQuery( $query );
		$timestamp = $db->loadResult();

		if ( $timestamp ) {
			$juser = JFactory::getUser( $credentials['user_id'] );

			$response->status        = JAUTHENTICATE_STATUS_SUCCESS;
			$response->error_message = '';
			$response->email         = $juser->email;
			$response->fullname      = $juser->name;

			return true;
		} else {
			$response->status        = JAUTHENTICATE_STATUS_FAILURE;
			$response->error_message = 'Could not authenticate';

			return false;
		}
	}
}
