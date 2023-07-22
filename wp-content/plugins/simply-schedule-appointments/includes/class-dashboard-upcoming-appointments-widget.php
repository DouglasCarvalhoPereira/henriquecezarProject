<?php
/**
 * SSA_Dashboard_Upcoming_Appointments_Widget
 *
 * @since   5.8.3
 * @package Simply_Schedule_Appointments
 */
class SSA_Dashboard_Upcoming_Appointments_Widget {
	/**
	 * Parent plugin class.
	 *
	 * @since 5.8.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;
	/**
	 * The id of this widget.
	 */

		/**
		 * Constructor.
		 *
		 * @since  5.8.3
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
		 * @since  5.8.3
		 */
	public function hooks() {
		add_action( 'wp_dashboard_setup', array( $this, 'init' ) );
	}

	const WID = 'ssa-dashboard-upcoming-appointments-widget';

	/**
	 * Hook to wp_dashboard_setup to add the widget.
	 */
	public static function init() {
		$current_staff_id = ssa()->staff_model->get_staff_id_for_user_id( get_current_user_id() );
		if ( current_user_can( 'ssa_manage_others_appointments' ) || ( current_user_can( 'ssa_manage_appointments' )  && $current_staff_id !== null ) ) {
			// Register the widget...
			wp_add_dashboard_widget(
				self::WID, // A unique slug/ID.
				__( 'Your Upcoming Appointments', 'simply-schedule-appointments' ), // Visible name for the widget.
				array( 'SSA_Dashboard_Upcoming_Appointments_Widget', 'widget' ) // Callback for the main widget content.
			);
		}
	}

	/**
	 * Load the widget code
	 */
	public static function widget() {
		echo do_shortcode( "[ssa_admin_upcoming_appointments appointment_type_displayed=true customer_id='']" );
	}

}
