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
		@include( dirname( __FILE__ ) .DS. 'admin' .DS. 'install.html.php' );
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
		@include( dirname( __FILE__ ) .DS. 'admin' .DS. 'install.html.php' );
	}

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @return void
	 */
	function preflight( $type, $parent )
	{
	}

	/**
	 * method to run after an install/update/uninstall method
	 *
	 * @return void
	 */
	function postflight( $type, $parent )
	{
		if ( in_array( $type, array( 'install', 'update' ) ) ) {
			jimport('joomla.filesystem.folder');
			jimport('joomla.filesystem.file');
			jimport('joomla.installer.installer');

			if ( !$this->install_modules( $type, $parent ) ) {
				JError::raiseWarning( 21, JText::_( 'COM_WORDPRESS_ERROR_INSTALLING_MODULES' ) );
			}

			if ( !$this->install_plugins( $type, $parent ) ) {
				JError::raiseWarning( 21, JText::_( 'COM_WORDPRESS_ERROR_INSTALLING_PLUGINS' ) );
			}
		}
	}

	function install_modules( $type, $parent )
	{
		// Get an installer instance
		$installer = new JInstaller(); // Cannot use the instance that is already created, no!
		$app = JFactory::getApplication();
		$db = JFactory::getDBO();
		$mlds_path = $parent->getParent()->getPath('source') . '/admin/extensions/modules';
		$returns = array();

		if ( !JFolder::exists( $mlds_path ) ) {
			return true;
		}

		// Loop through modules
		$modules = JFolder::folders( $mlds_path );
		foreach ( $modules as $module ) {
			$m_dir = $mlds_path .'/'. $module .'/';

			// Install the package
			if ( !$installer->install( $m_dir ) ) {
				// There was an error installing the package
				JError::raiseWarning( 21, JTEXT::sprintf(
					'COM_WORDPRESS_MODULE_INSTALL_ERROR', $module ) );
				$returns[] = false;
			} else {
				// Package installed sucessfully
				$app->enqueueMessage( JTEXT::sprintf(
					'COM_WORDPRESS_MODULE_INSTALL_SUCCESS', $module ) );
				$returns[] = true;
			}
		}

		return !in_array( false, $returns, true );
	}

	function install_plugins( $type, $parent )
	{
		// Get an installer instance
		$installer = new JInstaller(); // Cannot use the instance that is already created, no!
		$app = JFactory::getApplication();
		$db = JFactory::getDBO();
		$plgs_path = $parent->getParent()->getPath('source') . '/admin/extensions/plugins';
		$returns = array();
		$enable = array();
		$auto_enable = array(
			'authentication/wordpress', 'user/wordpress'
			);

		if ( !JFolder::exists( $plgs_path ) ) {
			return true;
		}

		// Loop through plugin types
		$plg_types = JFolder::folders( $plgs_path );
		foreach ( $plg_types as $plg_type ) {
			// Loop through plugins
			$plugins = JFolder::folders( $plgs_path .'/'. $plg_type );
			foreach ( $plugins as $plugin ) {
				$p_dir = $plgs_path .'/'. $plg_type .'/'. $plugin .'/';

				// Install the package
				if ( !$installer->install( $p_dir ) ) {
					// There was an error installing the package
					JError::raiseWarning( 21, JTEXT::sprintf(
						'COM_WORDPRESS_PLUGIN_INSTALL_ERROR', $plg_type . '/' . $plugin ) );
					$returns[] = false;
				} else {
					// Package installed sucessfully
					$app->enqueueMessage( JTEXT::sprintf(
						'COM_WORDPRESS_PLUGIN_INSTALL_SUCCESS', $plg_type . '/' . $plugin ) );
					$returns[] = true;

					// Maybe auto enable?
					if ( 'install' == $type && in_array( $plg_type.'/'.$plugin, $auto_enable ) ) {
						$enable[] = "(`folder` = '{$plg_type}' AND `element` = '{$plugin}')";
					}
				}
			}
		}

		// Run query
		if ( !empty( $enable ) ) {
			$db->setQuery( "UPDATE #__extensions
				SET `enabled` = 1
					WHERE ( " . implode( ' OR ', $enable ) . " ) AND `type` = 'plugin'" );

			if ( !$db->query() ) {
				JError::raiseWarning( 1, JText::_( 'COM_WORDPRESS_ERROR_ENABLING_PLUGINS' ) );
				return false;
			}
		}

		return !in_array( false, $returns, true );
	}
}
