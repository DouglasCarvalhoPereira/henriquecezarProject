<?php

// Ignore if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PayPal_Brasil_SPB_Gateway.
 *
 * @property string client_live
 * @property string client_sandbox
 * @property string secret_live
 * @property string secret_sandbox
 * @property string format
 * @property string color
 * @property string shortcut_enabled
 * @property string reference_enabled
 * @property string debug
 * @property string invoice_id_prefix
 * @property string title_complement
 */
class PayPal_Brasil_SPB_Gateway extends PayPal_Brasil_Gateway {

	private static $instance;
	private static $uuid;

	/**
	 * PayPal_Brasil_Plus constructor.
	 */
	public function __construct() {
		parent::__construct();

		// Store some default gateway settings.
		$this->id                 = 'paypal-brasil-spb-gateway';
		$this->has_fields         = true;
		$this->method_title       = __( 'PayPal Brasil', "paypal-brasil-para-woocommerce" );
		$this->icon               = plugins_url( 'assets/images/paypal-logo.png', PAYPAL_PAYMENTS_MAIN_FILE );
		$this->method_description = __( 'Add PayPal Digital Wallet Solutions to Your WooCommerce Store.',
			"paypal-brasil-para-woocommerce" );
		$this->supports           = array(
			'products',
			'refunds',
		);

		// Load settings fields.
		$this->init_form_fields();
		$this->init_settings();

		// Get available options.
		$this->enabled          = $this->get_option( 'enabled' );
		$this->title            = __( 'PayPal Brasil - Digital Wallet', "paypal-brasil-para-woocommerce" );
		$this->title_complement = $this->get_option( 'title_complement' );
		$this->mode             = $this->get_option( 'mode' );
		$this->client_live      = $this->get_option( 'client_live' );
		$this->client_sandbox   = $this->get_option( 'client_sandbox' );
		$this->secret_live      = $this->get_option( 'secret_live' );
		$this->secret_sandbox   = $this->get_option( 'secret_sandbox' );

		$this->format            = $this->get_option( 'format' );
		$this->color             = $this->get_option( 'color' );
		$this->shortcut_enabled  = $this->get_option( 'shortcut_enabled' );
		$this->reference_enabled = $this->get_option( 'reference_enabled' );

		$this->invoice_id_prefix = $this->get_option( 'invoice_id_prefix' );
		$this->debug             = $this->get_option( 'debug' );

		// Instance the API.
		$this->api = new PayPal_Brasil_API( $this->get_client_id(), $this->get_secret(), $this->mode, $this );

		// Save settings.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		), 10 );

		// Save custom settings.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'before_process_admin_options'
		), 20 );

		// Stop here if is not the first load.
		if ( ! $this->is_first_load() ) {
			return;
		}

		// Handler for IPN.
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'webhook_handler' ) );

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ), 0 );

		// Clear SPB session data when refresh fragments.
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'clear_spb_session_data' ) );

		// Process billing agreement
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'create_billing_agreement' ) );

		// Add shortcut button in cart.
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'shortcut_button_cart' ), 9999 );

		// Add shortcut button in mini cart.
		add_action( 'woocommerce_after_mini_cart', array( $this, 'shortcut_button_mini_cart' ) );

		// Add custom trigger for mini cart.
		add_action( 'woocommerce_after_mini_cart', array( $this, 'trigger_mini_cart_update' ) );

		// Add fraudnet to footer.
		add_action( 'wp_footer', array( $this, 'fraudnet_script' ) );

		// Render different things if is shortcut process.
		if ( $this->is_processing_shortcut() ) {

			// Add shortcut custom fields
			add_action( 'woocommerce_before_checkout_billing_form', array(
				$this,
				'shortcut_before_checkout_fields'
			) );

			// Add some fields to store data
			add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'shortcut_checkout_fields' ) );

			// Remove all other gateways
			add_action( 'woocommerce_available_payment_gateways', array( $this, 'filter_gateways' ) );

			// Filter the page title.
			add_filter( 'the_title', array( $this, 'filter_review_title' ), 10, 2 );

			// If is NOT override address, we should remove unnecessary fields.
			if ( ! $this->is_shortcut_override_address() ) {
				// Filter form fields validation when is shortcut.
				add_filter( 'woocommerce_checkout_posted_data', array( $this, 'shortcut_filter_posted_data' ) );
				add_filter( 'wcbcf_disable_checkout_validation', '__return_true' );

				// Remove unnecessary fields.
				add_filter( 'woocommerce_billing_fields', array( $this, 'remove_billing_fields' ) );
				add_filter( 'woocommerce_shipping_fields', array( $this, 'remove_shipping_fields' ) );
			} else {
				// Pre populate with correct information.
				add_filter( 'woocommerce_checkout_get_value', array( $this, 'pre_populate_shortcut_fields' ), 10, 2 );
			}

		}

		// Content only for SPB.
		if ( $this->is_processing_spb() ) {

			// Add custom submit button.
			add_action( 'woocommerce_review_order_before_submit', array( $this, 'html_before_submit_button' ) );
			add_action( 'woocommerce_pay_order_before_submit', array( $this, 'html_before_submit_button' ) );
			add_action( 'woocommerce_review_order_after_submit', array( $this, 'html_after_submit_button' ) );
			add_action( 'woocommerce_pay_order_after_submit', array( $this, 'html_after_submit_button' ) );
		}

		// If it's first load, add a instance of this.
		self::$instance = $this;
	}

	/**
	 * Return the gateway's title.
	 *
	 * @return string
	 */
	public function get_title() {
		// A description only for admin section.
		if ( is_admin() ) {
			global $pagenow;

			return $pagenow === 'post.php' ? __( 'PayPal - Digital Wallet',
				"paypal-brasil-para-woocommerce" ) : __( 'Digital Wallet', "paypal-brasil-para-woocommerce" );
		}

		// Title for frontend.
		$title = __( 'PayPal', "paypal-brasil-para-woocommerce" );
		if ( ! empty( $this->title_complement ) ) {
			$title .= ' (' . $this->title_complement . ')';
		}

		return apply_filters( 'woocommerce_gateway_title', $title, $this->id );
	}

	/**
	 * Define gateway form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'           => array(
				'title'   => __( 'Enable/Disable', "paypal-brasil-para-woocommerce" ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable', "paypal-brasil-para-woocommerce" ),
				'default' => 'no',
			),
			'title_complement'  => array(
				'title' => __( 'Display name (add-on)', "paypal-brasil-para-woocommerce" ),
				'type'  => 'text',
			),
			'mode'              => array(
				'title'   => __( 'Mode', "paypal-brasil-para-woocommerce" ),
				'type'    => 'select',
				'options' => array(
					'live'    => __( 'Live', "paypal-brasil-para-woocommerce" ),
					'sandbox' => __( 'Sandbox', "paypal-brasil-para-woocommerce" ),
				),
			),
			'client_live'       => array(
				'title' => __( 'Client (live)', "paypal-brasil-para-woocommerce" ),
				'type'  => 'text',
			),
			'client_sandbox'    => array(
				'title' => __( 'Client (sandbox)', "paypal-brasil-para-woocommerce" ),
				'type'  => 'text',
			),
			'secret_live'       => array(
				'title' => __( 'Secret (live)', "paypal-brasil-para-woocommerce" ),
				'type'  => 'text',
			),
			'secret_sandbox'    => array(
				'title' => __( 'Secret (sandbox)', "paypal-brasil-para-woocommerce" ),
				'type'  => 'text',
			),
			'format'            => array(
				'title'   => __( 'Format', "paypal-brasil-para-woocommerce" ),
				'type'    => 'select',
				'label'   => __( 'Enable', "paypal-brasil-para-woocommerce" ),
				'options' => array(
					'rect' => __( 'Rectangle', "paypal-brasil-para-woocommerce" ),
					'pill' => __( 'Rounded', "paypal-brasil-para-woocommerce" ),
				),
				'default' => 'rect',
			),
			'color'             => array(
				'title'   => __( 'Color', "paypal-brasil-para-woocommerce" ),
				'type'    => 'select',
				'label'   => __( 'Enable', "paypal-brasil-para-woocommerce" ),
				'options' => array(
					'blue'   => __( 'Blue', "paypal-brasil-para-woocommerce" ),
					'gold'   => __( 'Gold', "paypal-brasil-para-woocommerce" ),
					'silver' => __( 'Silver', "paypal-brasil-para-woocommerce" ),
					'white'  => __( 'White', "paypal-brasil-para-woocommerce" ),
					'black'  => __( 'Black', "paypal-brasil-para-woocommerce" ),
				),
				'default' => 'blue',
			),
			'shortcut_enabled'  => array(
				'title'   => __( 'Enable/Disable', "paypal-brasil-para-woocommerce" ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable', "paypal-brasil-para-woocommerce" ),
				'default' => 'yes',
			),
			'reference_enabled' => array(
				'title'   => __( 'Enable/Disable', "paypal-brasil-para-woocommerce" ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable', "paypal-brasil-para-woocommerce" ),
				'default' => 'no',
			),
			'debug'             => array(
				'title'   => __( 'Enable/Disable', "paypal-brasil-para-woocommerce" ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable', "paypal-brasil-para-woocommerce" ),
				'default' => 'no',
			),
			'invoice_id_prefix' => array(
				'title'       => __( 'Prefix in the order number', "paypal-brasil-para-woocommerce" ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Add a prefix to the order number, this is useful for identifying you when you have more than one store processing through PayPal.',
					"paypal-brasil-para-woocommerce" ),
			),
		);
	}

	/**
	 * Check if is first load of this class.
	 * This should prevent add double hooks.
	 *
	 * @return bool
	 */
	private function is_first_load() {
		return ! self::$instance;
	}

	/**
	 * Check if the try of make a shortcut request is invalid.
	 * @return bool|string
	 */
	private function is_invalid_shortcut_session() {
		// Check if is a ajax request first.
		if ( is_ajax() ) {
			$post_data      = $this->get_posted_data();
			$review_payment = isset( $post_data['paypal-brasil-shortcut-review-payment'] ) ? sanitize_text_field( $post_data['paypal-brasil-shortcut-review-payment'] ) : '';
			$pay_id         = isset( $post_data['paypal-brasil-shortcut-pay-id'] ) ? sanitize_text_field( $post_data['paypal-brasil-shortcut-pay-id'] ) : '';
			$payer_id       = isset( $post_data['paypal-brasil-shortcut-payer-id'] ) ? sanitize_text_field( $post_data['paypal-brasil-shortcut-payer-id'] ) : '';
		} else {
			$review_payment = isset( $_GET['paypal-brasil-shortcut-review-payment'] ) ? sanitize_text_field( $_GET['paypal-brasil-shortcut-review-payment'] ) : '';
			$pay_id         = isset( $_GET['paypal-brasil-shortcut-pay-id'] ) ? sanitize_text_field( $_GET['paypal-brasil-shortcut-pay-id'] ) : '';
			$payer_id       = isset( $_GET['paypal-brasil-shortcut-payer-id'] ) ? sanitize_text_field( $_GET['paypal-brasil-shortcut-payer-id'] ) : '';
		}

		if ( ! $review_payment ) {
			return 'missing_review_payment';
		} elseif ( ! $payer_id ) {
			return 'missing_payer_id';
		} elseif ( ! $pay_id ) {
			return 'missing_pay_id';
		}

		$session = WC()->session->get( 'paypal-brasil-spb-shortcut-data' );
		if ( ! $session || $session['pay_id'] !== $pay_id ) {
			return 'invalid_pay_id';
		}

		return false;
	}

	/**
	 * Check if is shortcut and also overriding address.
	 * @return bool
	 */
	private function is_shortcut_override_address() {
		// Check if is $_GET.
		if ( $this->is_processing_shortcut() && isset( $_GET['override-address'] ) && $_GET['override-address'] ) {
			return true;
		}

		// Check if is $_POST (fragments)
		if ( $post_data = $this->get_posted_data() ) {
			if ( isset( $post_data['paypal-brasil-shortcut-override-address'] ) && $post_data['paypal-brasil-shortcut-override-address'] ) {
				return true;
			}
		}

		// Check if is $_POST (checkout)
		if ( isset( $_POST['paypal-brasil-shortcut-override-address'] ) && $_POST['paypal-brasil-shortcut-override-address'] ) {
			return true;
		}

		// It isn't, so we can return false.
		return false;
	}

	/**
	 * Get the posted data in $_POST['post_data'].
	 * @return array
	 */
	private function get_posted_data() {
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );

			return $post_data;
		}

		return array();
	}

	/**
	 * Check if we are processing spb checkout.
	 * Will check if is not processing reference transaction or shortcut, so it's spb.
	 * @return bool
	 */
	private function is_processing_spb() {
		return ! $this->is_processing_reference_transaction() && ! $this->is_processing_shortcut();
	}

	public function before_process_admin_options() {
		// Check first if is enabled
		$enabled = $this->get_field_value( 'enabled', $this->form_fields['enabled'] );
		if ( $enabled !== 'yes' ) {
			return;
		}

		// update credentials
		$this->update_credentials();

		// validate credentials
		$this->validate_credentials();

		// validate billing agreement.
		$this->validate_billing_agreement();

		// create webhooks
		$this->create_webhooks();
	}

	/**
	 * Validate the billing agreement.
	 */
	private function validate_billing_agreement() {
		try {
			$this->api->create_billing_agreement_token();
			update_option( $this->get_option_key() . '_reference_transaction_validator', 'yes' );
		}
		catch ( PayPal_Brasil_API_Exception $ex ) {
			$data = $ex->getData();
			if ( isset( $data['name'] ) && $data['name'] === 'AUTHORIZATION_ERROR'
			     && isset( $data['details'] ) && $data['details'][0]['name'] === 'REFUSED_MARK_REF_TXN_NOT_ENABLED' ) {
				update_option( $this->get_option_key() . '_reference_transaction_validator', 'no' );
			}
		}
		catch ( Exception $ex ) {
			update_option( $this->get_option_key() . '_reference_transaction_validator', 'no' );
		}
	}

	/**
	 * Populate checkout fields if is running shortcut override address.
	 *
	 * @param $input
	 * @param $key
	 *
	 * @return string
	 */
	public function pre_populate_shortcut_fields( $input, $key ) {

		$session = WC()->session->get( 'paypal_brasil_shortcut_payer_info' );

		if ( $session ) {
			switch ( $key ) {
				case 'billing_first_name':
					return paypal_brasil_explode_name( $session['shipping_name'] )['first_name'];
				case 'billing_last_name':
					return paypal_brasil_explode_name( $session['shipping_name'] )['last_name'];
				case 'billing_persontype':
					return $session['persontype'];
				case 'billing_cpf':
					return $session['cpf'];
				case 'billing_cnpj':
					return $session['cnpj'];
				case 'billing_company':
					return $session['company'];
				case 'billing_country':
					return $session['country'];
				case 'billing_state':
					return $session['state'];
				case 'billing_city':
					return $session['city'];
				case 'billing_postcode':
					return $session['postcode'];
				case 'billing_email':
					return $session['email'];
				case 'billing_number':
				case 'billing_address_1':
				case 'billing_address_2';
				case 'billing_neighborhood':
					return '';
			}
		}

		return $input;
	}

	/**
	 * Check if is processing shortcut and if is a valid process.
	 *
	 * @return bool
	 */
	private function is_processing_shortcut() {
		// If shortcut is not enabled, we can say it's not.
		if ( $this->shortcut_enabled !== 'yes' ) {
			return false;
		}

		// Check if is $_GET
		if ( isset( $_GET['review-payment'] ) && $_GET['review-payment']
		     && isset( $_GET['payer-id'] ) && $_GET['payer-id']
		     && isset( $_GET['pay-id'] ) && $_GET['pay-id'] ) {
			return true;
		}

		// Check if is $_POST (fragments)
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );

			if ( isset( $post_data['paypal-brasil-shortcut-review-payment'] ) && $post_data['paypal-brasil-shortcut-review-payment']
			     && isset( $post_data['paypal-brasil-shortcut-payer-id'] ) && $post_data['paypal-brasil-shortcut-payer-id']
			     && isset( $post_data['paypal-brasil-shortcut-pay-id'] ) && $post_data['paypal-brasil-shortcut-pay-id'] ) {
				return true;
			}
		}

		// Check if is $_POST (checkout)
		if ( isset( $_POST['paypal-brasil-shortcut-review-payment'] ) && $_POST['paypal-brasil-shortcut-review-payment']
		     && isset( $_POST['paypal-brasil-shortcut-payer-id'] ) && $_POST['paypal-brasil-shortcut-payer-id']
		     && isset( $_POST['paypal-brasil-shortcut-pay-id'] ) && $_POST['paypal-brasil-shortcut-pay-id'] ) {
			return true;
		}

		return false;
	}

	public function is_reference_enabled() {
		return $this->reference_enabled === 'yes' && $this->is_reference_transaction_credentials_validated() && paypal_brasil_wc_settings_valid();
	}

	private function is_reference_transaction() {
		if ( ! $this->is_reference_enabled() || $this->is_processing_shortcut() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if is processing reference transaction.
	 * @return bool
	 */
	private function is_processing_reference_transaction() {
		// We can't process if is not enabled or user is not logged in.
		if ( ! $this->is_reference_transaction() ) {
			return false;
		}

		// If posted a billing agreement, we are processing.
		if ( isset( $_POST['paypal_brasil_billing_agreement'] ) && $_POST['paypal_brasil_billing_agreement'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Filter shortcut data to add fields information sent by PayPal.
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function shortcut_filter_posted_data( $data ) {
		if ( $this->is_processing_shortcut() ) {
			$payer_info = WC()->session->get( 'paypal_brasil_shortcut_payer_info' );

			if ( isset( $payer_info ) ) {
				$data['billing_first_name']   = $payer_info['first_name'];
				$data['billing_last_name']    = $payer_info['last_name'];
				$data['billing_persontype']   = $payer_info['persontype'] === '1' ? '1' : '2';
				$data['billing_cpf']          = $payer_info['cpf'];
				$data['billing_company']      = $payer_info['company'];
				$data['billing_cnpj']         = $payer_info['cnpj'];
				$data['billing_country']      = $payer_info['country'];
				$data['billing_postcode']     = $payer_info['postcode'];
				$data['billing_address_1']    = $payer_info['address_line_1'];
				$data['billing_number']       = '';
				$data['billing_neighborhood'] = '';
				$data['billing_city']         = $payer_info['city'];
				$data['billing_state']        = $payer_info['state'];
				$data['billing_email']        = $payer_info['email'];

				$data['shipping_first_name']   = $payer_info['shipping_name'];
				$data['shipping_company']      = $payer_info['company'];
				$data['shipping_country']      = $payer_info['country'];
				$data['shipping_postcode']     = $payer_info['postcode'];
				$data['shipping_address_1']    = $payer_info['address_line_1'];
				$data['shipping_number']       = '';
				$data['shipping_neighborhood'] = '';
				$data['shipping_city']         = $payer_info['city'];
				$data['shipping_state']        = $payer_info['state'];
			}
		}

		return $data;
	}

	public function create_billing_agreement( $input_data ) {
		parse_str( $input_data, $post_data );

		// If we sent a billing agreement token, we should create this token to the user.
		// For safety, check the user session.
		$session_billing_agreement_token = WC()->session->get( 'paypal_brasil_billing_agreement_token' );
		// The checkbox should have empty value, otherwise the billing agreement is selected.
		if ( empty( $post_data['paypal_brasil_billing_agreement'] ) ) {
			// If we have the billing agreement token, we should create the billing agreement.
			if ( ! empty( $post_data['paypal_brasil_billing_agreement_token'] ) ) {
				if ( ! $session_billing_agreement_token || $session_billing_agreement_token !== $post_data['paypal_brasil_billing_agreement_token'] ) {
					// This means something happened with user session and billing agreement token doesn't match.
					wc_add_notice( __( 'There was a problem verifying the payment settlement token session.',
						"paypal-brasil-para-woocommerce" ), 'error' );
				} else {
					try {
						// Create the billing agreement.
						$billing_agreement = $this->api->create_billing_agreement( $post_data['paypal_brasil_billing_agreement_token'] );
						// Save the billing agreement to the user.
						if ( is_user_logged_in() ) {
							update_user_meta( get_current_user_id(), 'paypal_brasil_billing_agreement_id',
								$billing_agreement['id'] );
							update_user_meta( get_current_user_id(), 'paypal_brasil_billing_agreement_payer_info',
								$billing_agreement['payer']['payer_info'] );
						} else {
							WC()->session->set( 'paypal_brasil_billing_agreement_id', $billing_agreement['id'] );
							WC()->session->set( 'paypal_brasil_billing_agreement_payer_info',
								$billing_agreement['payer']['payer_info'] );
						}
					}
					catch ( PayPal_Brasil_API_Exception $ex ) {
						// Some problem happened creating billing agreement.
						wc_add_notice( __( 'There was an error creating the payment authorization.',
							"paypal-brasil-para-woocommerce" ), 'error' );
					}
				}
			} else {
				// We don't have the billing agreement and also don't have the token, something wrong
				// Probably the user updated the cart without opening lightbox, so do nothing.
			}

			// At the end, clean the token session.
			unset( WC()->session->paypal_brasil_billing_agreement_token );
		}
	}

	/**
	 * Change the page title for shortcut.
	 *
	 * @param $title
	 * @param $id
	 *
	 * @return string
	 */
	public function filter_review_title( $title, $id ) {
		if ( $id === wc_get_page_id( 'checkout' ) ) {
			return __( 'Payment Review', "paypal-brasil-para-woocommerce" );
		}

		return $title;
	}

	public function shortcut_before_checkout_fields() {
		include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/checkout/shortcut-before-checkout-fields.php';
	}

	/**
	 * Add hidden fields to store params data to get when fragments is refreshed.
	 */
	public function shortcut_checkout_fields() {
		include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/checkout/shortcut-checkout-fields.php';
	}

	/**
	 * Allow only the current gateway in checkout.
	 *
	 * @param $gateways
	 *
	 * @return mixed
	 */
	public function filter_gateways( $gateways ) {
		foreach ( $gateways as $key => $gateway ) {
			if ( $key !== $this->id ) {
				unset( $gateways[ $key ] );
			}
		}

		return $gateways;
	}

	/**
	 * On shortcut remove billing fields.
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function remove_billing_fields( $fields ) {
		unset( $fields['billing_first_name'] );
		unset( $fields['billing_last_name'] );
		unset( $fields['billing_persontype'] );
		unset( $fields['billing_cpf'] );
		unset( $fields['billing_cnpj'] );
		unset( $fields['billing_company'] );
		unset( $fields['billing_country'] );
		unset( $fields['billing_address_1'] );
		unset( $fields['billing_number'] );
		unset( $fields['billing_address_2'] );
		unset( $fields['billing_neighborhood'] );
		unset( $fields['billing_city'] );
		unset( $fields['billing_state'] );
		unset( $fields['billing_postcode'] );
		unset( $fields['billing_email'] );

		return $fields;
	}

	/**
	 * On shortcut remove shipping fields.
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function remove_shipping_fields( $fields ) {
		unset( $fields['shipping_first_name'] );
		unset( $fields['shipping_last_name'] );
		unset( $fields['shipping_company'] );
		unset( $fields['shipping_country'] );
		unset( $fields['shipping_address_1'] );
		unset( $fields['shipping_number'] );
		unset( $fields['shipping_address_2'] );
		unset( $fields['shipping_neighborhood'] );
		unset( $fields['shipping_city'] );
		unset( $fields['shipping_state'] );
		unset( $fields['shipping_postcode'] );

		return $fields;
	}

	/**
	 * Trigger a script in mini cart to alert our JS when mini cart is updated.
	 */
	public function trigger_mini_cart_update() {
		echo '<script>jQuery("body").trigger("updated_mini_cart");</script>';
	}

	/**
	 * Render shortcut button in cart.
	 */
	public function shortcut_button_cart() {
		echo '<div class="shortcut-button"></div>';
	}

	/**
	 * Render shortcut button in mini cart.
	 */
	public function shortcut_button_mini_cart() {
		if ( ! WC()->cart->is_empty() ) {
			echo '<div class="shortcut-button-mini-cart"></div>';
		}
	}

	/**
	 * Clear SPB session data every time checkout fragments is updated.
	 * As checkout fragment is updated when some field is updated, we should
	 * render the button again to create a new payment with correct data.
	 * As we compare the session data when process the payment, we should check
	 * if is sending the last payment id.
	 *
	 * @param $fragments
	 *
	 * @return mixed
	 */
	public function clear_spb_session_data() {
		// Set payment_token to null. It's a security reason.
		WC()->session->set( 'paypal_brasil_spb_data', null );
	}

	/**
	 * Add a code before submit button to show and hide ours.
	 */
	public function html_before_submit_button() {
		echo '<div id="paypal-brasil-button-container"><div class="default-submit-button">';
	}

	/**
	 * Add a code after submit button to show and hide ours.
	 */
	public function html_after_submit_button() {
		echo '</div><!-- .default-submit-button -->';
		echo '<div class="paypal-submit-button"><div id="paypal-button"></div></div>';
		echo '</div><!-- #paypal-spb-container -->';
	}

	public function is_credentials_validated() {
		return get_option( $this->get_option_key() . '_validator' ) === 'yes';
	}

	public function is_reference_transaction_credentials_validated() {
		return get_option( $this->get_option_key() . '_reference_transaction_validator' ) === 'yes';
	}

	/**
	 * Get if gateway is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = ( 'yes' === $this->enabled );

		if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
			$is_available = false;
		}

		if ( ! $this->is_credentials_validated() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Clear all user session. This should be used after process payment.
	 * Will clean every session for all integrations, as we don't need that anymore.
	 */
	private function clear_all_sessions() {
		$sessions = array(
			'paypal-brasil-spb-data',
			'paypal-brasil-spb-shortcut-data',
			'paypal_brasil_billing_agreement_token',
			'paypal-brasil-spb-data',
			'paypal_brasil_shortcut_payer_info',
		);

		// Each session will be destroyed.
		foreach ( $sessions as $session ) {
			unset( WC()->session->{$session} );
		}
	}

	/**
	 * @param WC_order $order
	 *
	 * @return array
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	private function process_payment_shortcut( $order ) {
		$order_total = wc_add_number_precision( $order->get_total() );
		$order_shipping_total = wc_add_number_precision( $order->get_shipping_total() );

		$data = array(
			array(
				'op'    => 'replace',
				'path'  => '/transactions/0/amount',
				'value' => array(
					'total'    => paypal_format_amount( wc_remove_number_precision_deep( $order_total ) ),
					'currency' => $order->get_currency(),
					'details'  => array(
						'subtotal' => paypal_format_amount( wc_remove_number_precision_deep( $order_total - $order_shipping_total ) ),
						'shipping' => paypal_format_amount( wc_remove_number_precision_deep( $order_shipping_total ) ),
					),
				),
			),
			array(
				'op'    => 'add',
				'path'  => '/transactions/0/invoice_number',
				'value' => sprintf( '%s%s', $this->invoice_id_prefix, $order->get_id() ),
			),
		);

		// Path address if needed.
		if ( $this->is_shortcut_override_address() && ! paypal_brasil_is_order_only_digital( $order ) ) {
			$data[] = array(
				'op'    => 'replace',
				'path'  => '/transactions/0/item_list/shipping_address',
				'value' => paypal_brasil_get_shipping_address( $order ),
			);
		}

		$session  = WC()->session->get( 'paypal_brasil_spb_shortcut_data' );
		$payer_id = isset( $_POST['paypal-brasil-shortcut-payer-id'] ) ? sanitize_text_field( $_POST['paypal-brasil-shortcut-payer-id'] ) : '';

		// Execute API requests.
		$this->api->update_payment( $session['pay_id'], $data, array(), 'shortcut' );
		$response = $this->api->execute_payment( $session['pay_id'], $payer_id, array(), 'shortcut' );

		update_post_meta( $order->get_id(), 'paypal_brasil_execute_data', $response );
		update_post_meta( $order->get_id(), 'paypal_brasil_id', $response['id'] );
		update_post_meta( $order->get_id(), 'paypal_brasil_sale_id',
			$response['transactions'][0]['related_resources'][0]['sale']['id'] );

		$order->add_order_note( 'Pagamento processado pelo PayPal. ID da transação: ' . $response['transactions'][0]['related_resources'][0]['sale']['id'] );

		// Process the order.
		switch ( $response['transactions'][0]['related_resources'][0]['sale']['state'] ) {
			case 'completed';
				$order->payment_complete();
				break;
			case 'pending':
				wc_reduce_stock_levels( $order->get_id() );
				$order->update_status( 'on-hold',
					__( 'Payment is under review by PayPal.', "paypal-brasil-para-woocommerce" ) );
				break;
		}

		// Check if user isn't logged in.
		if ( ! is_user_logged_in() ) {
			try {
				// We should create or associate the user.
				$user_email = $order->get_billing_email();
				$user       = get_user_by( 'email', $user_email );

				// Create new user if doesn't exists.
				if ( ! $user ) {
					$user_password = wp_generate_password();
					$user_username = wc_create_new_customer_username( $user_email, array(
						'first_name' => $order->get_billing_first_name(),
						'last_name'  => $order->get_billing_last_name(),
					) );
					$user_id       = wc_create_new_customer( $user_email, $user_username, $user_password );
					$user          = get_user_by( 'id', $user_id );
				}

				$order->set_customer_id( $user->ID );
				$order->save();
			}
			catch ( Exception $ex ) {
				// do nothing
			}
		}

		// Clear all sessions for this order.
		$this->clear_all_sessions();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	private function process_payment_reference_transaction( $order ) {
		$installment = isset( $_POST['paypal_brasil_billing_agreement_installment'] ) ? json_decode( stripslashes( $_POST['paypal_brasil_billing_agreement_installment'] ),
			true ) : array();

		$uuid = isset( $_POST['paypal-brasil-uuid'] ) ? sanitize_text_field( $_POST['paypal-brasil-uuid'] ) : '';

		// Check if we got uuid.
		if ( ! $uuid ) {
			wc_add_notice( __( 'There was a problem verifying the fraudnet token.',
				"paypal-brasil-para-woocommerce" ), 'error' );

			return;
		}

		// Check if is only digital items.
		$only_digital_items = paypal_brasil_is_order_only_digital( $order );

		// Try to get billing agreement from session.
		$billing_agreement_id = WC()->session->get( 'paypal_brasil_billing_agreement_id' );

		// If it's not in session, try to get from user meta.
		if ( ! $billing_agreement_id ) {
			$billing_agreement_id = get_user_meta( get_current_user_id(), 'paypal_brasil_billing_agreement_id', true );
		}

		$order_total = wc_add_number_precision( $order->get_total() );
		$order_shipping_total = wc_add_number_precision( $order->get_shipping_total() );

		$data = array(
			'intent'              => 'sale',
			'payer'               => array(
				'payment_method'      => 'paypal',
				'funding_instruments' => array(
					array(
						'billing' => array(
							'billing_agreement_id'        => $billing_agreement_id,
							'selected_installment_option' => $installment,
						),
					),
				),
			),
			'application_context' => array(
				'brand_name'          => get_bloginfo( 'name' ),
				'shipping_preference' => $only_digital_items ? 'NO_SHIPPING' : 'SET_PROVIDED_ADDRESS',
			),
			'transactions'        => array(
				array(
					'amount'         => array(
						'currency' => $order->get_currency(),
						'total'    => paypal_format_amount( wc_remove_number_precision_deep( $order_total ) ),
						'details'  => array(
							'shipping' => paypal_format_amount( wc_remove_number_precision_deep( $order_shipping_total ) ),
							'subtotal' => paypal_format_amount( wc_remove_number_precision_deep( $order_total - $order_shipping_total ) ),
						),
					),
					'item_list'      => array(
						'items' => array(
							array(
								'name'     => sprintf( __( 'Store order %s', "paypal-brasil-para-woocommerce" ),
									get_bloginfo( 'name' ) ),
								'currency' => get_woocommerce_currency(),
								'quantity' => 1,
								'price'    => paypal_format_amount( wc_remove_number_precision_deep( $order_total - $order_shipping_total ) ),
								'sku'      => 'order-items',
							)
						),
					),
					'description'    => sprintf( __( 'Order payment #%s at %s shop',
						"paypal-brasil-para-woocommerce" ), $order->get_id(), get_bloginfo( 'name' ) ),
					'invoice_number' => sprintf( '%s%s', $this->invoice_id_prefix, $order->get_id() ),
				),
			),
			'redirect_urls'       => array(
				'return_url' => home_url(),
				'cancel_url' => home_url(),
			),
		);

		// Add shipping address for non digital goods
		if ( ! $only_digital_items ) {
			$data['transactions'][0]['item_list']['shipping_address'] = paypal_brasil_get_shipping_address( $order );
		}

		// Make API request.
		$response = $this->api->create_payment( $data, array( 'PAYPAL-CLIENT-METADATA-ID' => $uuid ), 'reference' );

		// If has discount, add to order information.
		if ( $discount_value = floatval( $installment['discount_amount']['value'] ) ) {
			$discount = new WC_Order_Item_Fee();
			$discount->set_amount( - $discount_value );
			$discount->set_total( - $discount_value );
			$discount->set_name( sprintf( __( 'PayPal discount (%d%%)', "paypal-brasil-para-woocommerce" ),
				floatval( $installment['discount_percentage'] ) ) );
			$discount->save();

			$order->add_item( $discount );
			$order->calculate_totals();
			$order->save();
		}

		// Process the order.
		switch ( $response['transactions'][0]['related_resources'][0]['sale']['state'] ) {
			case 'completed';
				$order->payment_complete();
				break;
			case 'pending':
				wc_reduce_stock_levels( $order->get_id() );
				$order->update_status( 'on-hold',
					__( 'Payment is under review by PayPal.', "paypal-brasil-para-woocommerce" ) );
				break;
		}

		try {
			$billing_agreement_id_session = WC()->session->get( 'paypal_brasil_billing_agreement_id' );

			// Only do that if billing agreement is from session.
			if ( $billing_agreement_id_session ) {
				update_user_meta( get_current_user_id(), 'paypal_brasil_billing_agreement_id',
					$billing_agreement_id_session );
				update_user_meta( get_current_user_id(), 'paypal_brasil_billing_agreement_payer_info',
					WC()->session->get( 'paypal_brasil_billing_agreement_payer_info' ) );

				unset( WC()->session->paypal_brasil_billing_agreement_id );
				unset( WC()->session->paypal_brasil_billing_agreement_payer_info );
			}
		}
		catch ( Exception $ex ) {
			// do nothing
		}

		update_post_meta( $order->get_id(), 'paypal_brasil_execute_data', $response );
		update_post_meta( $order->get_id(), 'paypal_brasil_id', $response['id'] );
		update_post_meta( $order->get_id(), 'paypal_brasil_sale_id',
			$response['transactions'][0]['related_resources'][0]['sale']['id'] );

		$order->add_order_note( 'Payment processed by PayPal. Transaction ID: ' . $response['transactions'][0]['related_resources'][0]['sale']['id'] );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	private function process_payment_spb( $order ) {
		$spb_payer_id = sanitize_text_field( $_POST['paypal-brasil-spb-payer-id'] );
		$spb_pay_id   = sanitize_text_field( $_POST['paypal-brasil-spb-pay-id'] );

		$data = array(
			array(
				'op'    => 'add',
				'path'  => '/transactions/0/invoice_number',
				'value' => sprintf( '%s%s', $this->invoice_id_prefix, $order->get_id() ),
			),
		);

		// Execute API requests.
		$this->api->update_payment( $spb_pay_id, $data, array(), 'ec' );
		$response = $this->api->execute_payment( $spb_pay_id, $spb_payer_id, array(), 'ec' );

		// Process the order.
		switch ( $response['transactions'][0]['related_resources'][0]['sale']['state'] ) {
			case 'completed';
				$order->payment_complete();
				break;
			case 'pending':
				wc_reduce_stock_levels( $order->get_id() );
				$order->update_status( 'on-hold',
					__( 'Payment is under review by PayPal.', "paypal-brasil-para-woocommerce" ) );
				break;
		}

		update_post_meta( $order->get_id(), 'paypal_brasil_execute_data', $response );
		update_post_meta( $order->get_id(), 'paypal_brasil_id', $response['id'] );
		update_post_meta( $order->get_id(), 'paypal_brasil_sale_id',
			$response['transactions'][0]['related_resources'][0]['sale']['id'] );

		$order->add_order_note( 'Payment processed by PayPal. Transaction ID: ' . $response['transactions'][0]['related_resources'][0]['sale']['id'] );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Process gateway payment for a given order ID.
	 *
	 * @param $order_id
	 *
	 * @return array
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		try {
			if ( $this->is_processing_shortcut() ) {
				return $this->process_payment_shortcut( $order );
			} elseif ( $this->is_processing_reference_transaction() ) {
				return $this->process_payment_reference_transaction( $order );
			} elseif ( $this->is_processing_spb() ) {
				return $this->process_payment_spb( $order );
			} else {
				wc_add_notice( __( 'The payment method was not correctly detected. Please try again.',
					"paypal-brasil-para-woocommerce" ), 'error' );
			}
		} catch ( PayPal_Brasil_API_Exception $ex ) {
			$data = $ex->getData();

			switch ( $data['name'] ) {
				// Repeat the execution
				case 'INTERNAL_SERVICE_ERROR':
					wc_add_notice( __( 'An unexpected error occurred, please try again. If the error persists, please contact us. (#01)',
						"paypal-brasil-para-woocommerce" ), 'error' );
					break;
				case 'VALIDATION_ERROR':
					wc_add_notice( __( 'An unexpected error occurred, please try again. If the error persists, please contact us. (#12)',
						"paypal-brasil-para-woocommerce" ), 'error' );
					break;
				case 'PAYMENT_ALREADY_DONE':
					wc_add_notice( __( 'A payment already exists for this order.', "paypal-brasil-para-woocommerce" ),
						'error' );
					break;
				default:
					wc_add_notice( __( 'Your payment was not approved, please try again.',
						"paypal-brasil-para-woocommerce" ), 'error' );
					break;
			}
			WC()->session->set( 'refresh_totals', true );
		}
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$sale_id = get_post_meta( $order_id, 'paypal_brasil_sale_id', true );
		// Check if the amount is bigger than zero
		if ( $amount <= 0 ) {
			$min_price = number_format( 0, wc_get_price_decimals(), wc_get_price_decimal_separator(),
				wc_get_price_thousand_separator() );

			return new WP_Error( 'error',
				sprintf( __( 'The refund cannot be less than %s.', "paypal-brasil-para-woocommerce" ),
					html_entity_decode( get_woocommerce_currency_symbol() ) . $min_price ) );
		}
		// Check if we got the sale ID
		if ( $sale_id ) {
			try {
				$refund_sale = $this->api->refund_payment( $sale_id, paypal_brasil_money_format( $amount ),
					get_woocommerce_currency() );
				// Check the result success.
				if ( $refund_sale['state'] === 'completed' ) {
					return true;
				} else {
					return new WP_Error( 'error', $refund_sale->getReason() );
				}
			}
			catch ( PayPal_Brasil_API_Exception $ex ) { // Catch any PayPal error.
				$data = $ex->getData();

				return new WP_Error( 'error', $data['message'] );
			}
			catch ( Exception $ex ) {
				return new WP_Error( 'error',
					__( 'There was an error trying to make a refund.', "paypal-brasil-para-woocommerce" ) );
			}
		} else { // If we don't have the PayPal sale ID.
			return new WP_Error( 'error', sprintf( __( 'It looks like you don\'t have a request for a refund.',
				"paypal-brasil-para-woocommerce" ) ) );
		}
	}

	/**
	 * Frontend Payment Fields.
	 */
	public function payment_fields() {
		if ( $this->is_processing_shortcut() ) {
			echo __('Ready! Your PayPal account is already enabled for this payment. Review your order information and complete your purchase.',"paypal-brasil-para-woocommerce");
		} elseif ( $this->is_reference_transaction() ) {
			include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/checkout/reference-transaction-html-fields.php';
		} else {
			include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/checkout/spb-checkout-fields.php';
		}
	}

	/**
	 * Backend view for admin options.
	 */
	public function admin_options() {
		include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/admin-options/admin-options-spb/admin-options-spb.php';
	}

	private function get_fields_values() {
		return array(
			'enabled'              => $this->enabled,
			'shortcut_enabled'     => $this->shortcut_enabled,
			'reference_enabled'    => $this->reference_enabled,
			'mode'                 => $this->mode,
			'client'               => array(
				'live'    => $this->client_live,
				'sandbox' => $this->client_sandbox,
			),
			'secret'               => array(
				'live'    => $this->secret_live,
				'sandbox' => $this->secret_sandbox,
			),
			'button'               => array(
				'format' => $this->format,
				'color'  => $this->color,
			),
			'title'                => $this->title,
			'title_complement'     => $this->title_complement,
			'invoice_id_prefix'    => $this->invoice_id_prefix,
			'debug'                => $this->debug,
			'woocommerce_settings' => array(
				'enable_checkout_login_reminder'        => get_option( 'woocommerce_enable_checkout_login_reminder' ),
				'enable_signup_and_login_from_checkout' => get_option( 'woocommerce_enable_signup_and_login_from_checkout' ),
				'enable_guest_checkout'                 => get_option( 'woocommerce_enable_guest_checkout' ),
			),
		);
	}

	/**
	 * Enqueue admin scripts for gateway settings page.
	 */
	public function admin_scripts() {
		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$wc_screen_id   = sanitize_title( __( 'WooCommerce', "paypal-brasil-para-woocommerce" ) );
		$wc_settings_id = $wc_screen_id . '_page_wc-settings';

		// Check if we are on the gateway settings page.
		if ( $wc_settings_id === $screen_id && isset( $_GET['section'] ) && $_GET['section'] === $this->id ) {

			// Add shared file if exists.
			if ( file_exists( dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/assets/dist/js/shared.js' ) ) {
				wp_enqueue_script( 'paypal_brasil_admin_options_shared',
					plugins_url( 'assets/dist/js/shared.js', PAYPAL_PAYMENTS_MAIN_FILE ), array(),
					PAYPAL_PAYMENTS_VERSION, true );
			}

			// Enqueue admin options and localize settings.
			wp_enqueue_script( $this->id . '_script',
				plugins_url( 'assets/dist/js/admin-options-spb.js', PAYPAL_PAYMENTS_MAIN_FILE ), array(),
				PAYPAL_PAYMENTS_VERSION, true );
			wp_localize_script( $this->id . '_script', 'paypal_brasil_admin_options_spb', array(
				'template'             => $this->get_admin_options_template(),
				'enabled'              => $this->enabled,
				'shortcut_enabled'     => $this->shortcut_enabled,
				'reference_enabled'    => $this->reference_enabled,
				'mode'                 => $this->mode,
				'client'               => array(
					'live'    => $this->client_live,
					'sandbox' => $this->client_sandbox,
				),
				'secret'               => array(
					'live'    => $this->secret_live,
					'sandbox' => $this->secret_sandbox,
				),
				'button'               => array(
					'format' => $this->format,
					'color'  => $this->color,
				),
				'title'                => $this->title,
				'title_complement'     => $this->title_complement,
				'invoice_id_prefix'    => $this->invoice_id_prefix,
				'debug'                => $this->debug,
				'images_path'          => plugins_url( 'assets/images/buttons', PAYPAL_PAYMENTS_MAIN_FILE ),
				'woocommerce_settings' => array(
					'enable_checkout_login_reminder'        => get_option( 'woocommerce_enable_checkout_login_reminder' ),
					'enable_signup_and_login_from_checkout' => get_option( 'woocommerce_enable_signup_and_login_from_checkout' ),
					'enable_guest_checkout'                 => get_option( 'woocommerce_enable_guest_checkout' ),
				),
			) );

			wp_enqueue_style( $this->id . '_style',
				plugins_url( 'assets/dist/css/admin-options-spb.css', PAYPAL_PAYMENTS_MAIN_FILE ), array(),
				PAYPAL_PAYMENTS_VERSION, 'all' );

		}
	}

	/**
	 * Get the admin options template to render by Vue.
	 */
	private function get_admin_options_template() {
		ob_start();
		include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/admin-options/admin-options-spb/admin-options-spb-template.php';

		return ob_get_clean();
	}

	/**
	 * Check if shortcut is enabled.
	 * @return bool
	 */
	private function is_shortcut_enabled() {
		return $this->shortcut_enabled === 'yes';
	}

	public static function get_uuid() {
		if ( ! self::$uuid ) {
			self::$uuid = md5( uniqid( rand(), true ) );
		}

		return self::$uuid;
	}

	public function fraudnet_script() {
		if ( $this->is_reference_transaction() ) {
			$token = $this->get_uuid();
			echo '<script type="application/json" fncls="fnparams-dede7cc5-15fd-4c75-a9f4-36c430ee3a99">{"f":"' . $token . '", "s":"WOO_WOOCOMMERCEBRAZIL_RT_PYMNT"}</script>';
			echo '<script type="text/javascript" src="https://c.paypal.com/da/r/fb.js"></script>';
		}
	}

	/**
	 * @param $order WC_Order
	 *
	 * @return array
	 * @throws PayPal_Brasil_API_Exception
	 * @throws PayPal_Brasil_Connection_Exception
	 */
	public function create_spb_ec_for_order( $order ) {
		$only_digital_items = paypal_brasil_is_order_only_digital( $order );

		$order_total = wc_add_number_precision_deep( $order->get_total() );
		$order_shipping_total = wc_add_number_precision_deep( $order->get_shipping_total() );

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
								'name'     => sprintf( __( 'Store order %s', "paypal-brasil-para-woocommerce" ),
									get_bloginfo( 'name' ) ),
								'currency' => get_woocommerce_currency(),
								'quantity' => 1,
								'price'    => paypal_format_amount( wc_remove_number_precision_deep( $order_total - $order_shipping_total ) ),
								'sku'      => 'order-items',
							)
						),
					),
					'amount'          => array(
						'currency' => $order->get_currency(),
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
			'shipping' => paypal_format_amount( wc_remove_number_precision_deep( $order_shipping_total ) ),
			'subtotal' => paypal_format_amount( wc_remove_number_precision_deep( $order_total - $order_shipping_total ) ),
		);

		// Set total Total
		$data['transactions'][0]['amount']['total'] = paypal_format_amount( wc_remove_number_precision_deep( $order_total ) );

		// Prepare address
		$address_line_1 = array();
		$address_line_2 = array();

		if ( $order->get_shipping_address_1() ) {
			$address_line_1[] = $order->get_shipping_address_1();
		}

		if ( $number = get_post_meta( $order->get_id(), 'shipping_number', true ) ) {
			$address_line_1[] = $number;
		}

		if ( $neighborhood = get_post_meta( $order->get_id(), 'shipping_neighborhood', true ) ) {
			$addres_line_2[] = $neighborhood;
		}

		if ( $order->get_shipping_address_2() ) {
			$addres_line_2[] = $order->get_shipping_address_2();
		}

		// Prepare shipping address.
		$shipping_address = array(
			'recipient_name' => $order->get_formatted_shipping_full_name(),
			'country_code'   => $order->get_shipping_country(),
			'postal_code'    => $order->get_shipping_postcode(),
			'line1'          => mb_substr( implode( ', ', $address_line_1 ), 0, 100 ),
			'city'           => $order->get_shipping_city(),
			'state'          => $order->get_shipping_state(),
			'phone'          => $order->get_billing_phone(),
		);

		// If is anything on address line 2, add to shipping address.
		if ( $address_line_2 ) {
			$shipping_address['line2'] = mb_substr( implode( ', ', $address_line_2 ), 0, 100 );
		}

		// Add shipping address for non digital goods
		if ( ! $only_digital_items ) {
			$data['transactions'][0]['item_list']['shipping_address'] = $shipping_address;
		}

		// Set the application context
		$data['application_context'] = array(
			'brand_name'          => get_bloginfo( 'name' ),
			'shipping_preference' => $only_digital_items ? 'NO_SHIPPING' : 'SET_PROVIDED_ADDRESS',
		);

		// Create the payment in API.
		$create_payment = $this->api->create_payment( $data, array(), 'ec' );

		// Get the response links.
		$links = $this->api->parse_links( $create_payment['links'] );

		// Extract EC token from response.
		preg_match( '/(EC-\w+)/', $links['approval_url'], $ec_token );

		// Separate data.
		$data = array(
			'pay_id' => $create_payment['id'],
			'ec'     => $ec_token[0],
		);

		// Store the requested data in session.
		WC()->session->set( 'paypal_brasil_spb_data', $data );

		return $data;
	}

	/**
	 * Enqueue scripts in checkout.
	 */
	public function checkout_scripts() {
		if ( ! $this->is_available() ) {
			return;
		}

		$enqueues  = array();
		$localizes = array();

		// PayPal SDK arguments.
		$paypal_args = array(
			'currency'        => get_woocommerce_currency(),
			'client-id'       => $this->get_client_id(),
			'commit'          => 'false',
			'locale'          => get_locale(),
			'disable-funding' => 'card',
			//			'disable-card' => 'amex,jcb,visa,discover,mastercard,hiper,elo',
		);

		// Enqueue shared.
		$enqueues[]  = array( 'underscore' );

		if( is_checkout() || $this->is_shortcut_enabled() ) {
			$enqueues[]  = array(
				'paypal-brasil-shared',
				plugins_url( 'assets/dist/js/frontend-shared.js', PAYPAL_PAYMENTS_MAIN_FILE ),
				array(),
				PAYPAL_PAYMENTS_VERSION,
				true
			);
		}

		$localizes[] = array(
			'paypal-brasil-shared',
			'paypal_brasil_settings',
			array(
				'is_order_pay_page'         => is_checkout_pay_page(),
				'nonce'                     => wp_create_nonce( 'paypal-brasil-checkout' ),
				'is_reference_transaction'  => $this->is_reference_enabled(),
				'current_user_id'           => get_current_user_id(),
				'style'                     => array(
					'color'  => $this->color,
					'format' => $this->format,
				),
				'paypal_brasil_handler_url' => add_query_arg( array(
					'wc-api' => 'paypal_brasil_handler',
					'action' => '{ACTION}'
				), home_url() . '/' ),
				'checkout_page_url'         => wc_get_checkout_url(),
				'checkout_review_page_url'  => add_query_arg( array(
					'review-payment' => '1',
					'pay-id'         => '{PAY_ID}',
					'payer-id'       => '{PAYER_ID}',
				), wc_get_checkout_url() ),
			)
		);

		if( is_checkout() ) {
			if ( $this->is_reference_transaction() ) { // reference transaction checkout
				$paypal_args['vault'] = 'true';
				$enqueues[]           = array(
					'paypal-brasil-reference-transaction',
					plugins_url( 'assets/dist/js/frontend-reference-transaction.js', PAYPAL_PAYMENTS_MAIN_FILE ),
					array(),
					PAYPAL_PAYMENTS_VERSION,
					true
				);
				ob_start();
				wc_print_notice( __( 'You canceled the creation of the payment term.', "paypal-brasil-para-woocommerce" ),
					'error' );
				$cancel_message = ob_get_clean();

				$localizes[] = array(
					'paypal-brasil-reference-transaction',
					'paypal_brasil_reference_transaction_settings',
					array(
						'cancel_message' => $cancel_message,
						'uuid'           => $this->get_uuid(),
					)
				);

			} else if ( ! $this->is_processing_shortcut() ) { // spb checkout
				$enqueues[] = array(
					'paypal-brasil-spb',
					plugins_url( 'assets/dist/js/frontend-spb.js', PAYPAL_PAYMENTS_MAIN_FILE ),
					array(),
					PAYPAL_PAYMENTS_VERSION,
					true
				);

				ob_start();
				wc_print_notice( __( 'You canceled the payment.', "paypal-brasil-para-woocommerce" ), 'error' );
				$cancel_message = ob_get_clean();

				$localizes[] = array(
					'paypal-brasil-spb',
					'paypal_brasil_spb_settings',
					array(
						'cancel_message' => $cancel_message,
					)
				);
			}
		}

		// Shortcut
		if ( $this->enabled === 'yes' && $this->is_shortcut_enabled() ) {
			$enqueues[] = array(
				'paypal-brasil-shortcut',
				plugins_url( 'assets/dist/js/frontend-shortcut.js', PAYPAL_PAYMENTS_MAIN_FILE ),
				array(),
				PAYPAL_PAYMENTS_VERSION,
				true
			);
			wp_enqueue_style( 'paypal-brasil-shortcut',
				plugins_url( 'assets/dist/css/frontend-shortcut.css', PAYPAL_PAYMENTS_MAIN_FILE ), array(),
				PAYPAL_PAYMENTS_VERSION, 'all' );

			ob_start();
			wc_print_notice( __( 'You canceled the payment.', "paypal-brasil-para-woocommerce" ), 'error' );
			$cancel_message = ob_get_clean();

			$localizes[] = array(
				'paypal-brasil-shortcut',
				'paypal_brasil_shortcut_settings',
				array(
					'cancel_message' => $cancel_message,
				)
			);
		}

		if( is_checkout() || $this->is_shortcut_enabled() ) {
			wp_enqueue_script( 'paypal-brasil-scripts', add_query_arg( $paypal_args, 'https://www.paypal.com/sdk/js' ),
			array(), null, true );
		}

		foreach ( $enqueues as $enqueue ) {
			call_user_func_array( 'wp_enqueue_script', $enqueue );
		}

		foreach ( $localizes as $localize ) {
			call_user_func_array( 'wp_localize_script', $localize );
		}
	}

}