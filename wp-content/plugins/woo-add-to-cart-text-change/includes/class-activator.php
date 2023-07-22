<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.8
 * @package    WACTC
 * @author     BM Rafiul Alam <bmrafiul.alam@gmail.com>
 */
class Free_Activator {
	/**
	 * When plugin activate work activate function.
	 *
	 * @since      1.8
	 */
	public static function activate() {
		deactivate_plugins( 'woo-add-to-cart-text-change-premium/init-pro.php' );
	}
	public static function set_plugin_info(){
		update_option( 'wactc_activation_date', current_time( 'timestamp' ) );
		update_option( 'wactc_version', '1.8' );
	}
}