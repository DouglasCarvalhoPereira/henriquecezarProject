<?php

/**
 * Plugin Name: PayPal Brasil para WooCommerce
 * Description: Adicione facilmente opções de pagamento do PayPal à sua loja do WooCommerce.
 * Version: 1.4.5
 * Author: PayPal
 * Author URI: https://paypal.com.br
 * Requires at least: 4.4
 * Tested up to: 6.1
 * Text Domain: paypal-brasil-para-woocommerce
 * Domain Path: /languages
 * WC requires at least: 3.6
 * WC tested up to: 7.5
 * Requires PHP: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Init PayPal Payments.
 */
function paypal_brasil_init() {
	include_once dirname( __FILE__ ) . '/vendor/autoload.php';
	include_once dirname( __FILE__ ) . '/class-paypal-brasil.php';

	// Define files.
	define( 'PAYPAL_PAYMENTS_MAIN_FILE', __FILE__ );
	define( 'PAYPAL_PAYMENTS_VERSION', '1.4.0' );

	// Init plugin.
	PayPal_Brasil::get_instance();
}

function my_plugin_load_my_own_textdomain( $mofile, $domain ) {
	
	if ( "paypal-brasil-para-woocommerce" === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
		$locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
		$mofile = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/languages/' . $domain . '-' . $locale . '.mo';
	}
	return $mofile;
}



// Init plugin.
paypal_brasil_init();
add_filter( 'load_textdomain_mofile', 'my_plugin_load_my_own_textdomain', 10, 2 );