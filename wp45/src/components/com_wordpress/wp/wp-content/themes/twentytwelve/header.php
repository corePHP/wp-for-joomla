<?php
/**
 * The Header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="main">
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */

$mainframe = JFactory::getApplication();
$document  = JFactory::getDocument();
$menu      = $mainframe->getMenu()->getActive();

foreach ( $document->_links as $k => $array ) {
	if ( $array['relation'] == 'canonical' ) {
		unset($document->_links[$k]);
	}
}

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
<meta name="viewport" content="width=device-width" />
<?php if ( !class_exists('All_in_One_SEO_Pack') ) { ?>
<title><?php
	/*
	 * Print the <title> tag based on what is being viewed.
	 */
	global $page, $paged;

	wp_title( '|', true, 'right' );

	// Add the blog name.
	//bloginfo( 'name' );

	// Add the blog description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) )
		echo " | $site_description";

	// Add a page number if necessary:
	if ( $paged >= 2 || $page >= 2 )
		echo ' | ' . sprintf( __( 'Page %s', 'twentyten' ), max( $paged, $page ) );

	?></title>
<?php } ?>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<?php // Loads HTML5 JavaScript file to add support for HTML5 elements in older IE versions. ?>
<!--[if lt IE 9]>
<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
<![endif]-->
<?php
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
$_title_compare = $_title;

// Title override
if ( !empty( $title_override ) ) {
	if( is_home() || is_front_page() ) {
		$_title = $title_override;
	}
}

// Check to see if all in one is installed
if ( class_exists('All_in_One_SEO_Pack') ) {
	$_temp_title = $document->getTitle();
	if ( !$_temp_title ) {
		$document->setTitle( $_title );
	} else {
		$document->setTitle( str_replace( array( '&#8216;', '&#8217;', '&#8211;', '&#039;' ),
			array( '\'', '\'', ' ', "'" ), $_temp_title ) );
	}
} else {
	$document->setTitle( $_title );
}

$wp_head_contents = str_replace( $title_start . $_title_compare . $title_end, '', $wp_head_contents );

$document->addCustomTag( $wp_head_contents );
unset( $wp_head_contents );

//Add pathway a.k.a. breadcrumbs
$pathway	= $mainframe->getPathway();
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
?>

<div id="wp-page" class="hfeed site">
	<header id="masthead" class="site-header" role="banner">
		<hgroup>
			<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
			<h2 class="site-description"><?php bloginfo( 'description' ); ?></h2>
		</hgroup>

		<nav id="site-navigation" class="main-navigation" role="navigation">
			<h3 class="menu-toggle"><?php _e( 'Menu', 'twentytwelve' ); ?></h3>
			<a class="assistive-text" href="#content" title="<?php esc_attr_e( 'Skip to content', 'twentytwelve' ); ?>"><?php _e( 'Skip to content', 'twentytwelve' ); ?></a>
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'menu_class' => 'nav-menu' ) ); ?>
		</nav><!-- #site-navigation -->

		<?php if ( get_header_image() ) : ?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php header_image(); ?>" class="header-image" width="<?php echo get_custom_header()->width; ?>" height="<?php echo get_custom_header()->height; ?>" alt="" /></a>
		<?php endif; ?>
	</header><!-- #masthead -->

	<div id="main" class="wrapper">