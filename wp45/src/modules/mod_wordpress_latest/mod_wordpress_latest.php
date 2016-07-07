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

require_once( JPATH_ROOT . '/components/com_wordpress/wordpress_loader.php' );
wpj_loader::load();
?>
<div class="wp_mod">
<?php

// Add helper file
require_once( JPATH_ROOT . '/modules/mod_wordpress_latest/helper.php' );

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
$filter_categories = (array) $params->get( 'filter_categories', 0 );
$showCategories    = $params->get( 'showCategories', 1 );

// Add head styles
if ( $showAvatar ) {
	$document = JFactory::getDocument();
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

if (!function_exists('caption_shortcode')) {
	function caption_shortcode($attr, $content = null) {

		extract(shortcode_atts(array(
			'id'	=> '',
			'align'	=> 'alignnone',
			'width'	=> '80',
			'caption' => ''
		), $attr));

		if ( $id ) $idtag = 'id="' . esc_attr($id) . '" ';
		$align = 'class="' . esc_attr($align) . '" ';

		return '<figure ' . $idtag . $align . 'aria-describedby="figcaption_' . $id . '" style="width: ' . (10 + (int) $width) . 'px">'
		. do_shortcode( $content ) . '<figcaption id="figcaption_' . $id . '">' . $caption . '</figcaption></figure>';
	}
}
?>
<div id="wp-latest-wrapper">
<?php

	$r = new WP_Query(array('showposts' => $limit, 'nopaging' => 0, 'post_status' => 'publish', 'caller_get_posts' => 1, 'cat' => $filter_categories));

	if ( $r->have_posts() ) :
	while ( $r->have_posts() ) : $r->the_post();
	?>
	<div id="post-<?php the_ID(); ?>" class="post_entry module_post_entry">
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

			<?php
			if (  $show_post_meta ) { ?>
				<div class="wp-latest-date-readmore">
					<div class="entry-meta">
						<?php twentytwelve_posted_on(); ?>
					</div><!-- .entry-meta -->
				</div>
			<?php }

			/**
			 * Introtext
			 **/
			if ( $introMaxLength ) {
				$text = get_post();
				$text = $text->post_content;
				$text = apply_filters( 'get_the_content', $text );
				$text = str_replace(']]>', ']]&gt;', $text);

				// Lets build some caption data to be used later
				preg_match('/(?:.*)\[caption[^\]]*?\](\<a .*?\<\/a>)\s*(.*?)\[\/caption\]/', $text, $matches);
				list($all, $link, $caption) = $matches;

				/* Strip unwanted tags */
				$allowable_tags = '';
				// Allow to display images
				if ( $display_images ) {
					$allowable_tags = '<img><a>';
				}
				$text_img = strip_tags( $text, $allowable_tags );
				$text_clone = strip_tags( $text );

				$text = preg_replace( '#\s*<[^>]+>?\s*$#', '', $text_img );
				if( stripos( $text, "caption" ) !== false ) {
					$text = preg_replace( '[(\[caption)+.+(\[/caption\])]',
											'<div class="wp-caption alignnone" id="attachment_' . get_the_ID() . '">' . $link . '<p class="wp-caption-text">' . $caption .'</p></div>',
											strip_tags( $text, '<img><p><div>' ) );
				}

				if( !$resize_images ) {
					$pattern   = "/<img[^>]+src\\s*=\\s*['\"]([^'\"]+)['\"][^>]*>/";
					$matches   = '';
					$imageName = '';
					$imgsmall  = '';
					$imgbig    = '';
					$matches   = "";

					preg_match_all( $pattern, $text_img, $matches );

					// Remove all unessesary images
					for ( $i = $images_count; $i < count( $matches[0] ); $i++ ) {
						$text = str_replace( $matches[0][$i], '', $text );
						unset( $matches[0][$i] );
					}
				}



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
						$text = str_replace( $matches[0][$i], '<div class="wp-caption alignnone" id="attachment_' . get_the_ID() . '">' . $img . '</div>', $text );
					}
				}

				// Is text too long? Probably...

				$toolong = ( strlen( strip_tags( $text_clone ) ) > (int) $introMaxLength );

				// Trim text
				if ( $toolong ) {
					// Do a different type of replacement if there are html tags,
					// to avoid counting them
					if ( strpos( $text_clone, '>' ) ) {
						//$count = preg_match('/^(<[^>]*.*<\/[^>]*>)\s*(.*)/s', $text, $matches);
						$count = preg_match('/(<a[^>*?].*?>.*?<\/a>)(.*)/s', $text_clone, $matches);
						$text = substr( $matches[2], 0, ( $introMaxLength ) );
						$text = $matches[1] . $text_clone;
					} else {
						$text  = substr( $text_clone, 0, $introMaxLength );
					}
				}

				// Wrap text
				if ( $wrapIntroText ) {
					$text   = wordwrap( $text_clone, $wrapIntroText, '<br />' );
				}

				if ( $toolong ) {
					$text .= ' ...';
				}

				if( $matches[0][0] ) {
					echo $matches[0][0];
				}
				echo '<div class="wp-latest-introtext">'.$text.'</div>';

				// Only show readmore if the text is too long
				if ( $toolong && $showReadmore ) { ?>
					<span class="wp-latest-readmore">
						<a href="<?php the_permalink() ?>"><?php echo $readmoreText; ?></a>
					</span>
					<?php
				}
			}
			?>
	</div>

	<?php
	endwhile;
	endif;
	// Reset the global $the_post as this query will have stomped on it
	wp_reset_postdata();

if ( $showCategories ) {
	echo '<div id="wp-latest-cats">';
	echo wp_list_categories();
	echo '</div>';
}
echo '</div>';
?>
</div>
<?php
wpj_loader::unload();