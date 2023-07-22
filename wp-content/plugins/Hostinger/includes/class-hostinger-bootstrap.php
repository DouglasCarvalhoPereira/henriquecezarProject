<?php

defined( 'ABSPATH' ) || exit;

class Hostinger_Bootstrap {
	protected Hostinger_Loader $loader;

	public function __construct() {
		require_once HOSTINGER_ABSPATH . 'includes/class-hostinger-loader.php';
		$this->loader = new Hostinger_Loader();
	}

	public function run(): void {
		$this->load_dependencies();
		$this->load_extendify();

		$this->loader->run();
	}

	private function load_dependencies(): void {
		if ( is_admin() ) {
			$this->load_admin_dependencies();
			$this->define_admin_hooks();
		}

		if ( get_option( 'hostinger_maintenance_mode', 0 ) ) {
			require_once HOSTINGER_ABSPATH . 'includes/class-hostinger-coming-soon.php';
		}
	}

	public function load_extendify(): void {
		require_once HOSTINGER_ABSPATH . 'includes/extendify/class-hostinger-extendify.php';
		$extendify = new Hostinger_Extendify();
		$extendify->init();
	}

	private function load_admin_dependencies(): void {
		require_once HOSTINGER_ABSPATH . 'includes/admin/class-hostinger-admin-assets.php';
		require_once HOSTINGER_ABSPATH . 'includes/admin/class-hostinger-admin-menu.php';
		require_once HOSTINGER_ABSPATH . 'includes/admin/class-hostinger-affiliates.php';
		require_once HOSTINGER_ABSPATH . 'includes/admin/class-hostinger-admin-ajax.php';
		require_once HOSTINGER_ABSPATH . 'includes/admin/class-hostinger-admin-redirect.php';
	}

	private function define_admin_hooks(): void {
		$hostinger_affiliate = new Hostinger_Affiliates();

		$this->loader->add_filter( 'astra_get_pro_url', $hostinger_affiliate, 'astra_pro_affiliate_link', 10, 2 );
		$this->loader->add_filter( 'optinmonster_sas_id', $hostinger_affiliate, 'affiliate_monsterinsights' );
		$this->loader->add_filter( 'monsterinsights_shareasale_id', $hostinger_affiliate, 'affiliate_monsterinsights' );
		$this->loader->add_filter( 'wpforms_upgrade_link', $hostinger_affiliate, 'wpforms_upgrade_link' );
		$this->loader->add_filter( 'aioseo_upgrade_link', $hostinger_affiliate, 'aioseo_upgrade_link' );
	}
}
