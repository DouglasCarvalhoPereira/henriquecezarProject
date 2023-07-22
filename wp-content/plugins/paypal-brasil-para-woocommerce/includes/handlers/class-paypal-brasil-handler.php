<?php

// Exit if run outside WP.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PayPal_Brasil_Handler.
 */
class PayPal_Brasil_Handler {

	/**
	 * PayPal_Brasil_Handler constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_api_paypal_brasil_handler', array( $this, 'handler' ) );

		// Include API files.
		$this->includes();
	}

	/**
	 * Include all API files.
	 */
	private function includes() {
		include_once dirname( __FILE__ ) . '/abstratct-class-paypal-brasil-api-handler.php';
		include_once dirname( __FILE__ ) . '/api/class-paypal-brasil-api-checkout-handler.php';
		include_once dirname( __FILE__ ) . '/api/class-paypal-brasil-api-webhook-handler.php';
		include_once dirname( __FILE__ ) . '/api/class-paypal-brasil-api-shortcut-cart-handler.php';
		include_once dirname( __FILE__ ) . '/api/class-paypal-brasil-api-shortcut-mini-cart-handler.php';
		include_once dirname( __FILE__ ) . '/api/class-paypal-brasil-api-billing-agreement-token.php';
		include_once dirname( __FILE__ ) . '/api/class-paypal-brasil-api-save-billing-agreement.php';
	}

	/**
	 * Get all the setup handlers.
	 * @return array
	 */
	public function get_handlers() {
		return apply_filters( 'paypal_brasil_handlers', array() );
	}

	/**
	 * Execute the request.
	 */
	public function handler() {
		$handlers = $this->get_handlers();

		// Return an error in case action doesn't exists .
		if ( ! isset( $_GET['action'] ) || empty( $_GET['action'] ) || ! array_key_exists( $_GET['action'], $handlers ) ) {
			wp_send_json( array(
				'result'  => 'error',
				'message' => "Invalid request. Invalid param 'action'.",
			), 500 );
		}

		$action = $_GET['action'];
		call_user_func( $handlers[ $action ]['callback'] );
	}

}
