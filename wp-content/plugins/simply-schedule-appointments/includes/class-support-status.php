<?php
/**
 * Simply Schedule Appointments Support Status.
 *
 * @since   2.1.6
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Support Status.
 *
 * @since 2.1.6
 */
class SSA_Support_Status {
	/**
	 * Parent plugin class.
	 *
	 * @since 2.1.6
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  2.1.6
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  2.1.6
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'validate_directory_name' ), 0 );

	}

	/**
	 * Get file path
	 *
	 * @param  string $filename Filename
	 *
	 * @return string
	 */
	public function get_log_file_path($filename = 'debug')
	{
		$path = SSA_Filesystem::get_uploads_dir_path();
		if (empty($path)) {
			return false;
		}

		$path .= '/logs';
		if (!wp_mkdir_p($path)) {
			return false;
		}

		if (!file_exists($path . '/index.html')) {
			$handle = @fopen($path . '/index.html', 'w');
			@fwrite($handle, '');
			@fclose($handle);
		}
		
		if ( defined('AUTH_KEY') ) {
			$filename .= '-' . substr(sha1(AUTH_KEY), 0, 10);
		}

		return $path . '/' . sanitize_title($filename) . '.log';
	}


	/**
	 * Performs a list o site and plugin status checks and return the results.
	 *
	 * @return array
	 */
	public function get_site_status() {
		$site_status = new TD_Health_Check_Site_Status();

		$status = array(
			'plugin_version'       => $site_status->test_ssa_plugin_version(),
			'php_version'          => $site_status->test_php_version(),
			'wordpress_version'    => $site_status->test_wordpress_version(),
			'sql_server'           => $site_status->test_sql_server(),
			'json_extension'       => $site_status->test_json_extension(),
			'utf8mb4_support'      => $site_status->test_utf8mb4_support(),
			'dotorg_communication' => $site_status->test_dotorg_communication(),
			'https_status'         => $site_status->test_https_status(),
			'ssl_support'          => $site_status->test_ssl_support(),
			'scheduled_events'     => $site_status->test_scheduled_events(),
			'php_timezone'         => $site_status->test_php_default_timezone()
		);

		// If Paid edition, test site license
		if ( $this->plugin->settings_installed->is_installed( 'license' ) ) {
			$status = array_merge( array( 'ssa_license' => $this->test_site_license() ), $status );
		}

		return $status;
	}

