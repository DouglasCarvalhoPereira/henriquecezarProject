<?php
/**
 * Simply Schedule Appointments Notices.
 *
 * @since   0.1.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Notices.
 *
 * @since 0.1.0
 */
class SSA_Notices {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.1.0
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	protected $top_notice_transient_key = 'ssa/notices/one_notice_to_display';


	/**
	 * Constructor.
	 *
	 * @since  0.1.0
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
	 * @since  0.1.0
	 */
	public function hooks() {

	}

	/**
	 * Call & Return cached notice if set, otherwise call for it and cache it 
	 *
	 * @return string
	 */
	public function get_the_one_notice_to_display() {

		$cached_notice = get_transient( $this->top_notice_transient_key );

		if( empty( $cached_notice ) ) {

			$cached_notice = $this->get_appropriate_notice();
	
			set_transient( $this->top_notice_transient_key , $cached_notice, HOUR_IN_SECONDS );

		}

		return $cached_notice === 'none' ? '' : $cached_notice;

	}
	
	/**
	 * Invoke the chain of filters to run
	 * Return the notice's id to display for the current site | 'none'
	 *
	 * @return string
	 */
	public function get_appropriate_notice(){

		$valid_notices = $this->get_appropriate_notices_for_this_site();

		$filtered_by_pinned_notices = $this->filter_by_pinned_notices( $valid_notices );

		$notice_to_display = $this->filter_by_priority( $filtered_by_pinned_notices );

		return $notice_to_display ? $notice_to_display['id'] : 'none';
	}

	/**
	 * Get the max priority from a list of notices then return the first that matches
	 * If two notices have their priority set to 1 (most important)
	 * We would return the first one found and discard all other notices
	 * 
	 * @param array $input
	 * @return array
	 */
	public function filter_by_priority( $input = array() ) {

		if ( empty( $input ) ) { return []; }

		$min_priority = min(array_column($input, 'priority'));

		foreach( $input as $notice ) {
			if ( $min_priority === $notice['priority'] ) {
				$output = $notice;
				break;
			}
		}
		return $output;
	}

	/**
	 * Get all notices, then filter out all inapproriate for the current site
	 * Returns an array of all valid notices to display
	 *
	 * @return array
	 */
	public function get_appropriate_notices_for_this_site() {

		$all_notices_list = $this->get_all_notices();

		$filtered_by_active_state = $this->filter_by_active_status( $all_notices_list );

		$filtered_by_dissmissed_notices = $this->filter_by_dismissed_notices( $filtered_by_active_state );

		$filtered_by_edition  = $this->filter_by_edition( $filtered_by_dissmissed_notices );

		$filtered_by_active_plugin = $this->filter_by_active_plugin_any( $filtered_by_edition );

		$filtered_by_installed_features = $this->filter_by_installed_feature_any( $filtered_by_active_plugin );

		$filtered_by_enabled_features = $this->filter_by_enabled_feature_any( $filtered_by_installed_features );

		$filtered_by_activated_features = $this->filter_by_activated_feature_any( $filtered_by_enabled_features );

		$filtered_by_not_installed_features = $this->filter_by_not_installed_feature_any( $filtered_by_activated_features );

		$filtered_by_not_enabled_features = $this->filter_by_not_enabled_feature_any( $filtered_by_not_installed_features );

		$filtered_by_not_activated_features = $this->filter_by_not_activated_feature_any( $filtered_by_not_enabled_features );

		$filtered_by_current_user_can = $this->filter_by_current_user_can( $filtered_by_not_activated_features );

		$filtered_by_min_activated_days = $this->filter_by_min_activated_days( $filtered_by_current_user_can );

		$filtered_by_min_appt_count = $this->filter_by_min_appt_count( $filtered_by_min_activated_days );

		return $filtered_by_min_appt_count;
	}

	/**
	 * Get ALL_NOTICES_LIST
	 *
	 * @return array
	 */
	public function get_all_notices() {
		return SSA_Notices_Data::get_notices_list();
	}

	/**
	 * Each notice has and active property set to true by default
	 * In case set to false, will allow us to eliminate this notice early
	 * Without the need to remove it from the list entirely
	 *
	 * @return array
	 */
	public function filter_by_active_status( $input = array() ) {

		if ( empty( $input ) ) { return []; }

		$output = array_filter( $input, function( $notice ) {
			return $notice['active'] === true;
		});

		return $output;
	}

