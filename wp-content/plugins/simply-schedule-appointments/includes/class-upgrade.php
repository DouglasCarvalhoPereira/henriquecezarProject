<?php
/**
 * Simply Schedule Appointments Upgrade.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Upgrade.
 *
 * @since 0.0.3
 */
class SSA_Upgrade {
	protected $last_version_seen;
	protected $versions_requiring_upgrade = array(
		// '0.0.3', // create /appointments booking page maybe_create_booking_page()
		'1.2.3', // fix "Email address" -> "email"
		'1.5.1', // fix customer_information vs custom_customer_information capitalization
		'2.6.9_12', // flush permalinks
		'2.6.9_13', // Whitelist for Disable REST API
		'2.7.1', // Notifications
		'2.9.2', // SMS phone
		'3.1.0', // Appointment.date_timezone -> Appointment.customer_timezone
		'3.5.0', // Appointment.AppointmentType.instructions -> instructions
		'4.2.2', // Team Member Role
		'4.4.5', // Fix staff_capacity=100000 -> staff_capacity=1
		'5.4.4', // Run Google Calendar sync
		'5.4.6', // Cleanup Calendar events templates
		'6.1.5', // Populate the new appt type labels table
		'6.4.3', // Maybe fix appt type label id equal to zero
		'6.4.5', // Fix missing appointment types
		'6.4.9', // Handle upgrade to new class-error-notices logic
		'6.5.1', // Add cancelation_note to the admin and customers canceled notifications
	);

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
		add_action( 'init', array( $this, 'migrations' ), 20 );
		add_action( 'init', array( $this, 'check_version_change' ), 20 );
		add_action( 'ssa_upgrade_free_to_paid', array( $this, 'migrate_free_to_paid_customer_info' ), 30 );
		add_action( 'ssa_downgrade_paid_to_free', array( $this, 'migrate_paid_to_free_customer_info' ), 30 );
	}

	/**
	 * Helper function to check version changes - when the user upgrades or downgrades - and to run migration logic when necessary.
	 *
	 * @since 4.4.9
	 *
	 * @return void
	 */
	public function check_version_change() {
		$current_version = $this->plugin->get_current_version();
		$stored_version  = get_option( 'ssa_plugin_version' );
		$current_version_num = $this->plugin->get_current_edition();

		// Reset/Disable beta option
		if ( $current_version_num > 1 && ! empty( $stored_version ) ) {

			if ( $current_version !== $stored_version ) {

				$this->reset_beta_option();
			}
		}


		// If no stored version, might be the either a fresh install or an existing install with an old version of the plugin.
		// On this case, we still want to run the logic to make sure that all fields are properly updated.
		if ( ! $stored_version ) {
			// if no stored version, and current version is "Free", run the logic to update fields to the free version.
			if ( '1' === $current_version_num ) {
				do_action( 'ssa_downgrade_paid_to_free' );
			}

			// if no stored version, and current version is "Paid" run the logic to update fields to paid version.
			if ( '1' < $current_version_num ) {
				do_action( 'ssa_upgrade_free_to_paid' );
			}

			$this->plugin->store_current_version();
			return;
		}

		// Ok, we do have a version number. On this case, we need to verify if it's an upgrade or downgrade, and run the necessary conversion.
		$stored_version_num = mb_substr( $stored_version, 0, 1 );

		// if stored version was "Free" and the user upgraded to a paid version, run the logic to update fields again.
		if ( '1' === $stored_version_num && $current_version_num > $stored_version_num ) {
			do_action( 'ssa_upgrade_free_to_paid' );
		}

		// if stored version was "Paid" and the user downgraded to the free version, run the logic to update fields again.
		if ( '1' < $stored_version_num && '1' === $current_version_num ) {
			do_action( 'ssa_downgrade_paid_to_free' );
		}

		$this->plugin->store_current_version();
	}

	/**
	 * Helper logic to migrate customer info from free to paid edition.
	 *
	 * @since 4.4.9
	 *
	 * @return void
	 */
	public function migrate_free_to_paid_customer_info() {

		$field_type_conversion_map = array(
			'Name'    => 'single-text',
			'Email'   => 'single-text',
			'Phone'   => 'phone',
			'Address' => 'single-text',
			'City'    => 'single-text',
			'State'   => 'single-text',
			'Zip'     => 'single-text',
			'Notes'   => 'multi-text',
		);

		/* Migrate Appointment Types */
		$appointment_types = $this->plugin->appointment_type_model->query(
			array(
				'number' => -1,
			)
		);

		foreach ( $appointment_types as $appointment_type_key => $appointment_type ) {
			// if custom_customer_information is not empty, then we can't populate it again, since it might lose data.
			if ( ! empty( $appointment_type['custom_customer_information'] ) ) {
				continue;
			}

			if ( ! empty( $appointment_type['customer_information']['0']['field'] ) ) {
				$appointment_types[ $appointment_type_key ]['custom_customer_information'] = array();
				foreach ( $appointment_type['customer_information'] as $field_key => $field ) {
					$field = $appointment_type['customer_information'][ $field_key ];

					if ( ! $field['display'] ) {
						continue;
					}

					$field_array = $field;

					// include field type.
					$type = isset( $field_type_conversion_map[ $field['field'] ] ) ? $field_type_conversion_map[ $field['field'] ] : 'single-text';
					$field_array['type']   = $type;
					$field_array['values'] = array();
					$appointment_types[ $appointment_type_key ]['custom_customer_information'][] = $field_array;
				}
			}

			// update appointment type.
			$this->plugin->appointment_type_model->update( $appointment_types[ $appointment_type_key ]['id'], $appointment_types[ $appointment_type_key ] );
		}

		/* Migrate Appointments */
		$appointments = $this->plugin->appointment_model->query(
			array(
				'number' => -1,
			)
		);

		// clear appointment types cache.
		$this->plugin->appointment_type_model->invalidate_appointment_type_cache();
	}

	/**
	 * Helper logic to migrate customer info from paid to free edition.
	 *
	 * @since 4.4.9
	 *
	 * @return void
	 */
	public function migrate_paid_to_free_customer_info() {

		$field_conversion_map = array(
			'Name'    => array(
				'field'    => 'Name',
				'display'  => true,
				'required' => true,
				'icon'     => 'face',
			),
			'Email'   => array(
				'field'    => 'Email',
				'display'  => true,
				'required' => true,
				'icon'     => 'email',
			),
			'Phone'   => array(
				'field'    => 'Phone',
				'display'  => true,
				'required' => false,
				'icon'     => 'phone',
			),
			'Address' => array(
				'field'    => 'Address',
				'display'  => false,
				'required' => false,
				'icon'     => 'place',
			),
			'City'    => array(
				'field'    => 'City',
				'display'  => false,
				'required' => false,
				'icon'     => 'place',
			),
			'State'   => array(
				'field'    => 'State',
				'display'  => false,
				'required' => false,
				'icon'     => 'place',
			),
			'Zip'     => array(
				'field'    => 'Zip',
				'display'  => false,
				'required' => false,
				'icon'     => 'place',
			),
			'Notes'   => array(
				'field'    => 'Notes',
				'display'  => false,
				'required' => false,
				'icon'     => 'assignment',
			),
		);

		/* Migrate Appointment Types */
		$appointment_types = $this->plugin->appointment_type_model->query(
			array(
				'number' => -1,
			)
		);

		foreach ( $appointment_types as $appointment_type_key => $appointment_type ) {
			// if customer_information is not empty, then we can't populate it again, since it might lose data.
			if ( ! empty( $appointment_type['customer_information'] ) ) {
				continue;
			}

			$appointment_types[ $appointment_type_key ]['customer_information'] = array();
			if ( ! empty( $appointment_type['custom_customer_information']['0']['field'] ) ) {
				$schema = $appointment_type['custom_customer_information']['0'];
				foreach ( $field_conversion_map as $field_key => $field_value ) {
					// check if we have this field set before so we can verify if it's set to display / require.
					$existing_field_key = array_search( $field_value, array_column( $appointment_type['custom_customer_information'], 'field' ), true );
					if ( false !== $existing_field_key ) {
						$field = $appointment_type['custom_customer_information'][ $existing_field_key ];
					} else {
						$field = $field_conversion_map[ $field_key ];
					}

					// remove field type.
					if ( isset( $field['type'] ) ) {
						unset( $field['type'] );
					}

					$appointment_types[ $appointment_type_key ]['customer_information'][] = $field;
				}
			}

			// update appointment type.
			$this->plugin->appointment_type_model->update( $appointment_types[ $appointment_type_key ]['id'], $appointment_types[ $appointment_type_key ] );
		}

		// clear appointment types cache.
		$this->plugin->appointment_type_model->invalidate_appointment_type_cache();
	}

	public function get_last_version_seen() {
		$db_versions = get_option( 'ssa_versions', json_encode( array() ) );
		$db_versions = json_decode( $db_versions, true );
		$db_version_keys = array_keys( $db_versions );
		$last_changed_date = array_pop( $db_version_keys );
		if ( $last_changed_date === null ) {
			// First time we're seeing SSA installed
			$this->last_version_seen = '0.0.0';
		} else {
			$this->last_version_seen = $db_versions[$last_changed_date];
		}

		return $this->last_version_seen;
	}

	public function record_version( $version ) {
		$db_versions = get_option( 'ssa_versions', json_encode( array() ) );
		$db_versions = json_decode( $db_versions, true );

		$db_versions[gmdate('Y-m-d H:i:s')] = $version;
		$this->last_version_seen = $version;
		return update_option( 'ssa_versions', json_encode($db_versions) );
	}

	public function migrations() {
		$this->last_version_seen = $this->get_last_version_seen();
		foreach ($this->versions_requiring_upgrade as $version ) {
			if ( $this->last_version_seen >= $version ) {
				continue;
			}
			
			$method_name = 'migrate_to_version_'.str_replace('.', '_', $version);
			$this->$method_name( $this->last_version_seen );
		}
	}

	// public function migrate_to_version_0_0_3( $from_version ) {
	// 	$post_id = $this->plugin->wp_admin->maybe_create_booking_page();
	// 	if ( !empty( $post_id ) ) {
	// 		$this->record_version( '0.0.3' );
	// 	}
	// }

	public function migrate_to_version_1_2_3( $from_version ) {
		if ( $from_version === '0.0.0' ) {
			return; // we don't need to migrate fresh installs
		}

		$appointment_types = $this->plugin->appointment_type_model->query( array(
			'number' => -1,
		) );

		if ( empty( $appointment_types['0']['id'] ) ) {
			$this->record_version( '1.2.3' );
			return;
		}

		foreach ($appointment_types as $appointment_type_key => $appointment_type) {
			if ( empty( $appointment_type['custom_customer_information']['0']['field'] ) ) {
				continue;
			}

			foreach ($appointment_type['custom_customer_information'] as $field_key => $field ) {
				if ( $field['field'] != 'Email address' ) {
					continue;
				}

				$appointment_types[$appointment_type_key]['custom_customer_information'][$field_key]['field'] = 'Email';
			}

			$this->plugin->appointment_type_model->update( $appointment_types[$appointment_type_key]['id'], $appointment_types[$appointment_type_key] );
		}

		$this->record_version( '1.2.3' );
	}


	public function migrate_to_version_1_5_1( $from_version ) {
		if ( $from_version === '0.0.0' ) {
			return; // we don't need to migrate fresh installs
		}

		$field_name_conversion_map = array(
			'name' => 'Name',
			'email' => 'Email',
			'phone_number' => 'Phone',
			'address' => 'Address',
			'city' => 'City',
			'state' => 'State',
			'zip' => 'Zip',
			'notes' => 'Notes',
		);

		/* Migrate Appointment Types */
		$appointment_types = $this->plugin->appointment_type_model->query( array(
			'number' => -1,
		) );
		foreach ($appointment_types as $appointment_type_key => $appointment_type) {
			if ( !empty( $appointment_type['custom_customer_information']['0']['field'] ) ) {
				foreach ($appointment_type['custom_customer_information'] as $field_key => $field ) {
					if ( empty( $field_name_conversion_map[$field['field']] ) ) {
						continue;
					}

					$appointment_types[$appointment_type_key]['custom_customer_information'][$field_key]['field'] = $field_name_conversion_map[$field['field']];
				}
			}

			if ( !empty( $appointment_type['customer_information']['0']['field'] ) ) {
				foreach ($appointment_type['customer_information'] as $field_key => $field ) {
					if ( empty( $field_name_conversion_map[$field['field']] ) ) {
						continue;
					}

					$appointment_types[$appointment_type_key]['customer_information'][$field_key]['field'] = $field_name_conversion_map[$field['field']];
				}
			}


			$this->plugin->appointment_type_model->update( $appointment_types[$appointment_type_key]['id'], $appointment_types[$appointment_type_key] );
		}

		/* Migrate Appointments */
		$appointments = $this->plugin->appointment_model->query( array(
			'number' => -1,
		) );
		foreach ($appointments as $appointment_key => $appointment) {
			if ( !empty( $appointment['customer_information'] ) ) {
				foreach ($appointment['customer_information'] as $field_key => $value ) {
					if ( empty( $field_name_conversion_map[$field_key] ) ) {
						continue;
					}

					$appointments[$appointment_key]['customer_information'][$field_name_conversion_map[$field_key]] = $value;
					unset( $appointments[$appointment_key]['customer_information'][$field_key] );
				}
			}


			$this->plugin->appointment_model->update( $appointments[$appointment_key]['id'], $appointments[$appointment_key] );
		}

		$this->record_version( '1.5.1' );
	}
	
	public function migrate_to_version_2_6_9_12( $from_version ) {
		global $wp_rewrite;
		$wp_rewrite->init();
		flush_rewrite_rules();

		$this->record_version( '2.6.9_12' );
	}

	public function migrate_to_version_2_6_9_13( $from_version ) {
		$DRA_route_whitelist = get_option( 'DRA_route_whitelist', array() );
		$ssa_routes_to_whitelist = array(
			"/ssa/v1","/ssa/v1/settings",
			"/ssa/v1/settings/(?P&lt;id&gt;[a-zA-Z0-9_-]+)",
			"/ssa/v1/settings/schema",
			"/ssa/v1/notices",
			"/ssa/v1/notices/(?P&lt;id&gt;[a-zA-Z0-9_-]+)",
			"/ssa/v1/notices/schema",
			"/ssa/v1/license",
			"/ssa/v1/license/schema",
			"/ssa/v1/google_calendars",
			"/ssa/v1/google_calendars/disconnect",
			"/ssa/v1/google_calendars/authorize_url",
			"/ssa/v1/mailchimp",
			"/ssa/v1/mailchimp/disconnect",
			"/ssa/v1/mailchimp/authorize",
			"/ssa/v1/mailchimp/deauthorize",
			"/ssa/v1/mailchimp/lists",
			"/ssa/v1/mailchimp/subscribe",
			"/ssa/v1/support_status",
			"/ssa/v1/support_ticket",
			"/oembed/1.0",
			"/ssa/v1/appointments",
			"/ssa/v1/appointments/bulk",
			"/ssa/v1/appointments/(?P&lt;id&gt;[\\d]+)",
			"/ssa/v1/appointments/(?P&lt;id&gt;[\\d]+)/ics",
			"/ssa/v1/appointment_types",
			"/ssa/v1/appointment_types/bulk",
			"/ssa/v1/appointment_types/(?P&lt;id&gt;[\\d]+)",
			"/ssa/v1/appointment_types/(?P&lt;id&gt;[\\d]+)/availability",
			"/ssa/v1/availability",
			"/ssa/v1/availability/bulk",
			"/ssa/v1/availability/(?P&lt;id&gt;[\\d]+)",
			"/ssa/v1/async",
			"/ssa/v1/payments",
			"/ssa/v1/payments/bulk",
			"/ssa/v1/payments/(?P&lt;id&gt;[\\d]+)"
		);
		if ( empty( $DRA_route_whitelist ) ) {
			$DRA_route_whitelist = $ssa_routes_to_whitelist;
		} else {
			foreach ( $ssa_routes_to_whitelist as $key => $route ) {
				if ( ! in_array( $route, $DRA_route_whitelist ) ) {
					$DRA_route_whitelist[] = $route;
				}
			}
		}

		update_option( 'DRA_route_whitelist', $DRA_route_whitelist );

		$this->record_version( '2.6.9_13' );
	}

	public function migrate_to_version_2_7_1( $from_version ) {
		$notifications_settings = $this->plugin->notifications_settings->get();

		$should_enable_admin_notification_for_all_appointment_types = true;
		$should_enable_customer_notification_for_all_appointment_types = true;

		$appointment_type_ids_with_admin_notification = array();
		$appointment_type_ids_with_customer_notification = array();

		$appointment_types = $this->plugin->appointment_type_model->query();
		if ( ! empty( $appointment_types ) ) {
			foreach ( $appointment_types as $key => $appointment_type ) {
				if ( empty( $appointment_type['notifications'] ) ) {
					$appointment_type_ids_with_admin_notification[] = $appointment_type['id'];
					$appointment_type_ids_with_customer_notification[] = $appointment_type['id'];

					continue;
				}

				foreach ($appointment_type['notifications'] as $notification_key => $notification ) {
					if ( $notification['field'] === 'admin' ) {
						if ( empty( $notification['send'] ) ) {
							$should_enable_admin_notification_for_all_appointment_types = false;
						} else {
							$appointment_type_ids_with_admin_notification[] = $appointment_type['id'];
						}
					} elseif ( $notification['field'] === 'customer' ) {
						if ( empty( $notification['send'] ) ) {
							$should_enable_customer_notification_for_all_appointment_types = false;
						} else {
							$appointment_type_ids_with_customer_notification[] = $appointment_type['id'];
						}
					}
				}
			}
		}

		
		$id = time();
		$booked_admin_notification = array(
			'appointment_types' => ( $should_enable_admin_notification_for_all_appointment_types ) ? array() : $appointment_type_ids_with_admin_notification,
			'id' => $id,
			'schema' => '2019-04-02',
			'sent_to' => array(
				'{{admin_email}}',
			),
			'title' => 'Email (Admin)',
			'subject' => '{{ Appointment.customer_information.Name }} just booked an appointment',
			'message' => wpautop( nl2br( $this->plugin->templates->get_template( 'notifications/email/text/booked-staff.php' ) ) ),
			'trigger' => 'appointment_booked',
			'type' => 'email',
			'when' => 'after',
			'duration' => 0,
		);

		$id = time() + 1;
		$booked_customer_notification = array(
			'appointment_types' => ( $should_enable_customer_notification_for_all_appointment_types ) ? array() : $appointment_type_ids_with_customer_notification,
			'id' => $id,
			'schema' => '2019-04-02',
			'sent_to' => array(
				'{{customer_email}}',
			),
			'subject' => 'Your appointment details',
			'message' => wpautop( nl2br( $this->plugin->templates->get_template( 'notifications/email/text/booked-customer.php' ) ) ),
			'title' => 'Email (Customer)',
			'trigger' => 'appointment_booked',
			'type' => 'email',
			'when' => 'after',
			'duration' => 0,
		);

		$id = time() + 2;
		$canceled_admin_notification = array(
			'appointment_types' => ( $should_enable_admin_notification_for_all_appointment_types ) ? array() : $appointment_type_ids_with_admin_notification,
			'id' => $id,
			'schema' => '2019-04-02',
			'sent_to' => array(
				'{{admin_email}}',
			),
			'title' => 'Email (Admin)',
			'subject' => '{{ Appointment.customer_information.Name }} just canceled an appointment',
			'message' => wpautop( nl2br( $this->plugin->templates->get_template( 'notifications/email/text/canceled-staff.php' ) ) ),
			'trigger' => 'appointment_canceled',
			'type' => 'email',
			'when' => 'after',
			'duration' => 0,
		);

		$id = time() + 3;
		$canceled_customer_notification = array(
			'appointment_types' => ( $should_enable_customer_notification_for_all_appointment_types ) ? array() : $appointment_type_ids_with_customer_notification,
			'id' => $id,
			'schema' => '2019-04-02',
			'sent_to' => array(
				'{{customer_email}}',
			),
			'subject' => 'Your appointment has been canceled',
			'message' => wpautop( nl2br( $this->plugin->templates->get_template( 'notifications/email/text/canceled-customer.php' ) ) ),
			'title' => 'Email (Customer)',
			'trigger' => 'appointment_canceled',
			'type' => 'email',
			'when' => 'after',
			'duration' => 0,
		);

		$notifications_settings['notifications'] = array(
			$booked_admin_notification,
			$booked_customer_notification,
			$canceled_admin_notification,
			$canceled_customer_notification,
		);

		$this->plugin->notifications_settings->update( $notifications_settings );

		$this->record_version( '2.7.1' );
	}

	public function migrate_to_version_2_9_2( $from_version ) {
		$appointment_types = $this->plugin->appointment_type_model->query( array(
			'number' => -1,
		) );

		if ( empty( $appointment_types['0']['id'] ) ) {
			$this->record_version( '2.9.2' );
			return;
		}

		foreach ($appointment_types as $appointment_type_key => $appointment_type) {
			if ( empty( $appointment_type['custom_customer_information']['0']['field'] ) ) {
				continue;
			}

			foreach ($appointment_type['custom_customer_information'] as $field_key => $field ) {
				if ( false === stripos( $field['field'], 'phone' ) ) {
					if ( $field['type'] !== 'single-text' ) {
						continue;
					}

					if ( empty( $field['icon'] ) || ( $field['icon'] !== 'call' ) ) {
						continue;
					}
				}

				$appointment_types[$appointment_type_key]['custom_customer_information'][$field_key]['type'] = 'phone';
			}

			$this->plugin->appointment_type_model->update( $appointment_types[$appointment_type_key]['id'], $appointment_types[$appointment_type_key] );
		}

		$this->record_version( '2.9.2' );
	}

	public function migrate_to_version_3_1_0( $from_version ) {
		$notifications_settings = $this->plugin->notifications_settings->get();
		foreach ($notifications_settings['notifications'] as $key => $notification) {
			if ( empty( $notification['sent_to'] ) || ! is_array( $notification['sent_to'] ) ) {
				continue;
			}

			$is_customer_notification = false;
			foreach ( $notification['sent_to'] as $recipient ) {
				if ( false !== strpos( $recipient, '{{customer' ) ) {
					$is_customer_notification = true;
				}
			}
			
			if ( ! $is_customer_notification ) {
				continue;
			}

			$notifications_settings['notifications'][$key]['message'] = str_replace( 'Appointment.date_timezone', 'Appointment.customer_timezone', $notifications_settings['notifications'][$key]['message'] );
		}

		$this->plugin->notifications_settings->update( $notifications_settings );


		$this->record_version( '3.1.0' );
	}

	public function migrate_to_version_3_5_0( $from_version ) {
		$notifications_settings = $this->plugin->notifications_settings->get();
		foreach ($notifications_settings['notifications'] as $key => $notification) {

			$notifications_settings['notifications'][$key]['message'] = str_replace( 'Appointment.AppointmentType.instructions', 'instructions', $notifications_settings['notifications'][$key]['message'] );
		}

		$this->plugin->notifications_settings->update( $notifications_settings );


		$this->record_version( '3.5.0' );
	}

	public function migrate_to_version_4_2_2( $from_version ) {
		$this->plugin->capabilities->remove_roles();
		$this->plugin->capabilities->add_roles();

		$this->record_version( '4.2.2' );
	}

	public function migrate_to_version_4_4_5( $from_version ) {
		$appointment_types = $this->plugin->appointment_type_model->query( array( 
			'number' => -1,
		) );

		foreach ($appointment_types as $appointment_type) {
			if ( empty( $appointment_type['staff']['required'] ) ) {
				continue;
			}

			if ( $appointment_type['capacity_type'] == 'individual' && $appointment_type['staff_capacity'] == SSA_Constants::CAPACITY_MAX ) {
				$this->plugin->appointment_type_model->update( $appointment_type['id'], array(
					'staff_capacity' => 1,
				) );
			}
		}

		$this->record_version( '4.4.5' );
	}

	public function migrate_to_version_5_4_4( $from_version ) {
		// Get list of booked + upcoming appointments.
		$appointments = $this->plugin->appointment_model->query(
			array(
				'status'         => array('booked'),
				'start_date_min' => gmdate('Y-m-d H:i:s'),
				'number'         => -1,
			)
		);

		if (empty($appointments)) {
			$this->record_version('5.4.4');
			return;
		}

		$appointments = array_values(
			array_filter(
				$appointments,
				function ($appointment) {
					return empty($appointment['google_calendar_event_id']);
				}
			)
		);
		// if we have a list of appointments, we need to sync them with Google Calendar.
		$this->plugin->google_calendar->bulk_schedule_google_calendar_sync($appointments);

		$this->record_version( '5.4.4' );
	}

	/**
	 * Migrate to version 5.4.6
	 *
	 * @param string $from_version The version we are migrating from.
	 */
	public function migrate_to_version_5_4_6( $from_version ) {
		$settings = $this->plugin->calendar_events_settings->get();

		$settings_map = array(
			'event_type_customer',
			'event_type_individual_shared',
			'event_type_group_shared',
			'event_type_individual_admin',
			'event_type_group_admin',
		);

		$clear_map = array(
			'</p>'   => "</p>\r\n",
			'<br />' => "\r\n",
			'<br>'   => "\r\n",
			'<br/>'  => "\r\n",
		);

		foreach ( $settings_map as $event_type ) {
			if ( ! isset( $settings[ $event_type ] ) ) {
				continue;
			}

			foreach ( $settings[ $event_type ] as $key => $value ) {
				if ( ! isset( $settings[ $event_type ][ $key ] ) ) {
					continue;
				}

				// Remove all html tags and turn paragraphs or <br> into line breaks.
				// Required to avoid html issues with Google Calendar and other calendar apps.
				$value = str_replace( array_keys( $clear_map ), array_values( $clear_map ), $value );
				$value = wp_strip_all_tags( $value );

				$settings[ $event_type ][ $key ] = $value;
			}
		}

		$this->plugin->calendar_events_settings->update( $settings );

		$this->record_version( '5.4.6' );
	}

	/**
	 * Reset/Disable the beta option
	 * Beta optin one time only; Turn off after one beta is downloaded.
	 *
	 * @return void
	 */
	public function reset_beta_option() {

		$developer_settings = $this->plugin->developer_settings->get();

			if ( ! empty($developer_settings['beta_updates']) ) {
	
				$developer_settings['beta_updates'] = false;
				$this->plugin->developer_settings->update($developer_settings);
			}

	}


	/**
	 * Populate the new appt type labels with the existing registered labels if any
	 *
	 * @param string $from_version
	 * @return void
	 */
	public function migrate_to_version_6_1_5( $from_version ) {

		$this->migrate_appointment_type_labels();

		$this->record_version( '6.1.5' );
	}

	/**
	 * Helper function to migrate to the new appt type labels setup
	 * Needed in case to be called when importing; check class-support-status -> import_data()
	 *
	 * @return void
	 */
	public function migrate_appointment_type_labels(){

		// First let's make sure we have a fresh appt type labels tabel for the IDs to be reset
		$this->plugin->appointment_type_label_model->truncate();
		$this->plugin->appointment_type_label_model->create_table();

		// Query all appointment types that are defined
		$appointment_types = $this->plugin->appointment_type_model->query( array( 
			'number' => -1,
		) );

		if ( empty( $appointment_types ) ) {

			// This is either a new install or has no appt types defined -> insert one row in labels table
			$default_label['name'] = __('Default', 'simply-schedule-appointments');
			$default_label['color'] = 'light-green';

			$this->plugin->appointment_type_label_model->raw_insert( $default_label );

		} else {
			// Get all registered labels for this site
			$labels = array();
			foreach( $appointment_types as $appointment_type ) {

				if ( empty( $appointment_type['label'] ) ){
					continue;
				}
	
				if ( ! in_array( $appointment_type['label'], $labels)){

					$labels[] =  $appointment_type['label'];
				}
			}
	
			// If no labels have been detected insert the Default label into labels table
			if( empty( $labels ) ){
				$default_label['name'] = __('Default', 'simply-schedule-appointments');
				$default_label['color'] = 'light-green';
	
				$this->plugin->appointment_type_label_model->raw_insert( $default_label );
			}
			elseif( count( $labels ) === 1 ){
				// If only one label detected then add this label with its name set to Default
				$default_label['name'] = __('Default', 'simply-schedule-appointments');
				$default_label['color'] = $labels[0];
	
				$this->plugin->appointment_type_label_model->raw_insert( $default_label );
				
			} else {
				// Insert all labels into new labels table
				foreach( $labels as $label ) {
		
					$row['name'] = ucwords( str_replace( '-', ' ', $label ) );
					$row['color'] = $label;
		
					$this->plugin->appointment_type_label_model->raw_insert( $row );
				}
			}
		}
		if ( ! empty( $appointment_types ) ) {

			// Query all labels that were inserted to access their ids
			$appointment_type_labels = $this->plugin->appointment_type_label_model->query( array( 
				'number' => -1,
			) );
	
			// Set the new label ids as foreign keys in appt types table
			foreach( $appointment_types as $appointment_type ) {

				if ( empty( $appointment_type['label'] ) ) {
					continue;
				}
	
				foreach($appointment_type_labels as $label ) {
	
					if ( $appointment_type['label'] === $label['color'] ) {
	
						$this->plugin->appointment_type_model->update( $appointment_type['id'], array(
							'label_id' => $label['id'],
						));
					}
				}
			}
		}

	}

	/**
	 * Since an index ID of 0 in Microsoft SQL Server is allowed
	 * Fix Default label id not being found
	 *
	 * @param string $from_version
	 * @return void
	 */
	public function migrate_to_version_6_4_3( $from_version ) {

		$this->maybe_fix_appointment_type_label_id_equal_to_zero();

		$this->record_version( '6.4.3' );
	}

	/**
	 * Since an index ID of 0 in Microsoft SQL Server is allowed
	 * Fix Default label id not being found
	 *
	 * @param string $from_version
	 * @return void
	 */
	public function migrate_to_version_6_4_5( $from_version ) {

		$this->maybe_fix_deleted_appointment_types();
		$this->record_version( '6.4.5' );
	}

	public function maybe_fix_deleted_appointment_types() {
		static $has_already_run_once_during_this_php_execution = false;
		if ( $has_already_run_once_during_this_php_execution ) {
			return; // this prevents an infinite loop since this function queries, which calls prepare_for_response(), which in turn may call this function again
			// There is still a problem if we end up here, but this prevents a fatal error
		}
		$has_already_run_once_during_this_php_execution = true;

		$orphaned_appointments = $this->plugin->appointment_model->query( array( 
			'number' => -1, 
			'append_where_sql' => " AND `appointment_type_id` NOT IN( SELECT `id` FROM " . $this->plugin->appointment_type_model->get_table_name() . " )",
		) );
		$labels = $this->plugin->appointment_type_label_model->query( array( 
			'number' => 1, 
		) );

		if ( empty( $orphaned_appointments ) ) {
			return;
		}

		$map_missing_appointment_type_ids_to_replacement_ids = array();
		foreach( $orphaned_appointments as $orphaned_appointment ) {
			$missing_appointment_type_id = $orphaned_appointment['appointment_type_id'];
			if ( empty( $map_missing_appointment_type_ids_to_replacement_ids[$missing_appointment_type_id] ) ) {
				// Create a new appointment type ID to replace the missing one
				$replacement_id = $this->plugin->appointment_type_model->insert( array( 
					'title' => __( 'Previously Deleted Appointment Type #', 'simply-schedule-appointments' ) . $missing_appointment_type_id, 
					'label_id' => $labels[0]['id'], 
					'status' => 'delete', 
					'custom_customer_information' => array (
						array (
							'field' => 'Name',
							'display' => true,
							'required' => true,
							'type' => 'single-text',
							'icon' => 'face',
							'values' => '',
						),
						array (
							'field' => 'Email',
							'display' => true,
							'required' => true,
							'type' => 'single-text',
							'icon' => 'email',
							'values' => '',
						),
					), 
					'customer_information' => array (
						array (
							'field' => 'Name',
							'display' => true,
							'required' => true,
							'type' => 'single-text',
							'icon' => 'face',
							'values' => '',
						),
						array (
							'field' => 'Email',
							'display' => true,
							'required' => true,
							'type' => 'single-text',
							'icon' => 'email',
							'values' => '',
						),
					),
				) );
				$map_missing_appointment_type_ids_to_replacement_ids[$missing_appointment_type_id] = $replacement_id;
			}

			$this->plugin->appointment_model->db_update( $orphaned_appointment['id'], array(
				'appointment_type_id' => $map_missing_appointment_type_ids_to_replacement_ids[$missing_appointment_type_id],  
			) );
		}
	}

	public function maybe_fix_appointment_type_label_id_equal_to_zero() {

		// Query all labels that were inserted to access their ids
		$appointment_type_labels = $this->plugin->appointment_type_label_model->query( array(
			'number' => -1,
			'append_where_sql' => " AND `id` IN( 0 ) ", 
		) );
		
		foreach ( $appointment_type_labels as $label ) {
			$raw_id = $this->plugin->appointment_type_label_model->raw_insert( array(
				'name' => $label['name'],
				'color'	=> $label['color']
			));
			$this->plugin->appointment_type_label_model->db_delete( $label['id'], true );

			// Update appointment types to use new label id
			$appointment_types = $this->plugin->appointment_type_model->query( array( 
				'number' => -1, 
				'label_id' => $label['id'],
			));
			if( empty( $appointment_types ) ) {
				continue;
			}

			foreach( $appointment_types as $type ) {
				$this->plugin->appointment_type_model->update( $type['id'], array(
					'label_id' => $raw_id,
				));
			}
		}	


	}

	/**
	 * Clear ssa_error_notices array since the old format of the stored errors has changed from indexed array to associative array
	 *
	 * @param string $from_version
	 * @return void
	 */
	public function migrate_to_version_6_4_9( $from_version ) {
		$errors = get_option( 'ssa_error_notices', array() );

		if( ! empty( $errors ) ) {
			update_option( 'ssa_error_notices', array() );
		}
		$this->record_version( '6.4.9' );
	}

	/**
	 * Add {{ cancelation_note }} to the customer and admin canceled notifications
	 *
	 * @param string $from_version
	 * @return void
	 */
	public function migrate_to_version_6_5_1( $from_version ) {

		$notifications_settings = $this->plugin->notifications_settings->get();

		if ( empty( $notifications_settings['notifications'] ) ) {
			$this->record_version( '6.5.1' );
			return;
		}

		foreach( $notifications_settings['notifications'] as $key => $notification ) {
			
			if( $notification['trigger'] === 'appointment_canceled' && $notification['type'] === 'email' ) {

				$message = $notification['message'];

				if ( empty( $message ) ) {
					continue;
				}

				$pattern = '/has been canceled\s*\<\/p>/i';

				$is_matching = preg_match($pattern, $message);
				
				if ( $is_matching ) {
					$message = preg_replace_callback( $pattern, function( $matches ) {
						return $matches[0] . 
						'{% if cancelation_note %}' .
						'<p>' .
						'{% if canceled_by_name %} {{ canceled_by_name }} ' . __( 'left a note', 'simply-schedule-appointments' ) . ': {% else %}' . __( 'Note', 'simply-schedule-appointments' ) . ': {% endif %}' .
						'{{ cancelation_note }} </p>' .
						'{% endif %}';

					}, $message );

				} else {
					$message .= 
					'{% if cancelation_note %}' .
					'<p>' .
					'{% if canceled_by_name %} {{ canceled_by_name }} ' . __( 'left a note', 'simply-schedule-appointments' ) . ': {% else %}' . __( 'Note', 'simply-schedule-appointments' ) . ': {% endif %}' .
					'{{ cancelation_note }} </p>' .
					'{% endif %}';

				}

				$notifications_settings['notifications'][$key]['message'] = $message;
			}

		}
		
		$this->plugin->notifications_settings->update( $notifications_settings );

		$this->record_version( '6.5.1' );

	}

}
