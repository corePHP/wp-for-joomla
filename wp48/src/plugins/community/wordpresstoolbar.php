<?php
/**
 * @category	Plugins
 * @package		JomSocial
 * @copyright (C) 2008 by Slashes & Dots Sdn Bhd - All rights reserved!
 * @license		GNU/GPL, see LICENSE.php
 */
// no direct access

defined('_JEXEC') or die('Restricted access');
require_once( JPATH_ROOT . DS . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php');

if(!class_exists('plgCommunityWordPressToolbar'))
{
	class plgCommunityWordPressToolbar extends CApplications
	{
		var $name 		= "WordPress Blog Toolbar";
		var $_name		= 'wordpresstoolbar';
		var $_path		= '';

	    function plgCommunityWordPressToolbar(& $subject, $config)
	    {
			$this->_path	= JPATH_ROOT . DS . 'components' . DS . 'com_wordpress';			 
			parent::__construct($subject, $config);
	    }	

		function onSystemStart()
		{				
			if( !class_exists('CFactory'))
				require_once( JPATH_ROOT . DS . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php');

			if( !file_exists( $this->_path . DS . 'wordpress.php' ) )
				return;

			// Load Wordpress

			include_once ( $this->_path . DS . 'wordpress_loader.php' );
			wpj_loader::load();

			// Lets check to see if multi-site is turned on or off. 

			// If it is not turned on - lets not run the render the toolbar.

			if ( !is_multisite() ) { return; }

			$user 		= JFactory::getUser();
			$userId 	= $user->id;

			global $current_user;

			wp_set_current_user($userId);

			$bloginfo = get_blog_details( (int) $current_user->primary_blog, false ); // only get bare details!
			//initialize the toolbar object	

			$toolbar	= CFactory::getToolbar();		

			//Load Language file.

			JPlugin::loadLanguage( 'plg_wordpresstoolbar' );								

			//adding new 'tab' 'Blog' in JomSocial toolbar

			$blankUrl = '#';

			$toolbar->addGroup('WORDPRESS', JText::_('PLG_WORDPRESSTOOLBAR_BLOG'), $blankUrl );

			if ($bloginfo ) {
				$writeUrl	= get_blogaddress_by_id( 1 ) . 'wp-admin/post-new.php';
				$toolbar->addItem('WORDPRESS', 'WORDPRESS_WRITE', JText::_('PLG_WORDPRESSTOOLBAR_WRITE_BLOG'), $writeUrl);
			} else {
				$registerUrl	= get_blogaddress_by_id( 1 ) . 'wp-signup.php';
				$toolbar->addItem('WORDPRESS', 'WORDPRESS_REGISTER', JText::_('PLG_WORDPRESSTOOLBAR_REGISTER_BLOG'), $registerUrl);
			}			

			if ( $bloginfo ) 
			{
				$blog_url = esc_url( 'http://' . $bloginfo->domain . $bloginfo->path );
				$toolbar->addItem('WORDPRESS', 'WORDPRESS_READ', JText::_('PLG_WORDPRESSTOOLBAR_READ_BLOG'), $blog_url );
			}

			$readAllUrl = network_site_url();

			$toolbar->addItem('WORDPRESS', 'WORDPRESS_READ_ALL', JText::_('PLG_WORDPRESSTOOLBAR_READ_ALL_BLOGS'), $readAllUrl );

			// Unload WordPress

			wpj_loader::unload();
		}	
	}	
}



