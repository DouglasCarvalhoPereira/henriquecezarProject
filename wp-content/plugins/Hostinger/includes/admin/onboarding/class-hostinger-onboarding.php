<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hostinger_Onboarding {
	private function load_steps(): array {
		$steps = [];
		$path  = HOSTINGER_ABSPATH . 'includes/admin/onboarding/steps/';
		require_once $path . 'abstract-hostinger-onboarding-step.php';
		require_once $path . 'class-hostinger-onboarding-logo-step.php';
		require_once $path . 'class-hostinger-onboarding-image-step.php';
		require_once $path . 'class-hostinger-onboarding-heading.php';
		require_once $path . 'class-hostinger-onboarding-description.php';
		require_once $path . 'class-hostinger-onboarding-add-page.php';

		$steps[] = new Hostinger_Onboarding_Logo_Step();
		$steps[] = new Hostinger_Onboarding_Image_Step();
		$steps[] = new Hostinger_Onboarding_Heading();
		$steps[] = new Hostinger_Onboarding_Description();
		$steps[] = new Hostinger_Onboarding_Add_Page();

		return $steps;
	}

	public function get_steps(): array {
		return $this->load_steps();
	}

	public function maintenance_mode_enabled(): bool {
		$published = get_option( 'hostinger_maintenance_mode' );

		return (bool) $published;
	}


	public function get_content(): array {
		if ( !$this->maintenance_mode_enabled() ) {
			return [
				'title'       => __( 'Website is published', 'hostinger' ),
				'description' => __( 'You can access this guide material any time when updating your website', 'hostinger' ),
				'btn'         => [
					'text'  => __( 'Preview website', 'hostinger' ),
					'class' => 'hsr-btn hsr-primary-btn hsr-publish-btn',
					'url'   => home_url(),
				]
			];
		}

		return [
			'title'       => __( 'Set up your website', 'hostinger' ),
			'description' => __( 'Follow our guided checklist to setup your website', 'hostinger' ),
			'btn_text'    => __( 'Publish website', 'hostinger' ),
			'btn'         => [
				'text'  => __( 'Publish website', 'hostinger' ),
				'class' => 'hsr-btn hsr-primary-btn hsr-publish-btn',
				'url'   => '#',
			]
		];
	}
}
