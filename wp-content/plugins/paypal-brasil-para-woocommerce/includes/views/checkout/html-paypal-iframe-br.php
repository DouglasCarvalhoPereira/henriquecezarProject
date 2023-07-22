<?php
$cards = array( 'visa', 'hipercard', 'mcard', 'elo', 'amex', 'hiper' );
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
            <input type="text" placeholder="<?php __("Card number","paypal-brasil-para-woocommerce") ;?>">
            <span class="ppp-dummy-card-input-icon"></span>
        </div>
    </div>
    <div class="ppp-dummy-row">
        <label class="ppp-dummy-label"><?php __("Name of card holder","paypal-brasil-para-woocommerce") ;?></label>
        <div class="ppp-container">
            <div class="ppp-dummy-half">
                <div class="ppp-dummy-input-credit-card ppp-dummy-input-credit-card-ccv">
                    <input type="text" placeholder="<?php __("Name","paypal-brasil-para-woocommerce") ;?>">
                </div>
            </div>
            <div class="ppp-dummy-half">
                <div class="ppp-dummy-input-credit-card ppp-dummy-input-credit-card-ccv">
                    <input type="text" placeholder="<?php __("Last name","paypal-brasil-para-woocommerce") ;?>">
                </div>
            </div>
        </div>
    </div>
    <div class="ppp-dummy-row">
        <div class="ppp-dummy-half">
            <label class="ppp-dummy-label"><?php __("Validity","paypal-brasil-para-woocommerce") ;?></label>
            <div class="ppp-dummy-flex">
                <div class="ppp-dummy-input-dropdown">
                    <input type="text" value="MM">
                </div>
                <div class="ppp-dummy-input-dropdown">
                    <input type="text" value="AA">
                </div>
            </div>
        </div>
        <div class="ppp-dummy-half">
            <label class="ppp-dummy-label"><?php __("Security code (CVV)","paypal-brasil-para-woocommerce") ;?></label>
            <div class="ppp-dummy-input-credit-card ppp-dummy-input-credit-card-ccv">
                <input type="text" placeholder="<?php __("3 digits","paypal-brasil-para-woocommerce") ;?>">
                <span class="ppp-dummy-card-input-icon"></span>
            </div>
        </div>
    </div>
    <div class="ppp-dummy-installments">
        <div class="ppp-dummy-input-dropdown">
            <input type="text" value="<?php __("Select installments for this purchase","paypal-brasil-para-woocommerce") ;?>">
        </div>
    </div>
    <div class="ppp-dummy-politice">
        <p><?php __("Your information will be collected in accordance with the ","paypal-brasil-para-woocommerce") ;?> <a href="#"> <?php __("PayPal Privacy Policy","paypal-brasil-para-woocommerce") ;?></a>.</p>
    </div>
    <div class="ppp-dummy-newsletter">
        <span class="fake-checkbox"></span>
        <p><?php __("I want to receive important information, special offers and discounts from PayPal.","paypal-brasil-para-woocommerce") ;?></p>
    </div>
</div>