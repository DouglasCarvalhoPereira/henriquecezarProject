<div class="admin-options-container">

    <div class="alert-dialog" v-if="showAlert && mode === 'live'">
        <div class="dialog-content">
            <img class="error-image"
                 src="<?php echo esc_url( plugins_url( 'assets/images/triangle.png', PAYPAL_PAYMENTS_MAIN_FILE ) ); ?>">
           <?php _e("<p>Dear customer, <strong>PayPal Transparent Checkout</strong> only works in production upon
                commercial release, if you have already received approval, please ignore this message. Otherwise,
                call our sales center (0800 047 4482) and request it right now.</p>
            <p>If you are seeing the \"prohibited\" sign during checkout, your account is not cleared for
                usage.</p>","paypal-brasil-para-woocommerce"); ?>
            <div class="dialog-actions">
                <button class="close-button" type="button" v-on:click="closeAlert">Ok</button>
            </div>
        </div>
    </div>

	<?php if ( ( empty( $_POST ) && $this->enabled === 'yes' ) || ( isset( $_POST ) && $this->get_updated_values()['enabled'] === 'yes' ) ): ?>

        <!-- CREDENTIALS ERROR -->
		<?php if ( get_option( $this->get_option_key() . '_validator' ) === 'no' ): ?>
            <div id="message" class="error inline">
                <p>
                    <strong><?php _e( "Your credentials are not valid. Please check the information provided.",
							"paypal-brasil-para-woocommerce" ); ?></strong>
                </p>
            </div>
		<?php elseif ( ( ! empty( $_POST ) && $this->get_updated_values()['reference_enabled'] === 'yes' && get_option( $this->get_option_key() . '_reference_transaction_validator' ) === 'no' )
		               || ( empty( $_POST ) && $this->reference_enabled === 'yes' && get_option( $this->get_option_key() . '_reference_transaction_validator' ) === 'no' ) ): ?>
            <div id="message" class="error inline">
                <p>
                    <strong><?php _e( "It was not possible to activate the \"Save Digital Wallet\" functionality because we verified that your PayPal account does not have permission to use this product. Contact PayPal at 0800 721 6959 and request its release.","paypal-brasil-para-woocommerce" ); ?></strong>
                </p>
            </div>
		<?php endif; ?>

        <!-- WEBHOOK -->
		<?php if ( ! $this->get_webhook_id() ): ?>
            <div id="paypal-brasil-message-webhook" class="error inline">
                <p>
                    <strong><?php _e( "Unable to create webhook configurations. Try to save again.",
							"paypal-brasil-para-woocommerce" ); ?></strong>
                </p>
            </div>
		<?php endif; ?>

	<?php endif; ?>

    <img class="banner"
         srcset="<?php echo esc_attr( plugins_url( 'assets/images/banner-plus-2x.png',
		     PAYPAL_PAYMENTS_MAIN_FILE ) ); ?> 2x"
         src="<?php echo esc_attr( plugins_url( 'assets/images/banner-plus.png', PAYPAL_PAYMENTS_MAIN_FILE ) ); ?>"
         title="<?php _e( "PayPal Brazil", "paypal-brasil-para-woocommerce" ); ?>"
         alt="<?php _e( "PayPal Brazil", "paypal-brasil-para-woocommerce" ); ?>">

	<?php echo wp_kses_post( wpautop( $this->get_method_description() ) ); ?>

    <table class="form-table">

        <tbody>

        <!-- HABILITAR -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'enabled' ) ); ?>"> <?php _e("Enable/Disable", "paypal-brasil-para-woocommerce"); ?> </label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span> <?php _e("Enable/Disable", "paypal-brasil-para-woocommerce"); ?> </span></legend>
                    <label for="<?php echo esc_attr( $this->get_field_key( 'enabled' ) ); ?>">
                        <input type="checkbox"
                               class="test"
                               name="<?php echo esc_attr( $this->get_field_key( 'enabled' ) ); ?>"
                               id="<?php echo esc_attr( $this->get_field_key( 'enabled' ) ); ?>"
                               value="<?php echo esc_attr( $this->enabled ); ?>"
                               v-model="enabled"
                               true-value="yes"
                               false-value="">
                        <?php _e("Enable","paypal-brasil-para-woocommerce"); ?></label><br>
                </fieldset>
            </td>
        </tr>

        <!-- NOME DE EXIBIÇÃO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label
                        for="<?php echo esc_attr( $this->get_field_key( 'title_complement' ) ); ?>"><?php echo esc_html( $this->get_form_fields()['title_complement']['title'] ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php echo esc_html( $this->get_form_fields()['title_complement']['title'] ); ?></span>
                    </legend>
                    <input class="input-text regular-input"
                           type="text"
                           name="<?php echo esc_attr( $this->get_field_key( 'title_complement' ) ); ?>"
                           id="<?php echo esc_attr( $this->get_field_key( 'title_complement' ) ); ?>"
                           v-model="titleComplement"
                           placeholder="<?php _e("Example: Installments up to 12x", "paypal-brasil-para-woocommerce"); ?>" >
                    <p class="description"><?php _e("Will be displayed at checkout: Credit Card ", "paypal-brasil-para-woocommerce"); ?> {{titleComplement ? '(' +
                        titleComplement +
                        ')':
                        ''}}</p>
                </fieldset>
            </td>
        </tr>

        <!-- MODO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label
                        for="<?php echo esc_attr( $this->get_field_key( 'mode' ) ); ?>"><?php echo esc_html( $this->get_form_fields()['mode']['title'] ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php echo esc_html( $this->get_form_fields()['mode']['title'] ); ?></span>
                    </legend>
                    <select class="select"
                            id="<?php echo esc_attr( $this->get_field_key( 'mode' ) ); ?>"
                            name="<?php echo esc_attr( $this->get_field_key( 'mode' ) ); ?>"
                            v-model="mode">
                        <option value="live"><?php _e("Production", "paypal-brasil-para-woocommerce"); ?></option>
                        <option value="sandbox" selected="selected"><?php _e("Sandbox","paypal-brasil-para-woocommerce"); ?></option>
                    </select>
                    <p class="description"><?php _e("Use this option to toggle between Sandbox and Production modes. Sandbox is
                        used for testing and production for actual purchases.", "paypal-brasil-para-woocommerce"); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- CLIENT ID LIVE -->

        <tr valign="top" :class="{hidden: !isLive()}">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'client_live' ) ); ?>"><?php _e("Client ID
                    (production)","paypal-brasil-para-woocommerce"); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span> <?php _e("Client ID", "paypal-brasil-para-woocommerce"); ?> </span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           id="<?php echo esc_attr( $this->get_field_key( 'client_live' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'client_live' ) ); ?>"
                           v-model="client.live">
                    <p class="description"> <?php _e("To generate the Client ID go to", "paypal-brasil-para-woocommerce"); ?> <a
                                href="https://developer.paypal.com/docs/multiparty/get-started/"
                                target="_blank"> <?php _e("here", "paypal-brasil-para-woocommerce"); ?> </a>
                                <?php _e('and get it from the “REST API APPS” section.', "paypal-brasil-para-woocommerce"); ?> </p>
                </fieldset>
            </td>
        </tr>

        <!-- CLIENT ID SANDBOX -->

        <tr valign="top" :class="{hidden: isLive()}">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'client_sandbox' ) ); ?>"> <?php _e(" Client ID
                    (sandbox)", "paypal-brasil-para-woocommerce"); ?> </label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e(" Client ID", "paypal-brasil-para-woocommerce"); ?> </span></legend>
                    <input class="input-text regular-input"
                           type="text"

                           id="<?php echo esc_attr( $this->get_field_key( 'client_sandbox' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'client_sandbox' ) ); ?>"
                           v-model="client.sandbox">
                    <p class="description"> <?php _e("To generate the Client ID go to", "paypal-brasil-para-woocommerce"); ?>  <a
                                href="https://developer.paypal.com/docs/multiparty/get-started/"
                                target="_blank"><?php _e(" here ", "paypal-brasil-para-woocommerce"); ?></a>
                                <?php _e("and get it from the “REST API APPS” section.", "paypal-brasil-para-woocommerce"); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- SECRET LIVE -->

        <tr valign="top" :class="{hidden: !isLive()}">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'secret_live' ) ); ?>"> <?php _e("Secret (production)", "paypal-brasil-para-woocommerce"); ?> </label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e("Secret", "paypal-brasil-para-woocommerce"); ?></span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           id="<?php echo esc_attr( $this->get_field_key( 'secret_live' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'secret_live' ) ); ?>"
                           v-model="secret.live">
                    <p class="description"> <?php _e("To generate the Secret go to ", "paypal-brasil-para-woocommerce"); ?> <a
                                href="https://developer.paypal.com/docs/multiparty/get-started/"
                                target="_blank"><?php _e("here","paypal-brasil-para-woocommerce"); ?></a>
                                <?php _e("and get it from the “REST API APPS” section.", "paypal-brasil-para-woocommerce"); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- SECRET SANDBOX -->

        <tr valign="top" :class="{hidden: isLive()}">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'secret_sandbox' ) ); ?>"><?php _e("Secret (sandbox)", "paypal-brasil-para-woocommerce"); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e("Secret (sandbox)", "paypal-brasil-para-woocommerce"); ?></span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           id="<?php echo esc_attr( $this->get_field_key( 'secret_sandbox' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'secret_sandbox' ) ); ?>"

                           v-model="secret.sandbox">
                    <p class="description"><?php _e("To generate the Secret go to ", "paypal-brasil-para-woocommerce"); ?> <a
                                href="https://developer.paypal.com/docs/multiparty/get-started/"
                                target="_blank"><?php _e("here","paypal-brasil-para-woocommerce"); ?></a>
                                <?php _e("and get it from the “REST API APPS” section.", "paypal-brasil-para-woocommerce"); ?></p>
                </fieldset>
            </td>
        </tr>

        <h2><?php _e("Advanced Settings", "paypal-brasil-para-woocommerce"); ?></h2>

        <!-- FORM HEIGHT -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'invoice_id_prefix' ) ); ?>"><?php esc_html_e( 'Form height', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php esc_html_e( 'Form height', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <input class="input-text regular-input"
                           type="range"
                           min="400"
                           max="700"
                           id="<?php echo esc_attr( $this->get_field_key( 'form_height' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'form_height' ) ); ?>"
                           v-model="formHeight">
                    <span class="form-height-value">{{formHeight}}px</span>
                    <p class="description"><?php esc_html_e( 'Use this option to set a maximum height of the greeting card form.
                        credit (will be considered a value in pixels). A value in pixels between 400 -
                        550.', "paypal-brasil-para-woocommerce" ); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- PREFIXO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'invoice_id_prefix' ) ); ?>"><?php esc_html_e( 'Prefix in number
                    order', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php esc_html_e( 'Prefix in number
                    order', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           id="<?php echo esc_attr( $this->get_field_key( 'invoice_id_prefix' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'invoice_id_prefix' ) ); ?>"
                           v-model="invoiceIdPrefix">
                    <p class="description"><?php esc_html_e( 'Add a prefix to the order number, this is useful for identifying you when you have more than one store processing through PayPal.', "paypal-brasil-para-woocommerce" ); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- MODO DEPURAÇÃO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'debug' ) ); ?>"><?php esc_html_e( 'Debug mode', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php esc_html_e( 'Debug mode', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <label for="<?php echo esc_attr( $this->get_field_key( 'debug' ) ); ?>">
                        <input type="checkbox"
                               id="<?php echo esc_attr( $this->get_field_key( 'debug' ) ); ?>"
                               name="<?php echo esc_attr( $this->get_field_key( 'debug' ) ); ?>"
                               v-model="debugMode"
                               true-value="yes"
                               false-value="">
                        <?php _e("Enable","paypal-brasil-para-woocommerce"); ?></label><br>
                    <p class="description"><?php esc_html_e( 'The logs will be saved in the path:', "paypal-brasil-para-woocommerce" ); ?> <a target="_blank"
                                                                               href="<?php echo esc_url( admin_url( sprintf( 'admin.php?page=wc-status&tab=logs&log_file=%s',
						                                                           paypal_brasil_get_log_file( $this->id ) ) ) ); ?>"><?php esc_html_e( 'Status
                                                                                   of the system &gt; Logs', "paypal-brasil-para-woocommerce" ); ?> </a>.</p>
                </fieldset>
            </td>
        </tr>

        </tbody>

    </table>

</div>
