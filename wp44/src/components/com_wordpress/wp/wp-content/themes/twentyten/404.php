<?php
/**
 * The template for displaying 404 pages (Not Found).
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

$modifydate = '';
if ( get_site_option( 'wpj_wrap_in_small', 0 ) ) {
	$modifydate = 'modifydate';
}
?>

		<div id="wp-container" class="<?php echo $template_company; ?>">
			<div id="<?php echo $template_wrapper; ?>">
				<div id="wp-content" class="inside" role="main">

					<div id="post-0" class="post error404 not-found">
						<div class="articleheading">
							<div class="article-rel-wrapper title_wrapper">
								<h1 class="entry-title contentheading"><span><?php _e( 'Not Found', 'twentyten' ); ?></span></h1>
							</div>
						</div>
						<div class="articledivider"></div>

						<div class="contentpaneopen clearfix">
					
							<div class="entry-content">
								<p><?php _e( 'Apologies, but the page you requested could not be found. Perhaps searching will help.', 'twentyten' ); ?></p>
								<?php get_search_form(); ?>
							</div><!-- .entry-content -->
						</div><!-- .contentpaneopen -->
				
						<div class="articlefooter"></div>
				
					</div><!-- #post-0 -->

				</div><!-- #content -->
			</div><!-- <?php echo $template_wrapper; ?> -->
		</div><!-- #container -->
	<script type="text/javascript">
		// focus on search field after it has loaded
		document.getElementById('s') && document.getElementById('s').focus();
	</script>

<?php get_footer(); ?>