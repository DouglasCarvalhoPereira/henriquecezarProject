<?php
/**
 * Default Menu adding
 * Generally Submenu added at Theme option
 * 
 * @package WooCommerce add to cart Text change
 * @since 1.0.8 Again added after review
 */
function wactc_admin_menu() {
    add_submenu_page( 'woocommerce', esc_html__( 'ADD TO CART', 'wactc' ), esc_html__( 'ADD TO CART', 'wactc' ), 'manage_options', 'wactc_button_text', 'wactc_text_form' );
}
add_action( 'admin_menu', 'wactc_admin_menu' );