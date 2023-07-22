<?php
/**
 * Simply Schedule Appointments Utils.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */
use League\Period\Period;

/**
 * Simply Schedule Appointments Utils.
 *
 * @since 0.0.3
 */
class SSA_Utils {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	protected $server_default_timezone_before_ssa;

	/**
	 * Constructor.
	 *
	 * @since  0.0.3
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
	 * @since  0.0.3
	 */
	public function hooks() {

	}

	public function defensive_timezone_fix() {
		if ( 'UTC' === date_default_timezone_get() ) {
			return;
		}

		$this->server_default_timezone_before_ssa = date_default_timezone_get();
		
		// We know that setting the default_timezone on a server is bad practice
		// WordPress expects it to be UTC to function properly
		// Our plugin also expects it to be UTC to function properly
		// Unfortunately we have found that some plugins do change the default timezone
		// We only call this function as a defensive measure, so SSA can co-exist with plugins
		// that set the timezone. Looking at you...
		// 
		// * Ajax Event Calendar plugin [https://wordpress.org/support/plugin/ajax-event-calendar] 
		// *** already removed from the wordpress.org repository
		// 
		// * Series Engine plugin [https://seriesengine.com/]
		// ** Pro plugin not available on wordpress.org
		// 
		// Here's our approach to addressing this issue:
		// We ONLY set the timezone to UTC if it's something different
		// 
		// We feel that it should be forced to UTC to adhere to WordPress standards, 
		// but that will probably break users' sites running these problematic plugins
		// 
		// To try and play nicely with others and to protect the user, we will call our
		// defensive_timezone_fix() before SSA does anything where we rely on a UTC timezone
		// at the end of our functions, we will call defensive_timezone_reset() so we'll put it
		// back to whatever the server already had set.
		// 
		// We see this as the only way to co-exist with these problematic plugins and simplify
		// life for the user. If there is a better approach, please get in touch.
		// We'd love to remove this code :)
		date_default_timezone_set( 'UTC' );
	}
	public function defensive_timezone_reset() {
		if ( empty( $this->server_default_timezone_before_ssa ) || 'UTC' === $this->server_default_timezone_before_ssa ) {
			return;
		}

		// We know that setting the default_timezone on a server is bad practice
		// ^^^ See note above in defensive_timezone_fix() ^^^
		date_default_timezone_set( $this->server_default_timezone_before_ssa );
	}

	public static function site_unique_hash( $string ) {
		if (defined('SSA_AUTH_SALT')) {
			$salt = SSA_AUTH_SALT;
		} else if ( defined( 'AUTH_SALT' ) ) {
			$salt = AUTH_SALT;
		} else {
			$salt = 'M+mZDQYJlrHoRZ0OfE1ESGG5T5CgPMOgOub25eOmwJYdPLmiNLbKwXYGQfG0pkF5YCw45DVtwaWREx3Jr4hILB';
		}

		return hash_hmac('md5', $string, $salt);
	}

	public static function hash( $string ) {
		if ( defined( 'SSA_AUTH_SALT' ) ) {
			$salt = SSA_AUTH_SALT;
		} else {
			$salt = '6U2aRk6oGvAZAEXstbFNMppRF=D|H.NX!-gU:-aXGVH<)8kcF~FPor5{Z<SFr~wKz';
		}
		
		return hash_hmac('md5', $string, $salt);
	}

	public static function get_home_id() {
		return self::hash( get_home_url() );
	}

	public static function is_assoc_array( array $arr ) {
		if ( array() === $arr ) return false;
		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}

	public static function array_key( $array, $key ) {
		if ( isset( $array[ $key ] ) ) {
			return $array[ $key ];
		}

		return false;
	}

	public static function datetime( $time='now' ) {
		if ( $time instanceof DateTimeImmutable ) {
			return $time;
		}
		
		if ( 0 === strpos( $time, 'Invalid' ) ) {
			ssa_debug_log( 'SSA_Utils::datetime()  `Invalid Date` detected' );
			// $time = 'now';
			return null; // TODO: handle error state gracefully
		} else if ( empty( $time ) ) {
			$time = 'now';
		}
		
		$timezone = new DateTimeZone( 'UTC' );

		return new DateTimeImmutable( $time, $timezone );
	}

