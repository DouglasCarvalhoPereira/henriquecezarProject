<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PayPal_Brasil_API_Billing_Agreement_Token_Handler extends PayPal_Brasil_API_Handler {

	public function __construct() {
		add_filter( 'paypal_brasil_handlers', array( $this, 'add_handlers' ) );
	}

	public function add_handlers( $handlers ) {
		$handlers['billing-agreement-token'] = array(
			'callback' => array( $this, 'handle' ),
			'method'   => 'POST',
		);

		return $handlers;
	}

	/**
	 * Add validators and input fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			array(
				'name'       => __( 'nonce', "paypal-brasil-para-woocommerce" ),
				'key'      => 'nonce',
				'sanitize' => 'sanitize_text_field',
				'validation' => array( $this, 'required_nonce' ),
			),
//			array(
//				'name'       => __( 'ID do usuário', "paypal-brasil-para-woocommerce" ),
//				'key'        => 'user_id',
//				'sanitize'   => 'sanitize_text_field',
//				'validation' => array( $this, 'required_current_user_id' ),
//			),
		);
	}

	/**
	 * Handle the request.
	 */
	public function handle() {
		try {

			$validation = $this->validate_input_data();

			if ( ! $validation['success'] ) {

				$error_message = __( 'Some fields are missing to create payment authorization token.', "paypal-brasil-para-woocommerce" );

				$errors   = array();
				$errors[] = '<p>' . $error_message . '</p>';
				$errors[] = '<ul>';
				foreach ( $validation['errors'] as $key => $value ) {
					$errors[] = '<li>' . $value . '</li>';
				}
				$errors[] = '</ul>';

				ob_start();
				wc_print_notice( implode( '', $errors ), 'error' );
				$error_message_notice = ob_get_clean();

				$this->send_error_response(
					$error_message,
					array(
						'errors'       => $validation['errors'],
						'error_notice' => $error_message_notice,
					)
				);
			}

			// Get the wanted gateway.
			$gateway = $this->get_paypal_gateway( 'paypal-brasil-spb-gateway' );

			// Create new token
			$response = $gateway->api->create_billing_agreement_token();

			// Store the requested data in session.
			WC()->session->set( 'paypal_brasil_billing_agreement_token', $response['token_id'] );

			// Send success response with data.
			$this->send_success_response( __( 'Successfully created token.', "paypal-brasil-para-woocommerce" ), array(
				'token_id' => $response['token_id'],
			) );
		} catch ( Exception $ex ) {
			$this->send_error_response( $ex->getMessage() );
		}
	}

	// CUSTOM VALIDATORS

	public function required_nonce( $data, $key, $name ) {
		if ( wp_verify_nonce( $data, 'paypal-brasil-checkout' ) ) {
			return true;
		}

		return sprintf( __( 'The %s is invalid.', "paypal-brasil-para-woocommerce" ), $name );
	}

	public function required_current_user_id( $data, $key ) {
		if ( ! $data || get_current_user_id() != $data ) {
			return __( 'You must be logged in to proceed with this type of payment.', "paypal-brasil-para-woocommerce" );
		}

		return true;
	}

	// CUSTOM SANITIZER

	public function sanitize_boolean( $data, $key ) {
		return ! ! $data;
	}

}

new PayPal_Brasil_API_Billing_Agreement_Token_Handler();