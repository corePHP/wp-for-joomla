<?php
/**
 * The template for displaying Search Results pages.
 *
 * @package WordPress
 * @subpackage Twenty_Ten
 * @since Twenty Ten 1.0
 */

get_header(); $template_company = get_site_option( 'wpj_template_club', '' );

if( 'rockettheme' == $template_company ) {
	$template_wrapper	= 'maincontent-block';
} elseif( 'rockettheme_gantry_1' == $template_company ) {
	$template_wrapper	= 'page';
} elseif( 'joomlart' == $template_company ) {
	$template_wrapper	= 'ja-content';
} else {
	$template_wrapper	= 'wp-maincontent';
}?>

		<div id="wp-container" class="<?php echo $template_company; ?>">
			<div id="<?php echo $template_wrapper; ?>">
				<div id="wp-content" class="inside" role="main">

	<?php if ( have_posts() ) : ?>

					<h1 class="page-title contentheading"><?php printf( __( 'Search Results for: %s', 'twentyten' ), '<span>' . get_search_query() . '</span>' ); ?></h1>

						<?php
						/* Run the loop for the search to output the results.
						 * If you want to overload this in a child theme then include a file
						 * called loop-search.php and that will be used instead.
						 */
						 get_template_part( 'loop', 'search' );
						?>

	<?php else : ?>
					<div id="post-0" class="post no-results not-found">
						<div class="articleheading">
							<div class="article-rel-wrapper title_wrapper">
								<h2 class="entry-title contentheading"><span><?php _e( 'Nothing Found', 'twentyten' ); ?></span></h2>
							</div>
						</div>
						
						<div class="contentpaneopen clearfix">
							<div class="entry-content">
								<p><?php _e( 'Sorry, but nothing matched your search criteria. Please try again with some different keywords.', 'twentyten' ); ?></p>
								<?php get_search_form(); ?>
							</div><!-- .entry-content -->
						</div><!-- .contentpaneopen -->
						
						<div class="articlefooter"></div>
						
					</div><!-- #post-0 -->
	<?php endif; ?>
			
				</div><!-- #content -->
			</div><!-- #<?php echo $template_wrapper; ?> -->
		</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
