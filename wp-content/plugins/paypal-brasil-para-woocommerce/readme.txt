=== PayPal Brasil para WooCommerce ===

Contributors: [apuhlmann](https://profiles.wordpress.org/apuhlmann)
Tags: paypal, paypal plus, woocommerce, woo commerce, checkout transparente, transparente, pagamento, gateway, paypal brasil, ecommerce, e-commerce
Requires at least: 4.4
Tested up to: 6.1
Stable tag: 1.4.5
Requires PHP: 7.0
License: GPLv2 or later
License URI:  [http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html)
WC requires at least: 3.6
WC tested up to:  7.5

Easily add PayPal payment options to your WooCommerce store.

== Description ==

= PayPal Transparent Checkout is now PayPal Brazil for WooCommerce! =

A complete package of payment solutions for your store. In addition to Transparent Checkout, you can now offer the traditional PayPal digital wallet, allowing your customers to pay using both a credit card and their PayPal account.

= Solutions =


* **Transparent Checkout\*:** the customer makes the payment directly on your website, without the need to have a PayPal account, using only your credit card details;
* **Digital Wallet:** the traditional PayPal solution where customers use their PayPal accounts - or create one at the time of purchase - in a secure environment and without redirection, maintaining the checkout experience within your store;
* **PayPal in Cart:** the PayPal digital wallet available directly in your store cart. The customer skips some stages of the process and makes the purchase directly from the cart, offering a more agile and safer experience;
* **Save digital wallet\*:** gain agility by saving your customer's PayPal digital wallet in their registration, so that in their next purchase they will no longer need to log into their PayPal account to approve the order.

*\* This feature requires PayPal approval, contact us at 0800 721 6959 and request it right now.*

= Advantages of PayPal =

* **Security:** Maximum level of PCI Compliance security certification and encryption on all transactions;
* **Seller Protection Program\*:** protects your sales in cases of “chargebacks”, complaints or cancellations requested by the buyer;
* **Facility in receiving sales:** pay your sales in up to 12 installments and receive them in 24 hours**, with no incremental advance fee;
* **Specialized service:** commercial and technical service to answer your questions and help you with integrations. Your customer also has a 24x7 bilingual service;
* **Sell to New Overseas Customers:** Receive payments from buyers in over 200 different markets*** and to 250 million buyers around the world.

*\*Subject to meeting Seller & Buyer Protection Program requirements.*
*\*\* Payments received into PayPal account and subject to risk and credit review by PayPal.*
*\*\*\* PayPal's Transparent Checkout only allows receipt in Brazilian Real (BRL) and US Dollar (USD) currencies.*

= Who is this module available to? =

The Transparent Checkout is only available for PayPal accounts registered with CNPJ (Company Account). If your account is an individual, you must open a PayPal account for a business via [this link](https://www.paypal.com/bizsignup/).

As for the Digital Wallet, you can use it both with a business account and an individual account.

= Approvals =

Some of the PayPal solutions require commercial approval to use:

* **Transparent Checkout & Save digital wallet:** contact us at 0800 047 4482 and request it right now.

= Requirements =

By default, WooCommerce does not ask for CPF/CNPJ information when registering. However, this information is necessary for PayPal's solutions to perform a more accurate risk analysis. Thus, this field becomes mandatory for the use of this plugin.

We recommend using a plugin, for example “[Brazilian Market on WooCommerce](https://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/)".

= Compatibility =

Compatible from version 3.6 of WooCommerce.

= Installation =

Install the module normally through the module manager or from the download of this repository copy the content inside the plugins folder of your WooCommerce installation.

If you have any questions, please contact us on 0800 047 4482.

= Questions/Support =

If you have any questions or difficulties using the plugin, access the Support section through this [link](https://wordpress.org/support/plugin/paypal-brasil-para-woocommerce).

== Frequently Asked Questions ==

= Plugin installation: =

* Upload the plugin files to the "wp-content/plugins" folder, or install using the WordPress plugin installer.
* Activate the plugin.

== Changelog ==

= 1.0 =
* Plugin launch.

= 1.0.1 =
* Changed calculations with prices in cents for PHP precision functions.

= 1.0.2 =
* Created fallback for cases that do not have the BC math extension activated.

= 1.0.3 =
* Optimized method for mathematical calculations.
* Improved handling of webhooks.
* Adjustment for some discount plugins.
* Fixed shortcut display even with disabled gateway.
* Fixed issue with refund.
* Fixed issue with webhooks.
* Fixed issue with digital products using PayPal in Cart.

= 1.0.4 =
* Added support for some discount plugins.
* Fixed issue that caused infinite loading due to discount plugins.
* Improved treatments for discount plugins and native discount.

= 1.0.5 =
* Modified handling of actions in Transparent Checkout.
* Fixed possible webhooks issue for some installs.
* Security updates for dependency packages.
* Fixed payment page for manual order.
* Removed scripts when payment method is not activated.
* Fixed issue with virtual products.

= 1.0.6 =
* Fixed some issues with webhooks.
* Fixed conflict with PayPal Transparent Checkout plugin
* Fixed styling issue in Digital Wallet.

= 1.1.0 =
* Added validation on Transparent Checkout order values.

= 1.1.1 =
* Fixed checkout errors.
* Fixed bug that duplicated button at checkout.
* Added authorization message for Transparent Checkout features.
* Changed support phone.

= 1.1.2 =
* Removed second button below "Pay with PayPal".

= 1.1.3 =
* Fixed warnings.
* Updated support version.

= 1.1.4 =
* Fixed issue with closing PayPal window.

= 1.2.0 =
* Adjustments to PayPal API calls.
* Fixed address formatting.

= 1.2.1 =
* Optimized state validation.
* Improved support for multiple currencies.

= 1.3.0 =
* Added support for countries without states.

= 1.4.0 =
* Updated dependencies.
* Improved log system.
* Added Transparent Checkout installment information to the order.
* Fixed potential session issues.
* Added support for WooCommerce 5.5 and WordPress 5.8.

= 1.4.1 =
* Updated dependencies.
* Fixed bug in webhooks.
* Added precise mathematical calculations to avoid validation errors.

= 1.4.2 =
* Fixed conflict in total checkout amount.

= 1.4.3 =
* Fixed vulnerability warning.

= 1.4.4 =
* Added support for WooCommerce 7.5.1 and WordPress 6.1.
* Added pt-BR translation as per i18n standard.

== Upgrade Notice ==

= 1.4.5 =
* Improved rendering of errors sent by Paypal.

== Screenshots ==

1. Example of unfilled data in the Storefront theme.
2. Credit card checkout example saved in Storefront theme.
3. PayPal payment area.
4. Pay with PayPal Digital Wallet.
5. PayPal account login screen.