	/**
	 * Filter out notices that got dissmissed by the user
	 *
	 * @param array $input
	 * @return array
	 */
	public function filter_by_dismissed_notices( $input = array() ) {

		$dismissed_notices = $this->get_dismissed_notices();

		if ( empty( $dismissed_notices ) || empty( $input ) ){
			return $input;
		}

		$output = array_filter( $input, function( $notice ) use ( $dismissed_notices ) {

			return !in_array( $notice['id'], $dismissed_notices);
		});

		return $output;
	}

	/**
	 * Get dismissed notices stored in the database if any
	 * An empty option dissmissed notices is stored: a:0:{}
	 *
	 * @return array
	 */
	public function get_dismissed_notices() {
		$dismissed_notices = get_option( 'ssa_dismissed_notices', array() );

		return $dismissed_notices;
	}

	/**
	 * Get pinned notices stored in the database if any
	 *
	 * @return array
	 */
	public function get_pinned_notices() {
		$pinned_notices = get_option( 'ssa_pinned_notices', array() );

		return $pinned_notices;
	}

	/**
	 * Check if the user's edition matches with the required edition for the notices
	 *
	 * @param array $input
	 * @return array
	 */
	public function filter_by_edition( $input = array() ) {

		if ( empty( $input ) ) { return []; }

		$edition_detected = $this->plugin->get_current_edition();

		$output = array_filter( $input, function( $notice ) use ( $edition_detected ) {

			$required_editions = $notice['requires']['current_edition_any'];

			if ( empty( $required_editions ) ) {
				// No specific edition required for this notice -> keep it
				return true;

			} else {
				// If the notice requires specific edition(s); check if the user's edition matches
				return in_array( $edition_detected, $required_editions );
			}
		});
		return $output;
	}


	/**
	 * Filter by ['active_plugin_any'] field
	 *
	 * @param array $input
	 * @return array
	 */
	public function filter_by_active_plugin_any( $input = array() ){

		if ( empty( $input ) ) { return []; }

		$output = array_filter( $input, function( $notice ) {

			$plugins = $notice['requires']['active_plugin_any'];

			if ( empty( $plugins ) ) {
				return true;
			}
			
			static $all_active_plugins;

			foreach( $plugins as $plugin ) {

				if( empty( $all_active_plugins ) ) {
					$all_active_plugins = $this->get_active_plugins();
				}
				if ( in_array( $plugin, $all_active_plugins ) ) {
					return true;
				}
			}
			return false;
		});

		return $output;
	}

	/**
	 * Get all active plugins
	 *
	 * @return array
	 */
	public function get_active_plugins(){

		$active_plugins = get_option( 'active_plugins' );
		$output = array();
		
		foreach ($active_plugins as $active_plugin ) {
			if ( strpos( $active_plugin, '/' ) ) {
				$active_plugin = substr( $active_plugin, 0, strpos( $active_plugin, '/' ) );
			}
			$output[] = $active_plugin;
		}
		return $output;
	}

	/**
	 * Check if any notice requires a specific installed feature
	 * By checking the ['installed_feature_any'] field
	 *
	 * @param array $input
	 * @return array
	 */
	public function filter_by_installed_feature_any( $input = array() ) {

		if ( empty( $input ) ) { return []; }

		$output = array_filter( $input, function( $notice ) {

			// For every notice in the array check the field below
			$features = $notice['requires']['installed_feature_any'];

			if ( empty( $features ) ) {
				// No feature required to be installed for this notice -> Nothing to check -> keep it
				return true;

			} else {
				// Else a feature is required to be installed, so we need to check
				foreach( $features as $feature) {
					if ( $this->check_if_feature_installed( $feature ) ) {
						return true;
					}
				}
				return false;
			}
		});

		return $output;
	}

	/**
	 * Check if any notice requires a NOT installed feature
	 *
	 * @param array $input
	 * @return array
	 */
	public function filter_by_not_installed_feature_any( $input = array() ) {

		if ( empty( $input ) ) { return []; }

		$output = array_filter( $input, function( $notice ) {

			// For every notice check if it requires a NOT installed feature
			$features = $notice['requires']['not_installed_feature_any'];

			if ( empty( $features ) ) {
				// No feature required to be NOT installed for this notice -> Nothing to check -> keep it
				return true;

			} else {
				// Else a feature is required to be NOT installed, so we need to check
				foreach( $features as $feature) {
					if ( ! $this->check_if_feature_installed( $feature ) ) {
						return true;
					}
				}
				return false;
			}
		});

		return $output;
	}