	/**
	 * Receives a JSON formatted string, parses into import data, and runs all the import process.
	 *
	 * @param array $decoded    the JSON import data, decoded into an associative array format.
	 * @return boolean|WP_Error true if import process was successful, WP_Error if something bad happens.
	 */
	public function import_data( $decoded ) {

		// If settings data is available, disable all settings (so we don't trigger hooks for notifications, webhooks, etc).
		// The settings will get overwritten again at the end of this import process.
		if ( isset( $decoded['settings'] ) ) {
			$old_settings = $this->plugin->settings->get();
			foreach ( $old_settings as $key => &$old_setting ) {
				if ( empty( $old_setting ) || ! is_array( $old_setting ) ) {
					continue;
				}

				$old_setting['enabled'] = false;
			}

			// disable settings before import.
			$update = $this->plugin->settings->update( $old_settings );

			// staff.
			$delete = $this->plugin->staff_model->truncate();
			$this->plugin->staff_model->create_table();
			foreach ( $decoded['staff'] as $staff ) {

				// Remove user IDs from export code since it sometimes assign staff members to the wrong WP users.
				$staff['user_id'] = 0;

				$include = $this->plugin->staff_model->raw_insert( $staff );

				// if any error happens while trying to import appointment type data, return.
				if ( is_wp_error( $include ) ) {
					return $include;
				}
			}
		}

		// if appointment types data is available, update.
		if ( isset( $decoded['appointment_types'] ) ) {
			$delete = $this->plugin->appointment_type_model->truncate();
			$this->plugin->appointment_type_model->create_table();

			foreach ( $decoded['appointment_types'] as $appointment_type ) {
				$include = $this->plugin->appointment_type_model->raw_insert( $appointment_type );

				// If any error happens while trying to import appointment type data, return.
				if ( is_wp_error( $include ) ) {
					return $include;
				}
			}

			$delete = $this->plugin->staff_appointment_type_model->truncate();
			$this->plugin->staff_appointment_type_model->create_table();
			foreach ( $decoded['staff_appointment_types'] as $staff_appointment_type ) {
				$include = $this->plugin->staff_appointment_type_model->raw_insert( $staff_appointment_type );

				// If any error happens while trying to import staff appointment type data, return.
				if ( is_wp_error( $include ) ) {
					return $include;
				}
			}

			// If $decoded contains 'appointment_type_labels' -> the exported site has the migration run & all was set for the labels -> just import labels
			if ( isset( $decoded['appointment_type_labels'] ) && ! empty( $decoded['appointment_type_labels'] ) ) {

				$delete = $this->plugin->appointment_type_label_model->truncate();
				$this->plugin->appointment_type_label_model->create_table();

				foreach ( $decoded['appointment_type_labels'] as $appointment_type_label ) {
					$include = $this->plugin->appointment_type_label_model->raw_insert( $appointment_type_label );
	
					if ( is_wp_error( $include ) ) {
						return $include;
					}
				}
			} else {
				// We need to call the migration for the appt type labels to be set
				$this->plugin->upgrade->migrate_appointment_type_labels();
				$this->plugin->upgrade->maybe_fix_appointment_type_label_id_equal_to_zero();
			}

		}

		// If appointments data is available, update.
		if ( isset( $decoded['appointments'] ) ) {
			$delete = $this->plugin->appointment_model->truncate();
			$this->plugin->appointment_model->create_table();

			foreach ( $decoded['appointments'] as $appointment ) {
				$include = $this->plugin->appointment_model->raw_insert( $appointment );

				// If any error happens while trying to import appointment data, return.
				if ( is_wp_error( $include ) ) {
					return $include;
				}
			}

			$delete = $this->plugin->staff_appointment_model->truncate();
			$this->plugin->staff_appointment_model->create_table();

			if ( ! empty( $decoded['staff_appointments'] ) ) {
				foreach ( $decoded['staff_appointments'] as $staff_appointment ) {
					$include = $this->plugin->staff_appointment_model->raw_insert( $staff_appointment );

					// If any error happens while trying to import staff_appointment data, return.
					if ( is_wp_error( $include ) ) {
						return $include;
					}
				}
			}
		}

		// If appointments meta data is available, update.
		if ( isset( $decoded['appointment_meta'] ) ) {
			$delete = $this->plugin->appointment_meta_model->truncate();

			foreach ( $decoded['appointment_meta'] as $appointment_meta ) {
				$include = $this->plugin->appointment_meta_model->raw_insert( $appointment_meta );

				// If any error happens while trying to import appointment data, return.
				if ( is_wp_error( $include ) ) {
					return $include;
				}
			}
		}

		// If settings data is available, update.
		if ( isset( $decoded['settings'] ) ) {
			$update = $this->plugin->settings->update( $decoded['settings'] );
		}

		$delete = $this->plugin->availability_model->truncate();
		$this->plugin->availability_cache_invalidation->increment_cache_version();
		$this->plugin->google_calendar->increment_google_cache_version();

		$this->plugin->upgrade->migrate_free_to_paid_customer_info();
		$this->plugin->upgrade->migrate_paid_to_free_customer_info();
		// Everything was successfully imported.
		return true;
	}

	/**
	 * Save JSON export backups into the database.
	 *
	 * @param string $code
	 * @return bool|WP_Error true if backup was successfully saved. WP_Error if something wrong happens.
	 */
	public function save_export_backup( $code = null ) {
		if( ! $code ) {
			return false;
		}

		$date = date('Y-m-d H:i:s');
		$encoded = json_encode($code);

		$backups = get_option( 'ssa_export_backups' );

		if( ! $backups ) {
			$backups = array();
		}

		// if there is already 3 backups, remove the oldest one
		if( count($backups) >= 3 ) {
			array_pop($backups);
		}

		// insert the newest one at the beginning of the array
		array_unshift( 
			$backups, 
			array(
				'date' => $date,
				'content' => $encoded
			) 
		);

		$update = update_option( 'ssa_export_backups', $backups );

		if( ! $update ) {
			return new WP_Error( 'ssa-export-backup-not-saved', __( 'An error occurred while trying to save a backup.', 'simply-schedule-appointments' ) );
		}

		return $update;
	}

