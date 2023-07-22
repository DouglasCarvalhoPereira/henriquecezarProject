<?php
/**
 * @package              HWCF_GLOBAl
 * @wordpress-plugin
 * 
 * Plugin Name:          Hide Cart Functions
 * Plugin URI:           http://wordpress.org/plugins/hide-cart-functions
 * Description:          Hide a product's price, add to cart button, quantity selector, and product options on any product in any order. Also, add a message below or above the short product description. Apply rules to any category, product ID, or custom elements.
 * Version:              1.0.6
 * Author:               Artios Media
 * Author URI:           http://www.artiosmedia.com
 * Assisting Developer:  Prashant Chaudhari
 * Copyright:            © 2022-2023 Artios Media (email : contact@artiosmedia.com).
 * License:              GNU General Public License v3.0
 * License URI:          http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:          hide-cart-functions
 * Domain Path:          /languages
 * Tested up to:         6.1.1
 * WC requires at least: 4.6.0
 * WC tested up to:      7.3.0
 * PHP tested up to:     8.2.2
 */

namespace Artiosmedia\WC_Purchase_Customization;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('HWCF_GLOBAl_VERSION', '1.0.5');
define('HWCF_GLOBAl_NAME', 'hwcf-global');
define('HWCF_GLOBAl_ABSPATH', __DIR__);
define('HWCF_GLOBAl_BASE_NAME', plugin_basename(__FILE__));
define('HWCF_GLOBAl_DIR', plugin_dir_path(__FILE__));
define('HWCF_GLOBAl_URL', plugin_dir_url(__FILE__));

include(HWCF_GLOBAl_DIR . 'inc/utilities-functions.php');
require HWCF_GLOBAl_DIR . 'admin/hwcf-table.php';
require HWCF_GLOBAl_DIR . 'admin/hwcf-admin.php';

