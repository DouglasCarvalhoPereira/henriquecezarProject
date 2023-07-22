<?php

// Exit if runs outside WP.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PayPal_Brasil_API_Handler.
 */
abstract class PayPal_Brasil_API_Handler {

	/**
	 * Send a JSON response with error.
	 *
	 * @param string $message
	 * @param array $data
	 * @param int $code
	 */
	protected function send_error_response( $message, $data = array(), $code = 400 ) {
		if ( ! isset( $data['error_notice'] ) ) {
			ob_start();
			if ( isset( $data['errors'] ) ) {
				$error_message = sprintf( '<p>%s</p>', $message );
				$error_message .= '<ul>';
				foreach ( $data['errors'] as $error ) {
					$error_message .= sprintf( '<li>%s</li>', $error );
				}
				$error_message .= '</ul>';
				wc_print_notice( $error_message, 'error' );
			} else {
				wc_print_notice( $message, 'error' );
			}
			$error_notice = ob_get_clean();

			$data['error_notice'] = $error_notice;
		}

		$this->send_response( 'error', $message, $data, $code );
	}

	/**
	 * Send a JSON response with success.
	 *
	 * @param string $message
	 * @param array $data
	 * @param int $code
	 */
	protected function send_success_response( $message, $data = array(), $code = 200 ) {
		$this->send_response( 'success', $message, $data, $code );
	}

	/**
	 * Send a JSON default response.
	 *
	 * @param string $result
	 * @param string $message
	 * @param array $data
	 * @param int $code
	 */
	protected function send_response( $result = 'success', $message = '', $data = array(), $code = 200 ) {
		wp_send_json( array(
			'result'  => $result,
			'message' => $message,
			'data'    => $data,
		), $code );
	}

	/**
	 * @param PayPal_Brasil_Gateway $gateway
	 *
	 * @return array
	 */
	protected function get_api_data( $gateway ) {
		return array(
			'client_id'              => $gateway->get_client_id(),
			'secret'                 => $gateway->get_secret(),
			'mode'                   => $gateway->mode,
			'partner_attribution_id' => $gateway->partner_attribution_id,
		);
	}

	/**
	 * Fields to validate.
	 * @return array
	 */
	public function get_fields() {
		return array();
	}

	/**
	 * Retrieve the raw request entity body.
	 *
	 * @return string
	 */
	protected function get_raw_data_as_json() {
		return json_decode( file_get_contents( 'php://input' ), true );
	}

	/**
	 * Get PayPal active gateway.
	 *
	 * @param string $gateway
	 *
	 * @return PayPal_Brasil_Gateway
	 * @throws Exception
	 */
	protected function get_paypal_gateway( $gateway ) {
		$payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		if ( isset( $payment_gateways[ $gateway ] ) ) {
			return $payment_gateways[ $gateway ];
		}

		throw new Exception( __( 'PayPal payment method not found', "paypal-brasil-para-woocommerce" ) );
	}

	/**
	 * Validate the input data.
	 *
	 * @return array
	 */
	protected function validate_input_data() {
		// Get input data in body.
		$input = $this->get_raw_data_as_json();

		// Get fields.
		$fields = $this->get_fields();

		// Prepare response.
		$errors = array();
		$data   = array();

		// Loop each item and validate.
		foreach ( $fields as $item ) {
			// Get the data if exists.
			$input_data = isset( $input[ $item['key'] ] ) ? $input[ $item['key'] ] : '';

			// Sanitize input data.
			$sanitized_data = call_user_func( $item['sanitize'], $input_data, $item['key'] );

			// Check first is there is any validation for this field.
			if ( isset( $item['validation'] ) ) {
				// Call for validation method.
				$validation = call_user_func( $item['validation'], $sanitized_data, $item['key'], $item['name'], $input );

				// If there is any validation error, add to error items.
				if ( $validation !== true ) {
					$errors[ $item['key'] ] = $validation;
				}
			}

			// Add the sanitized item to data.
			$data[ $item['key'] ] = $sanitized_data;
		}

		return array(
			'success' => ! $errors,
			'errors'  => $errors,
			'data'    => $data,
		);
	}


}