	/**
	 * Checks if there is a backup export file stored and, if it does, then decode the JSON into an associative array.
	 *
	 * @return boolean|array false if we can't find the file, or an associative array if we find it and it has a valid format.
	 */
	public function get_export_backup() {
		$backups = $this->get_export_backup_list();

		if( is_wp_error($backups) ) {
			return $backups;
		}

		$json = $backups[0]['content'];

		// verify if JSON data is valid
		$decoded = json_decode( $json, true );

		if ( ! is_object( $decoded ) && ! is_array( $decoded ) ) {
			return new WP_Error( 'export-code-invalid-format', __( 'Invalid data format.', 'simply-schedule-appointments'));
		}
		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'export-code-invalid-format', __( 'Invalid data format.', 'simply-schedule-appointments'));
		}

		if( $decoded ) {
			return $decoded;
		}

		return false;
	}

	/**
	 * Checks if there is an export backup stored and returns a list if any.
	 *
	 * @return array|WP_Error An associative array if we find backups. WP_Error if we can't find anything.
	 */
	public function get_export_backup_list() {
		$backups = get_option('ssa_export_backups');

		if( ! $backups || empty($backups) ) {
			return new WP_Error( 'ssa-export-backups-not-found', __( 'Could not find any export backups.', 'simply-schedule-appointments' ) );
		}

		return $backups;
	}	
	
	/**
	 * Searches for latest export backup and, if found, recover the data by running the import logic.
	 *
	 * @return boolean|WP_Error
	 */
	public function restore_settings_backup() {
		$backup = $this->get_export_backup();

		if( ! $backup || is_wp_error( $backup ) ) {
			return new WP_Error( 'ssa-export-file-not-found', __( 'No backup files were found.', 'simply-schedule-appointments' ) );
		}
		
		$import = $this->import_data( $backup );
		
		if( is_wp_error( $import ) ) {
			return $import;
		}

		return true;
	}

	/**
	 * Checks the current status of the plugin license.
	 *
	 * @return array
	 */
	public function test_site_license() {
		$expiration_date = $this->get_license_expiration_date();
		$settings  = $this->plugin->settings->get();
		$license   = $settings['license'];


		$login_url = 'https://simplyscheduleappointments.com/your-account/';
		$pricing_url = 'https://simplyscheduleappointments.com/pricing/';


		if ( ! $this->plugin->settings_installed->is_installed( 'license' ) || 'empty' === $license['license_status'] || 'inactive' === $license['license_status'] ) {
			return array(
				// translators: %s is the URL to the login page.
				'notices' => array( sprintf( __( '<a href="%s" target="_blank">Get your license key</a> and add it to <a href="#/ssa/settings/license">this site\'s settings</a> to enable automatic updates.', 'simply-schedule-appointments' ), $login_url ) ),
				'status'  => 'warning',
				'value'   => false,
			);
		}

		if ( 'disabled' === $license['license_status'] ) {
			return array(
				// translators: %s is the URL to the login page.
				'notices' => array( sprintf( __( 'Your license is disabled. <a href="%s" target="_blank">Purchase</a> a new license key to enable automatic updates and support.', 'simply-schedule-appointments' ), $pricing_url ) ),
				'status'  => 'warning',
				'value'   => false,
			);
		}

		if ( 'expired' === $license['license_status'] ) {
			return array(
				// translators: %s is the URL to the login page.
				'notices' => array(
					sprintf(
						__( 'Your license expired on %1$s. <a href="%2$s" target="_blank">Renew your license</a> to enable automatic updates, bug fixes and support.', 'simply-schedule-appointments' ),
						$expiration_date,
						$license['license_renewal_link']
					),
				),
				'status'  => 'warning',
				'value'   => false,
			);
		}

		if ( 'active' === $license['license_status'] || 'valid' === $license['license_status'] ) {
			return array(
				'notices' => array( __( 'Your license is up-to-date.', 'simply-schedule-appointments' ) ),
				'status'  => 'good',
				'value'   => true,
			);
		}

		// if there isn't any other information, then we assume that the license is invalid.
		return array(
			// translators: %s is the URL to the login page.
			'notices' => array( sprintf( __( '<a href="%s" target="_blank">Get your license key</a> and add it to <a href="#/ssa/settings/license">this site\'s settings</a> to enable automatic updates.', 'simply-schedule-appointments' ), $login_url ) ),
			'status'  => 'warning',
			'value'   => false,
		);
	}

	/**
	 * get the expiration date for expired license
	 *
	 */
	public function get_license_expiration_date() {

		$license = $this->plugin->license->check();

		if ( ! empty( $license['license_expiration_date'] ) ) {

			$formatted_date =	date_i18n( get_option( 'date_format' ), strtotime( $license['license_expiration_date'] ) );

			return $formatted_date;
		}
	}

	/**
	 * Check if the main directory is named correctly
	 *
	 * @return void
	 */
	public function validate_directory_name() {
		
		global $pagenow;
		if ( 'plugins.php' !== $pagenow ) {
			return;
		}

		$directory = $this->plugin->dir();
		$pattern = '/(.*)(\/|\\\)simply\-schedule\-appointments(\/|\\\)$/';
		$matching = preg_match( $pattern, $directory );
		
		if( ! $matching ) {
			add_action( 'after_plugin_row_' . $this->plugin->basename, array( $this, 'display_wrong_dir_name_error' ), 10 );
		}
	}

	/**
	 * Display warning message to users in plugins.php screen
	 *
	 * @return void
	 */
	public function display_wrong_dir_name_error () {

		echo '<tr class="plugin-update-tr active">';
		echo '<td colspan="4" class="plugin-update colspanchange">';
		echo '<div class="update-message notice inline notice-error notice-alt">';
		echo '<p>' .  __( '<strong>Error: Invalid directory name</strong>. Please rename this plugin\'s directory to simply-schedule-appointments to avoid errors when updating.', 'simply-schedule-appointments' ) . '</p>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';
	}

}