	public static function ceil_datetime( DateTimeImmutable $datetime, $mins = 5 ) {
		$seconds = $mins * 60;
		$time = ( ceil( $datetime->getTimestamp() / $seconds ) ) * $seconds;

		return $datetime->setTimestamp( $time );
	}

	public static function floor_datetime( DateTimeImmutable $datetime, $mins = 5 ) {
		$seconds = $mins * 60;
		$time = ( floor( $datetime->getTimestamp() / $seconds ) ) * $seconds;

		return $datetime->setTimestamp( $time );
	}

	public static function get_datetime_in_utc( $datetime, $datetimezone='UTC' ) {
		if ( ! ( $datetimezone instanceof DateTimeZone ) ) {
			$datetimezone = new DateTimeZone( $datetimezone );
		}

		if ( ! ( $datetime instanceof DateTimeImmutable ) ) {
			$datetime = new DateTimeImmutable( $datetime, $datetimezone );
		}

		$datetime = $datetime->setTimezone( new DateTimeZone( 'UTC' ) );
		return $datetime;
	}

	public static function get_period_in_utc( Period $period ) {
		return self::get_period_in_timezone( $period, new DateTimeZone( 'UTC' ) );
	}

	public static function get_period_in_timezone( Period $period, DateTimeZone $timezone ) {
		return new Period(
			$period->getStartDate()->setTimezone( $timezone ),
			$period->getEndDate()->setTimezone( $timezone )
		);
	}

	public function get_datetime_as_local_datetime( $datetime, $appointment_type_id=0, $staff_id = 0, $location_id = 0 ) {
		$local_timezone = $this->get_datetimezone( $appointment_type_id, $staff_id, $location_id );

		if ( empty( $local_timezone ) || ! ( $local_timezone instanceof DateTimeZone ) ) {
			throw new SSA_Exception( __( 'No $local_timezone defined' ), 500 );
		}

		if ( $datetime instanceof DateTime ) {
			$datetime = new DateTimeImmutable( $datetime );
		} elseif ( ! ( $datetime instanceof DateTimeImmutable ) ) {
			$utc_timezone = new DateTimeZone( 'UTC' );
			$datetime = new DateTimeImmutable( $datetime, $utc_timezone );
			$datetime = $datetime->setTimezone( $local_timezone );
		} else {
			$datetime = $datetime->setTimezone( $local_timezone );
		}


		return $datetime;
	}

	public function get_datetimezone( $appointment_type_id = 0, $staff_id = 0, $location_id = 0 ) {
		if ( !empty( $staff_id ) ) {
			throw new SSA_Exception( __( 'Staff members are not supported yet' ), 500 );
		} elseif ( !empty( $location_id ) ) {
			throw new SSA_Exception( __( 'Locations are not supported yet' ), 500 );
		} else {
			$local_timezone = $this->plugin->settings_global->get_datetimezone();
		}

		return $local_timezone;
	}

	/**
	 * Returns datetime format set by the customer on the global settings page.
	 *
	 * @since 4.9.1
	 *
	 * @return string
	 */
	public static function get_localized_date_format_from_settings() {
		$settings = ssa()->settings->get();
		$format   = $settings['global']['date_format'] . ' ' . $settings['global']['time_format'];

		return $format;
	}

	/**
	 * Returns date format set by the customer on the global settings page.
	 *
	 * @since 4.9.1
	 *
	 * @return string
	 */	
	public static function get_localized_date_only_format_from_settings() {
		$settings = ssa()->settings->get();
		$format   = $settings['global']['date_format'];

		return $format;
	}

	/**
	 * Returns time format set by the customer on the global settings page.
	 *
	 * @since 4.9.1
	 *
	 * @return string
	 */
	public static function get_localized_time_only_format_from_settings() {
		$settings = ssa()->settings->get();
		$format   = $settings['global']['time_format'];

		return $format;
	}

