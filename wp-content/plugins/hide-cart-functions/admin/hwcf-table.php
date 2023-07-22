<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class hwcf_List extends WP_List_Table
{

    /** Class constructor */
    public function __construct()
    {

        parent::__construct([
            'singular' => esc_html__('Hide Cart Functions', 'hide-cart-functions'), //singular name of the listed records
            'plural'   => esc_html__('Hide Cart Functions', 'hide-cart-functions'), //plural name of the listed records
            'ajax'     => false //does this table support ajax?
        ]);

    }

    /**
     * Retrieve hwcf_settings data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_hwcf_settings()
    {
        return hwcf_get_hwcf_data();
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count()
    {
        return count(hwcf_get_hwcf_data());
    }

    /**
     * Show single row item
     *
     * @param array $item
     */
    public function single_row($item)
    {
        $class = ['hwcf-tr'];
        $id    = 'hwcf-' . md5($item['ID']);
        echo sprintf('<tr id="%s" class="%s">', esc_attr($id), esc_attr(implode(' ', $class)));
        $this->single_row_columns($item);
        echo '</tr>';
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns()
    {
        $columns = [
            'title'       => esc_html__('Title', 'hide-cart-functions'),
            'status'      => esc_html__('Status', 'hide-cart-functions'),
            'quantity'    => esc_html__('Quantity', 'hide-cart-functions'),
            'add_to_cart' => esc_html__('Add to Cart', 'hide-cart-functions'),
            'price'       => esc_html__('Price', 'hide-cart-functions'),
            'options'     => esc_html__('Options', 'hide-cart-functions'),
            'category_ids'=> esc_html__('Selected Category', 'hide-cart-functions'),
            'product_ids' => esc_html__('Product IDs', 'hide-cart-functions'),
            // 'search_product' => esc_html__('Search Products', 'hide-cart-functions'),
        ];

        return $columns;
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        return !empty($item[$column_name]) ? $item[$column_name] : '&mdash;';
    }

    /** Text displayed when no stterm data is available */
    public function no_items()
    {
        esc_html_e('No Item available.', 'hide-cart-functions');
    }

    /**
     * Displays the search box.
     *
     * @param string $text The 'submit' button label.
     * @param string $input_id ID attribute value for the search input field.
     *
     *
     */
    public function search_box($text, $input_id)
    {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }

        $input_id = $input_id . '-search-input';

        if (!empty($_REQUEST['orderby'])) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr(sanitize_key($_REQUEST['orderby'])) . '" />';
        }
        if (!empty($_REQUEST['order'])) {
            echo '<input type="hidden" name="order" value="' . esc_attr(sanitize_key($_REQUEST['order'])) . '" />';
        }
        if (!empty($_REQUEST['page'])) {
            echo '<input type="hidden" name="page" value="' . esc_attr(sanitize_key($_REQUEST['page'])) . '" />';
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s"
                   value="<?php _admin_search_query(); ?>"/>
            <?php submit_button($text, '', '', false, ['id' => 'hwcf-table-items-submit']); ?>
        </p>
        <?php
    }

    /**
     * Sets up the items (roles) to list.
     */
    public function prepare_items()
    {

        $this->_column_headers = $this->get_column_info();

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = $this->get_items_per_page('hwcf_settings_per_page', 20);

        /**
         * Fetch the data
         */
        $data = self::get_hwcf_settings();

        /**
         * Handle search
         */
        if ((!empty($_REQUEST['s'])) && $search = sanitize_key($_REQUEST['s'])) {
            $data_filtered = [];
            foreach ($data as $item) {
                if ($this->str_contains($item['title'], $search, false)) {
                    $data_filtered[] = $item;
                }
            }
            $data = $data_filtered;
        }

        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         */
        function usort_reorder($a, $b)
        {
            $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'ID'; //If no sort, default to role
            $order   = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'asc'; //If no order, default to asc
            $result  = strnatcasecmp($a[$orderby],
                $b[$orderby]); //Determine sort order, case insensitive, natural order

            return ($order === 'asc') ? $result : -$result; //Send final sort direction to usort
        }

        usort($data, 'usort_reorder');

        /**
         * Pagination.
         */
        $current_page = $this->get_pagenum();
        $total_items  = count($data);


        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to
         */
        
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

        /**
         * Now we can add the data to the items property, where it can be used by the rest of the class.
         */
        $this->items = $data;

        /**
         * We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args([
            'total_items' => $total_items,                      //calculate the total number of items
            'per_page'    => $per_page,                         //determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page)   //calculate the total number of pages
        ]);
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @param bool $sensitive Use case sensitive search
     *
     * @return bool
     */
    public function str_contains($haystack, $needles, $sensitive = true)
    {
        foreach ((array)$needles as $needle) {
            $function = $sensitive ? 'mb_strpos' : 'mb_stripos';
            if ($needle !== '' && $function($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    protected function get_sortable_columns()
    {
        $sortable_columns = [
            'title' => ['title', true],
        ];

        return $sortable_columns;
    }

    /**
     * Generates and display row actions links for the list table.
     *
     * @param object $item The item being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary Primary column name.
     *
     * @return string The row actions HTML, or an empty string if the current column is the primary column.
     */
    protected function handle_row_actions($item, $column_name, $primary)
    {
        //Build row actions
        $actions = [
            'edit' => sprintf(
                '<a href="%s">%s</a>',
                add_query_arg(
                    [
                        'page'           => 'hwcf_settings',
                        'add'            => 'new_item',
                        'action'         => 'edit',
                        'hwcf_item'     => $item['ID'],
                    ],
                    admin_url('admin.php')
                ),
                __('Edit', 'hide-cart-functions')
            ),
            'delete' => sprintf(
                '<a href="%s" class="delete-hwcf-item">%s</a>',
                add_query_arg([
                    'page'                   => 'hwcf_settings',
                    'action'                 => 'hwcf-delete-item',
                    'hwcf_item'             => esc_attr($item['ID']),
                    '_wpnonce'               => wp_create_nonce('hwcf-action-request-nonce')
                ],
                admin_url('admin.php')),
                esc_html__('Delete', 'hide-cart-functions')
            ),
        ];

        return $column_name === $primary ? $this->row_actions($actions, false) : '';
    }

    /**
     * Method for column_title column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_title($item)
    {
        return sprintf(
            '<a href="%1$s"><strong><span class="row-title">%2$s</span></strong></a>', 
            add_query_arg(
                [
                    'page'           => 'hwcf_settings',
                    'add'            => 'new_item',
                    'action'         => 'edit',
                    'hwcf_item'     => $item['ID'],
                ],
                admin_url('admin.php')
            ), 
            esc_html($item['hwcf_title']),
        );
    }

    /**
     * Method for status column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_status($item)
    {
        if (isset($item['hwcf_disable']) && (int)$item['hwcf_disable'] > 0) {
            $return = '<font color="red">'.esc_html__('Disabled', 'hide-cart-functions').'</font>';
        }else{
            $return = '<font color="green">'.esc_html__('Enabled', 'hide-cart-functions').'</font>';
        }

        return $return;
    }

    /**
     * Method for quantity column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_quantity($item)
    {
        if (isset($item['hwcf_hide_quantity']) && (int)$item['hwcf_hide_quantity'] > 0) {
            $return = '<font color="green">'.esc_html__('Enabled', 'hide-cart-functions').'</font>';
        }else{
            $return = '<font color="red">'.esc_html__('Disabled', 'hide-cart-functions').'</font>';
        }

        return $return;
    }

    /**
     * Method for add_to_cart column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_add_to_cart($item)
    {
        if (isset($item['hwcf_hide_add_to_cart']) && (int)$item['hwcf_hide_add_to_cart'] > 0) {
            $return = '<font color="green">'.esc_html__('Enabled', 'hide-cart-functions').'</font>';
        }else{
            $return = '<font color="red">'.esc_html__('Disabled', 'hide-cart-functions').'</font>';
        }

        return $return;
    }

    /**
     * Method for price column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_price($item)
    {
        if (isset($item['hwcf_hide_price']) && (int)$item['hwcf_hide_price'] > 0) {
            $return = '<font color="green">'.esc_html__('Enabled', 'hide-cart-functions').'</font>';
        }else{
            $return = '<font color="red">'.esc_html__('Disabled', 'hide-cart-functions').'</font>';
        }

        return $return;
    }

    /**
     * Method for options column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_options($item)
    {
        if (isset($item['hwcf_hide_options']) && (int)$item['hwcf_hide_options'] > 0) {
            $return = '<font color="green">'.esc_html__('Enabled', 'hide-cart-functions').'</font>';
        }else{
            $return = '<font color="red">'.esc_html__('Disabled', 'hide-cart-functions').'</font>';
        }

        return $return;
    }

    /**
     * Method for category_ids column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_category_ids($item)
    {

        if (isset($item['hwcf_categories']) && is_array($item['hwcf_categories']) && !empty($item['hwcf_categories'])) {
            return join(", ", $item['hwcf_categories']);
        }

        return '&mdash;';
    }

    /**
     * Method for product_ids column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_product_ids($item)
    {

        if (isset($item['hwcf_products']) && !empty($item['hwcf_products'])) {
            return $item['hwcf_products'];
        }

        return '&mdash;';
    }

	/**
     * Add custom filter to tablenav
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {

		if ( 'bottom' === $which ) {
             ?>


            <div class="alignleft actions autoterms-log-table-filter">

            <span class="spinner hwcf-spinner"></span>
            <input 
                    type="checkbox"
                    name="hwcf_delete_on_deactivation" 
                    id="hwcf_delete_on_deactivation" 
                    class="hwcf_delete_on_deactivation"
                    value="1"
                    onclick="update_hwcf_delete_on_deactivation()"
                    <?php echo ( (int)get_option('hwcf_delete_on_deactivation', 0) === 1) ? 'checked="checked"' : ''; ?>
                     />
            <label for="hwcf_delete_on_deactivation">
                    <?php esc_html_e( 'Delete all database entries if the plugin is deactivated?', 'hide-cart-functions' ); ?>
                </label>
            </div>

            <script>
                function update_hwcf_delete_on_deactivation(){
                    var checkbox = jQuery(this);
                    jQuery(".hwcf-spinner").addClass("is-active");
                    var settings_action = '';
                    if (!jQuery('#hwcf_delete_on_deactivation').prop("checked")) {
                        settings_action = '0';
                    } else {
                        settings_action = '1';
                    }

                    jQuery.ajax({
                        url : "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
                        data : { action : "hwcf_delete_on_deactivation", settings_action : settings_action },
                        type : "POST",
                        dataType : "json",
                        success : function( response ) {
                            jQuery(".hwcf-spinner").removeClass("is-active");
                        }
                    })
                }

            </script>
        <?php
		}
	}

}
