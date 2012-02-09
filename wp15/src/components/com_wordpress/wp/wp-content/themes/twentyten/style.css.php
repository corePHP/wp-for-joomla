<?php
    // header('Content-type: text/css');

	// Assign colors for items that can't inherit from main template
	
	// Typography parameters
	$site_title_size					= get_site_option( 'wpj_site_title_size', 24 ) . 'px';
	$title_color					    = get_site_option( 'wpj_title_color', '' );
	$title_link_color					= get_site_option( 'wpj_title_link_color', '' );
	$title_link_hover_color				= get_site_option( 'wpj_title_link_hover_color', '' );
	
	// Color choices
	$sticky_background					= get_site_option( 'wpj_sticky_background', '' );
	
	// Menu parameters
	$navigation_background				= get_site_option( 'wpj_navigation_background', '#ccc' );
	$navigation_hover_background		= get_site_option( 'wpj_navigation_hover_background', '#000' );
	$navigation_text_color				= get_site_option( 'wpj_navigation_text_color', '#000' );
	$navigation_current_background		= get_site_option( 'wpj_navigation_current_background', '#ccc' );
	$navigation_current_text_color		= get_site_option( 'wpj_navigation_current_text_color', '#000' );
	$sub_navigation_background			= get_site_option( 'wpj_sub_navigation_background', '#ccc' );
	$navigation_hover_text_color		= get_site_option( 'wpj_navigation_hover_text_color', '#fff' );
	$sub_navigation_text_color			= get_site_option( 'wpj_sub_navigation_text_color', '#000' );
	$sub_navigation_current_background	= get_site_option( 'wpj_sub_navigation_current_background', '#ccc' );
	$sub_navigation_current_text_color	= get_site_option( 'wpj_sub_navigation_current_text_color', '#000' );
	$sub_navigation_hover_background	= get_site_option( 'wpj_sub_navigation_hover_background', '#000' );
	$sub_navigation_hover_text_color	= get_site_option( 'wpj_sub_navigation_hover_text_color', '#fff' );
?>

/* @group Typography 
-------------------------------------------------------------- */

<?php if ($title_link_color) { ?>
.entry-title a {
	color: <?php echo $title_link_color; ?>;
}
<?php }
if ($title_link_hover_color) { ?>
.entry-title a:focus, .entry-title a:hover {
	color: <?php echo $title_link_hover_color; ?>;
}
<?php } 
if ($site_title_size) { ?>
#site-title span a {
	font-size: <?php echo $site_title_size; ?>;
}
<?php } ?>
<?php if ($title_color) { ?>
.title_wrapper .entry-title span {
	color: <?php echo $title_color; ?>!important;
}
<?php } ?>

/* @end */

/* @group Color Choices 
-------------------------------------------------------------- */
<?php if ($sticky_background) { ?>
	.home .sticky {background: <?php echo $sticky_background; ?>;}
<?php } ?>

/* @end */

/* @group Menu 
-------------------------------------------------------------- */
<?php if ($navigation_background) { ?>
#wp-access {background: <?php echo $navigation_background; ?>!important;}
<?php } ?>
#wp-access .menu-header,
div.wp-menu {}
#wp-access .menu-header ul,
div.wp-menu ul {}
#wp-access .menu-header li,
div.wp-menu li {}
<?php if ($navigation_text_color) { ?>
#wp-access a {color: <?php echo $navigation_text_color; ?>!important}
<?php } ?>
#wp-access li:hover > a,
#wp-access ul li.current_page_item > a:hover,
#wp-access ul li.current-menu-ancestor > a:hover,
#wp-access ul li.current-menu-item > a:hover,
#wp-access ul li.current-menu-parent > a:hover {
	<?php if ($navigation_hover_background) { ?>
	background:<?php echo $navigation_hover_background; ?>!important;
	<?php } if ($navigation_hover_text_color) { ?>
	color: <?php echo $navigation_hover_text_color; ?>!important;
	<?php } ?>
}
#wp-access ul li.current_page_item > a,
#wp-access ul li.current-menu-ancestor > a,
#wp-access ul li.current-menu-item > a,
#wp-access ul li.current-menu-parent > a {
	<?php if ($navigation_current_background) { ?>
	background: <?php echo $navigation_current_background; ?>!important;
	<?php } if ($navigation_current_text_color) { ?>
	color: <?php echo $navigation_current_text_color; ?>!important;
	<?php } ?>
}

#wp-access ul ul li.current_page_item > a,
#wp-access ul ul li.current-menu-ancestor > a,
#wp-access ul ul li.current-menu-item > a,
#wp-access ul ul li.current-menu-parent > a {
	<?php if ($sub_navigation_current_background) { ?>
	background: <?php echo $sub_navigation_current_background; ?>!important;
	<?php } if ($sub_navigation_current_text_color) { ?>
	color: <?php echo $sub_navigation_current_text_color; ?>!important;
	<?php } ?>
}
#wp-access ul ul a {
	<?php if ($sub_navigation_background) { ?>
	background:<?php echo $sub_navigation_background; ?>!important;
	<?php } if ($sub_navigation_text_color) { ?>
	color: <?php echo $sub_navigation_text_color; ?>!important;
	<?php } ?>
}
#wp-access ul ul :hover > a,
#wp-access ul ul li.current_page_item > a:hover,
#wp-access ul ul li.current-menu-ancestor > a:hover,
#wp-access ul ul li.current-menu-item > a:hover,
#wp-access ul ul li.current-menu-parent > a:hover {
	<?php if ($sub_navigation_hover_background) { ?>
	background:<?php echo $sub_navigation_hover_background; ?>!important;
	<?php } if ($sub_navigation_hover_text_color) { ?>
	color: <?php echo $sub_navigation_hover_text_color; ?>!important;
	<?php } ?>
}

* html #wp-access ul li.current_page_item a,
* html #wp-access ul li.current-menu-ancestor a,
* html #wp-access ul li.current-menu-item a,
* html #wp-access ul li.current-menu-parent a,
* html #wp-access ul li a:hover {
	<?php if ($sub_navigation_hover_text_color) { ?>
	color: <?php echo $sub_navigation_hover_text_color; ?>!important;
	<?php } ?>
}

/* @end */