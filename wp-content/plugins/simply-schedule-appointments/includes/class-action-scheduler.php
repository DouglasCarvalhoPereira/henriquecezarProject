<?php
/**
 * Simply Schedule Appointments Scheduler.
 *
 * @since   4.7.4
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Scheduler.
 *
 * @since 4.7.4
 */
class SSA_Action_Scheduler {
	/**
	 * Parent plugin class.
	 *
	 * @since 4.7.4
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  4.7.4
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
	 * @since  4.7.4
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'schedule_async_actions' ) );
		add_action( 'ssa/async/ics_cleanup', array( $this, 'cleanup_ics_files' ) );
		add_action( 'ssa/async/csv_cleanup', array( $this, 'cleanup_csv_files' ) );
	}

	/**
	 * Schedule all async actions using Action Scheduler.
	 *
	 * @since 4.7.4
	 *
	 * @uses as_has_scheduled_action
	 * @uses as_schedule_recurring_action
	 * @return void
	 */
	public function schedule_async_actions() {
		if ( ! class_exists( 'ActionScheduler' ) ) {
			return;
		}

		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}
		
		if ( ! function_exists( 'as_unschedule_action' ) ) {
			return;
		}

		// This is the old action; no longer in use since OCT, 2022
		// To be removed later from the code
		// For now check if it's scheduled then unschedule
		try {
			if ( true === as_has_scheduled_action( 'ssa/google_calendar/resync_appointments_with_missing_event_ids' ) ) {
				as_unschedule_action( 'ssa/google_calendar/resync_appointments_with_missing_event_ids', array(), '' );
			}
		} catch ( Exception $e ) {
			// Do nothing
		}

		try {
			if ( false === as_has_scheduled_action( 'ssa/async/ics_cleanup' ) ) {
				as_schedule_recurring_action( strtotime( 'now' ), DAY_IN_SECONDS, 'ssa/async/ics_cleanup' );
			}
		} catch ( Exception $e ) {
			return;
		}

		try {
			if ( false === as_has_scheduled_action( 'ssa/async/csv_cleanup' ) ) {
				as_schedule_recurring_action( strtotime( 'now' ), DAY_IN_SECONDS, 'ssa/async/csv_cleanup' );
			}
		} catch ( Exception $e ) {
			return;
		}
	}

	/**
	 * Logic to cleanup .ics files periodically.
	 *
	 * @since 4.7.4
	 *
	 * @return void
	 */
	public function cleanup_ics_files() {
		$path  = $this->plugin->filesystem->get_uploads_dir_path() . '/ics/*';
		$files = glob( $path );

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
	}

	/**
	 * Logic to cleanup .csv files periodically.
	 *
	 * @since 4.7.4
	 *
	 * @return void
	 */
	public function cleanup_csv_files() {
		$path  = $this->plugin->filesystem->get_uploads_dir_path() . '/csv/*';
		$files = glob( $path );

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
	}


	/**
	 * Given a list of appointments and a specific notification, verifies if that notification
	 * should be triggered for each appointment. For each valid notification, schedule the action
	 * with a 5 minutes interval.
	 *
	 * @since 5.2.0
	 *
	 * @param array $notification An array containing the notification payload.
	 * @param array $appointments A list of appointments payload to verify the need of resending notifications.
	 * @return void
	 */
	public function bulk_schedule_notifications( $notification, $appointments ) {
		$time_interval_in_seconds = 15;
		$notification_index = 0;

		foreach ( $appointments as $key => $appointment ) {
			// build payload to verify notification.
			$payload = array(
				'notification' => $notification,
				'appointment'  => $appointment,
				'action'       => 'appointment_booked',
			);

			// check if this notification should be sent.
			if ( ! $this->plugin->notifications->should_fire_notification( $notification, $payload ) ) {
				continue;
			}

			// adds 5 minutes to the current time interval, and bump the notification index.
			$interval = $notification_index * $time_interval_in_seconds;
			$notification_index++;
			$timestamp = time() + $interval;

			// schedule the notification.
			$this->schedule_notification( $timestamp, $notification, $appointment );
		}
	}

	/**
	 * Queue notifications to be re-sent once on a specific interval in the future.
	 *
	 * @since 5.2.0
	 *
	 * @param int   $timestamp when the notification hook should be triggered.
	 * @param array $notification the notification type object.
	 * @param array $appointment the appointment object.
	 * @return void
	 */
	public function schedule_notification( $timestamp, $notification, $appointment ) {
		as_schedule_single_action(
			$timestamp,
			'ssa/async/send_notifications',
			array(
				array( 'id' => $notification['id'] ),
				array( 'appointment' => array( 'id' => $appointment['id'] ) ),
			),
			'send_notifications'
		);
	}



	/**
	 * Abstraction method to handle asynchronous actions.
	 *
	 * @since 5.6.0
	 *
	 * @param string $action the action to be executed.
	 * @param int    $timestamp timestamp to schedule the action.
	 * @param array  $args the arguments to be passed to the action.
	 * @param string $group the group to which the action belongs.
	 * 
	 * @return void
	 */
	public function add_action( $action, $timestamp, $args = array(), $group = null ) {
		if ( ! class_exists( 'ActionScheduler' ) ) {
			return;
		}

		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		$hook = 'ssa/async/' . $action;

		try {
			as_schedule_single_action( $timestamp, $hook, $args, $group );
		} catch ( Exception $e ) {
			return;
		}
	}
}