	public static function localize_default_date_strings( $php_date_format ) {
		if ( 'F j, Y' === $php_date_format ) {
			$php_date_format = __( 'F j, Y', 'default' );	
		} else if ( 'g:i a' === $php_date_format ) {
			$php_date_format = __( 'g:i a', 'default' );	
		} else if ( 'F j, Y g:i a' === $php_date_format ) {
			$php_date_format = __( 'F j, Y g:i a', 'default' );	
		}

		return $php_date_format;
	}
	
	public static function translate_formatted_date( $formatted_date ) {
		$should_reset = false;
		unload_textdomain( 'simply-schedule-appointments', true );
		
		if( !empty( ssa()->translation->programmatic_locale ) && is_string( ssa()->translation->programmatic_locale ) ){
			$mo_file = WP_LANG_DIR . '/' . ssa()->translation->programmatic_locale . '.mo';
			if ( file_exists( $mo_file ) ) {
				$should_reset = true;
				// use WP core translations for months, assumed always more complete than SSA translations
				load_textdomain( 'simply-schedule-appointments', $mo_file );

			}
		}
		
		$translations = array(
			'January' => __( 'January', 'simply-schedule-appointments' ),
			'February' => __( 'February', 'simply-schedule-appointments' ),
			'March' => __( 'March', 'simply-schedule-appointments' ),
			'April' => __( 'April', 'simply-schedule-appointments' ),
			'May' => __( 'May', 'simply-schedule-appointments' ),
			'June' => __( 'June', 'simply-schedule-appointments' ),
			'July' => __( 'July', 'simply-schedule-appointments' ),
			'August' => __( 'August', 'simply-schedule-appointments' ),
			'September' => __( 'September', 'simply-schedule-appointments' ),
			'October' => __( 'October', 'simply-schedule-appointments' ),
			'November' => __( 'November', 'simply-schedule-appointments' ),
			'December' => __( 'December', 'simply-schedule-appointments' ),

			'Monday' => __( 'Monday', 'simply-schedule-appointments' ),
			'Tuesday' => __( 'Tuesday', 'simply-schedule-appointments' ),
			'Wednesday' => __( 'Wednesday', 'simply-schedule-appointments' ),
			'Thursday' => __( 'Thursday', 'simply-schedule-appointments' ),
			'Friday' => __( 'Friday', 'simply-schedule-appointments' ),
			'Saturday' => __( 'Saturday', 'simply-schedule-appointments' ),
			'Sunday' => __( 'Sunday', 'simply-schedule-appointments' ),
		);
		
		
		if( $should_reset ){
			// this should unload the WP core .mo translation, and load the SSA .mo file instead
			unload_textdomain( 'simply-schedule-appointments', true );
			load_plugin_textdomain( 'simply-schedule-appointments', false, dirname( ssa()->basename ) . '/languages' );
		}
		
		return str_replace( array_keys( $translations ), array_values( $translations ), $formatted_date );
	   
		// return strtr( ( string ) $formatted_date, $translations );
	}

	public static function php_to_moment_format($php_date_format) {
		$php_date_format = self::localize_default_date_strings( $php_date_format );

	    $replacements = array(
	        '\\h' => '[h]',
	        '\\m\\i\\n' => '[min]',
	        '\\m' => '[m]',
	        '\\' => '',
	        '\\d' => '\\d', // Leave Spanish string 'de' (of) untouched
	        '\\e' => '\\e', // Leave Spanish string 'de' (of) untouched

	        'd' => 'DD',
	        'D' => 'ddd',
	        'j' => 'D',
	        'l' => 'dddd',
	        'N' => 'E',
	        'S' => 'o',
	        'w' => 'e',
	        'z' => 'DDD',
	        'W' => 'W',
	        'F' => 'MMMM',
	        'm' => 'MM',
	        'M' => 'MMM',
	        'n' => 'M',
	        't' => '', // no equivalent
	        'L' => '', // no equivalent
	        'o' => 'YYYY',
	        'Y' => 'YYYY',
	        'y' => 'YY',
	        'a' => 'a',
	        'A' => 'A',
	        'B' => '', // no equivalent
	        'g' => 'h',
	        'G' => 'H',
	        'h' => 'hh',
	        'H' => 'HH',
	        'i' => 'mm',
	        's' => 'ss',
	        'u' => 'SSS',
	        'e' => 'zz', // deprecated since version 1.6.0 of moment.js
	        'I' => '', // no equivalent
	        'O' => '', // no equivalent
	        'P' => '', // no equivalent
	        'T' => '', // no equivalent
	        'Z' => '', // no equivalent
	        'c' => '', // no equivalent
	        'r' => '', // no equivalent
	        'U' => 'X',
	    );

	    $moment_format = strtr($php_date_format, $replacements);

	    return $moment_format;
	}

