<?php
namespace ElementPack;

if (!defined('ABSPATH')) {
	exit;
} // Exit if accessed directly


require_once BDTEP_ADMIN_PATH . 'class-settings-api.php';
require_once BDTEP_ADMIN_PATH . 'admin-feeds.php';
// element pack admin settings here
require_once BDTEP_ADMIN_PATH . 'admin-settings.php';

/**
 * Admin class
 */

class Admin {

	public function __construct() {

		// Embed the Script on our Plugin's Option Page Only
		if (isset($_GET['page']) && ($_GET['page'] == 'element_pack_options')) {
			add_action('admin_init', [$this, 'admin_script']);
			add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
		}
        add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);
        add_filter( 'plugin_action_links_' . BDTEP_PBNAME, [ $this, 'plugin_action_links' ] );

		add_action('upgrader_process_complete', [$this, 'bdthemes_element_pack_plugin_on_upgrade_process_complete'], 10, 2);
		register_deactivation_hook(BDTEP__FILE__, [$this, 'bdthemes_element_pack_plugin_on_deactivate']);
	}

	/**
	 * Enqueue styles
	 * @access public
	 */

	public function enqueue_styles() {

		$direction_suffix = is_rtl() ? '.rtl' : '';
		$suffix           = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style('bdt-uikit', BDTEP_ASSETS_URL . 'css/bdt-uikit' . $direction_suffix . '.css', [], '3.13.1');
		wp_enqueue_style('ep-editor', BDTEP_ASSETS_URL . 'css/ep-editor' . $direction_suffix . '.css', [], BDTEP_VER);
		wp_enqueue_style('ep-admin', BDTEP_ADMIN_URL . 'assets/css/ep-admin' . $direction_suffix . '.css', [], BDTEP_VER);

		wp_enqueue_script('bdt-uikit', BDTEP_ASSETS_URL . 'js/bdt-uikit' . $suffix . '.js', ['jquery'], '3.13.1');
	}

	/**
	 * Row meta
	 * @access public
	 * @return array
	 */

	public function plugin_row_meta($plugin_meta, $plugin_file) {
		if (BDTEP_PBNAME === $plugin_file) {

            $row_meta = [
				'docs'  => '<a href="https://elementpack.pro/contact/" aria-label="' . esc_attr(__('Go for Get Support', 'bdthemes-element-pack')) . '" target="_blank">' . __('Get Support', 'bdthemes-element-pack') . '</a>',
				'video' => '<a href="https://www.youtube.com/playlist?list=PLP0S85GEw7DOJf_cbgUIL20qqwqb5x8KA" aria-label="' . esc_attr(__('View Element Pack Video Tutorials', 'bdthemes-element-pack')) . '" target="_blank">' . __('Video Tutorials', 'bdthemes-element-pack') . '</a>',

			];

			$plugin_meta = array_merge($plugin_meta, $row_meta);
		}

		return $plugin_meta;
	}


    public function plugin_action_links( $plugin_meta ) {

        $row_meta = [
            'settings' => '<a href="'.admin_url( 'admin.php?page=element_pack_options' ) .'" aria-label="' . esc_attr(__('Go to settings', 'bdthemes-element-pack')) . '" >' . __('Settings', 'bdthemes-element-pack') . '</b></a>',
            'gopro' => '<a href="https://www.elementpack.pro/pricing/?utm_source=ElementPackLite&utm_medium=PluginPage&utm_campaign=ElementPackLite&coupon=FREETOPRO" aria-label="' . esc_attr(__('Go get the pro version', 'bdthemes-element-pack')) . '" target="_blank" title="When you purchase through this link you will get 30% discount!" class="ep-go-pro">' . __('Go Pro', 'bdthemes-element-pack') . '</a>',
        ];

        $plugin_meta = array_merge($plugin_meta, $row_meta);

        return $plugin_meta;
    }

	/**
	 * Action meta
	 * @access public
	 * @return array
	 */


	public function plugin_action_meta($links) {

		$links = array_merge([sprintf('<a href="%s">%s</a>', element_pack_dashboard_link('#element_pack_welcome'), esc_html__('Settings', 'bdthemes-element-pack'))], $links);

		return $links;
	}

	/**
	 * Register admin script
	 * @access public
	 */

	public function admin_script() {
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
		if ( is_admin() ) { // for Admin Dashboard Only
			wp_enqueue_script('chart', BDTEP_ASSETS_URL . 'vendor/js/chart.min.js', ['jquery'], '2.7.3', true);
			wp_enqueue_script('ep-admin', BDTEP_ADMIN_URL  . 'assets/js/ep-admin' . $suffix . '.js', ['jquery'], BDTEP_VER, true);
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-form');
		}
	}

	/**
	 * Drop Tables on deactivated plugin
	 * @access public
	 */

	public function bdthemes_element_pack_plugin_on_deactivate() {

		global $wpdb;

		$table_cat      = $wpdb->prefix . 'ep_template_library_cat';
		$table_post     = $wpdb->prefix . 'ep_template_library_post';
		$table_cat_post = $wpdb->prefix . 'ep_template_library_cat_post';

		@$wpdb->query('DROP TABLE IF EXISTS ' . $table_cat_post);
		@$wpdb->query('DROP TABLE IF EXISTS ' . $table_cat);
		@$wpdb->query('DROP TABLE IF EXISTS ' . $table_post);
	}

	/**
	 * Upgrade Process Complete
	 * @access public
	 */

	public function bdthemes_element_pack_plugin_on_upgrade_process_complete($upgrader_object, $options) {
		if (isset($options['action']) && $options['action'] == 'update' && $options['type'] == 'plugin') {
			if (isset($options['plugins']) && is_array($options['plugins'])) {
				foreach ($options['plugins'] as $each_plugin) {
					if ($each_plugin == BDTEP_PBNAME) {
						@$this->bdthemes_element_pack_plugin_on_deactivate();
					}
				}
			}
		}
	}
}
