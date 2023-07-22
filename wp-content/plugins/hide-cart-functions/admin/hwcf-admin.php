<?php
namespace Artiosmedia\WC_Purchase_Customization_Admin;
use hwcf_List;
if (! class_exists('hwcf_admin')) {
    class hwcf_Admin
    {
        const MENU_SLUG = 'woocommerce';

        // class instance
        public static $instance;

        // WP_List_Table object
        public $terms_table;

        /**
         * Constructor
         *
         * @return void
         * @author Olatechpro
         */
        public function __construct()
        {
            if(!is_admin()){
                return;
            }
            add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
            // admin menu
            add_action('admin_menu', [$this, 'admin_menu'], 10);
            //add settings page to plugin activation menu
            add_filter('plugin_action_links_' . HWCF_GLOBAl_BASE_NAME, [$this, 'plugin_settings_link']);
            //add donate link to plugim row
            add_filter('plugin_row_meta', [$this, 'add_description_link'], 10, 2);
            //add more info link to plugin row
            add_filter('plugin_row_meta', [$this, 'add_details_link'], 10, 4);
            //admin scripts
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
            //plugin review admin notice
            add_action("admin_notices", [$this,"admin_notice"]);
            //plugin review admin notice ajax handler
            add_action("wp_ajax_pcfw_dismiss_notice", [$this,"dismiss_notice"]);
            //plugin data delete on deactivation ajax handler
            add_action("wp_ajax_hwcf_delete_on_deactivation", [$this,"hwcf_delete_on_deactivation_callback"]);
            //installation/upgrade code
            add_action("admin_init", [$this, "admin_init"]);

            //woocommerce product search field
            add_action("wp_ajax_custom_product_search",[$this,"custom_product_search"]);


        }

        public static function set_screen($status, $option, $value)
        {
            return $value;
        }

        /** 
         * Singleton instance
         */
        public static function get_instance()
        {
            if (!isset(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Installation/upgrade code
         * 
         * @since    1.0.0
         */
        public function admin_init() {
            if (!get_option("pcfw_version_1_0_0_installed")) {
                update_option("pcfw_notice_dismiss", date('Y-m-d', strtotime('+30 days')));
                update_option("pcfw_version_1_0_0_installed", 1);
            }
        }

        /**
         * Plugin data delete on deactivation ajax handler
         * 
         * @since    1.0.0
         */
        public function hwcf_delete_on_deactivation_callback() {
            if(current_user_can("manage_options")) {
                update_option("hwcf_delete_on_deactivation", isset($_POST["settings_action"]) ? (int)$_POST["settings_action"] : 0);
                wp_send_json(array("status" => true));
            }
        }

        /**
         * Plugin review admin notice ajax handler
         * 
         * @since    1.0.0
         */
        public function dismiss_notice() {
            if(current_user_can("manage_options")) {
                if(!empty($_POST["dismissed_final"])) {
                    update_option("pcfw_notice_dismiss", null);
                } else {
                    update_option("pcfw_notice_dismiss", date('Y-m-d',strtotime('+30 days')));
                }
                wp_send_json(array("status" => true));
            }
            
        }

         // wocommerce customproduct search ajax handler
        
        public function custom_product_search(){
             global $wpdb;
            $product_name = $_POST['product_name'];

            $search_query = "SELECT ID,post_name FROM {$wpdb->prefix}posts WHERE post_type = 'product' AND post_status='publish' AND post_name LIKE '%$product_name%'";
            $results=$wpdb->get_results($search_query);

           wp_send_json($results);
        }

        
        /**
         * Plugin review admin notice
         * 
         * @since    1.0.0
         */
        public function admin_notice() {
            $last_dismissed = get_option("pcfw_notice_dismiss");
    
            if( !empty($last_dismissed) && current_time('timestamp') >= strtotime($last_dismissed)) 
            {
                echo '<div class="notice notice-info is-dismissible" id="pcfw_notice">
                <p>How do you like <strong>Hide Cart Functions</strong>? Your feedback assures the continued maintenance of this plugin! <a class="button button-primary pcfw-feedback" href="https://wordpress.org/plugins/hide-cart-functions/#reviews" target="_blank">Leave Feedback</a></p>
                </div>';
            }
        }


        /**
         * Register the stylesheets for the public-facing side of the site.
         *
         * @since    1.0.0
         */
        public function enqueue_admin_styles()
        {
            global $pagenow;
    
            wp_enqueue_style(HWCF_GLOBAl_BASE_NAME . '-multi-select', HWCF_GLOBAl_URL . 'admin/assets/css/multi-select.css', [], HWCF_GLOBAl_VERSION, 'all');
            wp_enqueue_style(HWCF_GLOBAl_BASE_NAME . '-style', HWCF_GLOBAl_URL . 'admin/assets/css/style.css', [], HWCF_GLOBAl_VERSION, 'all');
           
            wp_enqueue_script(HWCF_GLOBAl_BASE_NAME . '-multi-select', HWCF_GLOBAl_URL . 'admin/assets/js/multi-select.js', ['jquery'], HWCF_GLOBAl_VERSION, false);
            wp_enqueue_script(HWCF_GLOBAl_BASE_NAME . '-admin-customs', HWCF_GLOBAl_URL . 'admin/assets/js/customs.js', ['jquery'], HWCF_GLOBAl_VERSION, false);
            //localize wpforo js
            wp_localize_script(HWCF_GLOBAl_BASE_NAME . '-admin-customs', 'hwcf', [
                'ajaxurl'     => admin_url('admin-ajax.php'),
                'search_text'     => esc_html__('Select category', 'hide-cart-functions'),
                'search_none'     => esc_html__('No results found.', 'hide-cart-functions'),
            ]);

              //select to library
             wp_enqueue_script('wc-enhanced-select');
             wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css');
    
        }

        /**
         * Add WP admin menu for Tags
         *
         * @return void
         * @author Olatechpro
         */
        public function admin_menu()
        {
            $hook = add_submenu_page(
                self::MENU_SLUG,
                esc_html__('Hide Functions', 'hide-cart-functions'),
                esc_html__('Hide Functions', 'hide-cart-functions'),
                'manage_options',
                'hwcf_settings',
                [
                    $this,
                    'page_manage_hwcf',
                ]
            );

            add_action("load-$hook", [$this, 'screen_option']);
        }

        /**
         * Add settings page to plugin activation menu
         *
         * @return void
         * @author Olatechpro
         */
        public function plugin_settings_link($links)
        {
            return array_merge(array(
                '<a href="' .
                admin_url('admin.php?page=hwcf_settings') .
                '">' . esc_html__('Settings', 'hide-cart-functions') . '</a>'
             ), $links);;
        }

        /**
         * add donate link to plugim row
         *
         * @param array $links
         * @param string $file
         * @return array
         */
        public function add_description_link($links, $file)
        {
            if (HWCF_GLOBAl_BASE_NAME == $file) {
                $row_meta = array(
                    'donation' => '<a href="' . esc_url('https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=E7LS2JGFPLTH2') . '" target="_blank">' . esc_html__('Donation for Homeless', 'hide-cart-functions') . '</a>'
                );
                return array_merge($links, $row_meta);
            }
            return (array) $links;
        }

        /**
         * add more info link to plugin row
         *
         * @param array $links
         * @param srting $plugin_file
         * @param array $plugin_data
         * @return array
         */
        public function add_details_link($links, $plugin_file, $plugin_data)
        {
            if (
                ( isset($plugin_data['PluginURI']) )
                && 
                ( 
                    false !== strpos($plugin_data['PluginURI'], 'http://wordpress.org/extend/plugins/' )
                    || false !== strpos($plugin_data['PluginURI'], 'http://wordpress.org/plugins/hide-cart-functions' )
                )
                ) {
                $slug = basename($plugin_data['PluginURI']);
                $links[2] = sprintf('<a href="%s" class="thickbox" title="%s">%s</a>', self_admin_url('plugin-install.php?tab=plugin-information&amp;plugin=' . $slug . '&amp;TB_iframe=true&amp;width=772&amp;height=563'), esc_attr(sprintf(__('More information about %s', 'hide-cart-functions'), $plugin_data['Name'])), __('View Details', 'hide-cart-functions'));
            }
            return $links;
        }

        /**
         * Screen options
         */
        public function screen_option()
        {
            $option = 'per_page';
            $args   = [
                'label'   => esc_html__('Number of items per page', 'hide-cart-functions'),
                'default' => 20,
                'option'  => 'hwcf_settings_per_page'
            ];

            add_screen_option($option, $args);

            $this->terms_table = new hwcf_List();
        }

        /**
         * Method for build the page HTML manage tags
         *
         * @return void
         * @author Olatechpro
         */
        public function page_manage_hwcf()
        {
            // Default order
            if (!isset($_GET['order'])) {
                $_GET['order'] = 'name-asc';
            }

            settings_errors(__CLASS__);

            if (!isset($_GET['add'])) {
                //all tax ?>
                <div class="wrap st_wrap st-manage-taxonomies-page">

                <div id="">
                    <h1 class="wp-heading-inline">
                        <?php esc_html_e('Hide Cart Functions', 'hide-cart-functions'); ?>
                    </h1>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=hwcf_settings&add=new_item')); ?>" class="page-title-action">
                        <?php esc_html_e('Add New', 'simple-tags'); ?>
                    </a>


                    <?php
                    if (isset($_REQUEST['s']) && $search = esc_attr(sanitize_key(wp_unslash($_REQUEST['s'])))) {
                        /* translators: %s: search keywords */
                        printf(' <span class="subtitle">' . esc_html__(
                            'Search results for %s',
                            'hide-cart-functions'
                        ) . '</span>', esc_html($search));
                    } ?>
                    <?php

                    //the terms table instance
                    $this->terms_table->prepare_items(); ?>


                    <hr class="wp-header-end">
                    <div id="ajax-response"></div>
                    <form class="search-form wp-clearfix st-taxonomies-search-form" method="get">
                        <?php $this->terms_table->search_box(esc_html__('Search Result', 'hide-cart-functions'), 'term'); ?>
                    </form>
                    <div class="clear"></div>

                    <div id="col-container" class="wp-clearfix">

                        <div class="col-wrap">
                            <form action="<?php echo esc_url(add_query_arg('', '')); ?>" method="post">
                                <?php $this->terms_table->display(); ?>
                            </form>
                        </div>


                    </div>


                </div>
            <?php
            } else {
                if ($_GET['add'] == 'new_item') {
                    $this->hwcf_manage_hwcf();
                    echo '<div>';
                }
            } ?>
            </div>
            <?php
        }


        /**
         * Create our settings page output.
         *
         * @internal
         */
        public function hwcf_manage_hwcf()
        {
            $tab       = (!empty($_GET) && !empty($_GET['action']) && 'edit' == $_GET['action']) ? 'edit' : 'new';
            $tab_class = 'hwcf-' . $tab;
            $current   = null; ?>

        <div class="wrap <?php echo esc_attr($tab_class); ?>">

            <?php

            $hwcf      = hwcf_get_hwcf_data();
            $hwcf_edit = false;

            global $current_user;

            if ('edit' === $tab) {
                $selected_hwcf = hwcf_get_current_hwcf();

                if ($selected_hwcf && array_key_exists($selected_hwcf, $hwcf)) {
                    $current         = $hwcf[$selected_hwcf];
                    $hwcf_edit = true;
                }
            } ?>


            <div class="wrap <?php echo esc_attr($tab_class); ?>">
                <h1><?php echo esc_html__('Manage Settings', 'hide-cart-functions'); ?> <a
                        href="<?php echo esc_url(admin_url('admin.php?page=hwcf_settings')); ?>"
                        class="page-title-action"><?php esc_html_e('All Settings', 'hide-cart-functions'); ?></a></h1>
                <div class="wp-clearfix"></div>

                <form method="post" action="">


                    <div class="hwcf-admin-ui">
                        <div class="hwcf-postbox-container">
                            <div id="poststuff">
                                <div class="hwcf-section postbox">
                                    <div class="postbox-header">
                                        <h2 class="hndle ui-sortable-handle">
                                            <?php
                                            if ($hwcf_edit) {
                                                echo esc_html__(
                                                    'Edit Settings',
                                                    'hide-cart-functions'
                                                ) . '<span>'. esc_html__('Existing Rule', 'hide-cart-functions') .': <font color="green">' . esc_html($current['hwcf_title']) . '</font></span>';
                                                echo '<input type="hidden" name="edited_hwcf" value="' . esc_attr($current['ID']) . '" />';
                                                echo '<input type="hidden" name="hwcf[ID]" value="' . esc_attr($current['ID']) . '" />';
                                            } else {
                                                echo esc_html__('Add New Settings', 'hide-cart-functions');
                                            } ?>
                                        </h2>
                                    </div>
                                    <div class="inside">
                                        <div class="main">

                                            <div class="st-taxonomy-content">

                                                <table class="form-table hwcf-table">

                                                    <tr valign="top">
                                                        <th scope="row"><label
                                                                for="hwcf_disable"><?php echo esc_html__('Disable Settings', 'hide-cart-functions'); ?></label>
                                                            </th>
                                                        <td>
                                                            <input type="checkbox"
                                                                id="hwcf_disable"
                                                                class="checkinput-box"
                                                                name="hwcf[hwcf_disable]"
                                                                value="1"
                                                                <?php echo ( isset($current) && isset($current['hwcf_disable']) && (int)$current['hwcf_disable'] > 0 ? 'checked="checked"' : ''); ?>
                                                                >
                                                            <p class="description checkinput-description"><?php echo esc_html__('Check this option to disable this rule. Leave unchecked to apply the selected settings.', 'hide-cart-functions'); ?></p>
                                                        </td>
                                                    </tr>
                                                    <?php $loggedinUsersArr=array();
                                                    if((isset($current) && isset($current['loggedinUsers']) && (int)$current['loggedinUsers'])){ 
                                                        $loggedinUsersArr =explode(",",$current['loggedinUsers']);
                                                    } ?>
                                                    <tr valign="top">
                                                        <th scope="row"><label for="hwcf_loggedinUsers"><?php echo esc_html__('Rule enabled','hide-cart-functions'); ?></label></th>
                                                        <td>
                                                        <input type="checkbox" class="checkinput-box usercheckbox guest-checkbox" name="hwcf[loggedinUsers][]" value="1" <?php echo (in_array(1,$loggedinUsersArr) )  ? 'checked="checked"' : ''; ?>><label for="hwcf_loggedinUsers"><p class="description checkinput-description"><?php echo esc_html__('Guests Only', 'hide-cart-function'); ?></p></label>

                                                        <input type="checkbox" class="checkinput-box usercheckbox logged-in-checkbox" name="hwcf[loggedinUsers][]" value="2" <?php echo (in_array(2,$loggedinUsersArr) )  ? 'checked="checked"' : ''; ?>><label for="hwcf_loggedinUsers"><p class="description checkinput-description"><?php echo esc_html__('Logged in Users --> Leave both unchecked for ALL users.', 'hide-cart-function'); ?></p></label>

                                                 	 
                        
                                                        </td>
                                                    </tr>
                                                 
                                                    
                                                    <tr valign="top">
                                                        <th scope="row">
                                                            <label for="hwcf_title">
                                                                <?php echo esc_html__('Rule Title', 'hide-cart-functions'); ?>
                                                            </label></th>
                                                        <td>
                                                            <span class="hwcf-tooltip"> 
                                                                 <span class="dashicons dashicons-editor-help"></span> 
                                                                <span class="tooltiptext tooltip-right-msg"><?php echo esc_html__("Create short title for rule for display in the settings table.", "hide-cart-functions"); ?></span>
                                                            </span>
                                                            <input type="text"
                                                                id="hwcf_title"
                                                                class=""
                                                                name="hwcf[hwcf_title]"
                                                                value="<?php echo (isset($current) && isset($current['hwcf_title'])) ? esc_html($current['hwcf_title']) : ''; ?>">
                                                        </td>
                                                    </tr>

                                                    <tr valign="top">
                                                        <th scope="row"><label
                                                                for="hwcf_hide_quantity"><?php echo esc_html__('Hide Quantity', 'hide-cart-functions'); ?></label>
                                                            </th>
                                                        <td>
                                                            <input type="checkbox"
                                                                id="hwcf_hide_quantity"
                                                                class="checkinput-box"
                                                                name="hwcf[hwcf_hide_quantity]"
                                                                value="1"
                                                                <?php echo ( isset($current) && isset($current['hwcf_hide_quantity']) && (int)$current['hwcf_hide_quantity'] > 0 ? 'checked="checked"' : ''); ?>
                                                                >
                                                            <p class="description checkinput-description"><?php echo esc_html__('Check this option to hide the default cart "Quantity" product function.', 'hide-cart-functions'); ?></p>
                                                        </td>
                                                    </tr>

                                                    <tr valign="top">
                                                        <th scope="row"><label
                                                                for="hwcf_hide_add_to_cart"><?php echo esc_html__('Hide Add to Cart', 'hide-cart-functions'); ?></label>
                                                            </th>
                                                        <td>
                                                            <input type="checkbox"
                                                                id="hwcf_hide_add_to_cart"
                                                                class="checkinput-box"
                                                                name="hwcf[hwcf_hide_add_to_cart]"
                                                                value="1"
                                                                <?php echo ( isset($current) && isset($current['hwcf_hide_add_to_cart']) && (int)$current['hwcf_hide_add_to_cart'] > 0 ? 'checked="checked"' : ''); ?>
                                                                >
                                                            <p class="description checkinput-description"><?php echo esc_html__('Check this option to hide the default cart "Add to Cart" button.', 'hide-cart-functions'); ?></p>
                                                        </td>
                                                    </tr>

                                                    <tr valign="top">
                                                        <th scope="row"><label
                                                                for="hwcf_hide_price"><?php echo esc_html__('Hide Price', 'hide-cart-functions'); ?></label>
                                                            </th>
                                                        <td>
                                                            <input type="checkbox"
                                                                id="hwcf_hide_price"
                                                                class="checkinput-box"
                                                                name="hwcf[hwcf_hide_price]"
                                                                value="1"
                                                                <?php echo ( isset($current) && isset($current['hwcf_hide_price']) && (int)$current['hwcf_hide_price'] > 0 ? 'checked="checked"' : ''); ?>
                                                                >
                                                            <p class="description checkinput-description"><?php echo esc_html__('Check this option to hide the default cart "Price" displayed.', 'hide-cart-functions'); ?></p>
                                                        </td>
                                                    </tr>

                                                    <tr valign="top">
                                                        <th scope="row">
                                                            <label for="hwcf_overridePriceTag"><?php echo esc_html__('Override Price Tag', 'hide-cart-functions'); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="text"
                                                                id="hwcf_overridePriceTag"
                                                                name="hwcf[overridePriceTag]"
                                                                value="<?php echo ( isset($current) && isset( $current['overridePriceTag'] ) && $current['overridePriceTag']!="" ) ? esc_html($current['overridePriceTag']) : '[price]' ; ?>"
                                                                <?php echo ( isset($current) && isset($current['hwcf_hide_price']) && (int)$current['hwcf_hide_price'] > 0 ? 'disabled' : '' ); ?>
                                                                />
                                                            <label for="hwcf_overridePriceTag"><p>This text will override the price tag. Use the <code>[price]</code> to keep showing price in the tag with other text.</p></label>                                                         
                                                        </td>
                                                    </tr>

                                                    <tr valign="top">
                                                        <th scope="row"><label
                                                                for="hwcf_hide_options"><?php echo esc_html__('Hide Options', 'hide-cart-functions'); ?></label>
                                                            </th>
                                                        <td>
                                                            <input type="checkbox"
                                                                id="hwcf_hide_options"
                                                                class="checkinput-box"
                                                                name="hwcf[hwcf_hide_options]"
                                                                value="1"
                                                                <?php echo ( isset($current) && isset($current['hwcf_hide_options']) && (int)$current['hwcf_hide_options'] > 0 ? 'checked="checked"' : ''); ?>
                                                                >
                                                            <p class="description checkinput-description"><?php echo esc_html__('Check this option to hide the default cart "Options" dropdown selector.', 'hide-cart-functions'); ?></p>
                                                        </td>
                                                    </tr>

                                                    <tr valign="top">
                                                        <th scope="row">
                                                            <label for="hwcf_custom_element">
                                                                <?php echo esc_html__('Hide Custom Element', 'hide-cart-functions'); ?>
                                                            </label></th>
                                                        <td>
                                                            <span class="hwcf-tooltip"> 
                                                                <span class="dashicons dashicons-editor-help"></span> 
                                                                <span class="tooltiptext tooltip-right-msg"><?php echo esc_html__("Enter specific IDs or classes to hide a custom element.", "hide-cart-functions"); ?></span>
                                                            </span>
                                                            <input
                                                                type="text"
                                                                id="hwcf_custom_element"
                                                                class=""
                                                                name="hwcf[hwcf_custom_element]"
                                                                value="<?php echo (isset($current) && isset($current['hwcf_custom_element'])) ? esc_html($current['hwcf_custom_element']) : ''; ?>" />
                                                            <p class="description"><?php echo esc_html__('Separate multiple values by comma (examples: .custom-item-one, .custom-item-two, #new-item-id).', 'hide-cart-functions'); ?></p>
                                                        </td>
                                                    </tr>

                                                    <tr valign="top">
                                                        <th scope="row">
                                                            <label for="hwcf_custom_message">
                                                                <?php echo esc_html__('Custom Message', 'hide-cart-functions'); ?></label>
                                                            </th>
                                                        <td class="wp-editor-td">
                                                            <?php
                                                            $content = (isset($current) && isset($current['hwcf_custom_message'])) ? stripslashes($current['hwcf_custom_message']) : '';
                                                            $editor_id = "hwcf_custom_message";
                                                            $args      = array(
                                                                'textarea_name' => 'hwcf[hwcf_custom_message]',
                                                                'media_buttons' => true,
                                                                'textarea_rows' =>  3
                                                            );
                                                            wp_editor( $content, $editor_id, $args );
                                                            ?>
                                                            <p class="description"><?php echo esc_html__('Enter a custom message to be added above or below the "Short product description"', 'hide-cart-functions'); ?></p>
                                                        </td>
                                                    </tr>

                                                    <tr valign="top">
                                                        <th scope="row">
                                                            <label for="hwcf_custom_message_position">
                                                                <?php echo esc_html__('Custom Message Position', 'hide-cart-functions'); ?></label>
                                                            </th>
                                                        <td>
                                                            <select
                                                                id="hwcf_custom_message_position"
                                                                class=""
                                                                name="hwcf[hwcf_custom_message_position]">
                                                                <?php 
                                                                $postion_options = [
                                                                    'above' => esc_html__('Above Short product description', 'hide-cart-functions'),
                                                                    'below' => esc_html__('Below Short product description.', 'hide-cart-functions')
                                                                ]; 
                                                                foreach ($postion_options as $key => $label) {
                                                                     ?>
                                                                    <option value="<?php echo esc_attr($key); ?>" 
                                                                    <?php if( isset($current) && isset($current['hwcf_custom_message_position']) && $current['hwcf_custom_message_position'] === $key) {echo 'selected="selected"'; }; ?>>
                                                                    <?php echo esc_html($label); ?></option>
                                                                <?php }

                                                                ?>
                                                            </select>
                                                            <p class="description"><?php echo esc_html__('Select position where Custom Message should be inserted.', 'hide-cart-functions'); ?> </a></p>
                                                        </td>
                                                    </tr>

                                                    <tr valign="top">
                                                        <th scope="row">
                                                            <label for="hwcf_categories">
                                                                <?php echo esc_html__('Selected Category', 'hide-cart-functions'); ?></label>
                                                            </th>
                                                        <td>

                                                            <select
                                                                id="hwcf_categories"
                                                                class="hwcf_categories"
                                                                name="hwcf[hwcf_categories][]"
                                                                multiple >
                                                                <?php 
                                                                $terms = get_terms( array(
                                                                    'taxonomy' => 'product_cat',
                                                                    'hide_empty' => false,
                                                                    ) );

                                                                foreach ($terms as $term) {
                                                                     ?>
                                                                    <option value="<?php echo esc_attr($term->term_id); ?>" 
                                                                    
                                                                    <?php if( isset($current) && isset($current['hwcf_categories']) && in_array($term->term_id, $current['hwcf_categories'])) {echo 'selected="selected"'; }; ?>><?php echo esc_html($term->name); ?></option>
                                                                <?php }

                                                                ?>
                                                            </select>
                                                            <p class="description"><?php echo esc_html__('Some browsers, hold the "Ctrl" key while clicking to select multiple or to deselect entirely.', 'hide-cart-functions'); ?> </a></p>
                                                        </td>
                                                    </tr>

                                                    <tr valign="top">
                                                        <th scope="row">
                                                            <label for="hwcf_products">
                                                                <?php echo esc_html__('Product IDs', 'hide-cart-functions'); ?></label>
                                                            </th>
                                                        <td>
                                                            <input type="text"
                                                                id="hwcf_products"
                                                                class=""
                                                                name="hwcf[hwcf_products]"
                                                                value="<?php echo (isset($current) && isset($current['hwcf_products'])) ? esc_html($current['hwcf_products']) : ''; ?>">
                                                            <p class="description"><?php echo esc_html__('Separate multiple values by comma (3443, 5567, 3456) or leave empty to apply this rule to all products.', 'hide-cart-functions'); ?></p>
                                                        </td>
                                                    </tr>
                                                    <tr valign="top">
                                                        <th scope="row">
                                                            <label for="hwcf_products">
                                                               <?php echo esc_html__('Search Products', 'hide-cart-functions'); ?></label>
                                                            </th>
                                                        <td>
                                                        <select
                                                            id="custom-product-search-field"
                                                            style=""
                                                            name="hwcf[hwcf_custom_product_search][]" multiple>
                                                        </select>
                                                        <p class="description"><?php echo esc_html__('Search and select products with 3 letter minimum. Works in combination with the Product ID field', 'hide-cart-functions'); ?></p>
                                                        </td>
                                                    </tr>

                                                </table>


                                            </div>
                                            <div class="clear"></div>


                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>


                    </div>

                    <div class="hwcf-right-sidebar">
                        <div class="hwcf-right-sidebar-wrapper" style="min-height: 205px;">


                            <p class="submit">

                                <?php
                                wp_nonce_field(
                                                'hwcf_addedit_hwcf_nonce_action',
                                                'hwcf_addedit_hwcf_nonce_field'
                                            );
            if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                    <input type="submit"
                                        class="button-primary hwcf-settings-submit hwcf-tag-cloud-submit"
                                        name="hwcf_submit"
                                        value="<?php echo esc_attr(esc_attr__('Save Settings', 'hide-cart-functions')); ?>"/>
                                    <?php
                                } else { ?>
                                    <input type="submit"
                                        class="button-primary hwcf-settings-submit hwcf-tag-cloud-submit"
                                        name="hwcf_submit"
                                        value="<?php echo esc_attr(esc_attr__('Add Settings', 'hide-cart-functions')); ?>"/>
                                <?php } ?>
                            </p>


                        </div>

                    </div>

                    <div class="clear"></div>


                </form>

            </div><!-- End .wrap -->

            <div class="clear"></div>

            <?php
        }
    }
    hwcf_Admin::get_instance();
}
