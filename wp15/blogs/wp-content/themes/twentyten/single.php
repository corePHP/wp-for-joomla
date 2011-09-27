<?php
/**
 * The Template for displaying all single posts.
 *
 * @package WordPress
 * @subpackage Twenty_Ten
 * @since Twenty Ten 1.0
 */

$template_company = get_site_option( 'wpj_template_club', '' );

if( 'rockettheme' == $template_company ) {
	$template_wrapper	= 'maincontent-block';
} elseif( 'rockettheme_gantry_1' == $template_company ) {
	$template_wrapper	= 'page';
} elseif( 'joomlart' == $template_company ) {
	$template_wrapper	= 'ja-content';
} else {
	$template_wrapper	= 'wp-maincontent';
}

// Wrap Posted on ... by ... in small font wrapper. Needed for some templates (ie. JoomlaShack Cascada). Just change 0 to 1 below. Also in loop.php file.
$modifydate = '';
if ( get_site_option( 'wpj_wrap_in_small', 0 ) ) {
	$modifydate = 'modifydate';
}

get_header(); ?>

		<div id="wp-container" class="<?php echo $template_company; ?>">
			<div id="<?php echo $template_wrapper; ?>">
				<div id="wp-content" class="inside" role="main">

	<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

					<div id="nav-above" class="navigation">
						<div class="nav-previous"><?php previous_post_link( '%link', '<span class="meta-nav">' . _x( '&larr;', 'Previous post link', 'twentyten' ) . '</span> %title' ); ?></div>
						<div class="nav-next"><?php next_post_link( '%link', '%title <span class="meta-nav">' . _x( '&rarr;', 'Next post link', 'twentyten' ) . '</span>' ); ?></div>
					</div><!-- #nav-above -->

					<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<div class="articleheading">
							<div class="article-rel-wrapper title_wrapper">
								<h1 class="entry-title contentheading"><span><?php the_title(); ?></span></h1>
							</div>
						</div>
						<div class="articledivider"></div>
						
						<div class="contentpaneopen clearfix">
							<div class="article-info-surround <?php echo $modifydate; ?>">
								<div class="entry-meta article-tools article-info-surround2">
									<?php twentyten_posted_on(); ?>
								</div><!-- .entry-meta -->
							</div><!-- .article-info-surround -->

							<div class="entry-content">
								<?php the_content(); ?>
								<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>
							</div><!-- .entry-content -->

						<?php if ( get_the_author_meta( 'description' ) ) : // If a user has filled out their description, show a bio on their entries  ?>
											<div id="entry-author-info">
												<div id="author-avatar">
													<?php echo getSocialAvatar( get_the_author_meta( 'user_email' ), apply_filters( 'twentyten_author_bio_avatar_size', 60 ) ); ?>
												</div><!-- #author-avatar -->
												<div id="author-description">
													<h2><?php printf( esc_attr__( 'About %s', 'twentyten' ), get_the_author() ); ?></h2>
													<?php the_author_meta( 'description' ); ?>
													<div id="author-link">
														<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>">
															<?php printf( __( 'View all posts by %s <span class="meta-nav">&rarr;</span>', 'twentyten' ), get_the_author() ); ?>
														</a>
													</div><!-- #author-link	-->
												</div><!-- #author-description -->
											</div><!-- #entry-author-info -->
						<?php endif; ?>

						</div><!-- .contentpaneopen -->

						<div class="articlefooter"></div>

						<div class="entry-utility">
							<?php twentyten_posted_in(); ?>
							<?php edit_post_link( __( 'Edit', 'twentyten' ), '<span class="edit-link">', '</span>' ); ?>
						</div><!-- .entry-utility -->
					</div><!-- #post-## -->

					<div id="nav-below" class="navigation">
						<div class="nav-previous"><?php previous_post_link( '%link', '<span class="meta-nav">' . _x( '&larr;', 'Previous post link', 'twentyten' ) . '</span> %title' ); ?></div>
						<div class="nav-next"><?php next_post_link( '%link', '%title <span class="meta-nav">' . _x( '&rarr;', 'Next post link', 'twentyten' ) . '</span>' ); ?></div>
					</div><!-- #nav-below -->

					<?php comments_template( '', true ); ?>

	<?php endwhile; // end of the loop. ?>

				</div><!-- #content -->
			</div><!-- #<?php echo $template_wrapper; ?> -->
		</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
