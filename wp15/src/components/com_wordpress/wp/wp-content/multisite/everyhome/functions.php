<?php
if ( ! (  defined( '_JEXEC' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }
/**
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

/**
 * @package WordPress
 * @subpackage Everyhome_Theme
 */

function wp_widget_updated_blogs( $args )
{
	global $wpsefblog, $component_name;

	extract( $args );
	$counter = 0;
	$options = get_option('widget_blogs');
	$title	= empty($options['title']) ? __('Updated Blogs') : $options['title'];
	$blogs	= get_last_updated('', 0, 10);
	?>
		<?php echo $before_widget; ?>
			<?php echo $before_title . $title . $after_title; ?>
			<ul>
				<?php foreach( (array) $blogs as $blog ) {
				$link = esc_url( 'http://' . $blog['domain'] . $blog['path'] );
				$name = get_blog_option( $blog['blog_id'], 'blogname' ); ?>
				<li><a href="<?php echo $link; ?>"><?php echo $name; ?></a></li>
				<?php } ?>
			</ul>
		<?php echo $after_widget; ?>
<?php
	
}

function wp_widget_updated_blogs_control()
{
	$options = $newoptions = get_option( 'widget_blogs' );
	if ( isset( $_POST['blogs-submit'] ) ) {
		$newoptions['title'] = strip_tags( stripslashes( $_POST['blogs-title'] ) );
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option( 'widget_blogs', $options );
	}
	$title = attribute_escape( $options['title'] );
?>
		<p><label for="blogs-title"><?php _e('Title:'); ?> <input class="widefat" id="blogs-title" name="blogs-title" type="text" value="<?php echo $title; ?>" /></label></p>
		<input type="hidden" id="blogs-submit" name="blogs-submit" value="1" />
<?php
}

function mu_register_widgets()
{
	$widget_ops = array( 'classname' => 'widget_updated_blogs',
		'description' => __( 'A list of the last 10 updated blogs on your site') );
	wp_register_sidebar_widget( 'updated_blogs', __('Updated Blogs'), 'wp_widget_updated_blogs',
	 	$widget_ops );
	wp_register_widget_control( 'updated_blogs', __('Updated Blogs'),
		'wp_widget_updated_blogs_control' );
}
add_action( 'widgets_init', 'mu_register_widgets' );

function wpmu_get_pagination_home( $total = 0, $start = 0, $limit = 0 )
{
	// Create the pagination object
	$pagination = new JPagination( $total, $start, $limit );
	$current    = $pagination->get( 'pages.current' );
	$stop       = $pagination->get( 'pages.stop' );

	if ( $stop == 1 ) { return; }
	?>
	<div class="pagination">
	<span>&laquo;</span>
	<?php
	if ( $current != 1 ) {
	?>
	<a title="<?php echo JText::_('Start'); ?>" href="<?php echo get_pagenum_link(1); ?>"><?php echo JText::_('Start'); ?></a>
	<a title="<?php echo JText::_('Prev'); ?>" href="<?php echo get_pagenum_link($current-1); ?>"><?php echo JText::_('Prev'); ?></a>
	<?php
	}else{
	?>
		<span><?php echo JText::_('Start'); ?></span>
		<span><?php echo JText::_('Prev'); ?></span>
	<?php
	}
	for($i=1; $i<=intval($stop); $i++){
		if($i == $current)
			echo '<strong><span>'.$i.'</span></strong>';
		else
			echo '<strong><a href="'.get_pagenum_link($i).'" title="'.$i.'" >'.$i.'</a></strong>';
	}
	if($current != $stop){
	?>
	<a title="<?php echo JText::_('Next'); ?>" href="<?php echo get_pagenum_link($current+1); ?>"><?php echo JText::_('Next'); ?></a>
	<a title="<?php echo JText::_('End'); ?>" href="<?php echo get_pagenum_link($stop); ?>"><?php echo JText::_('End'); ?></a>
	<?php
	}else{
	?>
		<span><?php echo JText::_('Next'); ?></span>
		<span><?php echo JText::_('End'); ?></span>
	<?php } ?>
	<span>&raquo;</span>
	</div>
<?php
}

/**
 * Remove the sh404SEF cache from EveryHome Template
 */
function removeEveryHomeCache()
{
	$cache =& JCache::getInstance();
	$cache->setLifeTime( 3600 );
	$content = $cache->remove( 1, 'wpmu_everyhome' );

	return true;
}

// Include twentyten functions.php file
require_once( WP_CONTENT_DIR .DS.'themes'.DS.'twentyten'.DS.'functions.php' );
