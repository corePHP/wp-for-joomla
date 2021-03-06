<?php
/**
 * Network installation administration panel.
 *
 * A multi-step process allowing the user to enable a network of WordPress sites.
 *
 * @since 3.0.0
 *
 * @package WordPress
 * @subpackage Administration
 */

define( 'WP_INSTALLING_NETWORK', true );

/** WordPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'setup_network' ) ) {
	wp_die( __( 'Sorry, you are not allowed to manage options for this site.' ) );
}

if ( is_multisite() ) {
	if ( ! is_network_admin() ) {
		wp_redirect( network_admin_url( 'setup.php' ) );
		exit;
	}

	if ( ! defined( 'MULTISITE' ) ) {
		wp_die( __( 'The Network creation panel is not for WordPress MU networks.' ) );
	}
}

// rc_corephp changes : Network Setup failing 

//require_once __DIR__ . '/includes/network.php';

// We need to create references to ms global tables to enable Network.
foreach ( $wpdb->tables( 'ms_global' ) as $table => $prefixed_table ) {
	$wpdb->$table = $prefixed_table;
}


/** rc_corephp added function
 * Check for an existing network.
 *
 * @since 3.0.0
 * @return Whether a network exists.
 */
 
function network_domain_check() {
	global $wpdb;

	$sql = $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( $wpdb->site ) );
	if ( $wpdb->get_var( $sql ) ) {
		return $wpdb->get_var( "SELECT domain FROM $wpdb->site ORDER BY id ASC LIMIT 1" );
	}
	return false;
}

/** rc_corephp added function
 * Allow subdomain install
 *
 * @since 3.0.0
 * @return bool Whether subdomain install is allowed
 */
function allow_subdomain_install() {
	$domain = preg_replace( '|https?://([^/]+)|', '$1', get_option( 'home' ) );
	if( parse_url( get_option( 'home' ), PHP_URL_PATH ) || 'localhost' == $domain || preg_match( '|^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$|', $domain ) )
		return false;

	return true;
}

/** rc_corephp added function
 * Allow subdirectory install.
 *
 * @since 3.0.0
 * @return bool Whether subdirectory install is allowed
 */
function allow_subdirectory_install() {
	global $wpdb;
        /**
         * Filter whether to enable the subdirectory install feature in Multisite.
         *
         * @since 3.0.0
         *
         * @param bool true Whether to enable the subdirectory install feature in Multisite. Default is false.
         */
	if ( apply_filters( 'allow_subdirectory_install', false ) )
		return true;

	if ( defined( 'ALLOW_SUBDIRECTORY_INSTALL' ) && ALLOW_SUBDIRECTORY_INSTALL )
		return true;

	$post = $wpdb->get_row( "SELECT ID FROM $wpdb->posts WHERE post_date < DATE_SUB(NOW(), INTERVAL 1 MONTH) AND post_status = 'publish'" );
	if ( empty( $post ) )
		return true;

	return false;
}

/** rc_corephp added function
 * Get base domain of network.
 *
 * @since 3.0.0
 * @return string Base domain.
 */
function get_clean_basedomain() {
	if ( $existing_domain = network_domain_check() )
		return $existing_domain;
	$domain = preg_replace( '|https?://|', '', get_option( 'siteurl' ) );
	if ( $slash = strpos( $domain, '/' ) )
		$domain = substr( $domain, 0, $slash );
	return $domain;
}


if ( ! network_domain_check() && ( ! defined( 'WP_ALLOW_MULTISITE' ) || ! WP_ALLOW_MULTISITE ) ) {
	wp_die(
		printf(
			/* translators: 1: WP_ALLOW_MULTISITE, 2: wp-config.php */
			__( 'You must define the %1$s constant as true in your %2$s file to allow creation of a Network.' ),
			'<code>WP_ALLOW_MULTISITE</code>',
			'<code>wp-config.php</code>'
		)
	);
}

if ( is_network_admin() ) {
	$title       = __( 'Network Setup' );
	$parent_file = 'settings.php';
} else {
	$title       = __( 'Create a Network of WordPress Sites' );
	$parent_file = 'tools.php';
}

