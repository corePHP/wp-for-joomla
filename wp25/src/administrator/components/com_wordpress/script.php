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

class com_WordPressInstallerScript
{
	/**
	* method to install the component
	*
	* @return void
	*/
	function install( $parent ) 
	{
		require ( dirname( __FILE__ ) .DS. 'admin' .DS. 'install.html.php' );
	}

	/**
	 * method to uninstall the component
	 *
	 * @return void
	 */
	function uninstall( $parent ) 
	{
		echo '<p>' . JText::_( 'COM_WORDPRESS_UNINSTALL_TEXT') . '</p>';
	}

	/**
	 * method to update the component
	 *
	 * @return void
	 */
	function update( $parent ) 
	{
		require ( dirname( __FILE__ ) .DS. 'admin' .DS. 'install.html.php' );
	}
}
