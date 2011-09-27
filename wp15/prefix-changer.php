<?php
/*
Plugin Name: WP table_prefix changer
Plugin URI: http://www.corephp.com
Description: This plugin will change your database table prefix to mitigate zero-day SQL Injection attacks.
Author: Philipp Heinze modified by Rafael Corral
Version: 1.1
Author URI: http://phsoftware.de
*/
/*Changelog:
*1.1 - Added Controling if the User has enough rights to alter the database structure
*       - Config is only changed when, all Table prefix where changed correct before
*       - Sanitized New Prefix correctly(only allowed Chars are used for the new Tableprefix)
*1.0 Beta - first release
*/
// This plugin is released under the GNU GPL License (http://www.gnu.org/copyleft/gpl.html)

add_action( 'admin_menu', 'prefix_changer_page' );

if ( !function_exists( 'prefix_changer_page' ) ) {
function prefix_changer_page() {
    add_submenu_page( 'plugins.php', 'WP Prefix Changer',
        'Prefix Changer', 10, __FILE__, 'wp_prefix_changer' );
}
}

if ( !function_exists( 'wp_prefix_changer' ) ) {
function wp_prefix_changer () {
?>
<div class='wrap'>
    <h2>WP Prefix Changer</h2>
	<p>This plugin will get your WordPress MU for Joomla database, ready for WordPress 3.0.</p>
    <form action='' method='post' name='prefixchanging'>
    <?php
    if ( function_exists( 'wp_nonce_field' ) ) {
		wp_nonce_field( 'prefix-changer-change_prefix' );
    }

	echo '<p>Please enter a directory where WordPress multi-site will be installed, you will get asked this again when you actually enable multisite, choose this wisely:</p>';
	echo 'New directory: <input type="text" name="path" />';
	echo '<br />Some example directories are: "blog, blogs, content, community."';
	echo '<br />Depending on the folder you pick all blogs will be prefixed with this URL.';
	echo '<br />For example if you create a blog named "sally" and your blog directory is "community", then the URL to the blog will be: ' . j_get_root_uri() . 'community/sally/';
    ?>
	<br />
    <input type='submit' name='renameprefix' value='Start Renaming'/>
    </form>

    <?php
    if ( !empty( $_POST ) ) {
        check_admin_referer( 'prefix-changer-change_prefix' );
		$wpdb           = & $GLOBALS['wpdb'];
		$JOOMLA_CONFIG  = new JConfig();
		$prefix_n       = $JOOMLA_CONFIG->dbprefix . 'wp_';
		$current_prefix = $JOOMLA_CONFIG->dbprefix . 'wpmu_';
		$newpref        = preg_replace( '/^[^0-9a-zA-Z_-]+$/', '', $prefix_n );

        if ( $newpref == $current_prefix ) {
            exit ( "No change: Please select a new table_prefix value.</div>" );
        } elseif ( strlen( $newpref ) < strlen( $prefix_n ) ) {
            echo ( "You used some Chars which aren't allowed within Tablenames" .
            "The sanitized prefix is used instead: " . $newpref);
        }

        echo '<h2>Started Prefix Changer:</h2>';

        // We rename the tables before we change the Config file, so We can aviod changed Configs,
        // without changed prefixes.
        echo '<h3>&nbsp;&nbsp;Start Renaming of Tables:</h3>';
        $oldtables = $wpdb->get_results( "SHOW TABLES LIKE '{$current_prefix}%'",
 			ARRAY_N ); // Retrieving all tables named with the prefix on start
        $table_c = count( $oldtables );
        $table_s = 0; // Holds the count of successful changed tables.
        $table_f[] = ''; // Holds all table names which failed to be changed
		$_blogs = array();
		$_table_replacements = array(
			$current_prefix . '1_commentmeta',
			$current_prefix . '1_comments',
			$current_prefix . '1_links',
			$current_prefix . '1_options',
			$current_prefix . '1_postmeta',
			$current_prefix . '1_posts',
			$current_prefix . '1_terms',
			$current_prefix . '1_term_relationships',
			$current_prefix . '1_term_taxonomy',
			$current_prefix . '1_usermeta',
			$current_prefix . '1_users'
		);

        for ( $i = 0; $i < $table_c; $i++ ) {//renaming each table to the new prefix
			preg_match( '/[0-9]/', $oldtables[$i][0], $matches );
			if ( isset( $matches[0] ) && is_int( (int) $matches[0] ) ) {
				$_blogs[] = $matches[0];
			}

            $wpdb->hide_errors();

			if ( in_array( $oldtables[$i][0], $_table_replacements ) ) {
				$table_n = str_replace( $current_prefix . '1_', $newpref, $oldtables[$i][0] );
			} else {
            	$table_n = str_replace( $current_prefix, $newpref, $oldtables[$i][0] );
			}

            echo "&nbsp;&nbsp;&nbsp;Renaming {$oldtables[$i][0]} to {$table_n}:";
            $table_r = $wpdb->query( "RENAME TABLE {$oldtables[$i][0]} TO {$table_n}");
            if ( $table_r === 0 ) {
                echo '<font color="#00ff00"> Success</font><br />';
                $table_s++;
            } elseif ( $table_r === FALSE ) {
                echo '<font color="#ff0000"> Failed</font><br />';
                $table_f[] = $oldtables[$i][0];
            }
        } // Changing some "hardcoded" wp values within the tables

        echo '<h3>&nbsp;&nbsp;Start changing Databasesettings:</h3>';

		$__uri = new JURI( j_get_root_uri() );
		$__path_clean = trim( $_POST['path'], '/' );
		$__path = trim( $__uri->getPath() . $__path_clean, '/' );
		$__db = JFactory::getDBO();
		$__query = "SELECT `blog_id`, `domain`, `path`
			FROM {$newpref}blogs";
		$__db->setQuery( $__query );
		$__all_blogs = $__db->loadObjectList( 'blog_id' );

		// Fix site path
		$__query = "UPDATE {$newpref}site
			SET `path` = '/{$__path}/'
				WHERE `id` = 1";
		$__db->setQuery( $__query );
		$__db->query();
		echo '&nbsp;&nbsp;&nbsp;Updating site path: <font color="#00ff00">Success</font><br />';

		// Fix site_url
		$__query = "UPDATE {$newpref}sitemeta
			SET `meta_value` = '" . j_get_root_uri() . $__path_clean . "/'
				WHERE `meta_key` = 'site_url'";
		$__db->setQuery( $__query );
		$__db->query();
		echo '&nbsp;&nbsp;&nbsp;Updating site_url: <font color="#00ff00">Success</font><br />';

		// Rename all blog paths
		foreach ( $__all_blogs as &$__blog ) {
			if ( 1 == $__blog->blog_id ) {
				$__temp_path = "/{$__path}/";
			} else {
				$__temp_path = "/{$__path}/" . ltrim( $__blog->path, '/' );
			}

			// Update blog path
			$__query = "UPDATE {$newpref}blogs
				SET `path` = '{$__temp_path}'
					WHERE `blog_id` = {$__blog->blog_id}";
			$__db->setQuery( $__query );
			$__db->query();
			echo '&nbsp;&nbsp;&nbsp;Updating blog path on table ' . $newpref . 'blogs for blog_id '
			 	. $__blog->blog_id . ': <font color="#00ff00">Success</font><br />';

			// Update siteurl and home
			$__blog_url = str_replace( $__uri->getPath(), '', j_get_root_uri() ) // Remove dupliate
				. $__temp_path;
			if ( 1 == $__blog->blog_id ) {
				$__table_name = "{$newpref}options";
			} else {
				$__table_name = "{$newpref}{$__blog->blog_id}_options";
			}
			$__query = "UPDATE {$__table_name}
				SET `option_value` = '{$__blog_url}'
					WHERE `option_name` = 'siteurl'
					OR `option_name` = 'home'";
			$__db->setQuery( $__query );
			$__db->query();
			echo '&nbsp;&nbsp;&nbsp;Updating siteurl and home URLs on table ' . $__table_name
				. ': <font color="#00ff00">Success</font><br />';
		}

		$_blogs = array_unique( $_blogs );
		foreach ( $_blogs as $_blog_id ) {
			if ( 1 == $_blog_id ) {
				$_table_name = $newpref;
			} else {
				$_table_name = $newpref.$_blog_id.'_';
			}
			$_blog_id .= '_';

			/* Update roles option table name */
			if ( $wpdb->query("UPDATE {$_table_name}options
				SET option_name = '{$_table_name}user_roles'
				WHERE option_name='{$current_prefix}{$_blog_id}user_roles' LIMIT 1" ) <> 1
			) {
				echo '&nbsp;&nbsp;&nbsp;Changing values in table ' . $_table_name
					. 'options: 1/1 <font color="#ff0000">Failed</font><br />';
			} else {
				echo '&nbsp;&nbsp;&nbsp;Changing values in table '
					. $current_prefix . $_blog_id
					. 'options 1/1: <font color="#00ff00">Success</font><br />';
			}

			/* Replace user settings on a perblog basis */
			if ( $wpdb->query( "UPDATE {$newpref}usermeta
				SET meta_key = '{$_table_name}capabilities'
					WHERE meta_key='{$current_prefix}{$_blog_id}capabilities'" ) <> 1
			) {
				echo '&nbsp;&nbsp;&nbsp;Changing values in table ' . $current_prefix
					. 'usermeta 1/5: <font color="#ff0000">Failed</font><br />';
			} else {
				echo '&nbsp;&nbsp;&nbsp;Changing values in table '
					. $current_prefix . 'usermeta 1/5: <font color="#00ff00">Success</font><br />';
			}

			/* Change the user_level column name to match new table name */
			if ( $wpdb->query( "UPDATE {$newpref}usermeta
				SET meta_key = '{$_table_name}user_level'
					WHERE meta_key = '{$current_prefix}{$_blog_id}user_level'" ) === FALSE
			) {
				echo '&nbsp;&nbsp;&nbsp;Changing values in table ' . $current_prefix
					. 'usermeta 2/5: <font color="#ff0000">Failed</font><br />';
			} else {
				echo '&nbsp;&nbsp;&nbsp;Changing values in table ' . $current_prefix
					. 'usermeta 2/5: <font color="#00ff00">Success</font><br />';
			}

			/* Misc */
			if ( $wpdb->query( "UPDATE {$newpref}usermeta
				SET meta_key='{$_table_name}autosave_draft_ids'
					WHERE meta_key = '{$current_prefix}{$_blog_id}autosave_draft_ids'" ) === 0
			) {
				echo '&nbsp;&nbsp;&nbsp;Changing values in table ' . $current_prefix
					. 'usermeta 3/5: <font color="#000000">Value doesn\'t exist</font><br />';
			} else {
				echo '&nbsp;&nbsp;&nbsp;Changing values in table ' . $current_prefix
					. 'usermeta 3/5: <font color="#00ff00">Success</font><br />';
			}

			/* Misc */
			if ( $wpdb->query( "UPDATE {$newpref}usermeta
				SET meta_key='{$_table_name}usersettings'
					WHERE meta_key='{$current_prefix}{$_blog_id}usersettings'" ) === 0
			) {
				echo '&nbsp;&nbsp;&nbsp;Changing values in table ' . $current_prefix
					. 'usermeta 4/5: <font color="#000000">Value doesn\'t exist</font><br />';
			} else {
				echo '&nbsp;&nbsp;&nbsp;Changing values in table ' . $current_prefix
					. 'usermeta 4/5: <font color="#00ff00">Success</font><br />';
			}

			/* Misc */
			if ( $wpdb->query( "UPDATE {$newpref}usermeta
				SET meta_key = '{$_table_name}usersettingstime'
					WHERE meta_key = '{$current_prefix}{$_blog_id}usersettingstime'" ) === 0
			) {
				echo '&nbsp;&nbsp;&nbsp;Changing values in table ' . $current_prefix
					. 'usermeta 5/5: <font color="#000000">Value doesn\'t exist</font><br />';
			} else {
				echo '&nbsp;&nbsp;&nbsp;Changing values in table ' . $current_prefix
					. 'usermeta 5/5: <font color="#00ff00">Success</font><br />';
			}
		}

		if ( $table_s == 0 ) {
			exit( '<font color="#ff0000">Some Error occured, it wasn\'t possible to change any '
			 	. 'Tableprefix.</font><br />');
		} elseif ( $table_s < $table_c ) {
			echo '<font color="#ff0000">It wasn\'t possible to rename some of your Tables prefix.'
				. ' Please change them manually. Following you\'ll see all failed tables:<br />';
			for ( $i = 1; $i < count( $tables_f ); $i++ ) {
				echo $tables_f[$i] . '<br />';
			}

			exit( 'No changes where done to your wp-config File.</font><br />' );
		}
	} // If prefix
?>
</div>
<?php
} // Function prefix_changer
}
?>