	public static function moment_to_php_format($moment_date_format) {
	    $replacements = array(
	        'DD' => 'd', 
	        'ddd' => 'D', 
	        'D' => 'j', 
	        'dddd' => 'l', 
	        'E' => 'N', 
	        'o' => 'S', 
	        'e' => 'w', 
	        'DDD' => 'z', 
	        'W' => 'W', 
	        'MMMM' => 'F', 
	        'MM' => 'm', 
	        'MMM' => 'M', 
	        'M' => 'n', 
	        'YYYY' => 'o', 
	        'YYYY' => 'Y', 
	        'YY' => 'y', 
	        'a' => 'a', 
	        'A' => 'A', 
	        'h' => 'g', 
	        'H' => 'G', 
	        'hh' => 'h', 
	        'HH' => 'H', 
	        'mm' => 'i', 
	        'ss' => 's', 
	        'SSS' => 'u', 
	        'zz' => 'e',  // deprecated since version 1.6.0 of moment.js
	        'X' => 'U', 
	    );
	    
	    $php_format = strtr($moment_date_format, $replacements);

	    return $php_format;
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	public static function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	public static function get_appointment_type( $appointment_type ) {
		if ( (int)$appointment_type == $appointment_type ) {
			$appointment_type = ssa()->appointment_type_model->get( $appointment_type );
		}
		if ( empty( $appointment_type['id'] ) ) {
			return false;
		}

		return $appointment_type;
	}

	public static function get_query_period( Period $query_period = null ) {
		if ( null === $query_period ) {
			$query_period = SSA_Constants::EPOCH_PERIOD();
		}

		return $query_period;
	}
}

function ssa_datetime( $time='now' ) {
	return SSA_Utils::datetime( $time );
}

function ssa_gmtstrtotime( $string ) {
	$time = strtotime($string . ' +0000');

	if ( -1 == $time ) {
		return strtotime($string);
	}

	return $time;
}

function ssa_defensive_timezone_fix() {
	ssa()->utils->defensive_timezone_fix();
}
function ssa_defensive_timezone_reset() {
	ssa()->utils->defensive_timezone_reset();
}

function ssa_debug_log( $var, $debug_level = 1, $label = '', $file = 'debug' ) {
	if ( defined( 'SSA_DEBUG_LOG' ) && empty( SSA_DEBUG_LOG ) ) {
		return;
	}
	$developer_settings = ssa()->developer_settings->get();
	if( empty( $developer_settings['ssa_debug_mode'] ) ) {
		if ( $debug_level < 10 ) {
			// We want to log really fatal errors (level 10) so the support team can see them when logging in after the fact
			return;
		}
	}
	
	$path = ssa()->support_status->get_log_file_path( $file );
	$log_prefix = gmdate( '[Y-m-d H:i:s] ' );
	
	if ( empty( $path ) ) {
		return;
	}
	

	error_log( PHP_EOL . 'The following is logged from ' . debug_backtrace()[1]['function'] . '()' . PHP_EOL, 3, $path );
	error_log( 'in ' .debug_backtrace()[0]['file'] . ' line ' . debug_backtrace()[0]['line'] . ':' . PHP_EOL, 3, $path );

	error_log( PHP_EOL . '... ' . debug_backtrace()[2]['function'] . '()' . PHP_EOL, 3, $path );
	error_log( 'in ' .debug_backtrace()[1]['file'] . ' line ' . debug_backtrace()[1]['line'] . ':' . PHP_EOL, 3, $path );

	error_log( PHP_EOL . '...... ' . debug_backtrace()[3]['function'] . '()' . PHP_EOL, 3, $path );
	error_log( 'in ' .debug_backtrace()[2]['file'] . ' line ' . debug_backtrace()[2]['line'] . ':' . PHP_EOL, 3, $path );

	if ( ! empty( $label ) ) {
		error_log( $log_prefix.$label.PHP_EOL, 3, $path );
	}
	if ( is_string( $var ) ) {
		error_log( $log_prefix.$var.PHP_EOL, 3, $path );
	} else {
		// error_log( $log_prefix.print_r( $var, true ).PHP_EOL, 3, $path );
	}
}

function ssa_int_hash( $string ) {
	return abs( crc32( $string ) );
}

function ssa_get_staff_id( $user_id ) {
	return ssa()->staff_model->get_staff_id_for_user_id( $user_id );
}
function ssa_get_current_staff_id() {
	return ssa_get_staff_id( get_current_user_id() );
}

function ssa_cache_get( $key, $group = '', $force = false, &$found = null ) {
	return SSA_Cache::object_cache_get( $key, $group, $force, $found );
}
function ssa_cache_set( $key, $data, $group = '', $expire = 0 ) {
	return SSA_Cache::object_cache_set( $key, $data, $group, $expire );
}
function ssa_cache_delete( $key, $group = '' ) {
	return SSA_Cache::object_cache_delete( $key, $group );
}


/**
 * SSA custom class_exists function
 *
 * @since   6.0.3
 * 
 * @param string $class
 * @return bool
 */
function ssa_class_exists( $class = '' ) {
	if ( ! class_exists( $class ) ) {
		$var = 'Class "' . $class . '" not found!';
		$debug_level = 10;
		ssa_debug_log($var, $debug_level );
		return false;
	}
	return true;
}

/**
 * SSA custom function_exists function
 *
 * @since   6.0.3
 * 
 * @param string $function
 * @return bool
 */
function ssa_function_exists( $function = '' ) {
	if ( ! function_exists( $function ) ) {
		$var = 'Function "' . $function . '" not found!';
		$debug_level = 10;
		ssa_debug_log($var, $debug_level );
		return false;
	}
	return true;
}

/**
 * SSA custom as_has_scheduled_action that does all the checking needed
 * 
 * @since   6.0.3
 *
 * @param string $hook  Required
 * @return bool|null
 */
function ssa_has_scheduled_action( $hook = '' ) {

	if ( empty( $hook ) ) {
		return;
	}

	if ( ! ssa_class_exists( 'ActionScheduler' ) ) {
		return;
	}

	if ( ! ssa_function_exists( 'as_has_scheduled_action' ) ) {
		return;
	}

	try {
		return as_has_scheduled_action( $hook );

	} catch( \Exception $e ) {
		$var = $e->getMessage();
		$debug_level = 10;
		$label = 'Action Scheduler';
		ssa_debug_log( $var, $debug_level, $label );
	}
}

/**
 * SSA custom as_schedule_recurring_action that does all the checking needed
 * 
 * @since   6.0.3
 *
 * @param integer $timestamp            Required
 * @param integer $interval_in_seconds	Required
 * @param string $hook                  Required
 * @param array $args                   Optional
 * @param string $group                 Optional
 * @return integer|null                 The action ID or void
 */
function ssa_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook='', $args = array(), $group = '' ) {

	if ( empty( $timestamp ) ||  empty( $interval_in_seconds ) || empty( $hook ) ) {
		return;
	}

	if ( ! ssa_class_exists( 'ActionScheduler' ) ) {
		return;
	}

	if ( ! ssa_function_exists( 'as_schedule_recurring_action' ) ) {
		return;
	}

	try {
		// return the ID of the scheduled action
		return as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args, $group );

	} catch( \Exception $e ) {
		$var = $e->getMessage();
		$debug_level = 10;
		$label = 'Action Scheduler';
		ssa_debug_log( $var, $debug_level, $label );
	}
}

