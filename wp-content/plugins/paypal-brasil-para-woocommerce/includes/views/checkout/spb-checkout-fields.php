<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="paypal-spb-fields">
    <input type="hidden" name="paypal-brasil-spb-order-id">
    <input type="hidden" name="paypal-brasil-spb-payer-id">
    <input type="hidden" name="paypal-brasil-spb-pay-id">

	<?php try { ?>
		<?php if ( is_checkout_pay_page() ): ?>
			<?php
			global $wp;
			$order = wc_get_order( $wp->query_vars['order-pay'] );
			$data  = $this->create_spb_ec_for_order( $order );
			?>
            <input type="hidden" name="paypal-brasil-spb-order-pay-ec" value="<?php echo esc_attr( $data['ec'] ); ?>">
		<?php endif; ?>

        <img src="<?php echo esc_url( plugins_url( 'assets/images/saiba-mais.png', PAYPAL_PAYMENTS_MAIN_FILE ) ); ?>"
             style="width: 500px; max-width: 100%; margin: 0 auto; max-height: 100%; float: none;">
	<?php } catch ( Exception $ex ) { ?>
		<?php wc_print_notice( __( 'There was an error creating the payment token. Please try again.', "paypal-brasil-para-woocommerce" ), 'error' ); ?>
	<?php } ?>
</div>
