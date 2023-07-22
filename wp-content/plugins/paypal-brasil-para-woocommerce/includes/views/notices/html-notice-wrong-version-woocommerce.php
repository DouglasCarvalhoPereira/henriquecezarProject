<?php
/**
 * Missing WooCommerce notice.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $woocommerce;
?>

<div class="error">
    <p>
        <strong><?php esc_html_e( 'PayPal Brazil for WooCommerce', "paypal-brasil-para-woocommerce" ); ?></strong> <?php echo esc_html( sprintf( __( 'depends on WooCommerce version 3.6 or higher to work! You are using version %s. Please update.', "paypal-brasil-para-woocommerce" ), $woocommerce->version ) ); ?>
    </p>
</div>