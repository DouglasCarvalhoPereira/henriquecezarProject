<?php
/**
 * Fetch our HWCF option data.
 *
 * @return mixed
 */
function hwcf_get_hwcf_data()
{
    return array_filter( (array)apply_filters('hwcf_get_hwcf_data', get_option('hwcf_settings_data', []), get_current_blog_id()));
}

/**
 * Get the selected hwcf from the $_POST global.
 *
 * @return bool|string False on no result, sanitized hwcf if set.
 * @internal
 *
 */
function hwcf_get_current_hwcf()
{

    $hwcfs = false;

    if (!empty($_GET) && isset($_GET['hwcf_item'])) {
        $hwcfs = sanitize_text_field($_GET['hwcf_item']);
    } else {
        $hwcfs = hwcf_get_hwcf_data();
        if (!empty($hwcfs)) {
            // Will return the first array key.
            $hwcfs = key($hwcfs);
        }
    }

    /**
     * Filters the current hwcf to edit.
     *
     * @param string $hwcfs hwcf slug.
     */
    return apply_filters('hwcf_current_hwcf', $hwcfs);
}

/**
 * Handle the save and deletion of hwcf data.
 */
function hwcf_process_hwcf()
{

    if (wp_doing_ajax()) {
        return;
    }

    if (!is_admin()) {
        return;
    }

    if (empty($_GET)) {
        return;
    }

    if (!isset($_GET['page'])) {
        return;
    }
    if ('hwcf_settings' !== $_GET['page']) {
        return;
    }

    if (isset($_GET['new_hwcf'])) {
        if ((int)$_GET['new_hwcf'] === 1) {
            add_action('admin_notices', "hwcf_item_update_success_admin_notice");
            add_filter('removable_query_args', 'hwcf_saved_hwcf_filter_removable_query_args');
        }
    }

    if (isset($_GET['deleted_hwcf'])) {
        if ((int)$_GET['deleted_hwcf'] === 1) {
            add_action('admin_notices', "hwcf_item_delete_success_admin_notice");
            add_filter('removable_query_args', 'taxopress_deleted_hwcf_filter_removable_query_args');
        }
    }


    if (!empty($_POST) && isset($_POST['hwcf_submit'])) {
        $result = '';
        if (isset($_POST['hwcf_submit'])) {
            check_admin_referer('hwcf_addedit_hwcf_nonce_action', 'hwcf_addedit_hwcf_nonce_field');
            $result = hwcf_update_hwcf($_POST);
        }

        if ($result) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page'           => 'hwcf_settings',
                        'add'            => 'new_item',
                        'action'         => 'edit',
                        'hwcf_item'     => $result,
                        'new_hwcf'      => 1,
                    ],
                    admin_url('admin.php')
                )
            );

            exit();
        }
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] === 'hwcf-delete-item' && isset($_REQUEST['_wpnonce']) && isset($_REQUEST['hwcf_item'])) {
        $nonce = sanitize_text_field($_REQUEST['_wpnonce']);
        if (wp_verify_nonce($nonce, 'hwcf-action-request-nonce')) {
            hwcf_delete_hwcf(sanitize_text_field($_REQUEST['hwcf_item']));
        }
        add_filter('removable_query_args', 'hwcf_delete_hwcf_filter_removable_query_args');
    }
}

add_action('init', 'hwcf_process_hwcf', 8);


/**
 * Delete our custom settings from the array of settings.
 * @return bool|string False on failure, string on success.
 */
function hwcf_delete_hwcf($settings_id)
{
    $settings_data = hwcf_get_hwcf_data();

    if (array_key_exists($settings_id, $settings_data)) {
        unset($settings_data[$settings_id]);
        $success = update_option('hwcf_settings_data', $settings_data);
    }

    if (isset($success)) {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page'          => 'hwcf_settings',
                    'deleted_hwcf' => 1,
                ],
                admin_url('admin.php')
            )
        );
        exit();
    }
}

/**
 * Add to or update our hwcf option with new data.
 *
 *
 * @param array $data Array of hwcf data to update. Optional.
 * @return bool|string False on failure, string on success.
 * @internal
 *
 */
