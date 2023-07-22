<?php

// Exit if runs outside WP.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PayPal_Brasil_API.
 * @property string access_token_transient_key
 * @property string mode
 * @property string base_url
 * @property string client_id
 * @property string secret
 * @property string partner_attribution_id
 * @property PayPal_Brasil_Gateway gateway
 */
class PayPal_Brasil_API {

	private $bn_code = array(
		'reference' => 'WooCommerceBrazil_Ecom_RT',
		'ec'        => 'WooCommerceBrazil_Ecom_EC',
		'shortcut'  => 'WooCommerceBrazil_Ecom_ECS',
		'plus'      => 'WooCommerceBR_Ecom_PPPlus',
		'default'   => 'WooCommerceBrazil_Ecom_EC',
	);

	/**
	 * PayPal_Brasil_API constructor.
	 *
	 * @param string $client_id
	 * @param string $secret
	 * @param string $mode The API mode sandbox|live.
	 * @param $gateway PayPal_Brasil_Gateway
	 */
	public function __construct( $client_id, $secret, $mode, $gateway ) {
		// Gateway
		$this->gateway = $gateway;

		// Save the API data.
		$this->mode      = $mode;
		$this->client_id = $client_id;
		$this->secret    = $secret;

		// Set the access token transient key to a MD5 hash of client id and secret. So transient will change if
		// client id or secret changes also.
		$this->access_token_transient_key = 'paypal_brasil_access_token_' . $this->get_credentials_hash();
	}

	/**
	 * Get a hash for mode, client id and secret.
	 *
	 * @return string
	 */
	public function get_credentials_hash() {
		return md5( $this->mode . ':' . $this->client_id . ':' . $this->secret );
	}

	/**
	 * Update plugin credentials.
	 *
	 * @param $client_id
	 * @param $secret
	 * @param $mode
	 */
	public function update_credentials( $client_id, $secret, $mode ) {
		// Save the API data.
		$this->mode      = $mode;
		$this->client_id = $client_id;
		$this->secret    = $secret;

		// Set the access token transient key to a MD5 hash of client id and secret. So transient will change if
		// client id or secret changes also.
		$this->access_token_transient_key = 'paypal_brasil_access_token_' . $this->get_credentials_hash();
	}

	/**
	 * Get the base URL for API requests.
	 *
	 * @param null $mode
	 *
	 * @return string
	 */
	public function get_base_url( $mode = null ) {
		if ( ! $mode ) {
			$mode = $this->mode;
		}

		return ( $mode === 'live' ) ? 'https://api.paypal.com/v1' : 'https://api.sandbox.paypal.com/v1';
	}

	/**
	 * Get access token.
	 *
	 * @param bool $force
	 *
	 * @return array|WP_Error
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 *
	 */
	public function get_access_token( $force = false ) {
		$url = $this->get_base_url() . '/oauth2/token';

		// Try to get the transient for access token.
		$access_token = get_transient( $this->access_token_transient_key );

		// If there's any token in transients, return it.
		if ( ! $force && $access_token ) {
			return $access_token;
		}

		$headers = array(
			'Authorization'                 => 'Basic ' . base64_encode( $this->client_id . ':' . $this->secret ),
			'Content-Type'                  => 'application/x-www-form-urlencoded',
			'PayPal-Partner-Attribution-Id' => $this->bn_code['default'],
		);

		$data = 'grant_type=client_credentials';

		$response      = $this->do_request( 'GET_ACCESS_TOKEN', $url, 'POST', $data, $headers, false );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new PayPal_Brasil_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was ok.
		if ( $code === 200 ) {
			set_transient( $this->access_token_transient_key, $response_body['access_token'],
				$response_body['expires_in'] );

			return $response_body['access_token'];
		}

		throw new PayPal_Brasil_API_Exception( $code,
			__( 'Unable to get access token.', "paypal-brasil-para-woocommerce" ), $response_body );
	}

