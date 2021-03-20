<?php
if ( ! (  defined( '_JEXEC' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }
/**
* @version $Id: 1  2009-1-11 21:35 rafael $
* @package WordPress Recent Comments
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

if ( !function_exists('wp_widget_recent_comments2' ) ) {
	function wp_widget_recent_comments2( $args )
	{
		global $wpdb, $comments, $comment;

		extract($args, EXTR_SKIP);

		$options = get_option('widget_recent_comments');
		$title = empty($options['title']) ? __('Recent Comments') : apply_filters('widget_title', $options['title']);

		if ( !$number = (int) $options['number'] )
			$number = 5;
		else if ( $number < 1 )
			$number = 1;
		else if ( $number > 15 )
			$number = 15;

		if ( !$comments = wp_cache_get( 'recent_comments', 'widget' ) ) {
			$comments = get_comments( array( 'number' => $number, 'status' => 'approve' ) );
			wp_cache_add( 'recent_comments', $comments, 'widget' );
		}

	?>

	<?php  // echo $before_widget; ?>
		<ul id="recentcomments"><?php
		if ( $comments ) : foreach ($comments as $comment) :
		echo  '<li class="recentcomments">' . sprintf(__('%1$s on %2$s'), get_comment_author_link(), '<a href="'. get_permalink($comment->comment_post_ID) . '#comment-' . $comment->comment_ID . '">' . get_the_title($comment->comment_post_ID) . '</a>') . '</li>';
		endforeach; endif;?></ul>
	<?php // echo $after_widget; ?>
	<?php
	}
}

$number = $params->get( 'number', 5 );
?>
<div class="wp_mod">
<?php wp_widget_recent_comments2( array( 'number' => $number) ); ?>
</div>
<?php
wpj_loader::unload();