	/**
	 * Custom function to call out the is_installed built in method
	 * Return true if installed, false otherwise
	 *
	 * @param string $feature
	 * @return boolean
	 */
	public function check_if_feature_installed( $feature = '' ) {
		
		if ( empty( $feature ) ){ return false; }
		
		return $this->plugin->settings_installed->is_installed( $feature );
	}



	/**
	 * Check if any notice requires an enabled feature
	 * If no enabled feature is required then let it pass; keep the notice
	 * Otherwise check for the feature:
	 * If it is enabled; keep the notice
	 * If it is not then filter the notice out
	 *
	 * @param array $input
	 * @return array
	 */
	public function filter_by_enabled_feature_any( $input = array() ) {

		if ( empty( $input ) ) { return []; }

		$output = array_filter( $input, function( $notice ) {

			// For every notice in the array check the field below
			$features = $notice['requires']['enabled_feature_any'];

			if ( empty( $features ) ) {
				// No feature required to be enabled for this notice -> Nothing to check -> keep it
				return true;

			} else {
				// Else a feature is required to be enabled, so we need to check
				foreach( $features as $feature) {
					if ( $this->check_if_feature_enabled( $feature ) ) {
						return true;
					}
				}
				return false;
			}
		});

		return $output;
	}

	/**
	 * Check if any notice requires an NOT enabled feature
	 *
	 * @param array $input
	 * @return array
	 */
	public function filter_by_not_enabled_feature_any( $input = array() ) {

		if ( empty( $input ) ) { return []; }

		$output = array_filter( $input, function( $notice ) {

			// For every notice check for required feature to be NOT enabled
			$features = $notice['requires']['not_enabled_feature_any'];

			if ( empty( $features ) ) {
				// No feature required to be NOT enabled for this notice -> Nothing to check -> keep it
				return true;

			} else {
				// Else a feature is required to be NOT enabled, so we need to check
				foreach( $features as $feature) {
					if ( ! $this->check_if_feature_enabled( $feature ) ) {
						return true;
					}
				}
				return false;
			}
		});

		return $output;
	}

	/**
	 * Custom function to call out the is_enabled built in method
	 * Return true if enabled, false otherwise
	 *
	 * @param string $feature
	 * @return boolean
	 */
	public function check_if_feature_enabled( $feature = '' ) {

		if ( empty( $feature ) ){ return false; }
		
		return $this->plugin->settings_installed->is_enabled( $feature );
	}

	/**
	 * Check if any notice requires an activated feature 
	 * If any has been found call out the check_if_feature_activated
	 *
	 * @param array $input
	 * @return array
	 */
	public function filter_by_activated_feature_any( $input = array() ) {

		if ( empty( $input ) ) { return []; }

		$output = array_filter( $input, function( $notice ) {

			$features = $notice['requires']['activated_feature_any'];

			if ( empty( $features ) ) {
				// No feature required to be activated for this notice -> keep it
				return true;

			} else {
				foreach( $features as $feature) {
					if ( $this->check_if_feature_activated( $feature ) ) {
						return true;
					}
				}
				return false;
			}
		});

		return $output;
	}


	/**
	 * Check if any notice requires a NOT activated feature 
	 * If any has been found call out the check_if_feature_activated
	 *
	 * @param array $input
	 * @return array
	 */
	public function filter_by_not_activated_feature_any( $input = array() ) {

		if ( empty( $input ) ) { return []; }

		$output = array_filter( $input, function( $notice ) {

			$features = $notice['requires']['not_activated_feature_any'];

			if ( empty( $features ) ) {
				// No feature required to be not activated for this notice -> keep it
				return true;

			} else {
				// the `!` is needed since it requires the feature to be NOT activated
				foreach( $features as $feature) {
					if ( ! $this->check_if_feature_activated( $feature ) ) {
						return true;
					}
				}
				return false;
			}
		});

		return $output;
	}

