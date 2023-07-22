<?php

/**
 * Plugin Name: Hostinger
 * Plugin URI: https://www.hostinger.com
 * Description: Hostinger WordPress plugin.
 * Version: 1.1.0
 * Requires PHP: 7.4
 * Author: Hostinger
 * Author URI: https://www.hostinger.com
 * Text Domain:       hostinger
 * Domain Path:       /languages
 *
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'HOSTINGER_VERSION' ) ) {
	define( 'HOSTINGER_VERSION', '1.1.0' );
}

if ( ! defined( 'HOSTINGER_ABSPATH' ) ) {
	define( 'HOSTINGER_ABSPATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'HOSTINGER_PLUGIN_FILE' ) ) {
	define( 'HOSTINGER_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'HOSTINGER_PLUGIN_URL' ) ) {
	define( 'HOSTINGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

function activate_hostinger(): void {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hostinger-activator.php';
	Hostinger_Activator::activate();
}

function deactivate_hostinger(): void {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hostinger-deactivator.php';
	Hostinger_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_hostinger' );
register_deactivation_hook( __FILE__, 'deactivate_hostinger' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-hostinger.php';

$plugin = new Hostinger();
$plugin->run();
