<?php

// Exit if runs outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force init gateways on load.
 */
function paypal_brasil_init_gateways_on_load() {
	if(class_exists( 'WC_Payment_Gateway' )) {
		new PayPal_Brasil_SPB_Gateway();
	}
}

add_action( 'wp', 'paypal_brasil_init_gateways_on_load' );

/**
 * Check if is only digital items.
 *
 * @param $order WC_Order
 *
 * @return bool
 */
function paypal_brasil_is_order_only_digital( $order ) {
	// Consider as always digital.
	$only_digital = true;

	/** @var WC_Order_Item $item */
	foreach ( $order->get_items() as $id => $item ) {
		// Get the product.
		$product = $item->get_variation_id() ? wc_get_product( $item->get_variation_id() ) : wc_get_product( $item->get_product_id() );

		// Check if product is not digital.
		if ( ! $product->is_virtual() ) {
			$only_digital = false;
			break;
		}
	}

	return $only_digital;
}

/**
 * Check if cart is only digital items.
 *
 * @return bool
 */
function paypal_brasil_is_cart_only_digital() {
	// Consider as always digital.
	$only_digital_items = true;

	/** @var WC_Order_Item $item */
	foreach ( WC()->cart->get_cart() as $id => $item ) {
		$product = $item['variation_id'] ? wc_get_product( $item['variation_id'] ) : wc_get_product( $item['product_id'] );

		// Check if product is not digital.
		if ( ! $product->is_virtual() ) {
			$only_digital_items = false;
		}
	}

	return $only_digital_items;
}

function paypal_brasil_get_order_data( $order ) {
	$shipping_address_1    = $order->get_shipping_address_1();
	$shipping_number       = get_post_meta( $order->get_id(), '_shipping_number', true );
	$shipping_neighborhood = get_post_meta( $order->get_id(), '_shipping_neighborhood', true );
	$shipping_address_2    = $order->get_shipping_address_2();
	$shipping_city         = $order->get_shipping_city();
	$shipping_state        = $order->get_shipping_state();
	$shipping_postcode     = $order->get_shipping_postcode();
	$shipping_country      = $order->get_shipping_country();
	$shipping_name         = trim( $order->get_formatted_shipping_full_name() );

	return array(
		'address_1'    => $shipping_address_1 ? $shipping_address_1 : $order->get_billing_address_1(),
		'address_2'    => $shipping_address_2 ? $shipping_address_2 : $order->get_billing_address_2(),
		'number'       => $shipping_number ? $shipping_number : get_post_meta( $order->get_id(), '_billing_number',
			true ),
		'neighborhood' => $shipping_neighborhood ? $shipping_neighborhood : get_post_meta( $order->get_id(),
			'_billing_neighborhood', true ),
		'city'         => $shipping_city ? $shipping_city : $order->get_billing_city(),
		'state'        => $shipping_state ? $shipping_state : $order->get_billing_state(),
		'postcode'     => $shipping_postcode ? $shipping_postcode : $order->get_billing_postcode(),
		'country'      => $shipping_country ? $shipping_country : $order->get_billing_country(),
		'name'         => $shipping_name ? $shipping_name : $order->get_formatted_billing_full_name(),
	);
}

/**
 * Prepare the shipping address to send in API from an order.
 *
 * @param WC_Order $order
 *
 * @return array
 */
function paypal_brasil_get_shipping_address( $order ) {
	$line1 = array();
	$line2 = array();

	$order_data = paypal_brasil_get_order_data( $order );

	if ( $order_data['address_1'] ) {
		$line1[] = $order_data['address_1'];
	}

	if ( $order_data['number'] ) {
		$line1[] = $order_data['number'];
	}

	if ( $order_data['neighborhood'] ) {
		$line2[] = $order_data['neighborhood'];
		if ( $order_data['address_2'] ) {
			$line1[] = $order_data['address_2'];
		}
	} elseif ( $order_data['address_2'] ) {
		$line2[] = $order_data['address_2'];
	}

	$shipping_address = array(
		'line1'          => implode( ', ', $line1 ),
		'line2'          => implode( ', ', $line2 ),
		'city'           => $order_data['city'],
		'state'          => $order_data['state'],
		'postal_code'    => $order_data['postcode'],
		'country_code'   => $order_data['country'],
		'recipient_name' => $order_data['name'],
	);

	return $shipping_address;
}

/**
 * Prepare the installment option with API input data.
 *
 * @param $data
 *
 * @return array
 */
