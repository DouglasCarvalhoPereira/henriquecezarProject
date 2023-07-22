<?php

class Hostinger_Onboarding_Description extends Hostinger_Onboarding_Step {
	public function get_title(): string {
		return __( 'Edit post description', 'hostinger' );
	}

	public function get_body(): array {
		return [
			[
				'title'       => __( 'Go to Pages', 'hostinger' ),
				'description' => __( 'In the left sidebar, find the Pages button. Click on the All Pages button and find the page for which you want to change the description.', 'hostinger' )
			],
			[
				'title'       => __( 'Edit page', 'hostinger' ),
				'description' => __( 'Hover over the chosen page to see the options menu. Click on the Edit button to open the page editor.', 'hostinger' ),
			],
			[
				'title'       => __( 'Edit description', 'hostinger' ),
				'description' => __( 'You can see the whole page in the editor. Find the description part and change it to your preferences.', 'hostinger' ),
			],
		];
	}

	public function step_identifier(): string {
		return 'edit_description';
	}

	public function get_redirect_link(): string {
		return admin_url( 'edit.php?post_type=page' );
	}
}
