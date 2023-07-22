<?php

class Hostinger_Onboarding_Add_Page extends Hostinger_Onboarding_Step {
	public function get_title(): string {
		return __( 'Add a new page', 'hostinger' );
	}

	public function get_body(): array {
		return [
			[
				'title'       => __( 'Add a new page', 'hostinger' ),
				'description' => __( 'In the left sidebar, find the Pages menu and click on Add New button. You will see the WordPress page editor. Each paragraph, image, or video in the WordPress editor is presented as a “block” of content.', 'hostinger' )
			],
			[
				'title'       => __( 'Add a title', 'hostinger' ),
				'description' => __( 'Add the title of the page, for example, About. Click the Add Title text to open the text box where you will add your title. The title of your page should be descriptive of the information the page will have.', 'hostinger' ),
			],
			[
				'title'       => __( 'Add content', 'hostinger' ),
				'description' => __( 'Content can be anything you wish, for example, text, images, videos, tables, and lots more. Click on a plus sign and choose any block you want to add to the page.', 'hostinger' ),
			],
			[
				'title'       => __( 'Publish the page', 'hostinger' ),
				'description' => __( 'Before publishing, you can preview your created page by clicking on the Preview button. If you are happy with the result, click the Publish button.', 'hostinger' )
			]
		];
	}

	public function step_identifier(): string {
		return 'add_page';
	}

	public function get_redirect_link(): string {
		return admin_url( 'post-new.php' );
	}
}
