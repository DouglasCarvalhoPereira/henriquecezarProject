<?php

defined( 'ABSPATH' ) || exit;

class Hostinger_Admin_Ajax {
	public function __construct() {
		add_action( 'init', [ $this, 'define_ajax_events' ], 0 );
	}

	public function define_ajax_events(): void {
		$events = [
			'complete_onboarding_step',
			'publish_website',
			'track_click',
		];

		foreach ( $events as $event ) {
			add_action( 'wp_ajax_hostinger_' . $event, [ __CLASS__, $event ] );
			add_action( 'wp_ajax_nopriv_hostinger_' . $event, [ __CLASS__, $event ] );
		}
	}

	public static function publish_website(): void {
		$publish = (bool) $_POST['maintenance'];
		update_option( 'hostinger_maintenance_mode', $publish ? 1 : 0 );

		require_once HOSTINGER_ABSPATH . 'includes/admin/onboarding/class-hostinger-onboarding.php';
		$content = new Hostinger_Onboarding();

		do_action( 'litespeed_purge_all' );

		wp_send_json_success( [
			'published'   => $publish,
			'title'       => __( 'Website is published', 'hostinger' ),
			'description' => __( 'Congratulations! Your website is online.', 'hostinger' ),
			'content'     => $content->get_content(),
			'preview_url' => home_url(),
		] );
	}

	public static function complete_onboarding_step(): void {
		$step            = $_POST['step'];
		$completed_steps = get_option( 'hostinger_onboarding_steps', [] );
		if ( ! in_array( $step, $completed_steps, true ) ) {
			$completed_steps[] = $step;
		}

		update_option( 'hostinger_onboarding_steps', $completed_steps );

		wp_send_json_success( [] );
	}

    public static function track_click(): void
    {
        $valid_options = [
            'hostinger_preview_button_click'
        ];

        $click_action = $_POST['click_action'];
        if (!in_array($click_action, $valid_options, true)) {
            wp_send_json_error(__('invalid data', 'hostinger'));
        }

        $click_count = get_option($click_action, 0);
        update_option($click_action, ++$click_count, 'no');

        wp_send_json_success([]);
    }
}

new Hostinger_Admin_Ajax();
