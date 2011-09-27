<?php
/**
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
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
}
?>

		<div id="wp-container" class="<?php echo $template_company; ?>">
			<div id="<?php echo $template_wrapper; ?>">
				<div id="wp-content" class="inside" role="main">
					
<?php
	/* Queue the first post, that way we know
	 * what date we're dealing with (if that is the case).
	 *
	 * We reset this later so we can run the loop
	 * properly with a call to rewind_posts().
	 */
	if ( have_posts() )
		the_post();
?>
					
							<h2 class="page-title contentheading"><span>
		<?php if ( is_day() ) : ?>
						<?php printf( __( 'Daily Archives: <span>%s</span>', 'twentyten' ), get_the_date() ); ?>
		<?php elseif ( is_month() ) : ?>
						<?php printf( __( 'Monthly Archives: <span>%s</span>', 'twentyten' ), get_the_date('F Y') ); ?>
		<?php elseif ( is_year() ) : ?>
						<?php printf( __( 'Yearly Archives: <span>%s</span>', 'twentyten' ), get_the_date('Y') ); ?>
		<?php else : ?>
						<?php _e( 'Blog Archives', 'twentyten' ); ?>
		<?php endif; ?>
							</span></h2>
							<div class="articledivider"></div>
					
					
<?php
	/* Since we called the_post() above, we need to
	 * rewind the loop back to the beginning that way
	 * we can run the loop properly, in full.
	 */
	rewind_posts();

	/* Run the loop for the archives page to output the posts.
	 * If you want to overload this in a child theme then include a file
	 * called loop-archives.php and that will be used instead.
	 */
	 get_template_part( 'loop', 'archive' );
?>

				</div><!-- #content -->
			</div><!-- #<?php echo $template_wrapper; ?> -->
		</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
