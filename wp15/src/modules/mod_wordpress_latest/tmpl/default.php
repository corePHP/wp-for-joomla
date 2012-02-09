
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
						<?php twentyten_posted_on(); ?>
					</div><!-- .entry-meta -->
				</div>
			<?php }

			/**
			 * Introtext
			 **/
			if ( $introMaxLength ) {
				$text = get_the_content();

				/* Strip unwanted tags */
				$allowable_tags = '';
				// Allow to display images
				if ( $display_images ) {
					$allowable_tags = '<img><a>';
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