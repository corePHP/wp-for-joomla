<?php
/**
 * sh404SEF support for WordPress Component.
 * @copyright	Copyright (C) 2009-2010 'corePHP' / corephp.com. All rights reserved.
 * @version		$Id: com_wordpress.php 1 2008-14 00:14 rafael $
 * @license		GNU/GPL
 * 
 * Version 1.1.1
 */

defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

// ------------------  standard plugin initialize function - don't change ---------------------------
global $sh_LANG, $sefConfig;
$shLangName = '';
$shLangIso = '';
$title = array();
$shItemidString = '';
$dosef = shInitializePlugin( $lang, $shLangName, $shLangIso, $option);
if ( $dosef == false ) return;
// ------------------  standard plugin initialize function - don't change ---------------------------

// ------------------  load language file - adjust as needed ----------------------------------------
//$shLangIso = shLoadPluginLanguage( 'com_wordpress', $shLangIso, '_SH404SEF_WORDPRESS_w00t');
// ------------------  load language file - adjust as needed ----------------------------------------

if ( !function_exists( 'getWPTitleAlias' ) ) {
	function getWPTitleAlias()
	{
		static $slug;
		global $__sh_config;

		if ( $slug ) { return $slug; }
		
		$app =& JFactory::getApplication();
		
		$menu = $app->getMenu();
		if ( is_object( $menu ) ) {
			foreach ( $menu->getMenu() as $item ) {
				if ( $item->component == 'com_wordpress' ) {
					$slug = $item->alias;
					break;
				}
			}
		}

		if ( !$slug ) {
			$slug = 'blog';
		}

		if ( !$__sh_config->suffix ) {
			$slug = trim( $slug, '/' ) . '/';
		}

		return $slug;
	}
}

// So effing sad that I have to do this, because sh404 doesn't offer a way to get the
// config that is already cached on a variable. 

if (class_exists('shSEFConfig')){
	global $__sh_config;
	$__sh_config = new shSEFConfig();
} else {
	$__sh_config = shRouter::shGetConfig();
}

// Get the itemid for the main blog
if ( !isset( $Itemid ) || !$Itemid ) {
	$title[] = getWPTitleAlias();
} else {

	jimport( 'joomla.html.parameter' );

	$app =& JFactory::getApplication();
	$menu = $app->getMenu();
	$menu_item = $menu->getItem( $Itemid );

	$__params = new JParameter( $menu_item->params );

	$blog_path = $__params->get( 'blog_path' );

	if ( $blog_path ) {
		$_path = explode( '/', trim( $blog_path, '/' ) );
		foreach ( $_path as $_value ) {
			if ( !$_value ) {
				continue;
			}
			$title[] = $_value;
		}

		// Add a forward slash to the last value if no suffix
		if ( !$__sh_config->suffix ) {
			$__last = array_pop( $title );
			$title[] = $__last . '/';
		}
	} else {
		$title[] = getWPTitleAlias();
	}
}

unset( $__sh_config );

/* sh404SEF extension plugin : remove vars we have used, adjust as needed --*/
shRemoveFromGETVarsList( 'option' );
shRemoveFromGETVarsList( 'lang' );
if ( isset( $Itemid ) ) {
	shRemoveFromGETVarsList( 'Itemid' );
}
if ( isset( $task ) ) {
	shRemoveFromGETVarsList( 'task' );
}
if ( isset( $view ) ) {
	shRemoveFromGETVarsList( 'view' );
}
/* sh404SEF extension plugin : end of remove vars we have used -------------*/

// ------------------  standard plugin finalize function - don't change ---------------------------
if ($dosef){
  $string = shFinalizePlugin( $string, $title, $shAppendString, $shItemidString,
  (isset($limit) ? @$limit : null), (isset($limitstart) ? @$limitstart : null),
  (isset($shLangName) ? @$shLangName : null));
}
// ------------------  standard plugin finalize function - don't change ---------------------------

?>