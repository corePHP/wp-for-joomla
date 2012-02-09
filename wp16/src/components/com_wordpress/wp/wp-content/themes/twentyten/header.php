<?php
/**
 * The Header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="main">
 *
 * @package WordPress
 * @subpackage Twenty_Ten
 * @since Twenty Ten 1.0
 */

global $mainframe;

$document =& JFactory::getDocument();
$menu = $mainframe->getMenu()->getActive();

$title_override = '';

if ( !empty( $menu ) ) {
	if ( $menu->params->get( 'page_title' ) ) {
		$title_override = $menu->params->get( 'page_title' );
	}
}

// Add all of the head stuff to the head tag
ob_start();
?>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<title><?php
	/*
	 * Print the <title> tag based on what is being viewed.
	 */
	global $page, $paged;

	wp_title( '|', true, 'right' );

	// Add the blog name.
	bloginfo( 'name' );

	// Add the blog description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) )
		echo " | $site_description";

	// Add a page number if necessary:
	if ( $paged >= 2 || $page >= 2 )
		echo ' | ' . sprintf( __( 'Page %s', 'twentyten' ), max( $paged, $page ) );

	?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
<style type="text/css">
<?php require_once( dirname( __FILE__ ) .DS. 'style.css.php' ); ?>
</style>

<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<?php
	/* We add some JavaScript to pages with the comment form
	 * to support sites with threaded comments (when in use).
	 */
	if ( is_singular() && get_option( 'thread_comments' ) )
		wp_enqueue_script( 'comment-reply' );

	/* Always have wp_head() just before the closing </head>
	 * tag of your theme, or you will break many plugins, which
	 * generally use this hook to add elements to <head> such
	 * as styles, scripts, and meta tags.
	 */
	wp_head();

$wp_head_contents = ob_get_contents();
ob_end_clean();

// Get the title for the page
$title_start = '<title>';
$title_end = '</title>';
$_title = substr( $wp_head_contents,
	strpos( $wp_head_contents, $title_start ) + 7,
	strpos( $wp_head_contents, $title_end )
		- strpos( $wp_head_contents, $title_start ) - 7);
$_title = html_entity_decode( $_title, ENT_QUOTES, 'UTF-8' );

// Title override
if ( $title_override ) {
	$_title = $title_override;
}
$document->setTitle( $_title );

$wp_head_contents = str_replace( $title_start . $_title . $title_end, '', $wp_head_contents );
$document->addCustomTag( $wp_head_contents );
unset( $wp_head_contents );

//Add pathway a.k.a. breadcrumbs
$pathway	= &$mainframe->getPathway();
if(!is_home()){
	/* More specific * /
	if(is_tag())
		$pathway->addItem(__('Tag').': '.wp_title('', false, 'right'), '');
	elseif(is_category())
		$pathway->addItem(__('Category').': '.wp_title('', false, 'right'), '');
	elseif(is_date())
		$pathway->addItem(__('Archive for ').wp_title('', false, 'right'), '');
	else
	/* */
		$pathway->addItem(wp_title('', false, 'right'), '');
}

$j_tt_show_title_description = get_site_option( 'j_tt_show_title_description', 1 );
$j_tt_show_header_image      = get_site_option( 'j_tt_show_header_image', 1 );
$j_tt_show_blog_menu         = get_site_option( 'j_tt_show_blog_menu', 1 );
?>

<div id="wordpress" class="wordpress-content">
<div <?php body_class('wp-page'); ?>>
<div id="wp-wrapper" class="hfeed">
	<?php /* rc_corephp - Just a wrapper to make sure we are showing at least one */
	if ( $j_tt_show_title_description || $j_tt_show_header_image || $j_tt_show_blog_menu ) { ?>
	<div id="wp-header">
		<div id="wp-masthead">
			<?php /* rc_corephp - Just a wrapper to make sure we are showing at least one */
			if ( $j_tt_show_title_description || $j_tt_show_header_image ) { ?>
			<div id="wp-branding" role="banner">
				<?php $heading_tag = ( is_home() || is_front_page() ) ? 'h1' : 'div'; ?>
				<?php /* rc_corephp - This is one of the theme specific settings that we set up */
				if ( $j_tt_show_title_description ) { ?>
				<<?php echo $heading_tag; ?> id="site-title" class="componentheading">
					<span>
						<a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php
						if ( $menu->params->get( 'show_page_heading' ) ) {
							if ( $menu->params->get( 'page_heading' ) ) {
								echo $menu->params->get( 'page_heading' );
							} else {
								echo $menu->title;
							}
						} else {
							bloginfo( 'name' );
						}
						?></a>
					</span>
				</<?php echo $heading_tag; ?>>
				<div id="site-description"><?php bloginfo( 'description' ); ?></div>
				<?php } ?>

				<?php /* rc_corephp - This is one of the theme specific settings that we set up */
				if ( $j_tt_show_header_image ) { ?>
				<?php
					// Check if this is a post or page, if it has a thumbnail, and if it's a big one
					if ( is_singular() &&
							has_post_thumbnail( $post->ID ) &&
							( /* $src, $width, $height */ $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'post-thumbnail' ) ) &&
							$image[1] >= HEADER_IMAGE_WIDTH ) :
						// Houston, we have a new header image!
						echo get_the_post_thumbnail( $post->ID, 'post-thumbnail' );
					else : ?>
						<img src="<?php header_image(); ?>" width="<?php echo HEADER_IMAGE_WIDTH; ?>" height="<?php echo HEADER_IMAGE_HEIGHT; ?>" alt="" />
					<?php endif; ?>
				<?php } ?>
			</div><!-- #wp-branding -->
			<?php } ?>

			<?php /* rc_corephp - This is one of the theme specific settings that we set up */
			if ( $j_tt_show_blog_menu ) { ?>
			<div id="wp-access" role="navigation">
			  <?php /*  Allow screen readers / text browsers to skip the navigation menu and get right to the good stuff */ ?>
				<div class="skip-link screen-reader-text"><a href="#content" title="<?php esc_attr_e( 'Skip to content', 'twentyten' ); ?>"><?php _e( 'Skip to content', 'twentyten' ); ?></a></div>
				<?php /* Our navigation menu.  If one isn't filled out, wp_nav_menu falls back to wp_page_menu.  The menu assiged to the primary position is the one used.  If none is assigned, the menu with the lowest ID is used.  */ ?>
				<?php wp_nav_menu( array( 'container_class' => 'menu-header', 'menu_class' => 'wp-menu', 'theme_location' => 'primary' ) ); ?>
			</div><!-- #wp-access -->
			<?php } ?>
		</div><!-- #wp-masthead -->
	</div><!-- #wp-header -->
	<?php } ?>
<?php

/* rc_corephp */
// This determines if we are showing the sidebar or not, there is another conditional in sidebar.php
if ( get_site_option( 'wpj_use_wp_sidebar', 1 ) ) { ?>
	<div id="wp-main">
<?php } else { ?>
	<div id="wp-main" class="one-column">
<?php } ?>
