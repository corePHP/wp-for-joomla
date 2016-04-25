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
	function onAfterInitialise()
	{
		$app = JFactory::getApplication();
		$input = JFactory::getApplication()->input;

		if($app->isAdmin()){
		    $form = $input->get( 'jform', array(), 'ARRAY');
		    if (in_array($form['type'], array('alias','url','heading','separator'))){
		        return;
		    }
		}

		// Run menu creation through our own component
		if ( $app->isAdmin()
			   && $input->get('option') == 'com_menus'
			   //&& $input->get('id') === '0'
			   && false !== stripos($input->get('jform',array(),'ARRAY'), 'option=com_wordpress' )
			   && in_array( $input->get('task'), array('item.apply', 'item.save', 'item.save2new') ) ) {
			$input->set('option', 'com_wordpress', 'item.save2new');
		}

		$menuItems = jfactory::getapplication()->getmenu()->getItems('component', 'com_wordpress');

	   foreach ( $menuItems as &$item ) {
			if( $item->component === 'com_wordpress' && $item->query['view'] == 'bloglink' && !isset($item->query['layout']) ) {
				$isHomePage = (bool)$item->home;
				$item->route = $item->params->get('blog_path',$item->route);
			} elseif ( $item->component === 'com_wordpress' && $item->query['view'] == 'bloglink' && isset($item->query['layout']) ) {
				$item->route = '';

				if( !$isHomePage ) {
					$item->route = "{$alias}/";
				}

				$item->route .= "{$item->query['layout']}/{$item->query[$item->query['layout']]}/";
			}
		}

		if(!defined('DS')){
			define('DS', DIRECTORY_SEPARATOR);
		}

		global $_wp_url_param;

		if ( JFactory::getApplication()->isAdmin() ) {
			return;
		}

		if ( defined('WP_ADMIN') && WP_ADMIN ) {
			return;
		}

		if ( defined( 'SHORTINIT' ) ) {
			return;
		}

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

	function onAfterRoute()
	{
		$app = JFactory::getApplication();

		if($app->input->get('option') !== 'com_wordpress' && $app->input->get('view') !== 'bloglink' || $app->isAdmin()){
			return;
		}

		switch ( JFactory::getApplication()->input->get('layout') ) {
			case 'category' :
			   $_SERVER['WP_REQUEST_URI'] .= "/category/". JFactory::getApplication()->input->get('cat').'/';
		}

	}

	function onBeforeRender()
	{
		$app = JFactory::getApplication();

		if($app->input->get('option') === 'com_wordpress' && $app->input->get('view') === 'bloglink' || $app->isSite()){
			$app->getMenu()->setActive( $app->input->get('Itemid') );
		}
	}

	function onAfterRender()
	{
		if ( !$this->params->get( 'url_path' ) ) {
			return;
		}

		$buffer = JResponse::getBody();

		$buffer = str_replace( $this->params->get( 'url_path' ) . '.html',
			JRoute::_( rtrim( $this->params->get( 'url_path' ), '/' ) . '/' ), $buffer );

		$buffer = str_replace(
			JURI::root(true) . '/' . ltrim( $this->params->get( 'url_path' ), '/' ) . '" ',
			JURI::root(true) . '/' . trim( $this->params->get( 'url_path' ), '/' ) . '/" ',
			$buffer );

		JResponse::setBody( $buffer );
	}
}