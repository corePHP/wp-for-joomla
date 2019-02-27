<?php
if ( ! (  defined( '_JEXEC' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }
/**
* @version $Id: 1  2008-11-16 21:49 rafael $
* @package WordPress Categories Module
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

$title_show = $params->get('title_show', 1);
$title      = $params->get('title', 'Categories');
$order      = $params->get('order', 'ASC');

$args = array();

if($title_show)
	$args['title_li'] = $title;
else
	$args['title_li'] = false;

$args['order'] = $order;

?>
<div class="wp_mod">
<?php
echo '<div id="wp-latest-cats">';
echo "\r\n<ul>\r\n";
echo wp_list_categories($args);
echo "\r\n</ul>\r\n";
echo '</div>';
?>
</div>
<?php
wpj_loader::unload();
?>