function paypal_brasil_prepare_installment_option( $data ) {
	$value = array(
		'term'            => $data['credit_financing']['term'],
		'monthly_payment' => array(
			'value'    => $data['monthly_payment']['value'],
			'currency' => $data['monthly_payment']['currency_code'],
		),
	);

	if ( isset( $data['discount_percentage'] ) ) {
		$value['discount_percentage'] = $data['discount_percentage'];
		$value['discount_amount']     = array(
			'value'    => $data['discount_amount']['value'],
			'currency' => $data['discount_amount']['currency_code'],
		);
	}

	return $value;
}

/**
 * Explode a full name into first name and last name.
 *
 * @param $full_name
 *
 * @return array
 */
function paypal_brasil_explode_name( $full_name ) {
	$full_name  = explode( ' ', $full_name );
	$first_name = $full_name ? $full_name[0] : '';
	unset( $full_name[0] );
	$last_name = implode( ' ', $full_name );

	return array(
		'first_name' => $first_name,
		'last_name'  => $last_name,
	);
}

/**
 * Update WooCommerce settings.
 */
function paypal_brasil_wc_settings_ajax() {
	
	check_admin_referer( 'wp_ajax_paypal_brasil_wc_settings' );
	
	check_ajax_referer( 'wp_ajax_paypal_brasil_wc_settings' );

	if( !is_string( $_REQUEST['enable'] ) ){
		wp_die( '0',400 );
	}

	if ( !current_user_can( 'manage_options' ) ) {
		wp_die( '0',400 );
	}

	header( 'Content-type: application/json' );

	$choice = isset( $_REQUEST['enable'] ) && $_REQUEST['enable'] === 'yes' ? 'yes' : 'no';
	
	if ( $choice === 'yes' ) {
		update_option( 'woocommerce_enable_checkout_login_reminder', 'yes' );
		update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );
		update_option( 'woocommerce_enable_guest_checkout', 'no' );
	}

	echo json_encode( array(
		'success' => true,
		'choice'  => $choice,
		'message' => $choice === 'yes' ? __( 'WooCommerce settings have been changed successfully.',
			"paypal-brasil-para-woocommerce" ) : __( 'WooCommerce settings have not changed.',
			"paypal-brasil-para-woocommerce" ),
	) );

	wp_die();
}

add_action( 'wp_ajax_paypal_brasil_wc_settings', 'paypal_brasil_wc_settings_ajax' );

/**
 * Check if WooCommerce settings is activated.
 */
function paypal_brasil_wc_settings_valid() {
	return get_option( 'woocommerce_enable_checkout_login_reminder' ) === 'yes' &&
	       get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) === 'yes' &&
	       get_option( 'woocommerce_enable_guest_checkout' ) === 'no';
}

/**
 * Return if needs CPF.
 * @return bool
 */
function paypal_brasil_needs_cpf() {
	return function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() === 'BRL' : false;
}

/**
 * Protect some metadata.
 */
function paypal_brasil_protect_metadata( $protected, $meta_key ) {
	$keys = array(
		'paypal_brasil_id',
		'paypal_brasil_sale_id',
		'wc_ppp_brasil_installments',
		'wc_ppp_brasil_sale',
		'wc_ppp_brasil_sale_id',
		'wc_ppp_brasil_sandbox',
	);

	if ( 'shop_order' == get_post_type() ) {
		if ( in_array( $meta_key, $keys ) ) {
			return true;
		}
	}

	return $protected;
}

add_filter( 'is_protected_meta', 'paypal_brasil_protect_metadata', 10, 2 );

/**
 * Get the latest log for a gateway.
 *
 * @param $id
 *
 * @return string
 */
function paypal_brasil_get_log_file( $id ) {
	$logs         = WC_Admin_Status::scan_log_files();
	$matched_logs = array();

	foreach ( $logs as $key => $value ) {
		if ( preg_match( '/(' . $id . '-)/', $value ) ) {
			$matched_logs[] = $value;
		}
	}

	return $matched_logs ? end( $matched_logs ) : '';
}

/**
 * Format the money for PayPal API.
 *
 * @param $value
 * @param int $precision
 *
 * @return string
 */
function paypal_brasil_money_format( $value, $precision = 2 ) {
	return number_format( $value, $precision, '.', '' );
}

/**
 * Generate a unique id.
 * @return int
 */
function paypal_brasil_unique_id() {
	return rand( 1, 10000 );
}

function paypal_format_amount($amount) {
	return number_format($amount, 2, '.', '');
}