/**
 * SSA custom as_unschedule_action that does all the checking needed
 * 
 * @since   6.0.3
 *
 * @param string $hook      Required
 * @param array $args       Optional
 * @param string $group     Optional
 * @return integer|null.    The scheduled action ID if a scheduled action was found, or null if no matching action found.
 */
function ssa_unschedule_action( $hook='', $args = array(), $group = '' ) {

	if ( empty( $hook ) ) {
		return;
	}

	if ( ! ssa_class_exists( 'ActionScheduler' ) ) {
		return;
	}

	if ( ! ssa_function_exists( 'as_unschedule_action' ) ) {
		return;
	}

	try {
		return as_unschedule_action( $hook, $args, $group );

	} catch( \Exception $e ) {
		$var = $e->getMessage();
		$debug_level = 10;
		$label = 'Action Scheduler';
		ssa_debug_log( $var, $debug_level, $label );
	}
}

/**
 * SSA custom as_schedule_single_action that does all the checking needed
 * 
 * @since   6.0.3
 *
 * @param integer $timestamp	Required
 * @param string $hook          Required
 * @param array $args           Optional
 * @param string $group         Optional
 * @return integer|null.        The scheduled action ID if a scheduled action was found, or null if no matching action found.
 */
function ssa_schedule_single_action( $timestamp, $hook='', $args = array(), $group = '' ) {

	if ( empty( $timestamp ) || empty( $hook ) ) {
		return;
	}

	if ( ! ssa_class_exists( 'ActionScheduler' ) ) {
		return;
	}

	if ( ! ssa_function_exists( 'as_schedule_single_action' ) ) {
		return;
	}

	try {
		return as_schedule_single_action( $timestamp, $hook, $args, $group );

	} catch( \Exception $e ) {
		$var = $e->getMessage();
		$debug_level = 10;
		$label = 'Action Scheduler';
		ssa_debug_log( $var, $debug_level, $label );
	}
}

