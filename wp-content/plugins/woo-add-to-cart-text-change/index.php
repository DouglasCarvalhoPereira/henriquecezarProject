<?php
/**
 * Plugin Name: Add to Cart Text Changer by Code Astrology
 * Description: WooCommerce Product's [ Add to cart ] Button text change and easily set custom text by your own language. WooCommerce is one of the best Ecommerce plugin. Sometime can be need to change Add_to_cart Button text changing. Developed by <a href='https://codersaiful.net'>Saiful Islam</a>
 * Plugin URI: https://codeastrology.com/pricing-add-to-cart-button-changer/
 * Author: Saiful Islam
 * Version: 2.0
 * Author URI: https://profiles.wordpress.org/codersaiful
 * 
 * Requires at least:    4.0.0
 * Tested up to:         6.2.2
 * WC requires at least: 3.0.0
 * WC tested up to: 	 7.7.2
 * 
 * Text Domain: wactc
 * Domain Path: /languages/
 *
 * @package WACTC
 * @category Core
 *
 */
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.8
 * @package    WACTC
 * @author     BM Rafiul Alam <bmrafiul.alam@gmail.com>
 */
if (!function_exists('activate_free')){
    /**
     * The code that runs during plugin activation.
     * This action is documented in includes/class-activator.php
     */
    function activate_free(){
        require_once plugin_dir_path(__FILE__) . 'includes/class-activator.php';
        Free_Activator::activate();
    }
}
//Activation Hook
register_activation_hook(__FILE__, 'activate_free');


//End Activation
$wactc_default_args = array(
    'icon'      =>  'no_icon',//probale value: no_icon, only_icon, icon_left, icon_right
    'simple'    =>  __( 'Add to cart', 'wactc' ),
    'variable'  =>  __( 'Select options', 'wactc' ),
    'grouped'   =>  __( 'View products', 'wactc' ),
    'external'  =>  '',
);

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

define( 'WACTC_NAME', __( 'Add to Cart Text Changer', 'wactc' ) );
define( 'WACTC_PLUGIN_BASE_FOLDER', plugin_basename( dirname(__FILE__) ) );
define( 'WACTC_PLUGIN_BASE_FILE', plugin_basename(__FILE__) );

define( "WACTC_BASE_URL", plugins_url() . '/' . plugin_basename( dirname(__FILE__) ) . '/' );
define( "wactc_dir_base", dirname( __FILE__ ) . '/' );
define( "WACTC_BASE_DIR", str_replace( '\\', '/', wactc_dir_base ) );

//Define Options Path
define( 'WACTC_TABLE_OPTIONS_PATH', WACTC_BASE_DIR . 'modules/options' . DIRECTORY_SEPARATOR );
define( 'WACTC_TABLE_OPTIONS_URL', WACTC_BASE_URL . 'modules/options' );


add_action('plugins_loaded','wactc_free_plugin_loaded');

function wactc_free_plugin_loaded(){
    // Check if WooCommerce Activated
    if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            add_action( 'admin_notices', 'wactc_free_admin_notice_missing_wc' );
            return;
    }

    if( is_admin() ){
        include( WACTC_BASE_DIR . 'admin/plugin_setting_link.php' ); //To show Setting link at plugin page
        
        include( WACTC_BASE_DIR . 'admin/menu.php' ); //Adding menu to Dashboard.
        include( WACTC_BASE_DIR . 'admin/button_text_form.php' ); //Add to Cart Button text Customizing form.
  
    }
    
    $wactc_values = get_option( 'wactc_default_add_to_cart_text' );
    if ( $wactc_values && is_array( $wactc_values ) ) {
        include_once WACTC_BASE_DIR . 'includes/add_to_cart_front.php'; //Add Filter for add to cart button 
    }

}
/**
 * Admin notice
 *
 * Warning when the site doesn't have Elementor installed or activated.
 *
 * @since 1.0.0
 *
 * @access public
 */
function wactc_free_admin_notice_missing_wc() {

       if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

       $message = sprintf(
               
               esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'wactc' ),
               '<strong>' . WACTC_NAME . '</strong>',
               '<strong><a href="' . esc_url( 'https://wordpress.org/plugins/woocommerce/' ) . '" target="_blank">' . esc_html__( 'WooCommerce', 'wactc' ) . '</a></strong>'
       );

       printf( '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', $message );

   }
 
/**
 * Plugin Install function
 * 
 * @package WooCommerce add to cart Text change
 * @since v1.0
 */
function wactc_free_install(){
    global $wactc_default_args;
    
    $current = get_option( 'wactc_default_add_to_cart_text' );

    if( $current && !is_array( $current ) && is_string( $current ) ){
        $wactc_default_args['simple'] = $current;
    }else{
        $wactc_default_args = $current;
    }
    update_option( 'wactc_default_add_to_cart_text', $wactc_default_args );
}

/**
 * Plugin Uninstallation
 * Currently nothing to do.
 * 
 * @package WooCommerce add to cart Text change
 * @since v1.0
 */
function wactc_free_uninstall(){
    //Nothing to do.
}   
register_activation_hook(__FILE__, 'wactc_free_install');
register_deactivation_hook(__FILE__, 'wactc_free_uninstall');