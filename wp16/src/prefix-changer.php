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

add_action("admin_menu", "prefix_changer_page");

if ( !function_exists( 'prefix_changer_page' ) ) {
function prefix_changer_page() {
    add_submenu_page("plugins.php", "WP Prefix Changer",
        "Prefix Changer", 10, __FILE__, "wp_prefix_changer");
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
    if (function_exists('wp_nonce_field')) {
        wp_nonce_field('prefix-changer-change_prefix');
    }
    ?>
    <input type='submit' name='renameprefix' value='Start Renaming'/>
    </form>

    <?php
    if ( !empty( $_POST ) ) {
        check_admin_referer('prefix-changer-change_prefix');
        $wpdb =& $GLOBALS['wpdb'];
		$JOOMLA_CONFIG = new JConfig();
		$_POST['prefix_n'] = $JOOMLA_CONFIG->dbprefix . 'wp_';
		$current_prefix = $JOOMLA_CONFIG->dbprefix . 'wpmu_';
        $newpref = ereg_replace("[^0-9a-zA-Z_-]", "", $_POST['prefix_n']);
        //checking if user has enough rights to alter the Tablestructure
        $rights = $wpdb->get_results("SHOW GRANTS FOR '".DB_USER."'@'".DB_HOST."'", ARRAY_N);

		if ( !empty( $rights ) ) {
    	    foreach ($rights as $right) {
	            if (ereg("ALTER(.*)(\*|`".str_replace("_", "\\_", DB_NAME)."`)\.(\*|`".DB_HOST."`) TO '".DB_USER."'@'".DB_HOST."'", $right[0]) || ereg("ALL PRIVILEGES ON (\*|`".str_replace("_", "\\_", DB_NAME)."`)\.(\*|`".DB_HOST."`) TO '".DB_USER."'@'".DB_HOST."'", $right[0])) {
	                $rightsenough = true;
	                $rightstomuch = true;
	                break;
	            } else {
	                if (ereg("ALTER", $right[0])) {
	                    $rightsenough = true;
	                    break;
	                }
	            }
	        }

	        if (!isset($rightsenough) && $rightsenough != true) {
	            exit('<font color="#ff0000">Your User which is used to access your Wordpress Tables/Database, hasn\'t enough rights( is missing ALTER-right) to alter your Tablestructure.<br />');
	        }
	        if (isset($rightstomuch) && $rightstomuch === true) {
	            echo ('<font color="#FF9B05">Your currently used User to Access the Wordpress Database, holds too many rights. '.
	                'We suggest that you limit his rights or to use another User with more limited rights instead, to increase your Security.</font><br />');
	        }
		}

        if ($newpref == $current_prefix) {
            exit ("No change: Please select a new table_prefix value.</div>");
        } elseif (strlen($newpref) < strlen($_POST['prefix_n'])){
            echo ("You used some Chars which aren't allowed within Tablenames".
            "The sanitized prefix is used instead: " . $newpref);
        }

        echo("<h2>Started Prefix Changer:</h2>");

        //we rename the tables before we change the Config file, so We can aviod changed Configs, without changed prefixes.
        echo("<h3>&nbsp;&nbsp;Start Renaming of Tables:</h3>");
        $oldtables = $wpdb->get_results("SHOW TABLES LIKE '".$current_prefix."%'", ARRAY_N);//retrieving all tables named with the prefix on start
        $table_c = count($oldtables);
        $table_s = 0;//holds the count of successful changed tables.
        $table_f[] = '';//holds all table names which failed to be changed
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

        for($i = 0; $i < $table_c; $i++) {//renaming each table to the new prefix
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

            echo "&nbsp;&nbsp;&nbsp;Renaming ".$oldtables[$i][0]." to $table_n:";
            $table_r = $wpdb->query("RENAME TABLE ".$oldtables[$i][0]." TO $table_n");
            if ($table_r === 0) {
                echo ('<font color="#00ff00"> Success</font><br />');
                $table_s++;
            } elseif ($table_r === FALSE) {
                echo ('<font color="#ff0000"> Failed</font><br />');
                $table_f[] = $oldtables[$i][0];
            }
        }//changing some "hardcoded" wp values within the tables

        echo ("<h3>&nbsp;&nbsp;Start changing Databasesettings:</h3>");

		$_blogs = array_unique( $_blogs );
		foreach ( $_blogs as $_blog_id ) {
			if ( 1 == $_blog_id ) {
				$_table_name = $newpref;
			} else {
				$_table_name = $newpref.$_blog_id.'_';
			}
			$_blog_id .= '_';

	        if ($wpdb->query("UPDATE ".$_table_name."options SET option_name='".$_table_name."user_roles' WHERE option_name='".$current_prefix.$_blog_id."user_roles' LIMIT 1") <> 1) {
	            echo ('&nbsp;&nbsp;&nbsp;Changing values in table '.$_table_name.'options: 1/1 <font color="#ff0000">Failed</font><br />');
	        } else {
	            echo ('&nbsp;&nbsp;&nbsp;Changing values in table '.$current_prefix.$_blog_id.'options 1/1: <font color="#00ff00">Success</font><br />');
	        }

			/* Replace user settings on a perblog basis */
	        if ($wpdb->query("UPDATE ".$newpref."usermeta SET meta_key='".$_table_name."capabilities' WHERE meta_key='".$current_prefix.$_blog_id."capabilities'") <> 1) {
	            echo ('&nbsp;&nbsp;&nbsp;Changing values in table '.$current_prefix.'usermeta 1/5: <font color="#ff0000">Failed</font><br />');
	        } else {
	            echo ('&nbsp;&nbsp;&nbsp;Changing values in table '.$current_prefix.'usermeta 1/5: <font color="#00ff00">Success</font><br />');
	        }

	        if ($wpdb->query("UPDATE ".$newpref."usermeta SET meta_key='".$_table_name."user_level' WHERE meta_key='".$current_prefix.$_blog_id."user_level'") === FALSE)
	        {
	            echo ('&nbsp;&nbsp;&nbsp;Changing values in table '.$current_prefix.'usermeta 2/5: <font color="#ff0000">Failed</font><br />');
	        } else {
	            echo ('&nbsp;&nbsp;&nbsp;Changing values in table '.$current_prefix.'usermeta 2/5: <font color="#00ff00">Success</font><br />');
	        }

	        if ($wpdb->query("UPDATE ".$newpref."usermeta SET meta_key='".$_table_name."autosave_draft_ids' WHERE meta_key='".$current_prefix.$_blog_id."autosave_draft_ids'") === 0) {
	            echo ('&nbsp;&nbsp;&nbsp;Changing values in table '.$current_prefix.'usermeta 3/5: <font color="#000000">Value doesn\'t exist</font><br />');
	        } else {
	            echo ('&nbsp;&nbsp;&nbsp;Changing values in table '.$current_prefix.'usermeta 3/5: <font color="#00ff00">Success</font><br />');
	        }

	        if ($wpdb->query("UPDATE ".$newpref."usermeta SET meta_key='".$_table_name."usersettings' WHERE meta_key='".$current_prefix.$_blog_id."usersettings'") === 0) {
	            echo ('&nbsp;&nbsp;&nbsp;Changing values in table '.$current_prefix.'usermeta 4/5: <font color="#000000">Value doesn\'t exist</font><br />');
	        } else {
	            echo ('&nbsp;&nbsp;&nbsp;Changing values in table '.$current_prefix.'usermeta 4/5: <font color="#00ff00">Success</font><br />');
	        }

	        if ($wpdb->query("UPDATE ".$newpref."usermeta SET meta_key='".$_table_name."usersettingstime' WHERE meta_key='".$current_prefix.$_blog_id."usersettingstime'") === 0) {
	            echo ('&nbsp;&nbsp;&nbsp;Changing values in table '.$current_prefix.'usermeta 5/5: <font color="#000000">Value doesn\'t exist</font><br />');
	        } else {
	            echo ('&nbsp;&nbsp;&nbsp;Changing values in table '.$current_prefix.'usermeta 5/5: <font color="#00ff00">Success</font><br />');
	        }
		}
        
        if ($table_s == 0) {
            exit('<font color="#ff0000">Some Error occured, it wasn\'t possible to change any Tableprefix.</font><br />');
        } elseif ($table_s < $table_c) {
            echo('<font color="#ff0000">It wasn\'t possible to rename some of your Tables prefix. Please change them manually. Following you\'ll see all failed tables:<br />');
            for ($i = 1; $i < count($tables_f); $i++) {
                echo ($tables_f[$i])."<br />";
            }
            exit('No changes where done to your wp-config File.</font><br />');
        }

        // echo("<h3>Changing Config File:</h3>");
        // $conf_f = "../wp-config.php";
        // 
        // @chmod($conf_f, 0777);//making the the config readable to change the prefix
        // if (!is_writeable($conf_f)) {//when automatic config file changing isn't possible the user get's all needed information to do it manually
        //     echo('&nbsp;&nbsp;1/1 file writeable: <font color="#ff0000">Not Writeable</font><br />');
        //     echo('<b>Please make your wp-config.php file writable for this process.</b>');
        //     die("</div>");
        // } else {//changing if possible the config file automatically
        //     echo('&nbsp;&nbsp;1/3 file writeable: <font color="#00ff00"> Writeable</font><br />');
        //     $handle = @fopen($conf_f, "r+");
        //     if ($handle) {
        //         while (!feof($handle)) {
        //             $lines[] = fgets($handle, 4096);
        //         }//while feof
        //         fclose($handle);
        //         $handle = @fopen($conf_f, "w+");
        //         foreach ($lines as $line) {
        //             if (strpos($line, $GLOBALS['table_prefix'])) {
        //                 $line = str_replace($GLOBALS['table_prefix'], $newpref, $line);
        //                 echo('&nbsp;&nbsp;2/3 <font color="#00ff00">table prefix changed!</font><br />');
        //             }//if strpos
        //             fwrite($handle, $line);
        //         }//foreach $lines
        //         fclose($handle);
        //         if (chmod ($conf_f, 0644)) {
        //             echo('&nbsp;&nbsp;3/3 <font color="#00ff00">Config files permission set to 644, for security purpose.</font><br />');
        //         } else {
        //             echo ('&nbsp;&nbsp;3/3 wasn\'t able to set chmod to 644, please check if your files permission is set back to 644!<br />');
        //         }//if chmod
        //     }//if handle
        // }//if is_writeable
        // 
    }//if prefix
    ?>
</div>
<?php
}//function prefix_changer
}
?>