<?php
/**
 * Multisite upgrade administration panel.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.0.0
 * rc_corephp
 */

require_once('admin.php');

if ( !is_multisite() )
	wp_die( __( 'Multisite support is not enabled.' ) );

require_once( ABSPATH . WPINC . '/http.php' );

$title = __( 'Update Network' );
$parent_file = 'ms-admin.php';

add_contextual_help($current_screen,
	'<p>This page will allow you to upgrade your WordPress Multisite. You must first install the latest version of WordPress through your Joomla installed. After doing so, the WordPress files are now stored on your server, but they still need to be moved to where your multisite is located. This page is here to help you automate this process.</p>' .
	'<p><a href="https://www.corephp.com/members/submitticket.php" target="_blank">Support</a></p>'
);

require_once('admin-header.php');

if ( ! current_user_can( 'joomla_manage_network' ) )
	wp_die( __( 'You do not have permission to access this page.' ) );


function wpj_update_ms_dirs( $srcdir, $dstdir, $verbose = true, $avoided = array() )
{
    $num = 0;

    if ( !is_dir( $dstdir ) ) {
        mkdir( $dstdir );
        chmod( $dstdir, 0755 );
    }

    if ( $curdir = @opendir( $srcdir ) ) {
        while ( $file = readdir( $curdir ) ) {
            if ( $file != '.' && $file != '..' && $file != '.svn' && $file != '.DS_Store' ) {
                $srcfile = $srcdir .DS. $file;
                $dstfile = $dstdir .DS. $file;

				if ( in_array( $srcfile, $avoided ) ) {
					continue;
				}

                if ( is_file( $srcfile ) ) {
                    // if ( is_file( $dstfile ) ) {
                    //     $ow = filemtime( $srcfile ) - filemtime( $dstfile );
                    // } else {
                    //     $ow = 1;
                    // }
					// Always replace all files
					$ow = 1;

                    if ( $ow > 0 ) {
                        if ( $verbose ) {
                            $tmpstr = 'Copying "%src%" to "%dst%".....';
                            $tmpstr = str_replace( '%src%', $srcfile, $tmpstr );
                            $tmpstr = str_replace( '%dst%', $dstfile, $tmpstr );
                            echo "\n" . '<li class="wp_mv_list">'
								. str_replace( JPATH_ROOT, '', $tmpstr );
                        }

                        if ( copy( $srcfile, $dstfile ) ) {
                            touch( $dstfile, filemtime( $srcfile ) );
                            $num++;

                            if ( $verbose ) {
                                echo 'OK'." </li>";
                            }
                        } else {
                            if ( $verbose ) {
                                echo ' </li>';
                            }
                            echo "<li class='copy_error'>Error copying file '$srcfile' could not be copied!</li>";
                        }
                    }
                } else if ( is_dir( $srcfile ) ) {
                    $num += wpj_update_ms_dirs( $srcfile, $dstfile, $verbose, $avoided );
                }
            }
        }

        closedir ( $curdir );
    }

    return $num;
}

echo '<style type="text/css">.copy_error{background-color: yellow;}</style>';
echo '<div class="wrap">';
screen_icon();
echo '<h2>' . __( 'Update WordPress' ) . '</h2>';

$action = isset($_GET['action']) ? $_GET['action'] : 'show';

switch ( $action ) {
	case "upgrade":
		$dst = rtrim( ABSPATH, '\\/' .DS );
		$src = JPATH_ROOT .DS. 'components' .DS. 'com_wordpress' .DS. 'wp';

		$avoid_files   = array( $src .DS. 'wp-config.php' );
		$avoid_folders = array();
		$verbose       = false;
		if ( !isset( $_GET['plugins'] ) || 'on' != $_GET['plugins'] ) {
			$avoid_folders[] = $src .DS. 'wp-content' .DS. 'plugins';
		}
		if ( !isset( $_GET['themes'] ) || 'on' != $_GET['themes'] ) {
			$avoid_folders[] = $src .DS. 'wp-content' .DS. 'themes';
			$avoid_folders[] = $src .DS. 'wp-content' .DS. 'multisite';
		}
		if ( isset( $_GET['verbose'] ) && 'on' == $_GET['verbose'] ) {
			$verbose = true;
		}

		echo '<ul>';
		wpj_update_ms_dirs( $src, $dst, $verbose, array_merge( $avoid_files, $avoid_folders ) );
		echo '</ul>';

		// Now move the ms-index.php file
		if ( is_file( $dst .DS. 'ms-index.php' ) ) {
			echo '<ul>';
			$srcfile = $dst .DS. 'ms-index.php';
			$dstfile = $dst .DS. 'index.php';
			if ( $verbose ) {
				$tmpstr = 'Copying "%src%" to "%dst%".....';
				$tmpstr = str_replace( '%src%', $srcfile, $tmpstr );
				$tmpstr = str_replace( '%dst%', $dstfile, $tmpstr );
				echo "\n" . '<li class="wp_mv_list">'
					. str_replace( JPATH_ROOT, '', $tmpstr );
			}

			if ( copy( $srcfile, $dstfile ) ) {
				touch( $dstfile, filemtime( $srcfile ) );
				$num++;
				unlink( $srcfile );

				if ( $verbose ) {
					echo 'OK'." </li>";
				}
			} else {
				echo "</li><li class='copy_error'>Error copying file '$srcfile' could not be copied!</li>";
			}
			echo '</ul>';
		}

		// Move over the multisite folder
		if ( isset( $_GET['themes'] ) && 'on' == $_GET['themes']
			&& is_dir( $dst .DS. 'wp-content' .DS. 'multisite' .DS. 'everyhome' )
			&& is_dir( $dst .DS. 'wp-content' .DS. 'themes' .DS. 'everyhome' )
		) {
			$src = $dst .DS. 'wp-content' .DS. 'multisite' .DS. 'everyhome';
			$dst = $dst .DS. 'wp-content' .DS. 'themes' .DS. 'everyhome';
			echo '<ul>';
			wpj_update_ms_dirs( $src, $dst, $verbose );
			echo '</ul>';

			// Delete Multisite folder
			jimport( 'joomla.filesystem.folder' );
			JFolder::delete( dirname( $src ) );
		}

		echo '<br /><strong>Update complete!</strong><br /><br />';
		echo 'Now you can run the <a href="ms-upgrade-network.php">WordPress Update</a> to automatically update the rest of your site.<br />';
	break;
	case 'show':
	default:
		?><p><?php echo 'Before using this page, you must have installed a WordPress update through Joomla. This page will automatically move the files from the component location in Joomla, to the directory that your Multisite reside on.'; ?></p>
		<p>
			<form action="ms-joomla-upgrade.php" method="get">
				<ul>
					<li><input type="checkbox" name="plugins" id="wpplugins" /> <label for="wpplugins">Replace Plugins</label></li>
					<li><input type="checkbox" name="themes" id="wpthemes" /> <label for="wpthemes">Replace Themes</label></li>
					<li><input type="checkbox" name="verbose" id="verbose" /> <label for="verbose">Verbose Mode - Display all files that are copied</label></li>
				</ul>

				<input type="hidden" name="action" value="upgrade" />
				<input type="submit" class="button" name="submit" value="<?php _e("Update WordPress"); ?>" />
			</form>
		</p>
		<p>For this update to go smoothly make sure that all of the files and folders on your system have the correct ownerships and permissions. If somethings goes wrong the first time, you are able to run it again.<?php
	break;
}
?>
</div>

<?php include('./admin-footer.php'); ?>