$network_help = '<p>' . __( 'This screen allows you to configure a network as having subdomains (<code>site1.example.com</code>) or subdirectories (<code>example.com/site1</code>). Subdomains require wildcard subdomains to be enabled in Apache and DNS records, if your host allows it.' ) . '</p>' .
	'<p>' . __( 'Choose subdomains or subdirectories; this can only be switched afterwards by reconfiguring your installation. Fill out the network details, and click Install. If this does not work, you may have to add a wildcard DNS record (for subdomains) or change to another setting in Permalinks (for subdirectories).' ) . '</p>' .
	'<p>' . __( 'The next screen for Network Setup will give you individually-generated lines of code to add to your wp-config.php and .htaccess files. Make sure the settings of your FTP client make files starting with a dot visible, so that you can find .htaccess; you may have to create this file if it really is not there. Make backup copies of those two files.' ) . '</p>' .
	'<p>' . __( 'Add the designated lines of code to wp-config.php (just before <code>/*...stop editing...*/</code>) and <code>.htaccess</code> (replacing the existing WordPress rules).' ) . '</p>' .
	'<p>' . __( 'Once you add this code and refresh your browser, multisite should be enabled. This screen, now in the Network Admin navigation menu, will keep an archive of the added code. You can toggle between Network Admin and Site Admin by clicking on the Network Admin or an individual site name under the My Sites dropdown in the Toolbar.' ) . '</p>' .
	'<p>' . __( 'The choice of subdirectory sites is disabled if this setup is more than a month old because of permalink problems with &#8220;/blog/&#8221; from the main site. This disabling will be addressed in a future version.' ) . '</p>' .
	'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
	'<p>' . __( '<a href="https://wordpress.org/support/article/create-a-network/">Documentation on Creating a Network</a>' ) . '</p>' .
	'<p>' . __( '<a href="https://wordpress.org/support/article/tools-network-screen/">Documentation on the Network Screen</a>' ) . '</p>';

get_current_screen()->add_help_tab(
	array(
		'id'      => 'network',
		'title'   => __( 'Network' ),
		'content' => $network_help,
	)
);

get_current_screen()->set_help_sidebar(
	'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
	'<p>' . __( '<a href="https://wordpress.org/support/article/create-a-network/">Documentation on Creating a Network</a>' ) . '</p>' .
	'<p>' . __( '<a href="https://wordpress.org/support/article/tools-network-screen/">Documentation on the Network Screen</a>' ) . '</p>' .
	'<p>' . __( '<a href="https://wordpress.org/support/">Support</a>' ) . '</p>'
);

require_once ABSPATH . 'wp-admin/admin-header.php';
?>
<div class="wrap">
<h1><?php echo esc_html( $title ); ?></h1>

<?php

/**
 * rc_corephp - Function will allow user to move WordPress content directory to a specified dir
 *
 * This is done before the installation of the multisites
 *
 * @return void
 **/
