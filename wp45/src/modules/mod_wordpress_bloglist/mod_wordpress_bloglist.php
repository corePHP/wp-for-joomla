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

require_once( JPATH_ROOT . '/components/com_wordpress/wordpress_loader.php' );
wpj_loader::load();

global $component_name, $current_user, $wpdb, $mainframe;

$limit          = $params->get( 'limit', 5 );
$show_all_blogs = $params->get( 'show_all_blogs', 0 );
$document       = JFactory::getDocument();

if ( $show_all_blogs ) {
	$blogs = $wpdb->get_results( $wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY blog_id ASC", $wpdb->siteid ) , ARRAY_A );
} else {
	$blogs = get_last_updated( '', 0, $limit );
}

if ( empty( $blogs ) ) {
	return;
}

$head = '
.updated_blog {
	margin: 5px 0;
}
';
$document->addStyleDeclaration( $head );
?>
<div class="wp_mod">
<ul>
<?php

foreach( (array) $blogs as $blog ) {
	$link  = get_blogaddress_by_id( $blog['blog_id'] );
	$title = get_blog_option( $blog['blog_id'], 'blogname' );
	?>
	<li class="updated_blog" id="blog-<?php echo $blog['blog_id']; ?>">
		<p class="wpmu_blog">
			<a href="<?php echo $link; ?>" rel="bookmark" title="<?php echo JText::_('WP_PERMANENT_LINK_TO'); ?> <?php echo $title; ?>"><?php echo $title; ?></a>
		</p>
	</li>
	<?php
}

echo '</ul>';
?>
</div>