<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PayPal_Brasil_API_Save_Billing_Agreement_Handler extends PayPal_Brasil_API_Handler {

	public function __construct() {
		add_filter( 'paypal_brasil_handlers', array( $this, 'add_handlers' ) );
	}

	public function add_handlers( $handlers ) {
		$handlers['save-billing-agreement'] = array(
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
				'name'       => __( 'Billing Agreement', "paypal-brasil-para-woocommerce" ),
				'key'        => 'billing_agreement_token',
				'sanitize'   => 'sanitize_text_field',
				'validation' => array( $this, 'required_text' ),
			),
		);
	}

	/**
	 * Handle the request.
	 */
	public function handle() {
		try {

			$validation = $this->validate_input_data();

			if ( ! $validation['success'] ) {

				$error_message = __( 'Some fields are missing to create the billing agreement.', 'paypal-brasil' );

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

			// Get posted data.
			$posted_data = $validation['data'];

			// Get the wanted gateway.
			$gateway = $this->get_paypal_gateway( 'paypal-brasil-spb-gateway' );

			// Get billing agreement token in session.
			$session_billing_agreement_token = WC()->session->get( 'paypal_brasil_billing_agreement_token' );
			$billing_agreement_token         = $posted_data['billing_agreement_token'];

			// Check if there was a billing agreement token in session.
			if ( ! is_user_logged_in() || ! $session_billing_agreement_token || $session_billing_agreement_token !== $billing_agreement_token ) {
				$this->send_error_response( __( 'There was a problem verifying the payment settlement token session.', "paypal-brasil-para-woocommerce" ) );

				return;
			}

			// Create the billing agreement.
			$billing_agreement = $gateway->api->create_billing_agreement( $billing_agreement_token );

			// Save the billing agreement to the user.
			update_user_meta( get_current_user_id(), 'paypal_brasil_billing_agreement_id', $billing_agreement['id'] );
			update_user_meta( get_current_user_id(), 'paypal_brasil_billing_agreement_payer_info', $billing_agreement['payer']['payer_info'] );

			// Clear session.
			unset( WC()->session->paypal_brasil_billing_agreement_token );

			// Send success response with data.
			$this->send_success_response( __( 'Payment agreement successfully saved.', "paypal-brasil-para-woocommerce" ), array() );
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

	public function required_text( $data, $key, $name ) {
		if ( ! empty( $data ) ) {
			return true;
		}

		return sprintf( __( 'The field <strong>%s</strong> is required.', "paypal-brasil-para-woocommerce" ), $name );
	}

	// CUSTOM SANITIZER

	public function sanitize_boolean( $data, $key ) {
		return ! ! $data;
	}

}

new PayPal_Brasil_API_Save_Billing_Agreement_Handler();