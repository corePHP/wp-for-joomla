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
$cache =& JCache::getInstance();
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
	<div id="wp-content" class="inside" role="main">
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
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<?php // Display community avatar
			if ( $showavatar != 'blank' ) {
				echo getSocialAvatar( $post->post_author, 56 );
			} ?>

			<div class="articleheading">
				<div class="title_wrapper">
					<h2 class="entry-title contentheading"><span><a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'twentyten' ), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php the_title(); ?></a></span></h2>
				</div>
			</div>

			<div class="contentpaneopen">
				<div class="article-info-surround <?php echo $modifydate;?>">
					<div class="entry-meta article-tools article-info-surround2">
						<p class="articleinfo <?php echo $small;?>">
							<?php twentyten_posted_on(); ?>
						</p>
					</div><!-- .entry-meta -->
				</div><!-- .article-info-surround -->

				<div class="entry-content">
					<?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'twentyten' ) ); ?>
					<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>
				</div><!-- .entry-content -->
			</div>

			<div class="articlefooter"></div>
			<div class="entry-utility">
				<?php if ( count( get_the_category() ) ) : ?>
					<span class="cat-links">
						<?php printf( __( '<span class="%1$s">Posted in</span> %2$s', 'twentyten' ), 'entry-utility-prep entry-utility-prep-cat-links', get_the_category_list( ', ' ) ); ?>
					</span>
					<span class="meta-sep">|</span>
				<?php endif; ?>
				<?php
					$tags_list = get_the_tag_list( '', ', ' );
					if ( $tags_list ):
				?>
					<span class="tag-links">
						<?php printf( __( '<span class="%1$s">Tagged</span> %2$s', 'twentyten' ), 'entry-utility-prep entry-utility-prep-tag-links', $tags_list ); ?>
					</span>
					<span class="meta-sep">|</span>
				<?php endif; ?>
				<span class="comments-link"><?php comments_popup_link( __( 'Leave a comment', 'twentyten' ), __( '1 Comment', 'twentyten' ), __( '% Comments', 'twentyten' ) ); ?></span>
				<?php edit_post_link( __( 'Edit', 'twentyten' ), '<span class="meta-sep">|</span> <span class="edit-link">', '</span>' ); ?>
			</div><!-- .entry-utility -->
		</div><!-- #post-## -->

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
