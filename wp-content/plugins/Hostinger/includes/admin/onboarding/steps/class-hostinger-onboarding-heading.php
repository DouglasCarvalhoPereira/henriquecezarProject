<?php

class Hostinger_Onboarding_Heading extends Hostinger_Onboarding_Step {
	public function get_title(): string {
		return __( 'Edit heading', 'hostinger' );
	}

	public function get_body(): array {
		return [
			[
				'title'       => __( 'Go to the Customize page', 'hostinger' ),
				'description' => __( 'In the left sidebar, click Appearance to expand the menu. In the Appearance section, click Customize. The Customize page will open.', 'hostinger' )
			],
			[
				'title'       => __( 'Access the Header Builder', 'hostinger' ),
				'description' => __( 'Click on the Header Builder option in the panel on your left. Here you’ll find different settings to edit the appearance and style of the header.', 'hostinger' ),
			],
			[
				'title'       => __( 'Edit the header', 'hostinger' ),
				'description' => __( 'You can build a custom header by adding blocks. Hover over an empty area in the header and click the plus icon to add a header block. Now, you can select any block you’d like to add to your custom header.', 'hostinger' ),
			],
		];
	}

	public function step_identifier(): string {
		return 'edit_heading';
	}

	public function get_redirect_link(): string {
		return admin_url( 'customize.php' );
	}
}
