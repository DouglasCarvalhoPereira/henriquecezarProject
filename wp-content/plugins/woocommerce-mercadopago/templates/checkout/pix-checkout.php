<?php

/**
 * Part of Woo Mercado Pago Module
 * Author - Mercado Pago
 * Developer
 * Copyright - Copyright(c) MercadoPago [https://www.mercadopago.com]
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * @package MercadoPago
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class='mp-checkout-container'>
	<div class="mp-checkout-pix-container">
		<?php if ( true === $test_mode ) : ?>
			<div class="mp-checkout-pix-test-mode">
				<test-mode title="<?php echo esc_html_e('Pix in Test Mode', 'woocommerce-mercadopago'); ?>" description="<?php echo esc_html_e('You can test the flow to generate a code, but you cannot finalize the payment.', 'woocommerce-mercadopago'); ?>">
				</test-mode>
			</div>
		<?php endif; ?>

		<pix-template title="<?php echo esc_html_e('Pay instantly', 'woocommerce-mercadopago'); ?>" subtitle="<?php echo esc_html_e('By confirming your purchase, we will show you a code to make the payment.', 'woocommerce-mercadopago'); ?>" alt="<?php echo esc_html_e('Pix logo', 'woocommerce-mercadopago'); ?>" src="<?php echo esc_html($pix_image); ?>">
		</pix-template>

		<div class="mp-checkout-pix-terms-and-conditions">
			<terms-and-conditions description="<?php echo esc_html_e('By continuing, you agree with our', 'woocommerce-mercadopago'); ?>" link-text="<?php echo esc_html_e('Terms and conditions', 'woocommerce-mercadopago'); ?>" link-src="<?php echo esc_html($link_terms_and_conditions); ?>">
			</terms-and-conditions>
		</div>
	</div>
</div>

<script type="text/javascript">
	if ( document.getElementById("payment_method_woo-mercado-pago-custom") ) {
		jQuery("form.checkout").on(
			"checkout_place_order_woo-mercado-pago-pix",
			function() {
				cardFormLoad();
			}
		);
	}
</script>