if (! class_exists('HWCF_GLOBAl')) {

    class HWCF_GLOBAl
    {

        /**
         * Static instance of this class
         *
         * @var \selfx
         */
        private static $_instance;


        public function __construct()
        {
            // Load translation
            add_action( 'init', [$this, 'init_translation'] );
            //apply hide selector settings
            add_action( 'wp_head', [$this, 'apply_settings'] );
            //add short description message if added
            add_filter( 'woocommerce_short_description', [$this, 'short_description'], 999 );

            //run plugin option clean-up on plugin deactivation
            register_deactivation_hook(__FILE__, [$this, 'deactivation']);
            register_activation_hook(__FILE__, [$this, 'activation']);
            add_filter( "woocommerce_get_price_html", [$this, 'modify_woocommerce_price'], 999 );
            add_filter( "woocommerce_cart_item_price", [$this, 'modify_woocommerce_price'], 999 );
        }

        public static function init()
        {
            if (!self::$_instance) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        /**
         * Load translation
         *
         * @since    1.0.0
         */
        public function init_translation()
        {
 
            $domain = 'hide-cart-functions';
            $mofile_custom = sprintf( '%s-%s.mo', $domain, get_locale() );
            $locations = array(
                    trailingslashit( WP_LANG_DIR . '/' . $domain ),
                    trailingslashit( WP_LANG_DIR . '/loco/plugins/'),
                    trailingslashit( WP_LANG_DIR ),
                    trailingslashit( HWCF_GLOBAl_DIR . 'languages' ),
                );

            // Update Loggedin Users Checkbox
            $settings_data    = hwcf_get_hwcf_data();           
            $UpdateSettingData=array();
            if(!empty($settings_data) && is_array($settings_data)){                
                foreach($settings_data as $option){                  
                    $option['loggedinUsers'] = ($option['loggedinUsers']!='' ? $option['loggedinUsers'] : ''); 
                    $UpdateSettingData[$option['ID']]=$option;
                }   
                update_option('hwcf_settings_data', $UpdateSettingData);
            }


            // Try custom locations in WP_LANG_DIR.
            foreach ( $locations as $location ) {
                if ( load_textdomain( 'hide-cart-functions', $location . $mofile_custom ) ) {
                    return true;
                }
            }
        }

        /**
         * Apply hide selector settings
         *
         * @since    1.0.0
         */
        public function apply_settings()
        {
            $settings_data    = hwcf_get_hwcf_data();
            $hidding_selector = [];
			$ghost_protocol = [];
            if(!empty($settings_data) && is_array($settings_data)){
                foreach($settings_data as $option){
                    $hide_all = true;
                    $hide_products = false;
                    $hide_categories = false;

                    $loggedin_users = isset($option['loggedinUsers']) ? explode(",",$option['loggedinUsers']) : array();  
                    $hide_quantity = isset($option['hwcf_hide_quantity']) ? (int)($option['hwcf_hide_quantity']) : 0;
                    $hide_add_to_cart = isset($option['hwcf_hide_add_to_cart']) ? (int)($option['hwcf_hide_add_to_cart']) : 0;
                    $hide_price = isset($option['hwcf_hide_price']) ? (int)($option['hwcf_hide_price']) : 0;
                    $hide_options = isset($option['hwcf_hide_options']) ? (int)($option['hwcf_hide_options']) : 0;
                    $custom_element = isset($option['hwcf_custom_element']) ? $option['hwcf_custom_element'] : '';
                    $custom_message = isset($option['hwcf_custom_message']) ? stripslashes($option['hwcf_custom_message']) : '';
                    $categories_limit = isset($option['hwcf_categories']) ? (array)$option['hwcf_categories'] : [];
                    $categories_limit = array_filter($categories_limit);
                    $products_limit = isset($option['hwcf_products']) ? $option['hwcf_products'] : '';

                   
                    if (isset($option['hwcf_disable']) && (int)$option['hwcf_disable'] > 0) {
                        //skip setup if it's disabled
                        continue;
                    }
                     if( !is_user_logged_in() && in_array(1, $loggedin_users)){

                    }elseif(is_user_logged_in() && in_array(2, $loggedin_users) ){

                    }elseif( isset( $loggedin_users[0] ) && $loggedin_users[0] == '' ){

                    }else{   
                     continue;
                    }
                     
                    if (!empty($categories_limit)) {
                        //category limitation is 
                        $hide_all = false;
                        $hide_categories = true;
                    }

                    if (!empty(trim($products_limit))) {
                        //product limitation is enabled
                        $hide_all = false;
                        $hide_products = true;
                    }

                    if($hide_all){

                        if($hide_quantity){
                            $hidding_selector[] = '.product.type-product .quantity';
                            $hidding_selector[] = '.product.type-product .product-quantity';
                        }
                        if($hide_add_to_cart){
                            $hidding_selector[] = '.product.type-product .single_add_to_cart_button';
                            $hidding_selector[] = '.product.type-product .add_to_cart_button';
                        }
                        if($hide_price){
							$ghost_protocol[] = '.single-product .product .summary .price';    // added to remove entire container
							$ghost_protocol[] = '.products .product .price';
                            $hidding_selector[] = '.product.type-product .woocommerce-Price-amount';
                            $hidding_selector[] = '.product.type-product .fusion-price-rating .price';
                            $hidding_selector[] = '.widget .woocommerce-Price-amount';
                            $hidding_selector[] = '.widget .fusion-price-rating .price';
                        }
                        if($hide_options){
                            $hidding_selector[] = '.product.type-product .variations';
                            $hidding_selector[] = '.product.type-product .product_type_variable.add_to_cart_button';
                        }

                        if(!empty(trim($custom_element))){
                            $cl_element = explode (",", $custom_element);
                            $cl_element = array_map('trim', $cl_element);
                            $hidding_selector = array_merge($hidding_selector, $cl_element);
                        }

                    }else{

                        if($hide_products){
                            $product_ids = explode (",", $products_limit);
                            $product_ids = array_map('trim', $product_ids);
                            foreach($product_ids as $product_id){
                                $product_id = (int)$product_id;
                                if ($product_id > 0) {
                                    if ($hide_quantity) {
                                        $hidding_selector[] = '.product.type-product.post-'.$product_id.' .quantity';
                                        $hidding_selector[] = '.product.type-product.post-'.$product_id.' .product-quantity';
                                    }
                                    if ($hide_add_to_cart) {
                                        $hidding_selector[] = '.product.type-product.post-'.$product_id.' .single_add_to_cart_button';
                                        $hidding_selector[] = '.product.type-product.post-'.$product_id.' .add_to_cart_button';
                                    }
                                    if ($hide_price) {
										$ghost_protocol[] = '.product.type-product.post-'.$product_id . ' .price';
                                        $hidding_selector[] = '.product.type-product.post-'.$product_id.' .woocommerce-Price-amount';
                                        $hidding_selector[] = '.product.type-product.post-'.$product_id.' .fusion-price-rating .price';
                                    }
                                    if ($hide_options) {
                                        $hidding_selector[] = '.product.type-product.post-'.$product_id.' .variations';
                                        $hidding_selector[] = '.product.type-product.post-'.$product_id.' .product_type_variable.add_to_cart_button';
                                    }

                                    if(!empty(trim( $custom_element ))){
                                        $cl_element = explode (",", $custom_element);
                                        $cl_element = array_map(function( $el ) use ($product_id) {
                                            return '.product.type-product.post-'.$product_id. " " . trim( $el );
                                        }, $cl_element);
                                        $hidding_selector = array_merge($hidding_selector, $cl_element);
                                    }

                                }
                            }
                        }

                        if($hide_categories){
                            $category_ids = $categories_limit;
                            foreach($category_ids as $category_id){
                                $category_id = (int)$category_id;
                                if ($category_id > 0) {
                                    $category_data = get_term($category_id, 'product_cat');

                                    if ($category_data && !is_wp_error($category_data) && is_object($category_data) && isset($category_data->slug)) {
                                        $category_slug = $category_data->slug;

                                        if ($hide_quantity) {
                                            $hidding_selector[] = '.product.type-product.product_cat-'.$category_slug.' .quantity';
                                            $hidding_selector[] = '.product.type-product.product_cat-'.$category_slug.' .product-quantity';
                                        }
                                        if ($hide_add_to_cart) {
                                            $hidding_selector[] = '.product.type-product.product_cat-'.$category_slug.' .single_add_to_cart_button';
                                            $hidding_selector[] = '.product.type-product.product_cat-'.$category_slug.' .add_to_cart_button';
                                        }
                                        if ($hide_price) {
											$ghost_protocol[] = '.product.type-product.product_cat-'.$category_slug.' .summary .price';
											$ghost_protocol[] = '.product.type-product.product_cat-'.$category_slug.' .price';
                                            $hidding_selector[] = '.product.type-product.product_cat-'.$category_slug.' .woocommerce-Price-amount';
                                            $hidding_selector[] = '.product.type-product.product_cat-'.$category_slug.' .fusion-price-rating .price';
                                        }
                                        if ($hide_options) {
                                            $hidding_selector[] = '.product.type-product.product_cat-'.$category_slug.' .variations';
                                            $hidding_selector[] = '.product.type-product.product_cat-'.$category_slug.' .product_type_variable.add_to_cart_button';
                                        }

                                        if(!empty(trim( $custom_element ))){
                                            $cl_element = explode (",", $custom_element);
                                            $cl_element = array_map(function( $el ) use ($category_slug) {
                                                return '.product.type-product.product_cat-'.$category_slug . " " . trim( $el );
                                            }, $cl_element);
                                            $hidding_selector = array_merge($hidding_selector, $cl_element);
                                        }

                                    }
                                }
                            }
                        }

                    }

                }
            }

            echo '<style id="hwcf-style">.woocommerce-variation-description .hwcf-ui-custom-message ';
			if(!empty($hidding_selector)){
                echo esc_html(', '. join(',', $hidding_selector) .'');
            }
            echo '{ display: none!important;}';

            if( !empty($ghost_protocol) ){
                echo esc_html(' '. join( ',', $ghost_protocol ) . '' );
                echo '{visibility:hidden !important;}';
            }
            echo '</style>';
        }

        /**
         * Add short description message if added
         *
         * @since    1.0.0
         */
        public function short_description($excerpt)
        {
            global $post;

            $settings_data    = hwcf_get_hwcf_data();
            $hidding_selector = [];

            if (!empty($settings_data) && is_array($settings_data)) {
                foreach ($settings_data as $option) {

                    $loggedin_users = isset($option['loggedinUsers']) ? explode(",",$option['loggedinUsers']) : array();  
                    $custom_message = isset($option['hwcf_custom_message']) ? stripslashes($option['hwcf_custom_message']) : '';
                    $custom_message_postion = isset($option['hwcf_custom_message_position']) ? stripslashes($option['hwcf_custom_message_position']) : 'below';
                    $categories_limit = isset($option['hwcf_categories']) ? (array)$option['hwcf_categories'] : [];
                    $categories_limit = array_filter($categories_limit);
                    $products_limit = isset($option['hwcf_products']) ? $option['hwcf_products'] : '';


                    if (isset($option['hwcf_disable']) && (int)$option['hwcf_disable'] > 0) {
                        //skip setup if it's disabled
                        continue;
                    }
   
                    if( !is_user_logged_in() && in_array(1, $loggedin_users)){

                    }elseif(is_user_logged_in() && in_array(2, $loggedin_users) ){

                    }elseif( isset( $loggedin_users[0] ) && $loggedin_users[0] == '' ){

                    }else{   
                     continue;
                    }

                    if (!empty(trim($products_limit))) {
                        $product_ids = explode (",", $products_limit);
                        $product_ids = array_map('trim', $product_ids);
                        if(!in_array($post->ID, $product_ids)){
                            continue;
                        }
                    }

                    if (!empty($categories_limit)) {
                        $category_ids = $categories_limit;
                        $cat_ids = wp_get_post_terms( $post->ID, 'product_cat', array( 'fields' => 'ids' ) );
                        $intersection = array_intersect($category_ids, $cat_ids);
                        if(count($intersection) === 0){
                            continue;
                        }
                    }

                    if (!empty(trim($custom_message))) {
                        if($custom_message_postion === 'below'){
                            $excerpt .= " <div class='hwcf-ui-custom-message'> ".$custom_message."</div>";
                        }else{
                            $excerpt = "<div class='hwcf-ui-custom-message'> ".$custom_message."</div> ".$excerpt;
                        }
                    }
 
                }
            }

            return $excerpt;
            
        }

        /**
         * 
         * Modify woocommerce price text for selected user type/role
         * 
         */
        function modify_woocommerce_price( $price ){
            $settings_data    = hwcf_get_hwcf_data();
            global $id;

            if(!empty($settings_data) && is_array($settings_data)){
                foreach($settings_data as $option){

                    $loggedin_users = isset($option['loggedinUsers']) ? explode(",",$option['loggedinUsers']) : array(); 
                    $overridePriceTag = ( isset($option['overridePriceTag']) && !empty($option['overridePriceTag']) )? $option['overridePriceTag'] : $price;
                    $product_ids = isset($option['hwcf_products']) ? $option['hwcf_products'] : null ;
                    
                    if (isset($option['hwcf_disable']) && (int)$option['hwcf_disable'] > 0) {
                        //skip setup if it's disabled
                        continue;
                    }

                  if( !is_user_logged_in() && in_array(1, $loggedin_users)){

                    }elseif(is_user_logged_in() && in_array(2, $loggedin_users) ){

                    }elseif( isset( $loggedin_users[0] ) && $loggedin_users[0] == '' ){

                    }else{   
                     continue;
                    }

                    if( $product_ids != null ){

                        $product_ids = explode( ",", $product_ids );
                        $product_ids = array_map('trim', $product_ids );

                        foreach( $product_ids as $product_id ){
                            
                            if( $product_id == $id ){      
                                    $price = str_replace( '[price]', $price, $overridePriceTag );
                            }
                        }
                    }else{
                            $price = str_replace( '[price]', $price, $overridePriceTag );
                    }

                }

            }
            return $price ;
        }

        /**
         * Run plugin option clean-up on plugin deactivation
         * 
         * @since    1.0.0
         */
        public function deactivation() {
           if((int)get_option('hwcf_delete_on_deactivation', 0) === 1){
               delete_option('hwcf_delete_on_deactivation');
               delete_option('pcfw_notice_dismiss');
               delete_option('pcfw_version_1_0_0_installed');
               delete_option('hwcf_settings_data');
               delete_option('hwcf_settings_ids_increament');
           }
        }
        public function activation() {
            HWCF_Fix_Double_Selection();
        }

    }

    HWCF_GLOBAl::init();
}