function hwcf_update_hwcf($data = [])
{

    //update our custom checkbox value if not checked
    if (!isset($data['hwcf']['hwcf_disable'])) {
        $data['hwcf']['hwcf_disable'] = 0;
    }
    if (!isset($data['hwcf']['hwcf_hide_quantity'])) {
        $data['hwcf']['hwcf_hide_quantity'] = 0;
    }
    if (!isset($data['hwcf']['hwcf_hide_add_to_cart'])) {
        $data['hwcf']['hwcf_hide_add_to_cart'] = 0;
    }
    if (!isset($data['hwcf']['hwcf_hide_price'])) {
        $data['hwcf']['hwcf_hide_price'] = 0;
    }
    if (!isset($data['hwcf']['hwcf_hide_options'])) {
        $data['hwcf']['hwcf_hide_options'] = 0;
    }
    if (!empty($data['hwcf']['loggedinUsers'])) {
        $data['hwcf']['loggedinUsers'] = implode(",",$data['hwcf']['loggedinUsers']);
    }else{
        $data['hwcf']['loggedinUsers']='';
    }

    //sanitize input
    foreach ($data as $key => $value) {

        if ($key === 'hwcf_custom_message') {
            $data[$key] = stripslashes_deep($value);
        } elseif (is_string($value)) {
            $data[$key] = sanitize_text_field($value);
        } else {
            array_map('sanitize_text_field', $data[$key]);
        }
    }

    $settings_data = hwcf_get_hwcf_data();


    if (isset($data['edited_hwcf'])) {
        $settings_id                 = $data['edited_hwcf'];
        $settings_data[$settings_id] = $data['hwcf'];
        $status                      = update_option('hwcf_settings_data', $settings_data);
    } else {
        $settings_id                 = (int)get_option('hwcf_settings_ids_increament') + 1;
        $data['hwcf']['ID']         = $settings_id;
        $settings_data[$settings_id] = $data['hwcf'];
        $success                     = update_option('hwcf_settings_data', $settings_data);
        $update_id                   = update_option('hwcf_settings_ids_increament', $settings_id);
    }

    return $settings_id;

}

/**
 * Successful update callback.
 */
function hwcf_item_update_success_admin_notice()
{

    echo '<div id="message" class="updated notice is-dismissible"><p>';
    echo esc_html__('Settings Updated Successfully.', 'hide-cart-functions');
    echo '</p></div>';
}

/**
 * Successful deleted callback.
 */
function hwcf_item_delete_success_admin_notice()
{
    echo '<div id="message" class="error notice is-dismissible"><p>';
    echo esc_html__('Settings Successfully Deleted.', 'hide-cart-functions');
    echo '</p></div>';
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args Array of removable query arguments.
 * @return string[] Updated array of removable query arguments.
 */
function hwcf_saved_hwcf_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'new_hwcf',
    ]);
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args Array of removable query arguments.
 * @return string[] Updated array of removable query arguments.
 */
function hwcf_delete_hwcf_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'action',
        'hwcf_item',
        '_wpnonce',
    ]);
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args Array of removable query arguments.
 * @return string[] Updated array of removable query arguments.
 */
function taxopress_deleted_hwcf_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'deleted_hwcf',
    ]);
}


/**
 * Secondary admin notices function for use with admin_notices hook.
 *
 * Constructs admin notice HTML.
 *
 * @param string $message Message to use in admin notice. Optional. Default empty string.
 * @param bool $success Whether or not a success. Optional. Default true.
 * @return mixed
 */
function hwcf_admin_notices_helper($message = '', $success = true)
{

    $class   = [];
    $class[] = $success ? 'updated' : 'error';
    $class[] = 'notice is-dismissible';

    $messagewrapstart = '<div id="message" class="' . esc_attr(implode(' ', $class)) . '"><p>';

    $messagewrapend = '</p></div>';

    $action = '';

    /**
     * Filters the custom admin notice for hwcf.
     *
     *
     * @param string $value Complete HTML output for notice.
     * @param string $action Action whose message is being generated.
     * @param string $message The message to be displayed.
     * @param string $messagewrapstart Beginning wrap HTML.
     * @param string $messagewrapend Ending wrap HTML.
     */
    return apply_filters('hwcf_admin_notice', $messagewrapstart . $message . $messagewrapend, $action, $message,
        $messagewrapstart, $messagewrapend);
}


if( !function_exists( 'HWCF_Fix_Double_Selection') ) {
    function HWCF_Fix_Double_Selection() {
        $hide_rules = get_option( 'hwcf_settings_data' );
        foreach ($hide_rules as $key => $rule ) {
            if( $rule['loggedinUsers'] == '1,2' ){
                $hide_rules[$key]['loggedinUsers'] = '';
            }
        }
        update_option( 'hwcf_settings_data', $hide_rules );
    }
}

if( !function_exists( 'HWCF_Plugin_Update' ) ) {
    function HWCF_Plugin_Update( $upgrader_object, $options ) {
        $current_plugin_path_name = plugin_basename( __FILE__ );
        
        if ($options['action'] == 'update' && $options['type'] == 'plugin' ) {
            foreach($options['plugins'] as $each_plugin) {
                if ( $each_plugin == HWCF_GLOBAl_BASE_NAME ) {
                    HWCF_Fix_Double_Selection();
                }
            }
        }
    }
}

add_action( 'upgrader_process_complete', 'HWCF_Plugin_Update',10, 2);
