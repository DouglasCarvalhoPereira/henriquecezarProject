<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$review_payment = isset( $_GET['review-payment'] ) ? sanitize_text_field( $_GET['review-payment'] ) : '';
$payer_id       = isset( $_GET['payer-id'] ) ? sanitize_text_field( $_GET['payer-id'] ) : '';
$pay_id         = isset( $_GET['pay-id'] ) ? sanitize_text_field( $_GET['pay-id'] ) : '';
?>

    <!-- INPUT TO SEND POST REQUEST AND KNOW WE ARE ON SHORTCUT -->
    <input type="hidden" name="paypal-brasil-shortcut-review-payment"
           value="<?php echo esc_attr( $review_payment ); ?>">
    <input type="hidden" name="paypal-brasil-shortcut-payer-id" value="<?php echo esc_attr( $payer_id ); ?>">
    <input type="hidden" name="paypal-brasil-shortcut-pay-id" value="<?php echo esc_attr( $pay_id ); ?>">

    <!-- RELOAD REQUEST FOR JS -->
    <!-- THIS IS DUE WOOCOMMERCE BUG: https://github.com/woocommerce/woocommerce/issues/23888 -->
    <div class="paypal-brasil-reload-request"></div>

<?php
try {
	$pay_id   = isset( $_GET['pay-id'] ) ? sanitize_text_field( $_GET['pay-id'] ) : '';
	$payer_id = isset( $_GET['payer-id'] ) ? sanitize_text_field( $_GET['payer-id'] ) : '';

	$payer = WC()->session->get( 'paypal_brasil_shortcut_payer_info' );

	if ( ! $payer || $payer['pay-id'] !== $pay_id ) {
		$payment = $this->api->get_payment( $pay_id, array(), 'shortcut' );

		$cpf_cases  = array( 'BR_CPF', 'TAX_ID' );
		$cnpj_cases = array( 'BR_CNPJ', 'BUSINESS_TAX_ID' );

		$payer = array(
			'payer-id'       => $payer_id,
			'email'          => $payment['payer']['payer_info']['email'],
			'pay-id'         => $pay_id,
			'first_name'     => $payment['payer']['payer_info']['first_name'],
			'last_name'      => $payment['payer']['payer_info']['last_name'],
			'persontype'     => in_array( $payment['payer']['payer_info']['tax_id_type'], $cpf_cases ) ? '1' : '2',
			'cpf'            => in_array( $payment['payer']['payer_info']['tax_id_type'], $cpf_cases ) ? $payment['payer']['payer_info']['tax_id'] : '',
			'cnpj'           => in_array( $payment['payer']['payer_info']['tax_id_type'], $cnpj_cases ) ? $payment['payer']['payer_info']['tax_id'] : '',
			'company'        => in_array( $payment['payer']['payer_info']['tax_id_type'], $cnpj_cases ) ? $payment['payer']['payer_info']['business_name'] : '',
			'shipping_name'  => isset( $payment['payer']['payer_info']['shipping_address'] ) ? $payment['payer']['payer_info']['shipping_address']['recipient_name'] : '',
			'address_line_1' => isset( $payment['payer']['payer_info']['shipping_address'] ) ? $payment['payer']['payer_info']['shipping_address']['line1'] : '',
			'address_line_2' => '',
			'city'           => isset( $payment['payer']['payer_info']['shipping_address'] ) ? $payment['payer']['payer_info']['shipping_address']['city'] : '',
			'state'          => isset( $payment['payer']['payer_info']['shipping_address'] ) ? $payment['payer']['payer_info']['shipping_address']['state'] : '',
			'postcode'       => isset( $payment['payer']['payer_info']['shipping_address'] ) ? $payment['payer']['payer_info']['shipping_address']['postal_code'] : '',
			'country'        => isset( $payment['payer']['payer_info']['shipping_address'] ) ? $payment['payer']['payer_info']['shipping_address']['country_code'] : '',
		);

		if ( isset( $payment['payer']['payer_info']['shipping_address'] ) && isset( $payment['payer']['payer_info']['shipping_address']['line2'] ) ) {
			$payer['address_line_2'] = $payment['payer']['payer_info']['shipping_address']['line2'];
		}

		WC()->session->set( 'paypal_brasil_shortcut_payer_info', $payer );
	}
	$states = WC()->countries->get_states( $payer['country'] );
	?>
	<?php if ( ! $this->is_shortcut_override_address() ): ?>
		<?php
		// Set customer properties to ensure shipping calculation.
		WC()->customer->set_props(
			array(
				'shipping_country'   => $payer['country'],
				'shipping_state'     => $payer['state'],
				'shipping_postcode'  => $payer['postcode'],
				'shipping_city'      => $payer['city'],
				'shipping_address_1' => $payer['address_line_1'],
				'shipping_address_2' => $payer['address_line_2'],
			)
		);

		// Save customer data.
		WC()->customer->save();
		?>
        <div class="woocommerce-info">
			<?php _e( 'Please check the data obtained through your PayPal payment before finalizing your purchase.', "paypal-brasil-para-woocommerce" ); ?>
            <a href="<?php echo esc_url( add_query_arg( 'override-address', true ) ); ?>"><?php _e( 'This is not my address.', "paypal-brasil-para-woocommerce" ); ?></a>
        </div>
        <table class="shop_table">
			<?php // get the shipping name or the name if is NO_SHIPPING ?>
			<?php if ( $payer['shipping_name'] ): ?>
                <tr>
                    <th><?php _e( 'Name', "paypal-brasil-para-woocommerce" ); ?></th>
                    <td><?php echo sprintf( '%s', $payer['shipping_name'] ); ?></td>
                </tr>
			<?php else: ?>
                <tr>
                    <th><?php _e( 'Name', "paypal-brasil-para-woocommerce" ); ?></th>
                    <td><?php echo sprintf( '%s %s', $payer['first_name'], $payer['last_name'] ); ?></td>
                </tr>
			<?php endif; ?>
			<?php // check if have addresss, otherwise is NO_SHIPPING ?>
			<?php if ( $payer['address_line_1'] ): ?>
                <tr>
                    <th><?php _e( 'Address', "paypal-brasil-para-woocommerce" ); ?></th>
                    <td><?php echo $payer['address_line_1'] . ( isset( $payer['address_line_2'] ) ? ', ' . $payer['address_line_2'] : '' ); ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'City', "paypal-brasil-para-woocommerce" ); ?></th>
                    <td><?php echo esc_html( $payer['city'] ); ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'State', "paypal-brasil-para-woocommerce" ); ?></th>
                    <td><?php echo esc_html( $states[ $payer['state'] ] ); ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'Country', "paypal-brasil-para-woocommerce" ); ?></th>
                    <td><?php echo esc_html( WC()->countries->get_countries()[ $payer['country'] ] ); ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'Zip code', "paypal-brasil-para-woocommerce" ); ?></th>
                    <td><?php echo esc_html( $payer['postcode'] ); ?></td>
                </tr>
			<?php endif; ?>
        </table>
	<?php endif; ?>
	<?php
} catch ( Exception $ex ) {
	if ( isset( $_GET['retrying-attempt'] ) && $_GET['retrying-attempt'] === '1' ) {
		wc_add_notice( __( 'There was an unexpected error getting payment details. Try again.', "paypal-brasil-para-woocommerce" ), 'error' );
		wp_redirect( wc_get_cart_url() );
		exit;
	} else {
		wp_redirect( add_query_arg( 'retrying-attempt', '1' ) );
		exit;
	}
}