function network_step1_5()
{
	global $mainframe;

	jimport( 'joomla.filter.filteroutput' );
	jimport( 'joomla.filesystem.file' );
	jimport( 'joomla.filesystem.folder' );

	// Check here if they have a port on their URL
	$hostname = get_clean_basedomain();
	$has_ports = strstr( $hostname, ':' );
	if ( ( false !== $has_ports && ! in_array( $has_ports, array( ':80', ':443' ) ) ) ) {
		echo '<div class="error"><p><strong>' . __( 'Error:') . '</strong> ' . __( 'You cannot install a network of sites with your server address.' ) . '</p></div>';
		echo '<p>' . sprintf( __( 'You cannot use port numbers such as <code>%s</code>.' ), $has_ports ) . '</p>';
		echo '<a href="' . esc_url( admin_url() ) . '">' . __( 'Return to Dashboard' ) . '</a>';
		echo '</div>';
		include( './admin-footer.php' );
		die();
	}

	$wp_new_folder_name = trim( @$_POST['move_wp_to'] );
	$wp_dir = preg_replace('/\/{2,}|\\{2,}/', DS, get_site_option( 'jwp_wp_dir' ));

	// If we have not set the new wp_dir and if we are not trying to set it, then ask user
	if ( !$wp_dir && ( !isset( $_POST['move_wp_to'] ) || empty( $wp_new_folder_name ) ) ) {
		echo '<form method="post" action="">';

		wp_nonce_field( 'install-network-1' );

		echo '<p>Please enter a directory which you wish for WordPress multi-site to be installed to, choose this wisely:</p>';
		echo 'New directory: <input type="text" name="move_wp_to" />';
		echo '<br />Some example directories are: "blog, blogs, content, community."';
		echo '<br />Depending on the folder you pick all blogs will be prefixed with this URL.';
		echo '<br />For example if you create a blog named "sally" and your blog directory is "community", then the URL to the blog will be: ' . j_get_root_uri() . 'community/sally/';
		echo '<br /><br />If you have already moved the WordPress folder manually, still enter the new folder name here.';

		echo '<p class="submit"><input type="submit" value="Move folder" name="submit" class="button-primary"></p>';
		echo '</form>';
		return false;
	}

	$wp_cur_dir = str_replace( '/', DS, ABSPATH );
	if ( $wp_new_folder_name ) { // Make sure we are getting the correct wp_new_folder_name
		$wp_to_dir = JPATH_ROOT . DS . JFilterOutput::stringURLSafe( $wp_new_folder_name );
	} elseif ( $wp_dir ) {
		$wp_to_dir = $wp_dir;
	}

	// In the case that the new wp_dir already exists and is moved, lets set the site_option
	if ( !$wp_dir && is_dir( $wp_to_dir ) && is_file( $wp_to_dir . DS . 'wp-config.php' ) ) {
		$wp_dir = $wp_to_dir;
		update_site_option( 'jwp_wp_dir', $wp_to_dir );
	}

	// If we have not set the wp_dir then here is where we copy it over
	if ( !$wp_dir || !is_dir( $wp_dir ) || !is_file( $wp_dir . DS . 'wp-config.php' ) ) {

		$move = JFolder::copy( $wp_cur_dir, $wp_to_dir );
		if ( !$move || is_a( $move, 'JError' ) ) {
	        echo "There was an error copying this folder: {$wp_cur_dir}<br />
	        	To here: {$wp_to_dir}<br />Please move manually;";
			return false;
		}

		$wp_dir = $wp_to_dir;
		update_site_option( 'jwp_wp_dir', $wp_to_dir );
	}

	// Move new the everyhome template to its correct location
	if ( is_dir( $wp_dir .DS. 'wp-content' .DS. 'multisite' .DS. 'everyhome' ) ) {
		$from = $wp_dir .DS. 'wp-content' .DS. 'multisite' .DS. 'everyhome';
		$to   = $wp_dir .DS. 'wp-content' .DS. 'themes' .DS. 'everyhome';
		$move = JFolder::move( $from,	$to );
		if ( !$move || is_a( $move, 'JError' ) ) {
	        echo "There was an error moving the folder located here: {$from}<br />
	        	To here: {$to}<br />Please move manually;";
			return false;
		}

		// If move went well, delete multisite folder
		JFolder::delete( $wp_dir .DS. 'wp-content' .DS. 'multisite' );
	}

	// Move new index.php file for the new multisite directory
	if ( file_exists( $wp_dir .DS. 'ms-index.php' ) ) {
		$move = JFile::move( $wp_dir .DS. 'ms-index.php', $wp_dir .DS. 'index.php' );
		if ( !$move || is_a( $move, 'JError' ) ) {
	        echo "There was an error rename this file: {$wp_dir}/ms-index.php<br />
	        	To here: {$wp_dir}/index.php<br />Please rename manually;";
			return false;
		}
	}

	// Once everything is good to go, lets store the WordPress blog path to the db
	update_option( 'wpj_multisite_path', trim( str_replace( JPATH_ROOT, '', $wp_dir ), DS ) );

    // Set the template to everyhome - search for template and stylesheet and update value to everyhome
    // Update allowed themes to a:1:{s:9:"everyhome";i:1;}
    // Update the permalink structure for permalink_structure to /%postname%/
    update_option( 'template', 'everyhome' );
    update_option( 'stylesheet', 'everyhome' );
    update_option( 'themes', 'a:1:{s:9:"everyhome";i:1;}' );
    update_option( 'permalink_structure', '/%postname%/' );

    $clean_domain = trim( get_clean_basedomain(), DS );

    // Now lets insert some stuff in to the DB
    $site = new stdClass();
    $site->domain = $clean_domain;
    $site->path   = '/' . $wp_new_folder_name . '/';

    // Insert the object into the user profile table.
    $result = JFactory::getDbo()->insertObject('#__wp_site', $site);

    $blogs = new stdClass();
    $blogs->site_id      = 1;
    $blogs->domain       = $clean_domain;
    $blogs->path         = '/' . $wp_new_folder_name . '/';
    $blogs->registered   = current_time('mysql', true);
    $blogs->last_updated = current_time('mysql', true);

    // Insert the object into the user profile table.
    $result = JFactory::getDbo()->insertObject('#__wp_blogs', $blogs);

    // Now lets update files
    $config = JFile::read( $wp_dir . DS . 'wp-config.php' );

    $string = "/* That's all, stop editing! Happy blogging. */";
    $string .= "\r\n";
    $string .= 'define(\'MULTISITE\', true);';
    $string .= "\r\n";
    $string .= 'define(\'SUBDOMAIN_INSTALL\', false );';
    $string .= "\r\n";
    $string .= 'define(\'DOMAIN_CURRENT_SITE\', \'' . $clean_domain . '\');';
    $string .= "\r\n";
    $string .= "define('PATH_CURRENT_SITE', '/" . $wp_new_folder_name . "/' );";
    $string .= "\r\n";
    $string .= 'define(\'SITE_ID_CURRENT_SITE\', 1);';
    $string .= "\r\n";
    $string .= 'define(\'BLOG_ID_CURRENT_SITE\', 1);';

    $new_config = str_replace( "/* That's all, stop editing! Happy blogging. */", $string, $config );

    // Lets write our file now that we have replaced our strings
    JFile::write( $wp_dir . DS . 'wp-config.php', $new_config );

    $htaccess = 'RewriteEngine On';
    $htaccess .= "\r\n";
    $htaccess .= 'RewriteBase /' . $wp_new_folder_name . '/';
    $htaccess .= "\r\n";
    $htaccess .= 'RewriteRule ^index\.php$ - [L]';
    $htaccess .= "\r\n";
    $htaccess .= "\r\n";
    $htaccess .= '# add a trailing slash to /wp-admin';
    $htaccess .= "\r\n";
    $htaccess .= 'RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]';
    $htaccess .= "\r\n";
    $htaccess .= "\r\n";
    $htaccess .= 'RewriteCond %{REQUEST_FILENAME} -f [OR]';
    $htaccess .= "\r\n";
    $htaccess .= 'RewriteCond %{REQUEST_FILENAME} -d';
    $htaccess .= "\r\n";
    $htaccess .= 'RewriteRule ^ - [L]';
    $htaccess .= "\r\n";
    $htaccess .= 'RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]';
    $htaccess .= "\r\n";
    $htaccess .= 'RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ #2 [L]';
    $htaccess .= "\r\n";
    $htaccess .= 'RewriteRule . index.php [L]';

    // Lets write this file
    JFile::write( $wp_dir . DS . '.htaccess', $htaccess );

	if ( rtrim( $wp_cur_dir, DS ) != rtrim( $wp_dir, DS ) ) {
		$dir = str_replace( JPATH_ROOT, '', $wp_dir );
		$new_uri = j_get_root_uri() . trim( $dir, DS );

		$mainframe->redirect( $new_uri . '/wp-login.php?redirect_to='
			. urlencode( $new_uri . '/wp-admin/network.php' ) );
	} else {
		return true;
	}

	return false;
}
/** rc_corephp added function override function of wo-admin/includes/network.php
 * Prints step 1 for Network installation process.
 *
 * @todo Realistically, step 1 should be a welcome screen explaining what a Network is and such. Navigating to Tools > Network
 * 	should not be a sudden "Welcome to a new install process! Fill this out and click here." See also contextual help todo.
 *
 * @since 3.0.0
 */
