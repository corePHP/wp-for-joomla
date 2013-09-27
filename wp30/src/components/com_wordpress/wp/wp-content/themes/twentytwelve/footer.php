<?php
/**
 * The template for displaying the footer.
 *
 * Contains footer content and the closing of the
 * #main and #page div elements.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */
?>
	</div><!-- #main .wrapper -->
	<footer id="colophon" role="contentinfo">
		<div class="site-info">
			<a href="<?php echo home_url( '/' ) ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home">
				<?php bloginfo( 'name' ); ?>
			</a>
		</div><!-- .site-info -->

		<div id="site-generator">
			<?php do_action( 'twentytwelve_credits' ); ?>
			Blog powered by <a href="<?php echo esc_url( __('http://wordpress.org/', 'twentyten') ); ?>"
					title="<?php esc_attr_e('Semantic Personal Publishing Platform', 'twentyten'); ?>" rel="generator">
				<?php printf( __('%s.', 'twentyten'), 'WordPress' ); ?>
			</a><br />
			<a href="http://www.corephp.com/joomla-products/wordpress-for-joomla.html" title="WordPress for Joomla! by 'corePHP'">Joomla! extension</a> by <a href="http://www.corephp.com/" title="Joomla! extension">'corePHP'</a>
		</div><!-- #site-generator -->
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>