<?php

/**
 * Plugin Name: Element Pack Lite - Addons for Elementor
 * Plugin URI: http://elementpack.pro/
 * Description: The all-new <a href="https://elementpack.pro/">Element Pack</a> brings incredibly advanced, and super-flexible widgets, and A to Z essential addons to the Elementor page builder for WordPress. Explore expertly-coded widgets with first-class support by experts.
 * Version: 5.1.3
 * Author: BdThemes
 * Author URI: https://bdthemes.com/
 * Text Domain: bdthemes-element-pack-lite
 * Domain Path: /languages
 * License: GPL3
 * Elementor requires at least: 3.0.0
 * Elementor tested up to: 3.14.0
 */




if (!function_exists('element_pack_pro_installed')) {

	function element_pack_pro_installed() {

		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$file_path = 'bdthemes-element-pack/bdthemes-element-pack.php';
		$installed_plugins = get_plugins();

		return isset($installed_plugins[$file_path]);
	}
}

if (!function_exists('_is_ep_pro_activated_check')) {

	function _is_ep_pro_activated_check() {

		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$file_path = 'bdthemes-element-pack/bdthemes-element-pack.php';
		$installed_plugins = get_plugins();

		if (is_plugin_active($file_path)) {
			return true;
		}

		return false;
	}
}

if (!function_exists('element_pack_pro_activated')) {
	function element_pack_pro_activated() {
		if (function_exists('bdt_license_validation')) {
			if (bdt_license_validation()) {
				return true;
			}
		}
		if (!function_exists('bdt_license_validation')) {
			if (bdt_license_validation()) {
				return true;
			}
		}
		return false;
	}
}

if (!element_pack_pro_installed()) {

	// Some pre defined value for easy use
	define('BDTEP_VER', '5.1.3');
	define('BDTEP_TPL_DB_VER', '1.0.0');
	define('BDTEP__FILE__', __FILE__);
	if (!defined('BDTEP_TITLE')) {
		define('BDTEP_TITLE', 'Element Pack');
	} // Set your own name for plugin


	// Helper function here
	require_once(dirname(__FILE__) . '/includes/helper.php');
	require_once(dirname(__FILE__) . '/includes/utils.php');


	require_once BDTEP_INC_PATH . 'class-pro-widget-map.php';

	/**
	 * Plugin load here correctly
	 * Also loaded the language file from here
	 */
	function bdthemes_element_pack_load_plugin() {
		load_plugin_textdomain('bdthemes-element-pack', false, BDTEP_PNAME . '/languages');

		if (!did_action('elementor/loaded')) {
			add_action('admin_notices', 'bdthemes_element_pack_fail_load');

			return;
		}

		// Widgets filters here
		require_once(BDTEP_INC_PATH . 'element-pack-filters.php');

		// Element pack widget and assets loader
		require_once(BDTEP_PATH . 'loader.php');

		// Notice class
		require_once(BDTEP_ADMIN_PATH . 'admin-notice.php');
	}

	add_action('plugins_loaded', 'bdthemes_element_pack_load_plugin', 9);


	/**
	 * Check Elementor installed and activated correctly
	 */
	function bdthemes_element_pack_fail_load() {

		$screen = get_current_screen();

		if (isset($screen->parent_file) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id) {
			return;
		}

		$plugin = 'elementor/elementor.php';

		if (_is_elementor_installed()) {
			if (!current_user_can('activate_plugins')) {
				return;
			}
			$activation_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin);
			$admin_message  = '<p>' . esc_html__('Ops! Element Pack not working because you need to activate the Elementor plugin first.', 'bdthemes-element-pack') . '</p>';
			$admin_message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s</a>', $activation_url, esc_html__('Activate Elementor Now', 'bdthemes-element-pack')) . '</p>';
		} else {
			if (!current_user_can('install_plugins')) {
				return;
			}
			$install_url    = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=elementor'), 'install-plugin_elementor');
			$admin_message  = '<p>' . esc_html__('Ops! Element Pack not working because you need to install the Elementor plugin', 'bdthemes-element-pack') . '</p>';
			$admin_message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s</a>', $install_url, esc_html__('Install Elementor Now', 'bdthemes-element-pack')) . '</p>';
		}

		echo '<div class="error">' . $admin_message . '</div>';
	}

	/**
	 * Check the elementor installed or not
	 */
	if (!function_exists('_is_elementor_installed')) {
		function _is_elementor_installed() {
			$file_path         = 'elementor/elementor.php';
			$installed_plugins = get_plugins();

			return isset($installed_plugins[$file_path]);
		}
	}

	/**
	 * Added notice after install or upgrade to v6
	 *
	 * @param string $plugin
	 * @return void
	 */
	if (!function_exists('ep_activation_redirect')) {
		function ep_activation_redirect($plugin) {
			if (!did_action('elementor/loaded')) {
				return;
			}

			if ($plugin == plugin_basename(BDTEP__FILE__)) {
				exit(wp_redirect(admin_url('admin.php?page=element_pack_options&notice=v6')));
			}
		}
	}

	add_action('activated_plugin', 'ep_activation_redirect', 20);


	if (!function_exists('bdtep_fs')) {
		// Create a helper function for easy SDK access.
		function bdtep_fs() {
			global $bdtep_fs;

			if (!isset($bdtep_fs)) {
				// Include Freemius SDK.
				require_once dirname(__FILE__) . '/freemius/start.php';

				$bdtep_fs = fs_dynamic_init(array(
					'id'                  => '12687',
					'slug'                => 'bdthemes-element-pack-lite',
					'premium_slug'        => 'bdthemes-element-pack',
					'type'                => 'plugin',
					'public_key'          => 'pk_a3dc126cd59aadc29e8aec86e3159',
					'is_premium'          => false,
					'premium_suffix'      => 'Pro',
					// If your plugin is a serviceware, set this option to false.
					'has_premium_version' => false,
					'has_addons'          => false,
					'has_paid_plans'      => false,
					'menu'                => array(
						'slug'           => 'element_pack_options',
						'first-path'     => 'admin.php?page=element_pack_options',
						'contact'        => false,
						'support'        => false,
					),
					// Set the SDK to work in a sandbox mode (for development & testing).
					// IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
					'secret_key'          => 'sk_Wv&@AuP20ijQDykiwCQSw1_:OqMjZ',
				));
			}

			return $bdtep_fs;
		}

		// Init Freemius.
		bdtep_fs();
		// Signal that SDK was initiated.
		do_action('bdtep_fs_loaded');
	}
}
