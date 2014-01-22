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

jimport( 'joomla.html.pagination' );

global $component_name, $current_user, $wpdb, $mainframe, $blog_id;

jimport( 'joomla.cache.cache' );
$cache = JCache::getInstance();
$cache->setLifeTime( $mainframe->getCfg( 'cachetime' ) * 60 );

/**
 * @package WordPress
 * @subpackage Home_Theme
 */
preg_match( '#\/page\/([0-9]+)\/?#', $_SERVER['REQUEST_URI'], $matches );
if ( isset( $matches[1] ) ) {
	$_page = $matches[1];
} else {
	$_page = 1;
}
$start = $_page;
// $_showauthor = get_option('showauthor_joomla');
$_showauthor = 1;

$template_company = get_site_option( 'wpj_template_club', '' );

if( 'rockettheme' == $template_company ) {
	$template_wrapper	= 'maincontent-block';
} elseif( 'joomlart' == $template_company ) {
	$template_wrapper	= 'ja-content';
} else {
	$template_wrapper	= 'wp-maincontent';
}

// Wrap Posted on ... by ... in small font wrapper. Needed for some templates (ie. JoomlaShack Cascada). Just change 0 to 1 below. Also in single.php.

$small = '';
$modifydate = '';
if ( get_site_option( 'wpj_wrap_in_small', 0 ) ) {
	$small      = 'small';
	$modifydate = 'modifydate';
}

get_header();
?>
<div id="wp-container">
	<div id="<?php echo $template_wrapper; ?>">
	<div id="primary" class="site-content" role="main">
	<?php
	if ( get_site_option( 'registration' ) == 'blog'
		&& $current_user->ID
		&& ( !$current_user->primary_blog || $current_user->primary_blog == 1 )
		&& $current_user->ID != 62
	) { ?>
	<a href="<?php echo site_url( 'wp-signup.php' ); ?>">
		<?php echo JText::_('WP_CREATE_NEW_BLOG'); ?>
	</a>
	<?php
	} elseif ( !$current_user->ID && get_site_option( 'registration' ) == 'blog' ) { ?>
	<p><?php echo JText::_( 'WP_HELLO_MSG_START_NEW_BLOG' ); ?> <a href="<?php
		echo JRoute::_( 'index.php?option=com_user&view=register' ); ?>"><?php
		echo JText::_( 'WP_HELLO_MSG_START_NEW_BLOG2' ); ?></a></p>
	<?php
	}

