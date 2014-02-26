<?php
if ( ! (  defined( '_JEXEC' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }
/**
*
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

$mainframe = JFactory::getApplication();
$user      = JFactory::getUser();
$db        = JFactory::getDBO();

$now  = strtotime( 'now' );
$secret = ( $mainframe->getCfg('secret')
	? $mainframe->getCfg('secret')
	: md5( $mainframe->getCfg( 'sitename' ) )
);
$hash = md5( $user->username . $secret );

$query = "REPLACE INTO #__wp_jauthenticate
	( `user_id`, `hash`, `timestamp` )
	VALUES
	( {$user->id}, '{$hash}', {$now} )";
$db->setQuery( $query );
$db->query();

// Check if table exists
$query = $db->getQuery( true );
$query
	->select( '*' )
	->from( 'information_schema.TABLES' )
	->where( 'TABLE_SCHEMA = ' . $db->quote( $mainframe->getCfg( 'db' ) ) )
	->where( 'TABLE_NAME = ' . $db->quote( str_replace( '#__', $mainframe->getCfg( 'dbprefix' ), '#__wp_options') ) );
$db->setQuery( $query );
$table_found = $db->loadResult();
try
{
	$table_found = $db->loadResult();
}
catch (EXCEPTION $e)
{
	$table_found = null;
}

if( !is_null( $table_found ) ) {
	// Find path to WordPress folder
	$query = $db->getQuery( true );
	$query
		->select( 'option_value' )
		->from( $db->quoteName( '#__wp_options' ) )
		->where( 'option_name = ' . $db->quote( 'wpj_multisite_path' ) );
	$db->setQuery( $query );
	$wp_path = $db->loadResult();
} else {
	$wp_path = '';
}

// Check to see if we are in multisite or not
if ( !$wp_path ) {
	$path = 'components/com_wordpress/wp/wp-admin/';
} else {
	$path = "{$wp_path}/wp-admin/";
}
$mainframe->redirect( JURI::root() . "{$path}?h={$hash}" );
?>