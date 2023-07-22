<?php

defined( 'ABSPATH' ) || exit;

class Hostinger_Admin_Redirect {
	private string $platform;

	public function __construct() {
		if ( ! isset( $_GET['platform'] ) ) {
			return;
		}
		$this->platform = $_GET['platform'];
		$this->loginRedirect();
	}

	private function loginRedirect(): void {
		if ( ! get_option( 'hostinger_first_login_at', 0 ) ) {
			require_once HOSTINGER_ABSPATH . 'includes/admin/class-hostinger-first-login.php';

			return;
		}

		$maintenance_mode = get_option( 'hostinger_show_onboarding', 0 );
		$show_onboarding = get_option( 'hostinger_maintenance_mode', 0 );

		if ( $this->platform === 'hpanel' && $maintenance_mode && $show_onboarding ) {
			add_action( 'init', static function () {
				$redirect_url = admin_url( 'admin.php?page=hostinger' );
				wp_redirect( $redirect_url );
			} );
		}
	}
}

new Hostinger_Admin_Redirect();
