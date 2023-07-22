<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class PayPal_Brasil_Gateway extends WC_Payment_Gateway {

	public $mode;
	public $debug;

	public $client_live;
	public $client_sandbox;
	public $secret_live;
	public $secret_sandbox;

	public $webhook_id;

	/**
	 * @var WC_Logger
	 */
	public $logger;

	public $partner_attribution_id;

	/**
	 * @var PayPal_Brasil_API
	 */
	public $api;

	public function __construct() {
		$this->logger = new WC_Logger();
	}

	public function get_client_id() {
		return $this->mode === 'sandbox' ? $this->client_sandbox : $this->client_live;
	}

	public function get_secret() {
		return $this->mode === 'sandbox' ? $this->secret_sandbox : $this->secret_live;
	}

	public function log( $data ) {
		if ( $this->debug === 'yes' ) {
			$this->logger->add( $this->id, $data );
		}
	}

	/**
	 * Retrieve the raw request entity (body).
	 *
	 * @return string
	 */
	public function get_raw_data() {
		// $HTTP_RAW_POST_DATA is deprecated on PHP 5.6
		if ( function_exists( 'phpversion' ) && version_compare( phpversion(), '5.6', '>=' ) ) {
			return file_get_contents( 'php://input' );
		}
		global $HTTP_RAW_POST_DATA;
		// A bug in PHP < 5.2.2 makes $HTTP_RAW_POST_DATA not set by default,
		// but we can do it ourself.
		if ( ! isset( $HTTP_RAW_POST_DATA ) ) {
			$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
		}

		return $HTTP_RAW_POST_DATA;
	}

	/**
	 * Handle webhooks from PayPal.
	 */
	public function webhook_handler() {
		include_once dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/handlers/class-paypal-brasil-webhooks-handler.php';

		try {
			// Instance the handler.
			$handler = new PayPal_Brasil_Webhooks_Handler( $this->id, $this );

			// Get the data.
			$headers = array_change_key_case( getallheaders(), CASE_UPPER );
			$body    = $this->get_raw_data();

			$this->log( "Webhook recebido:\n" . $body . "\n" );

			$webhook_event = json_decode( $body, true );

			// Prepare the signature verification.
			$signature_verification = array(
				'auth_algo'         => $headers['PAYPAL-AUTH-ALGO'],
				'cert_url'          => $headers['PAYPAL-CERT-URL'],
				'transmission_id'   => $headers['PAYPAL-TRANSMISSION-ID'],
				'transmission_sig'  => $headers['PAYPAL-TRANSMISSION-SIG'],
				'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'],
				'webhook_id'        => $this->get_webhook_id(),
			);

			$payload = "{";
			foreach ( $signature_verification as $field => $value ) {
				$payload .= "\"$field\": \"$value\",";
			}
			$payload .= "\"webhook_event\": $body";
			$payload .= "}";

			$signature_response = $this->api->verify_signature( $payload );

			if ( $signature_response['verification_status'] === 'SUCCESS' ) {
				$handler->handle( $webhook_event );
			}

			echo __( 'Webhook successfully treated.', "paypal-brasil-para-woocommerce" );
			exit;
		} catch ( Exception $ex ) {
			http_response_code( 500 );
			echo $ex->getMessage();
			exit;
		}
	}

	/**
	 * Get the webhook ID.
	 *
	 * @return string|null
	 */
	public function get_webhook_id() {
		return defined( 'PAYPAL_BRASIL_WEBHOOK_ID' ) ? PAYPAL_BRASIL_WEBHOOK_ID : get_option( 'paypal_brasil_webhook_url-' . $this->id, null );
	}

	/**
	 * Checks if the CNPJ is valid.
	 *
	 * @param string $cnpj CNPJ to validate.
	 *
	 * @return bool
	 */
	public function is_cnpj( $cnpj ) {
		$cnpj = sprintf( '%014s', preg_replace( '{\D}', '', $cnpj ) );
		if ( 14 !== strlen( $cnpj ) || 0 === intval( substr( $cnpj, - 4 ) ) ) {
			return false;
		}
		for ( $t = 11; $t < 13; ) {
			for ( $d = 0, $p = 2, $c = $t; $c >= 0; $c --, ( $p < 9 ) ? $p ++ : $p = 2 ) {
				$d += $cnpj[ $c ] * $p;
			}
			if ( intval( $cnpj[ ++ $t ] ) !== ( $d = ( ( 10 * $d ) % 11 ) % 10 ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if the CPF is valid.
	 *
	 * @param string $cpf CPF to validate.
	 *
	 * @return bool
	 */
	public function is_cpf( $cpf ) {
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );
		if ( 11 !== strlen( $cpf ) || preg_match( '/^([0-9])\1+$/', $cpf ) ) {
			return false;
		}
		$digit = substr( $cpf, 0, 9 );
		for ( $j = 10; $j <= 11; $j ++ ) {
			$sum = 0;
			for ( $i = 0; $i < $j - 1; $i ++ ) {
				$sum += ( $j - $i ) * intval( $digit[ $i ] );
			}
			$summod11        = $sum % 11;
			$digit[ $j - 1 ] = $summod11 < 2 ? 0 : 11 - $summod11;
		}

		return intval( $digit[9] ) === intval( $cpf[9] ) && intval( $digit[10] ) === intval( $cpf[10] );
	}

	/**
	 * Get endpoint for webhook.
	 * @return mixed
	 */
	public function get_webhook_url() {
		$base_url = site_url();

		// Use example.com when it's localhost.
		if ( $_SERVER['HTTP_HOST'] === 'localhost' ) {
			$base_url = 'https://example.com/';
		}

		// Ensure trailing slash
		$ensure_trailing_slash = rtrim( $base_url, '/' ) . '/';

		// Return URL always with https.
		$ensure_https = str_replace( 'http:', 'https:', add_query_arg( 'wc-api', $this->id, $ensure_trailing_slash ) );

		// Return without the port in URL.
		return preg_replace( '/(\:[\d]+)/', '', $ensure_https );
	}

	public function update_credentials() {
		$mode        = $this->get_field_value( 'mode', $this->form_fields['mode'] );
		$client_type = $mode === 'sandbox' ? 'client_sandbox' : 'client_live';
		$secret_type = $mode === 'sandbox' ? 'secret_sandbox' : 'secret_live';
		$client      = $this->get_field_value( $client_type, $this->form_fields[ $client_type ] );
		$secret      = $this->get_field_value( $secret_type, $this->form_fields[ $secret_type ] );

		$this->api->update_credentials( $client, $secret, $mode );
	}

	/**
	 * Validate the credentials.
	 */
	public function validate_credentials() {
		try {
			$this->api->get_access_token( true );
			update_option( $this->get_option_key() . '_validator', 'yes' );
		} catch ( Exception $ex ) {
			update_option( $this->get_option_key() . '_validator', 'no' );
		}
	}

	/**
	 * Create the webhook or use a existent webhook.
	 */
	public function create_webhooks() {
		// Set by default as not found.
		$webhook     = null;
		$webhook_url = defined( 'PAYPAL_BRASIL_WEBHOOK_URL' ) ? PAYPAL_BRASIL_WEBHOOK_URL : $this->get_webhook_url();

		try {

			// Get a list of webhooks
			$registered_webhooks = $this->api->get_webhooks();

			// Search for registered webhook.
			foreach ( $registered_webhooks['webhooks'] as $registered_webhook ) {
				if ( $registered_webhook['url'] === $webhook_url ) {
					$webhook = $registered_webhook;
					break;
				}
			}

			// If no webhook matched, create a new one.
			if ( ! $webhook ) {
				$events_types = array(
					'PAYMENT.SALE.COMPLETED',
					'PAYMENT.SALE.DENIED',
					'PAYMENT.SALE.PENDING',
					'PAYMENT.SALE.REFUNDED',
					'PAYMENT.SALE.REVERSED',
				);

				// Create webhook.
				$webhook_result = $this->api->create_webhook( $webhook_url, $events_types );

				update_option( 'paypal_brasil_webhook_url-' . $this->id, $webhook_result['id'] );

				return;
			}

			// Set the webhook ID
			update_option( 'paypal_brasil_webhook_url-' . $this->id, $webhook['id'] );
		} catch ( Exception $ex ) {
			update_option( 'paypal_brasil_webhook_url-' . $this->id, null );
		}
	}

	/**
	 * Get values from settings after POST.
	 * @return array
	 */
	public function get_updated_values() {
		$fields = array();
		foreach ( $this->get_form_fields() as $key => $field ) {
			$fields[ $key ] = $this->get_field_value( $key, $this->form_fields[ $key ] );
		}

		return $fields;
	}

}