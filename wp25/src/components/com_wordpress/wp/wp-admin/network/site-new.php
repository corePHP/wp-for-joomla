<?php
/**
 * Add Site Administration Screen
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.1.0
 */

/** Load WordPress Administration Bootstrap */
require_once( './admin.php' );

if ( ! is_multisite() )
	wp_die( __( 'Multisite support is not enabled.' ) );

if ( ! current_user_can( 'manage_sites' ) )
	wp_die( __( 'You do not have sufficient permissions to add sites to this network.' ) );

	get_current_screen()->add_help_tab( array(
		'id'      => 'overview',
		'title'   => __('Overview'),
		'content' =>
			'<p>' . __('This screen is for Super Admins to add new sites to the network. This is not affected by the registration settings.') . '</p>' .
			'<p>' . __('If the admin email for the new site does not exist in the database, a new user will also be created.') . '</p>'
) );

get_current_screen()->set_help_sidebar(
	'<p><strong>' . __('For more information:') . '</strong></p>' .
	'<p>' . __('<a href="http://codex.wordpress.org/Network_Admin_Sites_Screen" target="_blank">Documentation on Site Management</a>') . '</p>' .
	'<p>' . __('<a href="http://wordpress.org/support/forum/multisite/" target="_blank">Support Forums</a>') . '</p>'
);

