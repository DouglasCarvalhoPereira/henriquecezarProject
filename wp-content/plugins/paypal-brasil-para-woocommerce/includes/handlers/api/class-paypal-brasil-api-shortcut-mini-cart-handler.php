<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PayPal_Brasil_API_Shortcut_Mini_Cart_Handler extends PayPal_Brasil_API_Handler {

	public function __construct() {
		add_filter( 'paypal_brasil_handlers', array( $this, 'add_handlers' ) );
	}

	public function add_handlers( $handlers ) {
		$handlers['shortcut'] = array(
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
				'name'     => __( 'nonce', "paypal-brasil-para-woocommerce" ),
				'key'      => 'nonce',
				'sanitize' => 'sanitize_text_field',
//				'validation' => array( $this, 'required_nonce' ),
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
				$this->send_error_response(
					__( 'Some fields are missing to initiate the payment.', 'paypal-brasil' ),
					array(
						'errors' => $validation['errors']
					)
				);
			}

			// Get the wanted gateway.
			$gateway = $this->get_paypal_gateway( 'paypal-brasil-spb-gateway' );

			// Store cart.
			$cart = WC()->cart;

			// Disable shipping while handle shortcode and force recalculate totals.
			add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
			$cart->calculate_totals();

			// Check if there is anything on cart.
			if ( ! $cart->get_totals()['total'] ) {
				$this->send_error_response( __( 'You cannot pay for an empty order.', "paypal-brasil-para-woocommerce" ) );
			}

			$wc_cart = WC()->cart;
			$wc_cart_totals = new WC_Cart_Totals($wc_cart);
			$cart_totals = $wc_cart_totals->get_totals(true);

			$only_digital_items = paypal_brasil_is_cart_only_digital();

			$data = array(
				'intent'        => 'sale',
				'payer'         => array(
					'payment_method' => 'paypal',
				),
				'transactions'  => array(
					array(
						'payment_options' => array(
							'allowed_payment_method' => 'IMMEDIATE_PAY',
						),
						'item_list'       => array(
							'items' => array(
								array(
									'name'     => sprintf( __( 'Store order %s', "paypal-brasil-para-woocommerce" ), get_bloginfo( 'name' ) ),
									'currency' => get_woocommerce_currency(),
									'quantity' => 1,
									'price'    => paypal_format_amount( wc_remove_number_precision_deep( $cart_totals['total'] - $cart_totals['shipping_total'] ) ),
									'sku'      => 'order-items',
								)
							),
						),
						'amount'          => array(
							'currency' => get_woocommerce_currency(),
						),
					),
				),
				'redirect_urls' => array(
					'return_url' => home_url(),
					'cancel_url' => home_url(),
				),
			);

			// Set details
			$data['transactions'][0]['amount']['details'] = array(
				'subtotal' => paypal_format_amount( wc_remove_number_precision_deep( $cart_totals['total'] - $cart_totals['shipping_total'] ) ),
			);

			// Set total Total
			$data['transactions'][0]['amount']['total'] = paypal_format_amount( wc_remove_number_precision_deep( $cart_totals['total'] ) );

			// Set the application context
			$data['application_context'] = array(
				'brand_name'          => get_bloginfo( 'name' ),
				'shipping_preference' => $only_digital_items ? 'NO_SHIPPING' : 'GET_FROM_FILE',
				'user_action'         => 'continue',
			);

			// Create the payment in API.
			$create_payment = $gateway->api->create_payment( $data, array(), 'shortcut' );

			// Get the response links.
			$links = $gateway->api->parse_links( $create_payment['links'] );

			// Extract EC token from response.
			preg_match( '/(EC-\w+)/', $links['approval_url'], $ec_token );

			// Separate data.
			$data = array(
				'pay_id'   => $create_payment['id'],
				'ec'       => $ec_token[0],
				'postcode' => preg_replace( '/[^0-9]/', '', WC()->customer->get_shipping_postcode() ),
			);

			// Store the requested data in session.
			WC()->session->set( 'paypal_brasil_spb_shortcut_data', $data );

			// Send success response with data.
			$this->send_success_response( __( 'Payment created successfully.', "paypal-brasil-para-woocommerce" ), $data );
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

	// CUSTOM SANITIZER

	public function sanitize_boolean( $data, $key ) {
		return ! ! $data;
	}

}

new PayPal_Brasil_API_Shortcut_Mini_Cart_Handler();