<?php
$cards = array( 'mcard', 'visa', 'amex', 'disc' );
shuffle( $cards );
?>
<div id="ppp-dummy">
    <div class="ppp-dummy-logo">
        <img src="https://www.paypalobjects.com/webstatic/mktg/logo/bdg_powered_by_130x27_2x.png">
    </div>
    <div class="ppp-dummy-card-list">
		<?php foreach ( $cards as $card ): ?>
            <div class="card"><span class="ccard <?php echo $card; ?>"></span></div>
		<?php endforeach; ?>
    </div>
    <div class="ppp-dummy-card-input">
        <div class="ppp-dummy-input-credit-card ppp-dummy-input-credit-card-generic">
            <input type="text" placeholder="Card number">
            <span class="ppp-dummy-card-input-icon"></span>
        </div>
    </div>
    <div class="ppp-dummy-row">
        <label class="ppp-dummy-label">Name of card holder</label>
        <div class="ppp-container">
            <div class="ppp-dummy-half">
                <div class="ppp-dummy-input-credit-card ppp-dummy-input-credit-card-ccv">
                    <input type="text" placeholder="Name">
                </div>
            </div>
            <div class="ppp-dummy-half">
                <div class="ppp-dummy-input-credit-card ppp-dummy-input-credit-card-ccv">
                    <input type="text" placeholder="Last name">
                </div>
            </div>
        </div>
    </div>
    <div class="ppp-dummy-row">
        <div class="ppp-dummy-half">
            <label class="ppp-dummy-label">Expires</label>
            <div class="ppp-dummy-flex">
                <div class="ppp-dummy-input-dropdown">
                    <input type="text" value="MM">
                </div>
                <div class="ppp-dummy-input-dropdown">
                    <input type="text" value="YY">
                </div>
            </div>
        </div>
        <div class="ppp-dummy-half">
            <label class="ppp-dummy-label">CSC</label>
            <div class="ppp-dummy-input-credit-card ppp-dummy-input-credit-card-ccv">
                <input type="text" placeholder="3 digits">
                <span class="ppp-dummy-card-input-icon"></span>
            </div>
        </div>
    </div>
    <div class="ppp-dummy-installments">
        <div class="ppp-dummy-input-dropdown">
			<?php $formatted_price = sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), WC()->cart->total ); ?>
            <input type="text" value="1x of <?php echo $formatted_price; ?>">
        </div>
    </div>
    <div class="ppp-dummy-politice">
        <p>Your information will be collected in accordance with the <a href="#">PayPal Privacy Policy</a>.</p>
    </div>
    <div class="ppp-dummy-newsletter">
        <span class="fake-checkbox"></span>
        <p>I want to receive important information, special offers and discounts from PayPal.</p>
    </div>
</div>