if ( isset($_REQUEST['action']) && 'add-site' == $_REQUEST['action'] ) {
	check_admin_referer( 'add-blog', '_wpnonce_add-blog' );

	if ( ! current_user_can( 'manage_sites' ) )
		wp_die( __( 'You do not have permission to access this page.' ) );

	if ( ! is_array( $_POST['blog'] ) )
		wp_die( __( 'Can&#8217;t create an empty site.' ) );
	$blog = $_POST['blog'];
	$domain = '';
	if ( preg_match( '|^([a-zA-Z0-9-])+$|', $blog['domain'] ) )
		$domain = strtolower( $blog['domain'] );

	// If not a subdomain install, make sure the domain isn't a reserved word
	if ( ! is_subdomain_install() ) {
		$subdirectory_reserved_names = apply_filters( 'subdirectory_reserved_names', array( 'page', 'comments', 'blog', 'files', 'feed' ) );
		if ( in_array( $domain, $subdirectory_reserved_names ) )
			wp_die( sprintf( __('The following words are reserved for use by WordPress functions and cannot be used as blog names: <code>%s</code>' ), implode( '</code>, <code>', $subdirectory_reserved_names ) ) );
	}

	/* rc_corephp - Commented out as we are using user_id and not email */
	// $email = sanitize_email( $blog['email'] );
	$title = $blog['title'];

	if ( empty( $domain ) )
		wp_die( __( 'Missing or invalid site address.' ) );
	/* rc_corephp - Commented out as we are using user_id and not email */
	// if ( empty( $email ) )
	// 	wp_die( __( 'Missing email address.' ) );
	// if ( !is_email( $email ) )
	// 	wp_die( __( 'Invalid email address.' ) );

	if ( is_subdomain_install() ) {
		$newdomain = $domain . '.' . preg_replace( '|^www\.|', '', $current_site->domain );
		$path      = $current_site->path;
	} else {
		$newdomain = $current_site->domain;
		$path      = $current_site->path . $domain . '/';
	}

	$password = 'N/A';
	/* rc_corephp - Commented out as we are using user_id and not email */
	// $user_id = email_exists($email);
	// if ( !$user_id ) { // Create a new user with a random password
	// 	$password = wp_generate_password();
	// 	$user_id = wpmu_create_user( $domain, $password, $email );
	// 	if ( false == $user_id )
	// 		wp_die( __( 'There was an error creating the user.' ) );
	// 	else
	// 		wp_new_user_notification( $user_id, $password );
	// }

	/* rc_corephp - Added function to get user_id or create wp user if it doesn't exist */
	$_user = get_userdata( $blog['user_id'] );
	if ( !isset( $_user->ID ) || !$_user->ID ) {
		$juser = JFactory::getUser( $blog['user_id'] );
		$user_id = j_create_wp_user( $juser );
	} else {
		$user_id = $_user->ID;
	}

	$wpdb->hide_errors();
	$id = wpmu_create_blog( $newdomain, $path, $title, $user_id , array( 'public' => 1 ), $current_site->id );
	$wpdb->show_errors();
	if ( !is_wp_error( $id ) ) {
		// rc_corephp - Edied if statement for the primary_blog update
		$_primary_blog = get_user_option( 'primary_blog', $user_id );
		if ( !is_super_admin( $user_id ) && ( ( 42 != $user_id && 1 == $_primary_blog ) || !$_primary_blog ) )
			update_user_option( $user_id, 'primary_blog', $id, true );
		$content_mail = sprintf( __( 'New site created by %1$s

Address: %2$s
Name: %3$s' ), $current_user->user_login , get_site_url( $id ), stripslashes( $title ) );
		wp_mail( get_site_option('admin_email'), sprintf( __( '[%s] New Site Created' ), $current_site->site_name ), $content_mail, 'From: "Site Admin" <' . get_site_option( 'admin_email' ) . '>' );
		wpmu_welcome_notification( $id, $user_id, $password, $title, array( 'public' => 1 ) );
		wp_redirect( add_query_arg( array( 'update' => 'added', 'id' => $id ), 'site-new.php' ) );
		exit;
	} else {
		wp_die( $id->get_error_message() );
	}
}

if ( isset($_GET['update']) ) {
	$messages = array();
	if ( 'added' == $_GET['update'] )
		$messages[] = sprintf( __( 'Site added. <a href="%1$s">Visit Dashboard</a> or <a href="%2$s">Edit Site</a>' ), esc_url( get_admin_url( absint( $_GET['id'] ) ) ), network_admin_url( 'site-info.php?id=' . absint( $_GET['id'] ) ) );
}

$title = __('Add New Site');
$parent_file = 'sites.php';

require('../admin-header.php');

?>

<div class="wrap">
<?php screen_icon('ms-admin'); ?>
<h2 id="add-new-site"><?php _e('Add New Site') ?></h2>
<?php
if ( ! empty( $messages ) ) {
	foreach ( $messages as $msg )
		echo '<div id="message" class="updated"><p>' . $msg . '</p></div>';
} ?>
<?php
/* rc_corephp - Replacing Admin Email field with a drop down list of all Joomla user */
// Get all users
$db    =& JFactory::getDBO();
$query = "SELECT id, name, username FROM `#__users` ORDER BY `id`";
$db->setQuery( $query );
$ulist = $db->loadObjectList();
$htmlulist = '';
foreach($ulist as $user){
	$htmlulist .= "<option value='{$user->id}'>(ID: {$user->id}) {$user->name} [{$user->username}]</option>\n";
}
?>
<form method="post" action="<?php echo network_admin_url('site-new.php?action=add-site'); ?>">
<?php wp_nonce_field( 'add-blog', '_wpnonce_add-blog' ) ?>
	<table class="form-table">
		<tr class="form-field form-required">
			<th scope="row"><?php _e( 'Site Address' ) ?></th>
			<td>
			<?php if ( is_subdomain_install() ) { ?>
				<input name="blog[domain]" type="text" class="regular-text" title="<?php esc_attr_e( 'Domain' ) ?>"/><span class="no-break">.<?php echo preg_replace( '|^www\.|', '', $current_site->domain ); ?></span>
			<?php } else {
				echo $current_site->domain . $current_site->path ?><input name="blog[domain]" class="regular-text" type="text" title="<?php esc_attr_e( 'Domain' ) ?>"/>
			<?php }
			echo '<p>' . __( 'Only lowercase letters (a-z) and numbers are allowed.' ) . '</p>';
			?>
			</td>
		</tr>
		<tr class="form-field form-required">
			<th scope="row"><?php _e( 'Site Title' ) ?></th>
			<td><input name="blog[title]" type="text" class="regular-text" title="<?php esc_attr_e( 'Title' ) ?>"/></td>
		</tr>
		<?php /* rc_corephp - Display users */ ?>
		<tr class="form-field form-required">
			<th scope="row"><?php _e('User') ?></th>
			<td><select name="blog[user_id]"><option value=""> - <?php _e('Select User'); ?> - </option><?php echo $htmlulist; ?></select></td>
		</tr>
		<?php /* rc_corephp - Comment old code out * / ?>
		<tr class="form-field form-required">
			<th scope="row"><?php _e( 'Admin Email' ) ?></th>
			<td><input name="blog[email]" type="text" class="regular-text" title="<?php esc_attr_e( 'Email' ) ?>"/></td>
		</tr>
		<?php /* */ ?>
		<tr class="form-field">
			<td colspan="2">Select the user to be the administrator of this blog. New users must be created through Joomla first.<?php /* rc_corephp - We don't need this * / _e( 'A new user will be created if the above email address is not in the database.' ) ?><br /><?php _e( 'The username and password will be mailed to this email address.' ) */ ?></td>
		</tr>
	</table>
	<?php submit_button( __('Add Site'), 'primary', 'add-site' ); ?>
	</form>
</div>
<?php
require('../admin-footer.php');
