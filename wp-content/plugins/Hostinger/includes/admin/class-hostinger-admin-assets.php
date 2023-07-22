<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hostinger_Admin_Assets {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_styles' ] );
	}

	public function admin_styles(): void {
		$path = untrailingslashit( plugins_url( '/', HOSTINGER_PLUGIN_FILE ) );

		wp_register_style( 'hostinger_main_styles', $path . '/assets/css/main.css', [], '1.0.0' );
		wp_register_style( 'hostinger_admin_styles', $path . '/assets/css/admin.css', [], '1.0.0' );
		wp_enqueue_style( 'hostinger_admin_styles' );
		wp_enqueue_style( 'hostinger_main_styles' );
	}
}

new Hostinger_Admin_Assets();
