<?php

defined( 'ABSPATH' ) || exit;

class Hostinger_First_Login {
	public const PLATFORM_HPANEL = 'hpanel';

	public function __construct() {
		$this->register_actions();
		update_option( 'hostinger_first_login_at', date( 'Y-m-d H:i:s' ) );
	}

	public function register_actions(): void {
		require_once HOSTINGER_ABSPATH . 'includes/admin/class-hostinger-admin-redirect.php';
		add_action( 'init', [ $this, 'redirect' ] );
	}

	public function redirect(): void {
		$platform = $_GET['platform'] ?: null;

		if ( $platform === self::PLATFORM_HPANEL && ! Hostinger_Extendify::extendifyFileExists() ) {
			$this->redirect_to_oldest_post();
		}
	}

	private function redirect_to_oldest_post(): void {
		$posts = get_posts( [
			'numberposts' => 1,
			'order'       => 'ASC'
		] );

		if ( isset( $posts[0] ) ) {
			$firstPost = $posts[0];

			$redirect_url = admin_url( 'post.php?post=' . $firstPost->ID . '&action=edit' );
			wp_redirect( $redirect_url );
			exit;
		}
	}
}

new Hostinger_First_Login();
