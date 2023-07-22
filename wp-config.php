<?php
define( 'WP_CACHE', true );


/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'u746755343_mcrSC' );

/** Database username */
define( 'DB_USER', 'u746755343_yR7aE' );

/** Database password */
define( 'DB_PASSWORD', 'bVOcO7FQpq' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'WPt=N-#Qqrj2^[iB@E37YpH*g [cGLQg]^^/>^^K#e}RC&_,S*AHRp0~ql=/$NmJ' );
define( 'SECURE_AUTH_KEY',   'CfQ<T$We)7TR`tzX)u3,,s%Sh$r}H&3RTOl(!t&;|V})FrC!HEk/?@-b>w$mk:(B' );
define( 'LOGGED_IN_KEY',     '2!HFlH5I|`gN5-[/!=rh[fF?(E_w Ey(+~r-cIe 3z@kH>f&$<R{q_JEEZ$%CpVr' );
define( 'NONCE_KEY',         '#]XWS-OtoTf}k^7k1!<ErH~wLtM+@+a<?*_L-=Khl;5MNyD/rU8&0@4b-]VuljP9' );
define( 'AUTH_SALT',         '[T>tr+.f?Oj&kI<;/DRWWF M1CLpo<ji<|yOoc|(~=cVSInUj/*V GQXWR17hZ=n' );
define( 'SECURE_AUTH_SALT',  'iK!P[0U&c/hu[wn!BQ*)/jIPNA[Uk7${lV(2[f?u>xLf?j|/tnU>/8^iDOUVoQXw' );
define( 'LOGGED_IN_SALT',    'Hi~tn#]]_rcfy.,;|qPDVNhWT9|W5LOQ#QSp/g&o!m@_vT%7w=pqJtv>yl7j^J@.' );
define( 'NONCE_SALT',        'w{F+Gqg#ASi.QC;*1Qy<5HaQ&N;ml^ p|9O--UKz,{PS]Zm?u97{P*?Z4m`ry:B[' );
define( 'WP_CACHE_KEY_SALT', '{fa]wkphJKl40Qi>aIFu?X>,1`CdHo<=NP/qEw0a(gUH{ AgJ^idQ`dGpQ]J7uwb' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );


/* Add any custom values between this line and the "stop editing" line. */



define( 'FS_METHOD', 'direct' );
define( 'WP_AUTO_UPDATE_CORE', true );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';