	/**
	 * Custom function to call out the is_activated built in method
	 *
	 * @param string $feature
	 * @return boolean
	 */
	public function check_if_feature_activated( $feature = '' ) {
		
		if ( empty( $feature ) ){ return false; }
		
		return $this->plugin->settings_installed->is_activated( $feature );
	}

	public function filter_by_pinned_notices( $input = array() ) {

		$pinned_notices = $this->get_pinned_notices();

		if ( empty( $pinned_notices ) || empty( $input ) ){
			return $input;
		}

		// Assign a priority of 22 for every pinned notices
		$output = array_map( function( $notice ) use ( $pinned_notices ) {
			// Check for pinned notices
			if ( in_array( $notice['id'], $pinned_notices ) ){
				$notice['priority'] = '22';

			}
			return $notice;

		}, $input );

		return $output;
	}

	/**
	 * Filter by ['requires']['current_user_can'] field
	 * Example: ['requires']['current_user_can'] => array( 'ssa_manage_site_settings' ),
	 *
	 * @param array $input
	 * @return array
	 */
	public function filter_by_current_user_can( $input = array() ) {

		if ( empty( $input ) ) { return []; }

		$output = array_filter( $input, function( $notice ) {

			$permissions = $notice['requires']['current_user_can'];

			if ( empty( $permissions ) ) {
				return true;

			} else {
				// Loop over the permissions since it's an array
				foreach( $permissions as $permission ) {
					// If any evaluated to false break & return false to filter the notice out
					if ( ! current_user_can( $permission ) ) {
						return false;
					}
				}
				return true;
			}
		});

		return $output;
	}

	/**
	 * Check and filter notices by min_activated_days
	 *
	 * @param array $input
	 * @return array
	 */
	public function filter_by_min_activated_days( $input = array() ) {

		if ( empty( $input ) ) { return []; }

		$output = array_filter( $input, function( $notice ) {
			
			$required_activated_days = $notice['requires']['min_activated_days'];
			
			if ( empty( $required_activated_days ) ) {
				return true;
				
			} else {
				static $ssa_activated_days;

				if( empty( $ssa_activated_days ) && $ssa_activated_days !== 0 ) {
					$ssa_activated_days = $this->ssa_activated_days();
				}

				return (int) $required_activated_days <= (int) $ssa_activated_days;
			}
		});

		return $output;
	}

	/**
	 * In class-upgrade.php -> record_version() stores the date of each migration running
	 * Hence the first migration date indicates the activation date of the plugin
	 *
	 * @return int
	 */
	public function ssa_activated_days() {

		$ssa_versions = get_option( 'ssa_versions', json_encode( array() ) );
		$ssa_versions = json_decode( $ssa_versions, true );

		if( empty( $ssa_versions ) ) {
			return 0;
		}

		$activation_date	= array_keys($ssa_versions)[0];
		$activation_date	= ssa_datetime( $activation_date );
		$now        		= ssa_datetime();
		$diff       		= $now->diff( $activation_date );

		return $diff->days;
	}
  
  	/**
	 * Check and filter notices that require minimum booked appointment count
	 *
	 * @param array $input
	 * @return array
	 */
	public function filter_by_min_appt_count( $input = array() ) {

		if ( empty( $input ) ) { return []; }

		$output = array_filter( $input, function( $notice ) {

			$required_appt_count = $notice['requires']['min_appt_count'];

			if ( empty( $required_appt_count ) ) {
				return true;

			} else {

				static $appointments_count;
				if( empty( $appointments_count ) && $appointments_count !== 0 ) {
					$appointments_count = $this->get_completed_appointments_count();
				}

				return (int) $required_appt_count <= (int) $appointments_count;
			}
		});

		return $output;
	}

	/**
	 * Query COUNT of all booked appointments that have their end_date before today
	 *
	 * @return integer
	 */
	public function get_completed_appointments_count(){

		$appointments_count = $this->plugin->appointment_model->count( array(
			'status'        => array( 'booked' ),
			'end_date_max'	=> gmdate( 'Y-m-d H:i:s' ),
		));
		
		return $appointments_count;
	}

	/**
	 * Delete cached transient
	 *
	 * @return boolean
	 */
	public function delete_top_notice_cached_transient(){
		$transient = $this->top_notice_transient_key;
		return delete_transient( $transient );
	}

}
