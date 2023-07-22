<?php
/** @var PayPal_Brasil_Plus_Gateway $this */

// Exit if not in WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$error      = false;
$data       = null;
$data_error = null;

try {
	$data = $this->get_posted_data();
} catch ( Exception $ex ) {
	$error = $ex->getMessage();
	wc_add_notice( $error, 'error' );
	if ( $ex->getData() ) {
		$data_error = $ex->getMessage();
	}
}catch(PayPal_Brasil_API_Exception $ex){
	$error = $ex->getData();
	wc_add_notice( $error, 'error' );
}
?>
<div id="wc-ppb-brasil-wrappers">
	<?php echo $data['cart_hash']; ?>
	<?php if ( $error ): ?>
        <p><?php echo $error; ?></p>
        <input type="hidden" id="wc-ppp-brasil-api-error-data" name="wc-ppp-brasil-data"
               value="<?php echo htmlentities( json_encode( $data_error ) ); ?>">
	<?php else: ?>
        <input type="hidden" id="wc-ppp-brasil-data" name="wc-ppp-brasil-data"
               value="<?php echo htmlentities( json_encode( $data ) ); ?>">
        <input type="hidden" id="wc-ppp-brasil-response" name="wc-ppp-brasil-response" value="">
        <input type="hidden" id="wc-ppp-brasil-error" name="wc-ppp-brasil-error" value="">
        <div id="wc-ppp-brasil-container-loading" class="hidden">
            <div class="paypal-loading"></div>
        </div>
        <div id="wc-ppp-brasil-container-dummy" class="hidden">
			<?php
			if ( get_woocommerce_currency() === 'BRL' ) {
				include dirname( __FILE__ ) . '/html-paypal-iframe-br.php';
			} else {
				include dirname( __FILE__ ) . '/html-paypal-iframe-us.php';
			}
			?>
        </div>
        <div id="wc-ppp-brasil-container"></div>
        <div id="wc-ppb-brasil-container-overlay" class="hidden">
            <div class="icon-lock"></div>
            <div class="text">
				<?php
				$string1_br = __('Please correctly fill in the information from the previous steps:', "paypal-brasil-para-woocommerce" );
				$string2_br = __('If you have already filled it out, <a href="#" data-action="update-checkout">click here</a>.',"paypal-brasil-para-woocommerce");
				$string3_br = __('Please contact us. There is invalid information in the order:',"paypal-brasil-para-woocommerce");
				$string1_us = __('Please fill correctly the previously asked information.',"paypal-brasil-para-woocommerce");
				$string2_us = __('If they are already filled,  <a href="#" data-action="update-checkout">click here</a>.',"paypal-brasil-para-woocommerce");
				$string3_us = __('Please contact us. There are invalid information on order:', "paypal-brasil-para-woocommerce");
				if ( is_checkout_pay_page() ) {
					echo get_woocommerce_currency() === 'BRL' ? $string3_br : $string3_us;
				} else {
					echo sprintf( '<p>%s</p>', get_woocommerce_currency() === 'BRL' ? $string1_br : $string1_us );
				}
				echo '<div class="missing-items"></div>';
				if ( ! is_checkout_pay_page() ) {
					echo sprintf( '<p>%s</p>', get_woocommerce_currency() === 'BRL' ? $string2_br : $string2_us );
				}
				?>
            </div>
        </div>
	<?php endif; ?>
</div>