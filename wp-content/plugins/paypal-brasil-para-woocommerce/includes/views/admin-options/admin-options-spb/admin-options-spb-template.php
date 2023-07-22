<div class="admin-options-container">

	<?php if ( ( empty( $_POST ) && $this->enabled === 'yes' ) || ( isset( $_POST ) && $this->get_updated_values()['enabled'] === 'yes' ) ): ?>

        <!-- CREDENTIALS ERROR -->
		<?php if ( get_option( $this->get_option_key() . '_validator' ) === 'no' ): ?>
            <div id="message" class="error inline">
                <p>
                    <strong><?php _e( 'Your credentials are not valid. Please check the information provided.', "paypal-brasil-para-woocommerce" ); ?></strong>
                </p>
            </div>
		<?php elseif ( ( ! empty( $_POST ) && $this->get_updated_values()['reference_enabled'] === 'yes' && get_option( $this->get_option_key() . '_reference_transaction_validator' ) === 'no' )
		               || ( empty( $_POST ) && $this->reference_enabled === 'yes' && get_option( $this->get_option_key() . '_reference_transaction_validator' ) === 'no' ) ): ?>
            <div id="message" class="error inline">
                <p>
                    <strong><?php _e( 'It was not possible to activate the "Save Digital Wallet" functionality because we verified that your PayPal account does not have permission to use this product. Contact PayPal at 0800 721 6959 and request its release.', "paypal-brasil-para-woocommerce" ); ?></strong>
                </p>
            </div>
		<?php endif; ?>

        <!-- REFERENCE TRANSACTION SETTINGS -->
		<?php if ( ( isset( $_POST ) && $this->get_updated_values()['reference_enabled'] === 'yes' ) || ( empty( $_POST ) && $this->reference_enabled === 'yes' ) ): ?>
			<?php if ( ! paypal_brasil_wc_settings_valid() ): ?>
                <div id="message-reference-transaction-settings" class="error inline">
                    <p>
                        <strong><?php _e( 'It was not possible to activate the "Save Digital Wallet" functionality because the mandatory settings were not applied.', "paypal-brasil-para-woocommerce" ); ?></strong>
                    </p>
                </div>
			<?php endif; ?>
		<?php endif; ?>

        <!-- WEBHOOK -->
		<?php if ( ! $this->get_webhook_id() ): ?>
            <div id="paypal-brasil-message-webhook" class="error inline">
                <p>
                    <strong><?php _e( 'Could not create webhook configurations. Try to save again.', "paypal-brasil-para-woocommerce" ); ?></strong>
                </p>
            </div>
		<?php endif; ?>

	<?php endif; ?>

    <img class="banner"
         srcset="<?php echo esc_attr( plugins_url( 'assets/images/banner-spb-2x.png', PAYPAL_PAYMENTS_MAIN_FILE ) ); ?> 2x"
         src="<?php echo esc_attr( plugins_url( 'assets/images/banner-spb.png', PAYPAL_PAYMENTS_MAIN_FILE ) ); ?>"
         title="<?php _e( 'PayPal Brasil', "paypal-brasil-para-woocommerce" ); ?>"
         alt="<?php _e( 'PayPal Brasil', "paypal-brasil-para-woocommerce" ); ?>">

	<?php echo wp_kses_post( wpautop( $this->get_method_description() ) ); ?>

    <table class="form-table">

        <tbody>

        <!-- HABILITAR -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'enabled' ) ); ?>"><?php _e( 'Enable/Disable', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Enable/Disable', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <label for="<?php echo esc_attr( $this->get_field_key( 'enabled' ) ); ?>">
                        <input type="checkbox"
                               class="test"
                               name="<?php echo esc_attr( $this->get_field_key( 'enabled' ) ); ?>"
                               id="<?php echo esc_attr( $this->get_field_key( 'enabled' ) ); ?>"
                               value="<?php echo esc_attr( $this->enabled ); ?>"
                               v-model="enabled"
                               true-value="yes"
                               false-value="">
                               <?php _e( 'Enable', "paypal-brasil-para-woocommerce" ); ?></label><br>
                </fieldset>
            </td>
        </tr>

        <!-- NOME DE EXIBIÇÃO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'title' ) ); ?>"><?php _e( 'Display name (complement)', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Display name', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           name="<?php echo esc_attr( $this->get_field_key( 'title_complement' ) ); ?>"
                           id="<?php echo esc_attr( $this->get_field_key( 'title_complement' ) ); ?>"
                           v-model="titleComplement"
                           placeholder="<?php _e( 'Example: (up to 12 installments)', "paypal-brasil-para-woocommerce" ); ?>">
                    <p class="description"><?php _e( 'Will show at checkout: PayPal ', "paypal-brasil-para-woocommerce" ); ?>{{titleComplement ? '(' + titleComplement + ')':
                        ''}}</p>
                </fieldset>
            </td>
        </tr>

        <!-- MODO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'mode' ) ); ?>"><?php _e( 'Mode', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Mode', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <select class="select"
                            id="<?php echo esc_attr( $this->get_field_key( 'mode' ) ); ?>"
                            name="<?php echo esc_attr( $this->get_field_key( 'mode' ) ); ?>"

                            v-model="mode">
                        <option value="live"><?php _e( 'Production ', "paypal-brasil-para-woocommerce" ); ?></option>
                        <option value="sandbox" selected="selected"><?php _e( 'Sandbox', "paypal-brasil-para-woocommerce" ); ?></option>
                    </select>
                    <p class="description"><?php _e( 'Use this option to toggle between Sandbox and Production modes. Sandbox is used for testing and Production for actual purchases.', "paypal-brasil-para-woocommerce" ); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- CLIENT ID LIVE -->

        <tr valign="top" :class="{hidden: !isLive()}">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'client_live' ) ); ?>"><?php _e( 'Client ID (production)', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Client ID', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           id="<?php echo esc_attr( $this->get_field_key( 'client_live' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'client_live' ) ); ?>"
                           v-model="client.live">
                    <p class="description"><?php _e( 'To generate the Client ID go to ', "paypal-brasil-para-woocommerce" ); ?><a
                                href="https://developer.paypal.com/docs/multiparty/get-started/"
                                target="_blank"><?php _e( 'here ', "paypal-brasil-para-woocommerce" ); ?></a>
                                <?php _e( 'and look for the “REST API apps” section.', "paypal-brasil-para-woocommerce" ); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- CLIENT ID SANDBOX -->

        <tr valign="top" :class="{hidden: isLive()}">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'client_sandbox' ) ); ?>"><?php _e( 'Client ID (sandbox) ', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Client ID', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <input class="input-text regular-input"
                           type="text"

                           id="<?php echo esc_attr( $this->get_field_key( 'client_sandbox' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'client_sandbox' ) ); ?>"
                           v-model="client.sandbox">
                    <p class="description"><?php _e( 'To generate the Client ID go to ', "paypal-brasil-para-woocommerce" ); ?><a
                                href="https://developer.paypal.com/docs/multiparty/get-started/"
                                target="_blank"><?php _e( 'here ', "paypal-brasil-para-woocommerce" ); ?></a>
                                <?php _e( ' and look for the “REST API apps” section.', "paypal-brasil-para-woocommerce" ); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- SECRET LIVE -->

        <tr valign="top" :class="{hidden: !isLive()}">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'secret_live' ) ); ?>"><?php _e( 'Secret (production)', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Secret', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           id="<?php echo esc_attr( $this->get_field_key( 'secret_live' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'secret_live' ) ); ?>"
                           v-model="secret.live">
                    <p class="description"><?php _e( 'To generate the Secret go to ', "paypal-brasil-para-woocommerce" ); ?><a
                                href="https://developer.paypal.com/docs/multiparty/get-started/"
                                target="_blank"><?php _e( 'here', "paypal-brasil-para-woocommerce" ); ?></a>
                                <?php _e( ' and look for the “REST API apps” section.', "paypal-brasil-para-woocommerce" ); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- SECRET SANDBOX -->

        <tr valign="top" :class="{hidden: isLive()}">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'secret_sandbox' ) ); ?>"><?php _e( 'Secret (sandbox)', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Secret (sandbox)', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           id="<?php echo esc_attr( $this->get_field_key( 'secret_sandbox' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'secret_sandbox' ) ); ?>"

                           v-model="secret.sandbox">
                    <p class="description"><?php _e( 'To generate the Secret go to ', "paypal-brasil-para-woocommerce" ); ?><a
                                href="https://developer.paypal.com/docs/multiparty/get-started/"
                                target="_blank"><?php _e( 'here ', "paypal-brasil-para-woocommerce" ); ?></a>
                                <?php _e( ' and look for the “REST API apps” section.', "paypal-brasil-para-woocommerce" ); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- HEADER -->
        <h2><?php _e( 'Button Settings', "paypal-brasil-para-woocommerce" ); ?></h2>

        <!-- FOMARTO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'format' ) ); ?>"><?php _e( 'Format', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Format', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <select class="select"
                            id="<?php echo esc_attr( $this->get_field_key( 'format' ) ); ?>"
                            name="<?php echo esc_attr( $this->get_field_key( 'format' ) ); ?>"

                            v-model="button.format">
                        <option value="rect"><?php _e( 'Rectangular', "paypal-brasil-para-woocommerce" ); ?></option>
                        <option value="pill"><?php _e( 'Rounded', "paypal-brasil-para-woocommerce" ); ?></option>
                    </select>
                </fieldset>
            </td>
        </tr>

        <!-- COR -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'color' ) ); ?>"><?php _e( 'Color', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Color', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <select class="select"
                            id="<?php echo esc_attr( $this->get_field_key( 'color' ) ); ?>"
                            name="<?php echo esc_attr( $this->get_field_key( 'color' ) ); ?>"

                            v-model="button.color">
                        <option value="blue"><?php _e( 'Blue', "paypal-brasil-para-woocommerce" ); ?></option>
                        <option value="gold"><?php _e( 'Gold', "paypal-brasil-para-woocommerce" ); ?></option>
                        <option value="silver"><?php _e( 'Silver', "paypal-brasil-para-woocommerce" ); ?></option>
                    </select>
                </fieldset>
            </td>
        </tr>

        <!-- PRÉ-VIUALIZAÇÃO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label><?php _e( 'Button preview', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <div class="preview-container">
                    <img class="preview" :src="imagesPath + '/' + button.format + '-' + button.color + '.png'">
                </div>
            </td>
        </tr>

        <!-- PAYPAL NO CARRINHO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'shortcut_enabled' ) ); ?>"><?php _e( 'PayPal in Cart', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Enable', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <label for="<?php echo esc_attr( $this->get_field_key( 'shortcut_enabled' ) ); ?>">
                        <input type="checkbox"
                               id="<?php echo esc_attr( $this->get_field_key( 'shortcut_enabled' ) ); ?>"
                               name="<?php echo esc_attr( $this->get_field_key( 'shortcut_enabled' ) ); ?>"
                               v-model="shortcutEnabled"
                               true-value="yes"
                               false-value="">
                               <?php _e( 'Enable', "paypal-brasil-para-woocommerce" ); ?></label><br>
                    <p class="description"><?php _e( 'The PayPal digital wallet will also be offered in the shopping cart.', "paypal-brasil-para-woocommerce" ); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- SALVAR CARTEIRA DIGITAL -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'reference_enabled' ) ); ?>"><?php _e( 'Save Digital Wallet', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Enable/Disable', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <label for="<?php echo esc_attr( $this->get_field_key( 'reference_enabled' ) ); ?>">
                        <input type="checkbox"
                               id="<?php echo esc_attr( $this->get_field_key( 'reference_enabled' ) ); ?>"
                               name="<?php echo esc_attr( $this->get_field_key( 'reference_enabled' ) ); ?>"
                               v-model="referenceEnabled"
                               true-value="yes"
                               false-value="">
                               <?php _e( 'Enable', "paypal-brasil-para-woocommerce" ); ?></label><br>
                    <p class="description"><?php _e( 'The convenience of saving your customer\'s PayPal digital wallet in your store. So he no longer needs to authenticate to his PayPal account, ensuring a faster and safer purchase. <b>This feature requires PayPal approval. Contact us at 0800 721 6959 and request its release.', "paypal-brasil-para-woocommerce" ); ?></b></p>
                </fieldset>
                <div class="reference-active-description" v-bind:class="{hidden: referenceEnabled != 'yes'}">
                    <p class="description"><?php _e( 'To guarantee the integrity of your client\'s digital wallet, it is necessary
that the following options are configured in ', "paypal-brasil-para-woocommerce" ); ?><a target="_blank"
                                                                         href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=account' ) ); ?>"><?php _e( 'WooCommerce > Settings > Accounts & Privacy', "paypal-brasil-para-woocommerce" ); ?></a>.</p>
                    <br>
                    <label class="reference-options-label"
                           :class="{'reference-options-label-wrong': woocommerce_settings.enable_guest_checkout === 'yes' && !updateSettingsState.success}">
                        <span v-if="woocommerce_settings.enable_guest_checkout === 'yes' && !updateSettingsState.success"
                              class="reference-options reference-options-false dashicons dashicons-no-alt"></span>
                        <span v-if="woocommerce_settings.enable_guest_checkout === 'no' || updateSettingsState.success"
                              class="reference-options reference-options-true dashicons dashicons-yes"></span>
                        <input type="checkbox"
                               disabled
                               true-value="yes"
                               false-value="">
                               <?php _e( 'Allow your customers to place orders without an account', "paypal-brasil-para-woocommerce" ); ?>
                    </label>
                    <label class="reference-options-label"
                           :class="{'reference-options-label-wrong': woocommerce_settings.enable_checkout_login_reminder === 'no' && !updateSettingsState.success}">
                        <span v-if="woocommerce_settings.enable_checkout_login_reminder === 'no' && !updateSettingsState.success"
                              class="reference-options reference-options-false dashicons dashicons-no-alt"></span>
                        <span v-if="woocommerce_settings.enable_checkout_login_reminder === 'yes' || updateSettingsState.success"
                              class="reference-options reference-options-true dashicons dashicons-yes"></span>
                        <input type="checkbox"
                               checked
                               disabled
                               true-value="yes"
                               false-value="">
                               <?php _e( 'Allow your customers to log in to an existing account during checkout', "paypal-brasil-para-woocommerce" ); ?>
                    </label>
                    <label class="reference-options-label"
                           :class="{'reference-options-label-wrong': woocommerce_settings.enable_signup_and_login_from_checkout === 'no' && !updateSettingsState.success}">
                        <span v-if="woocommerce_settings.enable_signup_and_login_from_checkout === 'no'  && !updateSettingsState.success"
                              class="reference-options reference-options-false dashicons dashicons-no-alt"></span>
                        <span v-if="woocommerce_settings.enable_signup_and_login_from_checkout === 'yes' || updateSettingsState.success"
                              class="reference-options reference-options-true dashicons dashicons-yes"></span>
                        <input type="checkbox"
                               checked
                               disabled
                               true-value="yes"
                               false-value="">
                               <?php _e( 'Allow your customers to create an account during checkout', "paypal-brasil-para-woocommerce" ); ?>
                    </label>
                    <button type="button"
                            :disabled="updateSettingsState.executed && updateSettingsState.loading"
                            v-on:click="updateSettings"
                            class="button-primary">
						<?php _e( 'Enable settings for me', "paypal-brasil-para-woocommerce" ); ?></button>
					<?php echo wc_help_tip( 'Para facilitar, você poderá clicar neste botão que ativaremos as configurações necessárias para você' ); ?>
                    <span class="state-loading" v-if="updateSettingsState.executed && updateSettingsState.loading">
                        <span class="dashicons dashicons-update"></span>
                    </span>
                    <span class="state-success"
                          v-if="updateSettingsState.executed && !updateSettingsState.loading && updateSettingsState.success">
                        <span class="dashicons dashicons-yes"></span>
                    </span>
                    <span class="state-error"
                          v-if="updateSettingsState.executed && !updateSettingsState.loading && !updateSettingsState.success">
                        <span class="dashicons dashicons-no-alt"></span>
                    </span>
                    <br>
                    <br>
                    <p class="description"><b><?php _e( 'Only enable this feature if you have PayPal approval and if the above settings have been applied.', "paypal-brasil-para-woocommerce" ); ?></b></p>
                </div>
            </td>
        </tr>

        <h2><?php _e( 'Advanced Settings', "paypal-brasil-para-woocommerce" ); ?></h2>

        <!-- PREFIXO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'invoice_id_prefix' ) ); ?>"><?php _e( 'Prefix in the order number', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Prefix in the order number', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           id="<?php echo esc_attr( $this->get_field_key( 'invoice_id_prefix' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'invoice_id_prefix' ) ); ?>"
                           v-model="invoiceIdPrefix">
                    <p class="description"><?php _e( 'Add a prefix to the order number, this is useful for identifying you when you have more than one store processing through PayPal.', "paypal-brasil-para-woocommerce" ); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- MODO DEPURAÇÃO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'debug' ) ); ?>"><?php _e( 'Debug mode', "paypal-brasil-para-woocommerce" ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e( 'Debug mode', "paypal-brasil-para-woocommerce" ); ?></span></legend>
                    <label for="<?php echo esc_attr( $this->get_field_key( 'debug' ) ); ?>">
                        <input type="checkbox"
                               id="<?php echo esc_attr( $this->get_field_key( 'debug' ) ); ?>"
                               name="<?php echo esc_attr( $this->get_field_key( 'debug' ) ); ?>"
                               v-model="debugMode"
                               true-value="yes"
                               false-value="">
                               <?php _e( 'Enable', "paypal-brasil-para-woocommerce" ); ?></label><br>
                    <p class="description"><?php _e( 'The logs will be saved in the path: ', "paypal-brasil-para-woocommerce" ); ?><a target="_blank"
                                                                               href="<?php echo esc_url( admin_url( sprintf( 'admin.php?page=wc-status&tab=logs&log_file=%s', paypal_brasil_get_log_file( $this->id ) ) ) ); ?>"><?php _e( 'System status &gt; Logs ', "paypal-brasil-para-woocommerce" ); ?></a>.</p>
                </fieldset>
            </td>
        </tr>

        </tbody>

    </table>

</div>