/**
 * SSA queue runner for wp_actionscheduler_actions table 
 *
 * @since   6.4.3
 * 
 * @return integer
 */
function ssa_run_action_scheduler_queue(){

	if ( ! class_exists( 'ActionScheduler' ) ) {
		return;
	}

	try {
		return ActionScheduler::runner()->run();

	} catch( \Exception $e ) {
		$var = $e->getMessage();
		$debug_level = 10;
		$label = 'Action Scheduler';
		ssa_debug_log( $var, $debug_level, $label );
	}

}

/**
 * Get all active plugins
 * Return array of active plugins names, eg. array( 'draw-attention', 'simply-schedule-appointments', 'wp-mail-logging' );
 *
 * @return array
 */
function ssa_get_all_active_plugins(){

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


function ssa_evaluate_datetime_merge_tag( $start_date, $modifier, $date_timezone ){
	$modifier_string = explode( ':', $modifier, 3);
	// by default format as day of the week
	$formatted_value = ssa_datetime( $start_date )->setTimezone(  $date_timezone )->format( 'l' );

	if( $modifier_string[1] === 'format' ){
		// if a format string is included, format accordingly
		$date_format = str_replace( 'y', 'Y', $modifier_string[2] );
		$date_format = str_replace( 'h', 'H', $date_format );
		$formatted_value = ssa_datetime( $start_date )->setTimezone(  $date_timezone )->format( $date_format );
	}
	
	$formatted_value = SSA_Utils::translate_formatted_date( $formatted_value );
	return $formatted_value;
}

function ssa_evaluate_merge_tag ( $appointment_id, $modifier ) {
	
	try {
		$appointment_obj = new SSA_Appointment_Object( $appointment_id );
		$start_date      = $appointment_obj->start_date;
	} catch ( Exception $e ) {
		// failed to get appointment from appointment_id
		return '';
	}
	// get settings
	$settings = ssa()->settings->get();

	if ( 'business_start_date' === explode( ':', $modifier )[0] ) {
		$date_timezone    = new DateTimeZone( $settings['global']['timezone_string'] );
		return ssa_evaluate_datetime_merge_tag( $start_date, $modifier, $date_timezone );
	}
	
	if ( 'customer_start_date' === explode( ':', $modifier )[0] && count( explode( ':', $modifier ) ) > 1 ) {
		return ssa_evaluate_datetime_merge_tag( $start_date, $modifier, $appointment_obj->get_customer_timezone() );
	}

	if ( 'instructions' === $modifier ) {
		return $appointment_obj->get_appointment_type()->instructions;
	}
	
	if ( 'public_edit_url' === $modifier ) {
		return $appointment_obj->get_public_edit_url();
	}

	if ( 'appointment_type_slug' === $modifier ) {
		$appointment_type = $appointment_obj->get_appointment_type();
		return $appointment_type->get_slug();
	}

	if ( 'appointment_type_title' === $modifier ) {
		$appointment_type = $appointment_obj->get_appointment_type();
		return $appointment_type->get_title();
	}

	if ( 'web_meeting_url' === $modifier ) {
		return $appointment_obj->__get( 'web_meeting_url' );
	}

	if ( 'add_to_calendar_link_ics' === $modifier ) {
		$token = ssa()->appointment_model->get_id_token( $appointment_id );
		return add_query_arg(
			array(
				'token' => $token,
			),
			$appointment_obj->get_ics_download_url( 'customer' )
		);
	}

	if ( 'add_to_calendar_link_gcal' === $modifier ) {
		return $appointment_obj->get_gcal_add_link( 'customer' );
	}

	if ( 'team_member_name' === $modifier ) {
		$members = $appointment_obj->get_staff_members();

		if ( empty( $members ) ) {
			return '';
		}

		// loop through members and get a list of names.
		$names = array();
		foreach ( $members as $member ) {
			$names[] = $member->get_name();
		}

		if ( count( $names ) > 1 ) {
			$last  = array_pop( $names );
			$text  = implode( ', ', $names );
			$text .= ' and ' . $last;

			return $text;
		} else {
			return $names[0];
		}
	}

	if ( 'team_member_email' === $modifier ) {
		$members = $appointment_obj->get_staff_members();

		if ( empty( $members ) ) {
			return '';
		}

		// loop through members and get a list of emails.
		$emails = array();
		foreach ( $members as $member ) {
			$emails[] = $member->get_email();
		}

		if ( count( $emails ) > 1 ) {
			$last  = array_pop( $emails );
			$text  = implode( ', ', $emails );
			$text .= ' and ' . $last;

			return $text;
		} else {
			return $emails[0];
		}
	}

	$ssa = ssa();

	if (
		'start_date_only' === $modifier ||
		'start_time_only' === $modifier
	) {
		$business_start_date = ssa_datetime( $start_date );

		if ( 'start_date_only' === $modifier ) {
			$format = SSA_Utils::get_localized_date_only_format_from_settings();
			$format = SSA_Utils::localize_default_date_strings( $format );
		} else {
			$format = SSA_Utils::get_localized_time_only_format_from_settings();
			$format = SSA_Utils::localize_default_date_strings( $format ) . ' (T)';
		}

		$formatted_value = $ssa->utils->get_datetime_as_local_datetime( $business_start_date, $appointment_id )->format( $format );
		$formatted_value = SSA_Utils::translate_formatted_date( $formatted_value );
		return $formatted_value;
	}

	if (
		'customer_start_date' === $modifier ||
		'customer_start_date_only' === $modifier ||
		'customer_start_time_only' === $modifier
	) {
		if ( 'customer_start_date' === $modifier ) {
			$format = SSA_Utils::get_localized_date_format_from_settings();
			$format = SSA_Utils::localize_default_date_strings( $format ) . ' (T)';
		} elseif ( 'customer_start_date_only' === $modifier ) {
			$format = SSA_Utils::get_localized_date_only_format_from_settings();
			$format = SSA_Utils::localize_default_date_strings( $format );
		} elseif ( 'customer_start_time_only' === $modifier ) {
			$format = SSA_Utils::get_localized_time_only_format_from_settings();
			$format = SSA_Utils::localize_default_date_strings( $format ) . ' (T)';
		}

		$formatted_value = ssa_datetime( $start_date )->setTimezone( $appointment_obj->get_customer_timezone() )->format( $format );
		$formatted_value = SSA_Utils::translate_formatted_date( $formatted_value );
		return $formatted_value;
	}

	$local_start_date = $ssa->utils->get_datetime_as_local_datetime( $start_date );
	$format           = SSA_Utils::get_localized_date_format_from_settings();
	$format           = SSA_Utils::localize_default_date_strings( $format ) . ' (T)';
	$formatted_value  = $local_start_date->format( $format );
	$formatted_value  = SSA_Utils::translate_formatted_date( $formatted_value );
	return $formatted_value;
}