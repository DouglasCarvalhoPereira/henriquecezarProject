<?php

/**
 * Simply Schedule Appointments Notifications Settings.
 *
 * @since   4.7.2
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Notifications Settings.
 *
 * @since 4.7.2
 */
class SSA_Calendar_Events_Settings extends SSA_Settings_Schema {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  0.0.3
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ){
		parent::__construct();
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.0.3
	 */
	public function hooks(){
	}

	protected $slug = 'calendar_events';

	public function get_schema(){
		if( !empty( $this->schema ) ){
			return $this->schema;
		}

		$this->schema = array(
			'version' => '2021-09-23 17:50',
			'fields' => array(
				'enabled' => array(
					'name' => 'enabled',
					'default_value' => false,
				),
				'event_type_customer' => array(
					'name' => 'event_type_customer',
					'default_value' => array(
						'title' => '{{Appointment.AppointmentType.title}} ({{Global.company_name}})',
						'location' => '{{location}}',
						'details' => wpautop( nl2br( $this->plugin->templates->get_template( 'calendar-events/customer.php' ) ) ),
					),
					'required_capability' => 'ssa_manage_site_settings',
					'before_save_function' => array( $this, 'cleanup_p_tags_and_br_tags' ),
				),
				'event_type_individual_shared' => array(
					'name' => 'event_type_individual_shared',
					'default_value' => array(
						'title' => '{{customer_name}} + {{Global.company_name}}: {{Appointment.AppointmentType.title}}',
						'location' => '{{location}}',
						'details' => wpautop( nl2br( $this->plugin->templates->get_template( 'calendar-events/individual-shared.php' ) ) ),
					),
					'required_capability' => 'ssa_manage_site_settings',
					'before_save_function' => array( $this, 'cleanup_p_tags_and_br_tags' ),
				),
				'event_type_group_shared' => array(
					'name' => 'event_type_group_shared',
					'default_value' => array(
						'title' => '{{Appointment.AppointmentType.title}} ({{Global.company_name}})',
						'location' => '{{location}}',
						'details' => wpautop( nl2br( $this->plugin->templates->get_template( 'calendar-events/group-shared.php' ) ) ),
					),
					'required_capability' => 'ssa_manage_site_settings',
					'before_save_function' => array( $this, 'cleanup_p_tags_and_br_tags' ),
				),
				'event_type_individual_admin' => array(
					'name' => 'event_type_individual_admin',
					'default_value' => array(
						'title' => '{{customer_name}} - {{Appointment.AppointmentType.title}}',
						'location' => '{{location}}',
						'details' => wpautop( nl2br( $this->plugin->templates->get_template( 'calendar-events/individual-admin.php' ) ) ),
					),
					'required_capability' => 'ssa_manage_site_settings',
					'before_save_function' => array( $this, 'cleanup_p_tags_and_br_tags' ),
				),
				'event_type_group_admin' => array(
					'name' => 'event_type_group_admin',
					'default_value' => array(
						'title' => '{{Appointment.AppointmentType.title}}',
						'location' => '{{location}}',
						'details' => wpautop( nl2br( $this->plugin->templates->get_template( 'calendar-events/group-admin.php' ) ) ),
					),
					'required_capability' => 'ssa_manage_site_settings',
					'before_save_function' => array( $this, 'cleanup_p_tags_and_br_tags' ),
				),
			),
		);

		return $this->schema;
	}

	public function get_calendar_event_types(){
		if ( ! $this->plugin->settings_installed->is_enabled( $this->slug ) ) {
			$settings = $this->get_field_defaults();
		} else {
			$settings = $this->get();
		}
		$types = array();
		$keys = array('event_type_customer','event_type_individual_shared','event_type_group_shared','event_type_individual_admin','event_type_group_admin');
		foreach($keys as $key) {
			if( !empty($settings[$key]) ) {
				$types[str_replace('event_type_', '', $key)] = $settings[$key];
			}
		}

		return $types;
	}

	public function cleanup_p_tags_and_br_tags( $event_type ){
		if( empty( $event_type ) ){
			return $event_type;
		}

		if( !empty( $event_type['title'] ) ){
			$event_type['title'] = $this->plugin->templates->cleanup_variables_in_string( $event_type['title'] );
		}
		if( !empty( $event_type['location'] ) ){
			$event_type['location'] = $this->plugin->templates->cleanup_variables_in_string( $event_type['location'] );
		}
		if( !empty( $event_type['details'] ) ){
			$event_type['details'] = $this->plugin->templates->cleanup_variables_in_string( $event_type['details'] );
		}

		return $event_type;
	}
}