// Use Cache
$content = $cache->get( 1 + $_page, 'wpmu_everyhome' );
if ( empty( $content ) || !$mainframe->getCfg( 'caching' ) ) {
	ob_start();

	$db             = & JFactory::getDBO();
	$start_blog_id  = $blog_id;
	$counter        = 0;
	$showavatar     = get_option( 'avatar_default', 'mystery' );
	$date_format    = get_option( 'date_format' );
	$limit          = $perpage = get_option( 'posts_per_page' );
	$start          = ( ( $start ) ? ( ($start - 1) * $perpage ) : 0 );
	$blogs          = get_last_updated( '', 0, $limit );
	$more_link_text = JText::_( 'WP_READ_REST_ENTRY' ) . '&raquo;';
	$query          = '';
	extract( findCommunityComponent() );

	foreach ( (array) $blogs as $blog ) {
		$blogid		= $blog['blog_id'];
		$path		= $blog['path'];
		if ( 1 == $blog['blog_id'] && constant( 'WPJ_DB_PREFIX' ) == 'wp_' ) {
			$table   = $wpdb->base_prefix . 'posts';
			$options = $wpdb->base_prefix . 'options';
		} else {
			$table   = $wpdb->base_prefix . $blog['blog_id'] . '_posts';
			$options = $wpdb->base_prefix . $blog['blog_id'] . '_options';
		}
		
		$query .= "(
			SELECT '$blogid' AS blog_id, '$path' AS path, $table.ID, $table.post_author, $table.post_date, $table.post_date_gmt, $table.post_content, $table.post_title, $table.post_excerpt, $table.post_status, $table.comment_status, $table.ping_status, $table.post_password, $table.post_name, $table.to_ping, $table.pinged, $table.post_modified, $table.post_modified_gmt, $table.post_content_filtered, $table.post_parent, $table.guid, $table.menu_order, $table.post_type, $table.post_mime_type, $table.comment_count
				FROM $table
					WHERE 1=1 AND 
					$table.post_type = 'post' AND 
					($table.post_status = 'publish') AND
					$table.post_password = ''
						ORDER BY $table.post_date DESC
			) UNION ";
	}

	$query = substr( $query, 0, -6 );
	$query = "SELECT SQL_CALC_FOUND_ROWS *
		FROM ($query) AS data
			ORDER BY post_date DESC
				LIMIT $start, $limit";
	$db->setQuery($query);
	$rows = $db->loadObjectList();
	$db->setQuery( 'SELECT FOUND_ROWS()' );
	$total = $db->loadResult();

	global $id, $post, $more, $page, $pages, $multipage, $preview, $authordata;
	$page = 1;
	foreach ( (array) $rows as $post ) {
		switch_to_blog( $post->blog_id );

		$id = $post->ID;
		$authordata = get_userdata( $post->post_author );
		$pages[0] = $post->post_content;

		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<?php if ( is_sticky() && is_home() && ! is_paged() ) : ?>
		<div class="featured-post">
			<?php _e( 'Featured post', 'twentytwelve' ); ?>
		</div>
		<?php endif; ?>
		<header class="entry-header">
			<?php the_post_thumbnail(); ?>
			<?php if ( is_single() ) : ?>
			<h1 class="entry-title"><?php the_title(); ?></h1>
			<?php else : ?>
			<h1 class="entry-title">
				<a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
			</h1>
			<?php endif; // is_single() ?>
			<?php if ( comments_open() ) : ?>
				<div class="comments-link">
					<?php comments_popup_link( '<span class="leave-reply">' . __( 'Leave a reply', 'twentytwelve' ) . '</span>', __( '1 Reply', 'twentytwelve' ), __( '% Replies', 'twentytwelve' ) ); ?>
				</div><!-- .comments-link -->
			<?php endif; // comments_open() ?>
		</header><!-- .entry-header -->

		<?php if ( is_search() ) : // Only display Excerpts for Search ?>
		<div class="entry-summary">
			<?php the_excerpt(); ?>
		</div><!-- .entry-summary -->
		<?php else : ?>
		<div class="entry-content">
			<?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'twentytwelve' ) ); ?>
			<?php wp_link_pages( array( 'before' => '<div class="page-links">' . __( 'Pages:', 'twentytwelve' ), 'after' => '</div>' ) ); ?>
		</div><!-- .entry-content -->
		<?php endif; ?>

		<footer class="entry-meta">
			<?php twentytwelve_entry_meta(); ?>
			<?php edit_post_link( __( 'Edit', 'twentytwelve' ), '<span class="edit-link">', '</span>' ); ?>
			<?php if ( is_singular() && get_the_author_meta( 'description' ) && is_multi_author() ) : // If a user has filled out their description and this is a multi-author blog, show a bio on their entries. ?>
				<div class="author-info">
					<div class="author-avatar">
						<?php
						/** This filter is documented in author.php */
						$author_bio_avatar_size = apply_filters( 'twentytwelve_author_bio_avatar_size', 68 );
						echo get_avatar( get_the_author_meta( 'user_email' ), $author_bio_avatar_size );
						?>
					</div><!-- .author-avatar -->
					<div class="author-description">
						<h2><?php printf( __( 'About %s', 'twentytwelve' ), get_the_author() ); ?></h2>
						<p><?php the_author_meta( 'description' ); ?></p>
						<div class="author-link">
							<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" rel="author">
								<?php printf( __( 'View all posts by %s <span class="meta-nav">&rarr;</span>', 'twentytwelve' ), get_the_author() ); ?>
							</a>
						</div><!-- .author-link	-->
					</div><!-- .author-description -->
				</div><!-- .author-info -->
			<?php endif; ?>
		</footer><!-- .entry-meta -->
	</article><!-- #post -->

		<?php comments_template( '', true ); ?>
	<?php
	$counter++;
	}

	switch_to_blog( $start_blog_id );
	wpmu_get_pagination_home( $total, $start, $limit );

	$content = ob_get_contents();
	ob_end_clean();
	$cache->store( $content, 1 + $_page, 'wpmu_everyhome' );
}
echo $content;
?>
	</div><!-- #content -->
	</div><!-- #ja-content -->
</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
