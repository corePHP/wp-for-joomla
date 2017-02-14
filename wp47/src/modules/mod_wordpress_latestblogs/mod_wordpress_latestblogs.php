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
?>
<div class="wp_mod">
<?php

global $id, $post, $more, $page, $pages, $multipage, $preview, $authordata, $wpdb, $blog_id;

$titleMaxLength = $params->get( 'titleMaxLength', 20 );
$introMaxLength = $params->get( 'introMaxLength', 50 );
$wrapIntroText  = $params->get( 'wrapIntro', 0 );
$limit          = $params->get( 'numLatestEntries', 5 );
$show_post_meta = $params->get( 'show_post_meta', 1 );
$showAvatar     = $params->get( 'showAvatar',0 );
$showReadmore   = $params->get( 'showReadmore', 1 );
$readmoreText   = $params->get( 'readmoreText', 'Readmore...' );
$display_images = $params->get( 'display_images', array(0) );
$images_count   = $params->get( 'images_count', 1 );
$resize_images  = $params->get( 'resize_images', 1 );
$resize_width   = $params->get( 'resize_width', 80 );
$resize_height  = $params->get( 'resize_height', 80 );

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

$db            = & JFactory::getDBO();
$start_blog_id = $blog_id;
$counter       = 0;
$start         = 0;
$blogs         = get_last_updated( '', 0, $limit );
$query         = '';
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
$query = "SELECT * FROM ($query) AS data ORDER BY post_date DESC LIMIT $start, $limit";
$db->setQuery( $query );
$rows = $db->loadObjectList();

foreach ( (array) $rows as $post ) {
	switch_to_blog( $post->blog_id );

	$id = $post->ID;
	$authordata = get_userdata( $post->post_author );
	$pages[0] = $post->post_content;

	?>
	<div id="blog-<?php echo $post->blog_id; ?>-post-<?php the_ID(); ?>" class="post_entry module_post_entry">
		<?php
		if ( $showAvatar ) {
			echo getSocialAvatar( $post->post_author, 32 );
		}
		?>
		<h4 class="entry-title"><a href="<?php the_permalink() ?>" title="<?php echo esc_attr(get_the_title() ? get_the_title() : get_the_ID()); ?>"><?php
			if ( $title = get_the_title() ) {
				$titlelength = strlen( $title );

				if ( $titlelength > $titleMaxLength ) {
					$title = substr( $title, 0, $titleMaxLength );
				}

				echo $title;

				if ( $titlelength > $titleMaxLength ) {
					echo ' ...';
				}
			} else { the_ID(); } ?></a></h4>

		<?php if ( $show_post_meta ) { ?>
			<div class="wp-latest-date-readmore">
				<div class="entry-meta">
					<?php twentytwelve_posted_on(); ?>
				</div><!-- .entry-meta -->
			</div>
		<?php } ?>

		<?php
		if ( $introMaxLength ) {
			$text = $post->post_content;

			/* Strip unwanted tags */
			$allowable_tags = '';
			// Allow to display images
			if ( $display_images ) {
				$allowable_tags = '<img>';
			}
			$text = strip_tags( $text, $allowable_tags );
			$text = preg_replace( '#\s*<[^>]+>?\s*$#', '', $text );
			$text = preg_replace( '[(\[caption)+.+(\[/caption\])]', '', $text );

			if ( $display_images && $resize_images ) {
				$pattern   = "/<img[^>]+src\\s*=\\s*['\"]([^'\"]+)['\"][^>]*>/";
				$matches   = '';
				$imageName = '';
				$imgsmall  = '';
				$imgbig    = '';
				$matches   = "";

				preg_match_all( $pattern, $text, $matches );

				// Remove all unessesary images
				for ( $i = $images_count; $i < count( $matches[0] ); $i++ ) {
					$text = str_replace( $matches[0][$i], '', $text );
					unset( $matches[0][$i] );
				}

				// Replace new images
				for ( $i = 0;$i < count( $matches[0] ); $i++ ) {
					$patterns = array( '/width="([0-9]+)"/', '/height="([0-9]+)"/' );
					$replacements = array( "width=\"{$resize_width}\"",
					 	"height=\"{$resize_height}\"" );
					$img = preg_replace( $patterns, $replacements, $matches[0][$i] );
					$text = str_replace( $matches[0][$i], $img, $text );
				}
			}

			// Is text too long? Probably...
			$toolong = ( strlen( $text ) > $introMaxLength );

			// Trim text
			if ( $toolong ) {
		    	$text  = substr( $text, 0, $introMaxLength );
			}

			// Wrap text
			if ( $wrapIntroText ) {
				$text   = wordwrap( $text, $wrapIntroText, '<br />' );
			}

			if ( $toolong ) {
				$text .= ' ...';
			}

			echo '<div class="wp-latest-introtext entry-content entry">'.$text.'</div>';

			// Only show readmore if the text is too long
			if ( $toolong && $showReadmore ) { ?>
				<span class="wp-latest-readmore">
					<a href="<?php the_permalink() ?>"><?php echo $readmoreText; ?></a>
				</span>
				<?php
			}
		}
		?>

	</div><!-- #post-## -->

<?php
$counter++;
}

switch_to_blog( $start_blog_id );
?>
</div>
<?php
wpj_loader::unload();