function network_step1( $errors = false ) {
	global $is_apache;

	/* rc_corephp - Add check to move WP files to other directory in system */
	if ( !network_step1_5() ) {
		return;
	}

	if ( defined('DO_NOT_UPGRADE_GLOBAL_TABLES') ) {
		echo '<div class="error"><p><strong>' . __('ERROR:') . '</strong> ' . __( 'The constant DO_NOT_UPGRADE_GLOBAL_TABLES cannot be defined when creating a network.' ) . '</p></div>';
		echo '</div>';
		include( ABSPATH . 'wp-admin/admin-footer.php' );
		die();
	}

	$active_plugins = get_option( 'active_plugins' );
	if ( ! empty( $active_plugins ) ) {
		echo '<div class="updated"><p><strong>' . __('Warning:') . '</strong> ' . sprintf( __( 'Please <a href="%s">deactivate your plugins</a> before enabling the Network feature.' ), admin_url( 'plugins.php?plugin_status=active' ) ) . '</p></div><p>' . __( 'Once the network is created, you may reactivate your plugins.' ) . '</p>';
		echo '</div>';
		include( ABSPATH . 'wp-admin/admin-footer.php' );
		die();
	}

	$hostname = get_clean_basedomain();
	$has_ports = strstr( $hostname, ':' );
	if ( ( false !== $has_ports && ! in_array( $has_ports, array( ':80', ':443' ) ) ) ) {
		echo '<div class="error"><p><strong>' . __( 'ERROR:') . '</strong> ' . __( 'You cannot install a network of sites with your server address.' ) . '</p></div>';
		echo '<p>' . sprintf( __( 'You cannot use port numbers such as <code>%s</code>.' ), $has_ports ) . '</p>';
		echo '<a href="' . esc_url( admin_url() ) . '">' . __( 'Return to Dashboard' ) . '</a>';
		echo '</div>';
		include( ABSPATH . 'wp-admin/admin-footer.php' );
		die();
	}

	echo '<form method="post" action="">';

	wp_nonce_field( 'install-network-1' );

	$error_codes = array();
	if ( is_wp_error( $errors ) ) {
		echo '<div class="error"><p><strong>' . __( 'ERROR: The network could not be created.' ) . '</strong></p>';
		foreach ( $errors->get_error_messages() as $error )
			echo "<p>$error</p>";
		echo '</div>';
		$error_codes = $errors->get_error_codes();
	}

	$site_name = ( ! empty( $_POST['sitename'] ) && ! in_array( 'empty_sitename', $error_codes ) ) ? $_POST['sitename'] : sprintf( _x('%s Sites', 'Default network name' ), get_option( 'blogname' ) );
	$admin_email = ( ! empty( $_POST['email'] ) && ! in_array( 'invalid_email', $error_codes ) ) ? $_POST['email'] : get_option( 'admin_email' );
	?>
	<p><?php _e( 'Welcome to the Network installation process!' ); ?></p>
	<p><?php _e( 'Fill in the information below and you&#8217;ll be on your way to creating a network of WordPress sites. We will create configuration files in the next step.' ); ?></p>
	<?php

	if ( isset( $_POST['subdomain_install'] ) ) {
		$subdomain_install = (bool) $_POST['subdomain_install'];
	} elseif ( apache_mod_loaded('mod_rewrite') ) { // assume nothing
		$subdomain_install = true;
	} elseif ( !allow_subdirectory_install() ) {
		$subdomain_install = true;
	} else {
		$subdomain_install = false;
		if ( $got_mod_rewrite = got_mod_rewrite() ) // dangerous assumptions
			echo '<div class="updated inline"><p><strong>' . __( 'Note:' ) . '</strong> ' . __( 'Please make sure the Apache <code>mod_rewrite</code> module is installed as it will be used at the end of this installation.' ) . '</p>';
		elseif ( $is_apache )
			echo '<div class="error inline"><p><strong>' . __( 'Warning!' ) . '</strong> ' . __( 'It looks like the Apache <code>mod_rewrite</code> module is not installed.' ) . '</p>';
		/* rc_corephp - Commenting the following two lines, to avoid confusion for the users * /
		if ( $got_mod_rewrite || $is_apache ) // Protect against mod_rewrite mimicry (but ! Apache)
			echo '<p>' . __( 'If <code>mod_rewrite</code> is disabled, ask your administrator to enable that module, or look at the <a href="http://httpd.apache.org/docs/mod/mod_rewrite.html">Apache documentation</a> or <a href="http://www.google.com/search?q=apache+mod_rewrite">elsewhere</a> for help setting it up.' ) . '</p></div>';
		/* */
	}

	if ( allow_subdomain_install() && allow_subdirectory_install() ) : ?>
		<h3><?php esc_html_e( 'Addresses of Sites in your Network' ); ?></h3>
		<p><?php _e( 'Please choose whether you would like sites in your WordPress network to use sub-domains or sub-directories. <strong>You cannot change this later.</strong>' ); ?></p>
		<p><?php _e( 'You will need a wildcard DNS record if you are going to use the virtual host (sub-domain) functionality.' ); ?></p>
		<?php // @todo: Link to an MS readme? ?>
		<table class="form-table">
			<tr>
				<th><label><input type="radio" name="subdomain_install" value="1"<?php checked( $subdomain_install ); ?> /> <?php _e( 'Sub-domains' ); ?></label></th>
				<td><?php printf( _x( 'like <code>site1.%1$s</code> and <code>site2.%1$s</code>', 'subdomain examples' ), $hostname ); ?></td>
			</tr>
			<tr>
				<th><label><input type="radio" name="subdomain_install" value="0"<?php checked( ! $subdomain_install ); ?> /> <?php _e( 'Sub-directories' ); ?></label></th>
				<td><?php printf( _x( 'like <code>%1$s/site1</code> and <code>%1$s/site2</code>', 'subdirectory examples' ), $hostname ); ?></td>
			</tr>
		</table>

<?php
	endif;

		if ( WP_CONTENT_DIR != ABSPATH . 'wp-content' && ( allow_subdirectory_install() || ! allow_subdomain_install() ) )
			echo '<div class="error inline"><p><strong>' . __('Warning!') . '</strong> ' . __( 'Subdirectory networks may not be fully compatible with custom wp-content directories.' ) . '</p></div>';

		$is_www = ( 0 === strpos( $hostname, 'www.' ) );
		if ( $is_www ) :
		?>
		<h3><?php esc_html_e( 'Server Address' ); ?></h3>
		<p><?php /* rc_corephp - Changed verbiage */ printf( __( 'We recommend you install your network when you are visiting you website at this address <code>%1$s</code>, do this before enabling the network feature. It will still be possible to visit your site using the <code>www</code> prefix with an address like <code>%2$s</code> but any links will not have the <code>www</code> prefix. If you know what you are doing, then move forward. <a href="http://www.corephp.com/members/knowledgebase.php?_m=knowledgebase&_a=viewarticle&kbarticleid=22&nav=0,1" target="_blank">Read more on this</a>.' ), substr( $hostname, 4 ), $hostname ); ?></p>
		<table class="form-table">
			<tr>
				<th scope='row'><?php esc_html_e( 'Server Address' ); ?></th>
				<td>
					<?php printf( __( 'The internet address of your network will be <code>%s</code>.' ), $hostname ); ?>
				</td>
			</tr>
		</table>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Network Details' ); ?></h3>
		<table class="form-table">
		<?php if ( 'localhost' == $hostname ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Sub-directory Install' ); ?></th>
				<td><?php
					_e( 'Because you are using <code>localhost</code>, the sites in your WordPress network must use sub-directories. Consider using <code>localhost.localdomain</code> if you wish to use sub-domains.' );
					// Uh oh:
					if ( !allow_subdirectory_install() )
						echo ' <strong>' . __( 'Warning!' ) . ' ' . __( 'The main site in a sub-directory install will need to use a modified permalink structure, potentially breaking existing links.' ) . '</strong>';
				?></td>
			</tr>
		<?php elseif ( !allow_subdomain_install() ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Sub-directory Install' ); ?></th>
				<td><?php
					/* rc_corephp - Added this line for explenation, commented the other one out */
					_e( 'Because this integration of WordPress is inside of Joomla we only offer the sub-directory install. It is not possible to do subdomains.' );
					// _e( 'Because your install is in a directory, the sites in your WordPress network must use sub-directories.' );
					// Uh oh:
					if ( !allow_subdirectory_install() )
						echo ' <strong>' . __( 'Warning!' ) . ' ' . __( 'The main site in a sub-directory install will need to use a modified permalink structure, potentially breaking existing links.' ) . '</strong>';
				?></td>
			</tr>
		<?php elseif ( !allow_subdirectory_install() ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Sub-domain Install' ); ?></th>
				<td><?php _e( 'Because your install is not new, the sites in your WordPress network must use sub-domains.' );
					echo ' <strong>' . __( 'The main site in a sub-directory install will need to use a modified permalink structure, potentially breaking existing links.' ) . '</strong>';
				?></td>
			</tr>
		<?php endif; ?>
		<?php if ( ! $is_www ) : ?>
			<tr>
				<th scope='row'><?php esc_html_e( 'Server Address' ); ?></th>
				<td>
					<?php printf( __( 'The internet address of your network will be <code>%s</code>.' ), $hostname ); ?>
				</td>
			</tr>
		<?php endif; ?>
			<tr>
				<th scope='row'><?php esc_html_e( 'Network Title' ); ?></th>
				<td>
					<input name='sitename' type='text' size='45' value='<?php echo esc_attr( $site_name ); ?>' />
					<p class="description">
						<?php _e( 'What would you like to call your network?' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope='row'><?php esc_html_e( 'Network Admin Email' ); ?></th>
				<td>
					<input name='email' type='text' size='45' value='<?php echo esc_attr( $admin_email ); ?>' />
					<p class="description">
						<?php _e( 'Your email address.' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Install' ), 'primary', 'submit' ); ?>
	</form>
	<?php
}

<?php
if ( $_POST ) {

	check_admin_referer( 'install-network-1' );

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	// Create network tables.
	install_network();
	$base              = parse_url( trailingslashit( get_option( 'home' ) ), PHP_URL_PATH );
	$subdomain_install = allow_subdomain_install() ? ! empty( $_POST['subdomain_install'] ) : false;
	if ( ! network_domain_check() ) {
		$result = populate_network( 1, get_clean_basedomain(), sanitize_email( $_POST['email'] ), wp_unslash( $_POST['sitename'] ), $base, $subdomain_install );
		if ( is_wp_error( $result ) ) {
			if ( 1 === count( $result->get_error_codes() ) && 'no_wildcard_dns' === $result->get_error_code() ) {
				network_step2( $result );
			} else {
				network_step1( $result );
			}
		} else {
			network_step2();
		}
	} else {
		network_step2();
	}
} elseif ( is_multisite() || network_domain_check() ) {
	/* rc_corephp - Added the following lines to redirect user if they are re-enabling multisite */
	global $wpdb, $mainframe;
	$domain = network_domain_check();
	$path = $wpdb->get_var( "SELECT path FROM $wpdb->site ORDER BY id ASC LIMIT 1" );
	if ( $domain && $path && false === strpos( $_SERVER['REQUEST_URI'], $path ) ) {
		update_option( 'wpj_enable_multisite', 1 );
		update_option( 'wpj_multisite_path', trim(
			str_replace(
				str_replace( '/', DS, JURI::root(true) ),
				'',
				str_replace( '/', DS, $path )
			), DS ) );
		$wpdb->query("DELETE FROM $wpdb->sitemeta WHERE meta_key = 'wpj_enable_multisite'");


		$mainframe->redirect( "http://{$domain}{$path}wp-admin/" );
		die();
	}

	network_step2();
} else {
	network_step1();
}
?>
</div>

<?php require_once ABSPATH . 'wp-admin/admin-footer.php'; ?>