	/**
	 * Create a payment.
	 *
	 * @param array $data
	 *
	 * @param array $headers
	 *
	 * @return mixed
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	public function create_payment( $data, $headers = array(), $bn_code_key = null ) {
		$url = $this->get_base_url() . '/payments/payment';

		// Add bn code if exits.
		if ( $bn_code_key && array_key_exists( $bn_code_key, $this->bn_code ) ) {
			$headers['PayPal-Partner-Attribution-Id'] = $this->bn_code[ $bn_code_key ];
		}

		// Get response.
		$response      = $this->do_request( 'CREATE_PAYMENT', $url, 'POST', $data, $headers );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new PayPal_Brasil_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 201 ) {
			return $response_body;
		}

		throw new PayPal_Brasil_API_Exception( $code,
			__( 'Unable to create payment.', "paypal-brasil-para-woocommerce" ), $response_body );
	}

	/**
	 * Get a given payment id.
	 *
	 * @param $payment_id
	 *
	 * @return array|mixed|object
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	public function get_payment( $payment_id, $headers = array(), $bn_code_key = null ) {
		$url = $this->get_base_url() . '/payments/payment/' . $payment_id;

		// Add bn code if exits.
		if ( $bn_code_key && array_key_exists( $bn_code_key, $this->bn_code ) ) {
			$headers['PayPal-Partner-Attribution-Id'] = $this->bn_code[ $bn_code_key ];
		}

		// Get response.
		$response      = $this->do_request( 'GET_PAYMENT', $url, 'GET', array(), $headers );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new PayPal_Brasil_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 200 ) {
			return $response_body;
		}

		throw new PayPal_Brasil_API_Exception( $code,
			__( 'Unable to obtain payment details.', "paypal-brasil-para-woocommerce" ),
			$response_body );
	}

	/**
	 * Execute a payment.
	 *
	 * @param $payment_id
	 * @param $payer_id
	 *
	 * @return array|mixed|object
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	public function execute_payment( $payment_id, $payer_id, $headers = array(), $bn_code_key = null ) {
		$url = $this->get_base_url() . '/payments/payment/' . $payment_id . '/execute';

		$data = array(
			'payer_id' => $payer_id,
		);

		// Add bn code if exits.
		if ( $bn_code_key && array_key_exists( $bn_code_key, $this->bn_code ) ) {
			$headers['PayPal-Partner-Attribution-Id'] = $this->bn_code[ $bn_code_key ];
		}

		// Get response.
		$response      = $this->do_request( 'EXECUTE_PAYMENT', $url, 'POST', $data, $headers );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new PayPal_Brasil_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 200 ) {
			return $response_body;
		}

		throw new PayPal_Brasil_API_Exception( $code,
			__( 'Unable to execute payment.', "paypal-brasil-para-woocommerce" ), $response_body );
	}

	/**
	 * @param $payment_id
	 * @param $data
	 *
	 * @return array|mixed|object
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	public function update_payment( $payment_id, $data, $headers = array(), $bn_code_key = null ) {
		$url = $this->get_base_url() . '/payments/payment/' . $payment_id;

		// Add bn code if exits.
		if ( $bn_code_key && array_key_exists( $bn_code_key, $this->bn_code ) ) {
			$headers['PayPal-Partner-Attribution-Id'] = $this->bn_code[ $bn_code_key ];
		}

		// Get response.
		$response      = $this->do_request( 'UPDATE_PAYMENT', $url, 'PATCH', $data, $headers );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new PayPal_Brasil_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 200 ) {
			return $response_body;
		}

		throw new PayPal_Brasil_API_Exception( $code,
			__( 'Unable to update payment.', "paypal-brasil-para-woocommerce" ), $response_body );
	}

	/**
	 * Create Billing Agreement Token
	 *
	 * @return array|mixed|object
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	public function create_billing_agreement_token() {
		$url  = $this->get_base_url() . '/billing-agreements/agreement-tokens';
		$data = array(
			'description' => sprintf( 'Billing Agreement', get_bloginfo( 'name' ) ),
			'payer'       => array(
				'payment_method' => 'PAYPAL',
			),
			'plan'        => array(
				'type'                 => 'MERCHANT_INITIATED_BILLING',
				'merchant_preferences' => array(
					'return_url'                 => esc_html( home_url() ),
					'cancel_url'                 => esc_html( home_url() ),
					'notify_url'                 => esc_html( home_url() ),
					'accepted_pymt_type'         => 'INSTANT',
					'skip_shipping_address'      => true,
					'immutable_shipping_address' => true,
				),
			),
		);

		// Get response.
		$response      = $this->do_request( 'CREATE_BILLING_AGREEMENT_TOKEN', $url, 'POST', $data,
			array( 'PayPal-Partner-Attribution-Id' => $this->bn_code['reference'] ) );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new PayPal_Brasil_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 201 ) {
			return $response_body;
		}

		throw new PayPal_Brasil_API_Exception( $code,
			__( 'Could not create billing authorization token.', "paypal-brasil-para-woocommerce" ),
			$response_body );
	}

	public function create_billing_agreement( $token ) {
		$url  = $this->get_base_url() . '/billing-agreements/agreements';
		$data = array(
			'token_id' => $token,
		);

		// Get response.
		$response      = $this->do_request( 'CREATE_BILLING_AGREEMENT', $url, 'POST', $data,
			array( 'PayPal-Partner-Attribution-Id' => $this->bn_code['reference'] ) );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new PayPal_Brasil_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 201 ) {
			return $response_body;
		}

		throw new PayPal_Brasil_API_Exception( $code,
			__( 'Unable to create billing authorization.', "paypal-brasil-para-woocommerce" ),
			$response_body );
	}

	public function get_calculate_financing( $billing_agreement, $value ) {
		$url  = $this->get_base_url() . '/credit/calculated-financing-options';
		$data = array(
			'financing_country_code' => 'BR',
			'transaction_amount'     => array(
				'value'         => $value,
				'currency_code' => 'BRL',
			),
			'funding_instrument'     => array(
				'type'              => 'BILLING_AGREEMENT',
				'billing_agreement' => array(
					'billing_agreement_id' => $billing_agreement,
				),
			),
		);

		// Get response.
		$response      = $this->do_request( 'GET_CALCULATE_FINANCING', $url, 'POST', $data,
			array( 'PayPal-Partner-Attribution-Id' => $this->bn_code['reference'] ) );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new PayPal_Brasil_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 200 ) {
			return $response_body;
		}

		throw new PayPal_Brasil_API_Exception( $code,
			__( 'Unable to get installment options.', "paypal-brasil-para-woocommerce" ),
			$response_body );
	}

	/**
	 * Verify PayPal signature.
	 *
	 * @param $data
	 *
	 * @return array|mixed|object
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	public function verify_signature( $data ) {
		$url = $this->get_base_url() . '/notifications/verify-webhook-signature';

		// Get response.
		$response      = $this->do_request( 'VERIFY_SIGNATURE', $url, 'POST', $data,
			array( 'PayPal-Partner-Attribution-Id' => $this->bn_code['ec'] ) );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new PayPal_Brasil_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 200 ) {
			return $response_body;
		}

		throw new PayPal_Brasil_API_Exception( $code,
			__( 'Unable to verify PayPal signature.', "paypal-brasil-para-woocommerce" ),
			$response_body );
	}

	/**
	 * Get webhook list.
	 *
	 * @return array|mixed|object
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	public function get_webhooks() {
		$url = $this->get_base_url() . '/notifications/webhooks';

		// Get response.
		$response      = $this->do_request( 'GET_WEBHOOKS', $url, 'GET', array(),
			array( 'PayPal-Partner-Attribution-Id' => $this->bn_code['ec'] ) );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new PayPal_Brasil_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 200 ) {
			return $response_body;
		}

		throw new PayPal_Brasil_API_Exception( $code,
			__( 'Unable to get the webhooks.', "paypal-brasil-para-woocommerce" ), $response_body );
	}

	/**
	 * Create a webhook.
	 *
	 * @param $webhook_url
	 * @param $events
	 *
	 * @return array|mixed|object
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	public function create_webhook( $webhook_url, $events ) {
		$url = $this->get_base_url() . '/notifications/webhooks';

		$data = array(
			// Remove any port in URL to use only port 80.
			'url'         => preg_replace( '/(\:[\d]+)/', '', $webhook_url ),
			'event_types' => array(),
		);

		// Add events.
		foreach ( $events as $event ) {
			$data['event_types'][] = array(
				'name' => $event,
			);
		}

		// Get response.
		$response      = $this->do_request( 'CREATE_WEBHOOK', $url, 'POST', $data,
			array( 'PayPal-Partner-Attribution-Id' => $this->bn_code['ec'] ) );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new PayPal_Brasil_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 201 ) {
			return $response_body;
		}

		throw new PayPal_Brasil_API_Exception( $code,
			__( 'Could not create webhook.', "paypal-brasil-para-woocommerce" ), $response_body );
	}

	/**
	 * Refund a payment.
	 *
	 * @param $payment_id
	 * @param null $total
	 * @param null $currency
	 *
	 * @return array|mixed|object
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	public function refund_payment( $payment_id, $total = null, $currency = null ) {
		$url = $this->get_base_url() . '/payments/sale/' . $payment_id . '/refund';

		// Body is default empty for full refund.
		$data = array();

		// If is set total, it's a partial refund.
		if ( $total !== null ) {
			$data = array(
				'amount' => array(
					'total'    => $total,
					'currency' => $currency ? $currency : get_woocommerce_currency(),
				),
			);
		}

		// Get response.
		$response      = $this->do_request( 'REFUND_PAYMENT', $url, 'POST', $data,
			array( 'PayPal-Partner-Attribution-Id' => $this->bn_code['ec'] ) );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new PayPal_Brasil_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 201 ) {
			return $response_body;
		}

		throw new PayPal_Brasil_API_Exception( $code,
			__( 'It was not possible to make a refund.', "paypal-brasil-para-woocommerce" ), $response_body );
	}

	/**
	 * Do requests in the API.
	 *
	 * @param string $url URL.
	 * @param string $method Request method.
	 * @param array $data Request data.
	 * @param array $headers Request headers.
	 *
	 * @return array            Request response.
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	protected function do_request( $name, $url, $method = 'POST', $data = array(), $headers = array(), $log = true ) {

		// Default headers.
		$headers = wp_parse_args( array(
			'Accept-Language' => get_locale(), // use default WP locale.
			'Content-Type'    => 'application/json;charset=UTF-8', // send as json for default.
		), $headers );

		// Add access token if needed.
		// In case is access token request, the authorization already exists, so no way
		// will reach the paypal_brasil_Api_Exception and paypal_brasil_Connection_Exception.
		if ( ! isset( $headers['Authorization'] ) ) {
			$headers['Authorization'] = 'Bearer ' . $this->get_access_token();
		}

		// Default partner id if nothing is passed.
		if ( ! isset( $headers['PayPal-Partner-Attribution-Id'] ) ) {
			$headers['PayPal-Partner-Attribution-Id'] = $this->bn_code['default'];
		}

		$params = array(
			'method'  => $method,
			'timeout' => 60,
			'headers' => $headers,
		);

		// Add the body for post requests.
		if ( in_array( $method, array( 'POST', 'PATCH' ) ) && ! empty( $data ) ) {
			if ( preg_match( '/(application\/json)/', $headers['Content-Type'] ) && is_array( $data ) ) {
				$data = json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			}

			$params['body'] = $data;
		}

		// Only log response when $log exists.
		if ( isset( $params['body'] ) ) {
			$this->gateway->log( __( "[{$name}] Making request ({$method}) for {$url}:\n" . $data . "\n" , "paypal-brasil-para-woocommerce" ) );
		} else {
			$this->gateway->log( __( "[{$name}] Making request ({$method}) for {$url}\n", "paypal-brasil-para-woocommerce" ) );
		}

		$request = wp_safe_remote_request( $url, $params );

		if ( is_wp_error( $request ) ) {
			$this->gateway->log( __( "[{$name}] HTTP error when making the request ({$method}) for {$url}\n", "paypal-brasil-para-woocommerce" ) );
		} else {
			// Only log response when $log exists.
			$body = json_decode( wp_remote_retrieve_body( $request ), true );
			if ( isset( $body['access_token'] ) ) {
				$body['access_token'] = 'xxxxxxxxxxxxxxxxxxxxxxxx';
			}

			// Don't log access token request.
			$response_object = $request['http_response']->get_response_object();
			$raw_response = $response_object->raw;
			$status_code = $response_object->status_code;
			if ( ! (preg_match( '/\/v1\/oauth2\/token$/', $url ) && $status_code >= 200 && $status_code <= 299 )) {
				$this->gateway->log( __("[{$name}] Request response ({$method}) for {$url}:\n","paypal-brasil-para-woocommerce") . json_encode( $body,
						JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" );
				$this->gateway->log( __("[{$name}] Request response ({$method}) for {$url} complete:\n" . $raw_response . "\n","paypal-brasil-para-woocommerce") );
			} else {
				$this->gateway->log( __("[{$name}] Request response ({$method}) for {$url} with status code {$status_code} hidden for security reasons.\n", "paypal-brasil-para-woocommerce"));
			}
		}

		return $request;
	}

	/**
	 * Parse the links from PayPal response.
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function parse_links( $links ) {
		$data = array();

		foreach ( $links as $link ) {
			$data[ $link['rel'] ] = $link['href'];
		}

		return $data;
	}
}