<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Add_Filter for Front-end Add to cart button
 * For Shop page or for Product Loop
 * 
 * @package WooCommerce add to cart Text change
 * @since v1.0
 */

function wactc_add_to_cart_text_filter( $text, $product ){
    $current = get_option( 'wactc_default_add_to_cart_text' );

    $default_text = $text;
    $type = $product->get_type();
    if( $current['icon'] == 'only_icon' && isset( $current[$type] ) ){
        $default_text = false;
        $current['simple'] = '';
    }
    
    $return = isset( $current[$type] ) && !empty( $current[$type] ) ? $current[$type] : $default_text;

    return  $return;
}
add_filter( 'woocommerce_product_add_to_cart_text', 'wactc_add_to_cart_text_filter', 10, 2 );

/**
 * Add_Filter for Front-end Add to cart button
 * For Single page Add to Cart Button
 * 
 * @package WooCommerce add to cart Text change
 * @since v1.0
 */

function wactc_add_to_cart_text_single_filter( $text, $product ){
    $current = get_option( 'wactc_default_add_to_cart_text' );
    $default_text = $text;
    if( $current['icon'] == 'only_icon' ){
        $default_text = false;
        $current['simple'] = '';
    }
    $custom_text = $current['simple'];
    $type = $product->get_type();
    
    $return = !empty( $custom_text ) && $type !== 'external' ? $custom_text : $default_text ;
    return $return;
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'wactc_add_to_cart_text_single_filter', 10, 2 );

/**
 * Style file / CSS file adding here 
 * Only for fonts (WooCommerce) font initialize
 * 
 * @since 1.5
 */
function wactc_adding_style_file(){
    $src = WACTC_BASE_URL . 'css/style.css';
    wp_enqueue_style( 'wactc_style', $src, false, '1.0', 'all' );
}
add_action( 'wp_enqueue_scripts', 'wactc_adding_style_file', 10 );

function wactc_customized_style_in_head(){
    $saved_data = get_option( 'wactc_default_add_to_cart_text' );
    $icon = $saved_data['icon'];
    $font_size = $icon == 'only_icon' ? '0' : 'initial';
    $after_before = $icon != 'icon_right' ? 'before' : 'after';
    if( $icon != 'no_icon' ):
    ?>
    <style>
        .single_add_to_cart_button.buttonss,.add_to_cart_buttonss{
            font-size: <?php echo wp_kses_post( $font_size ); ?>;
        }
        .single_add_to_cart_button.button:<?php echo wp_kses_post( $after_before ); ?>,.add_to_cart_button:<?php echo wp_kses_post( $after_before ); ?> {
            content: "\e01d";
            z-index: 99;
            font-family: WooCommerce;
            font-size: initial;
            padding: 0 4px;     
        } 
    </style>
    <?php
    endif;
    $image_bool = false;
    if( $saved_data['icon'] == '' && isset( $saved_data['cart_icon_preset'] )&& !empty( $saved_data['cart_icon_preset'] ) ){
        $name = $saved_data['cart_icon_preset'];
        $url = esc_url( WACTC_BASE_URL . 'images/' . $name . '.png' );
        $width = $height = 20;
        $image_bool = true;
        
    }elseif( $saved_data['icon'] == '' && isset( $saved_data['cart_icon_image']['url'] ) && !empty( $saved_data['cart_icon_image']['url'] ) ){
        $image = $saved_data['cart_icon_image'];
        $url = $image['url'];
        $width = isset( $image['width'] ) && !empty( $image['width'] ) ? absint( $image['width'] ) : 20;
        $height = isset( $image['height'] ) && !empty( $image['height'] ) ? absint( $image['height'] ) : 20;
        $image_bool = true;
    }
    
    if( $image_bool ){
        ?>
    <style>
    .single_add_to_cart_button.button:before, .add_to_cart_button:before {
        content: "";
        z-index: 99;
        position: relative;
        display: inline-block;
        width: <?php echo esc_attr( $width ); ?>px;
        height: <?php echo esc_attr( $height ); ?>px;
        background: url('<?php echo esc_url( $url ); ?>') no-repeat;
        background-size: <?php echo esc_attr( $width ); ?>px <?php echo esc_attr( $height ); ?>px;
        margin-right: 7px;
        float: left;
    }
    </style>    
    <?php
    }
}
add_action( 'wp_head', 'wactc_customized_style_in_head', 10 );