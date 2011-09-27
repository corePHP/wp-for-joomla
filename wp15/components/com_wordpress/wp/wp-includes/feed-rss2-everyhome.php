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

global $component_name, $current_user, $wpdb, $mainframe;

/**
 * RSS2 Feed Template for displaying RSS2 Posts feed.
 *
 * @package WordPress
 */

ob_get_clean();
header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
$more = 1;

preg_match( '#\/page\/([0-9]+)\/?#', $_SERVER['REQUEST_URI'], $matches );
if ( isset( $matches[1] ) ) {
	$_page = $matches[1];
} else {
	$_page = 1;
}
$start = $_page;
$_showauthor = 1;
// $_showauthor = get_site_option('showauthor_joomla');

$db    = & JFactory::getDBO();
$limit = $perpage = get_option( 'posts_per_page' );
$start = ( ( $start ) ? ( ($start - 1) * $perpage ) : 0 );
$blogs = get_last_updated('', 0, $limit);
$query = '';

//get posts from all blogs
foreach ( $blogs as $blog ) {
	$blogid = $blog['blog_id'];
	$path   = $blog['path'];
	$author = $wpdb->base_prefix . 'users';
	if ( 1 == $blog['blog_id'] && constant( 'WPJ_DB_PREFIX' ) == 'wp_' ) {
		$table   = $wpdb->base_prefix . 'posts';
		$options = $wpdb->base_prefix . 'options';
	} else {
		$table   = $wpdb->base_prefix . $blog['blog_id'] . '_posts';
		$options = $wpdb->base_prefix . $blog['blog_id'] . '_options';
	}

	$query .= "(
		SELECT '$blogid' AS blog_id, $author.display_name AS author, '$path' AS path, $table.*
			FROM $table 
			LEFT JOIN $author ON $author.ID =  $table.post_author
				WHERE 1=1 AND
					$table.post_type = 'post' AND 
					($table.post_status = 'publish') AND
					$table.post_password = ''
						ORDER BY $table.post_date DESC
		) UNION ";
}
$query	= substr( $query, 0, -6 );
$query	= "SELECT SQL_CALC_FOUND_ROWS * FROM ($query) AS data ORDER BY post_date DESC LIMIT $start, $limit";
$db->setQuery($query); 
$rows = $db->loadObjectList();

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	<?php do_action('rss2_ns'); ?>
>

<channel>
	<title><?php bloginfo_rss('name'); wp_title_rss(); ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss("description") ?></description>
	<lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
	<language><?php echo get_option('rss_language'); ?></language>
	<sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
	<sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
	<?php do_action('rss2_head'); ?>
	<?php
	global $id, $post, $more, $page, $pages, $multipage, $preview, $authordata;
	$page = 1;
	foreach ( (array) $rows as $post ) {
		switch_to_blog( $post->blog_id );

		$id = $post->ID;
		$authordata = get_userdata( $post->post_author );
		$pages[0] = $post->post_content;
		?>
	<item>
		<title><?php the_title_rss() ?></title>
		<link><?php the_permalink_rss() ?></link>
		<comments><?php comments_link_feed(); ?></comments>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
		<dc:creator><?php the_author() ?></dc:creator>
		<?php the_category_rss('rss2') ?>

		<guid isPermaLink="false"><?php the_guid(); ?></guid>
<?php if (get_option('rss_use_excerpt')) : ?>
		<description><![CDATA[<?php the_excerpt_rss() ?>]]></description>
<?php else : ?>
		<description><![CDATA[<?php the_excerpt_rss() ?>]]></description>
	<?php if ( strlen( $post->post_content ) > 0 ) : ?>
		<content:encoded><![CDATA[<?php the_content_feed('rss2') ?>]]></content:encoded>
	<?php else : ?>
		<content:encoded><![CDATA[<?php the_excerpt_rss() ?>]]></content:encoded>
	<?php endif; ?>
<?php endif; ?>
		<wfw:commentRss><?php echo esc_url( get_post_comments_feed_link(null, 'rss2') ); ?></wfw:commentRss>
		<slash:comments><?php echo get_comments_number(); ?></slash:comments>
<?php rss_enclosure(); ?>
	<?php do_action('rss2_item'); ?>
	</item>
	<?php }; ?>
</channel>
</rss>
