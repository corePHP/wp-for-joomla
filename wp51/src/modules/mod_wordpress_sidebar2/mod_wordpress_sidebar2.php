<?php
if ( ! (  defined( '_JEXEC' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }
/**
* @version $Id: 1  2008-12-11 21:35 rafael $
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
*
* Version 1.0
*
*/

require_once( JPATH_ROOT . '/components/com_wordpress/wordpress_loader.php' );
wpj_loader::load();
?>
<?php
	// A second sidebar for widgets, just because.
	if ( is_active_sidebar( 'joomla-secondary-widget-area' ) ) : ?>

		<div id="jsecondary" class="widget-area wp_mod" role="complementary">
			<ul class="xoxo">
				<?php dynamic_sidebar( 'joomla-secondary-widget-area' ); ?>
			</ul>
		</div><!-- #jsecondary .widget-area -->

<?php endif; ?>
<?php
wpj_loader::unload();
?>