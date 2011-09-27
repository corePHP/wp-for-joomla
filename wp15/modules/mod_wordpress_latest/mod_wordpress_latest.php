<?php
if ( ! (  defined( '_JEXEC' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }
/**
* @version $Id: 1  2008-11-16 21:49 rafael $
* @package WordPress Latest Post Module
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* WordPress is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* 
* For any support visit: http://www.corephp.com/wordpress/support
* 
* http://www.corephp.com
*/

global $id, $post, $more, $page, $pages, $multipage, $preview, $authordata, $wpdb, $blog_id;

require_once( JPATH_ROOT .DS. 'components' .DS. 'com_wordpress' .DS. 'wordpress_loader.php' );
wpj_loader::load();
?>
<div class="wp_mod">
<?php

// Add helper file
require_once( JPATH_ROOT . DS.'modules'.DS.'mod_wordpress_latest'.DS.'helper.php' );

$titleMaxLength    = $params->get( 'titleMaxLength', 20 );
$introMaxLength    = $params->get( 'introMaxLength', 50 );
$wrapIntroText     = $params->get( 'wrapIntro', 0 );
$limit             = $params->get( 'numLatestEntries', 5 );
$show_post_meta    = $params->get( 'show_post_meta', 1 );
$showAvatar        = $params->get( 'showAvatar',0 );
$showReadmore      = $params->get( 'showReadmore', 1 );
$readmoreText      = $params->get( 'readmoreText', 'Readmore...' );
$display_images    = $params->get( 'display_images', array(0) );
$images_count      = $params->get( 'images_count', 1 );
$resize_images     = $params->get( 'resize_images', 1 );
$resize_width      = $params->get( 'resize_width', 80 );
$resize_height     = $params->get( 'resize_height', 80 );
$blogs_blog_id     = $params->get( 'blog_id', 1 );
$filter_categories = (array) $params->get( 'filter_categories', 0 );
$showCategories    = $params->get( 'showCategories', 1 );

// Add head styles
if ( $showAvatar ) {
	$document = &JFactory::getDocument();
	$head = 'img.avatar {
		padding: 4px;
		margin: 0 0 2px 7px;
		display: inline;
		float: left;
		}
	.module_post_entry {
		display:block;
		clear:both;
	}';
	$document->addStyleDeclaration( $head );
}

if ( $filter_categories[0] != 0 ) {
	$filter_categories = implode( $filter_categories, ',' );
} else {
	$filter_categories = '';
}

if ( is_multisite() ) {
	if ( $blog_id != $blogs_blog_id ) {
		$old_blog_id = $blog_id;
		switch_to_blog( $blogs_blog_id );
	}
}

include JModuleHelper::getLayoutPath( 'mod_wordpress_latest' );

if ( isset( $old_blog_id ) ) {
	switch_to_blog( $old_blog_id );
}

wpj_loader::unload();

?>