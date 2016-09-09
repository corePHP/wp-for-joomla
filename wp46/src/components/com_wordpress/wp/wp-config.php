<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

global $JOOMLA_CONFIG;

// System configuration
$JOOMLA_CONFIG = new JConfig();

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', $JOOMLA_CONFIG->db);

/** MySQL database username */
define('DB_USER', $JOOMLA_CONFIG->user);

/** MySQL database password */
define('DB_PASSWORD', $JOOMLA_CONFIG->password);

/** MySQL hostname */
define('DB_HOST', $JOOMLA_CONFIG->host);

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '9,DpntYaaqJkr2/LMb.@8|/oz`-DYw.HCKr3W`cf94%Z?.WmQHPemwj_weF&yHJz');
define('SECURE_AUTH_KEY',  'bP)[9soq>[RW-E(!z-H:<h5(:qqkHfE;s.SUHaFdwt]}0[uvg0/v_@qJK W` T2&');
define('LOGGED_IN_KEY',    '0`+pT4zu8}gWZuJC,6x7zgYpum71u/`zS]F3Qp2*p#6=}v;D*G#e/]GG[<p=vOjQ');
define('NONCE_KEY',        'oj]H9E,GVC9rb,rEZKT.:s}+e6#i-FB^Lah84(b8U&yg6{Tb9[DbDD@AD4EfxId0');
define('AUTH_SALT',        'U:p[{1/Yu%~gVw$Z}<:9$gDtwWj<[(:>Sq3vhx3pJZj5p%q4*^+:Hz_{#{qrALy8');
define('SECURE_AUTH_SALT', '}f`7lOG2AxB_}-bFrY,HKPlK*B#4q/~MSY+U_dOkK@4_J4+D,cLo0;.l9v9q28~N');
define('LOGGED_IN_SALT',   'qdsp5y[nZgD[x?f#q)VOl_UOVS1cCNi]  w(/?wo!waq}Q[9;uu0aU!(xgDN@Y}[');
define('NONCE_SALT',       'A[@tzT:X|e]?`1E+g{NdWe;GnNG4i_R(:Nf9~0i#|FWh>aWnh]~9lmoB0^6[n;Ok');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
/* rc_corephp - This constant is used at a later point, do not delete */

define( 'WPJ_DB_PREFIX', 'wp_' );
$table_prefix  = $JOOMLA_CONFIG->dbprefix . constant( 'WPJ_DB_PREFIX' ) ;
// No auto updates
define( 'WP_AUTO_UPDATE_CORE', false );
define( 'WP_DEFAULT_THEME', 'twentytwelve' );
/* end rc_corephp */

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', ( $JOOMLA_CONFIG->debug ? true : false ) );

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
