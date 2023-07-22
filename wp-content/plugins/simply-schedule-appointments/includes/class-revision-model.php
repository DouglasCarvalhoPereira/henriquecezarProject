<?php
/**
 * Simply Schedule Appointment Revision Model.
 *
 * @since   6.1.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointment Revision Model.
 *
 * @since 6.1.0
 */
class SSA_Revision_Model extends SSA_Db_Model {
	protected $slug    = 'revision';
	protected $version = '3.7.5';
	/**
	 * Parent plugin class.
	 *
	 * @since 6.1.0
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  6.1.0
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {

		parent::__construct( $plugin );

		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  6.1.0
	 */
	public function hooks() {
		add_action( 'ssa/appointment/booked', array( $this, 'insert_revision_booked_appointment' ), 10, 3 );
		add_action( 'ssa/appointment/edited', array( $this, 'insert_revision_edited_appointment' ), 10, 3 );
		add_action( 'ssa/appointment/abandoned', array( $this, 'insert_revision_abandoned_appointment' ), 10, 3 );
		add_action( 'ssa/appointment/canceled', array( $this, 'insert_revision_canceled_appointment' ), 10, 3 );
		add_action( 'ssa/appointment/pending', array( $this, 'insert_revision_pending_appointment' ), 10, 3 );
		// scheduled cleanup
		add_action( 'init', array( $this, 'schedule_async_actions' ) );
		add_action( 'ssa/revisions/cleanup', array( $this, 'cleanup_revisions' ), 10, 0 );
	}

	/**
	 * Scheduling the revisions cleanup async action
	 *
	 * @return void
	 */
	public function schedule_async_actions() {
		// below functions wrap the action scheduler methods, make all the needed checks and log any failures
		if ( false === ssa_has_scheduled_action( 'ssa/revisions/cleanup' ) ) {
			ssa_schedule_recurring_action( strtotime( 'now' ), DAY_IN_SECONDS, 'ssa/revisions/cleanup' );
		}
	}

	/**
	 * revisions scheduled cleanup
	 *
	 * @return void
	 */
	public function cleanup_revisions() {
		$revisions = $this->query(
			array(
				'date_created_max' => date( 'Y-m-d H:i:s', strtotime( '-3 months' ) ),
			)
		);

		// get ids of revisions as an array
		$revisions_ids = wp_list_pluck( $revisions, 'id' );
		if ( ! empty( $revisions_ids ) ) {
			// delete all corresponding revision_meta rows
			$this->plugin->revision_meta_model->bulk_delete(
				array(
					'revision_id' => $revisions_ids,
				)
			);
			// delete revisions rows
			$this->bulk_delete( $revisions_ids );
		}
	}

	public function has_many() {
		return array(
			// TODO check correct name
			'Revision_Meta_Values' => array(
				'model'       => $this->plugin->revision_meta_model,
				'foreign_key' => 'revision_id',
			),
		);
	}

	protected $schema = array(
		'result'              => array(
			'field'            => 'result',
			'label'            => 'Result',
			'default_value'    => '',
			'format'           => '%s',
			'mysql_type'       => 'VARCHAR', // 'success', 'failure', or 'warning'
			'mysql_length'     => '8',
			'mysql_unsigned'   => false,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),
		// foreign key
		'appointment_id'      => array(
			'field'            => 'appointment_id',
			'label'            => 'Appointment ID',
			'default_value'    => 0,
			'format'           => '%d',
			'mysql_type'       => 'BIGINT',
			'mysql_length'     => 20,
			'mysql_unsigned'   => true,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),

		// foreign key
		'appointment_type_id' => array(
			'field'            => 'appointment_type_id',
			'label'            => 'Appointment Type ID',
			'default_value'    => 0,
			'format'           => '%d',
			'mysql_type'       => 'BIGINT',
			'mysql_length'     => 20,
			'mysql_unsigned'   => true,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),

		// foreign key
		'user_id'             => array(
			'field'            => 'user_id',
			'label'            => 'Customer ID',
			'default_value'    => 0,
			'format'           => '%d',
			'mysql_type'       => 'BIGINT',
			'mysql_length'     => 20,
			'mysql_unsigned'   => true,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),

		// foreign key
		'staff_id'            => array(
			'field'            => 'staff_id',
			'label'            => 'Staff ID',
			'default_value'    => 0,
			'format'           => '%d',
			'mysql_type'       => 'BIGINT',
			'mysql_length'     => 20,
			'mysql_unsigned'   => true,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),

		// foreign key
		'payment_id'          => array(
			'field'            => 'payment_id',
			'label'            => 'Payment ID',
			'default_value'    => 0,
			'format'           => '%d',
			'mysql_type'       => 'BIGINT',
			'mysql_length'     => 20,
			'mysql_unsigned'   => true,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),

		// foreign key
		'async_action_id'     => array(
			'field'            => 'async_action_id',
			'label'            => 'Async Action ID',
			'default_value'    => 0,
			'format'           => '%d',
			'mysql_type'       => 'BIGINT',
			'mysql_length'     => 20,
			'mysql_unsigned'   => true,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),

		// allows filtering/looking at what happened in a certain timeframe
		'date_created'        => array(
			'field'            => 'date_created',
			'label'            => 'Date Created',
			'default_value'    => false,
			'format'           => '%s',
			'mysql_type'       => 'DATETIME',
			'mysql_length'     => '',
			'mysql_unsigned'   => false,
			'mysql_allow_null' => true,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),

		// action ( edit, cancel, etc...)
		'action'              => array(
			'field'            => 'action',
			'label'            => 'Action',
			'default_value'    => '',
			'format'           => '%s',
			'mysql_type'       => 'VARCHAR',
			'mysql_length'     => '32',
			'mysql_unsigned'   => false,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),

		// action_title ( Appointment Canceled, Appointment Booked, etc...)
		'action_title'				=> array(
			'field'            => 'action_title',
			'label'            => 'Action Title',
			'default_value'    => '',
			'format'           => '%s',
			'mysql_type'       => 'TINYTEXT',
			'mysql_length'     => false,
			'mysql_unsigned'   => false,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),

		// event summary
		'action_summary'      => array(
			'field'            => 'action_summary',
			'label'            => 'Action Summary',
			'default_value'    => '',
			'format'           => '%s',
			'mysql_type'       => 'TEXT',
			'mysql_length'     => false,
			'mysql_unsigned'   => false,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),

		'summary_vars'        => array(
			'field'            => 'summary_vars',
			'label'            => 'Summary Variables',
			'default_value'    => '',
			'format'           => '%s',
			'mysql_type'       => 'TEXT',
			'mysql_length'     => false,
			'mysql_unsigned'   => false,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
			'encoder'          => 'json_serialize',
		),

		// context ( booking, settings, syncing etc...)
		'context'             => array(
			'field'            => 'context',
			'label'            => 'Context',
			'default_value'    => '',
			'format'           => '%s',
			'mysql_type'       => 'VARCHAR',
			'mysql_length'     => '32',
			'mysql_unsigned'   => false,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),

		// better filtering, also cross context filtering, like web meetings across several contexts (google, zoom,, etc)
		'sub_context'         => array(
			'field'            => 'sub_context',
			'label'            => 'Sub Context',
			'default_value'    => '',
			'format'           => '%s',
			'mysql_type'       => 'VARCHAR',
			'mysql_length'     => '32',
			'mysql_unsigned'   => false,
			'mysql_allow_null' => false,
			'mysql_extra'      => '',
			'cache_key'        => false,
		),
	);


	// below fields are indexed to use in filtering, like getting all events of a certain appointment_id
	public $indexes = array(
		'appointment_id'      => array( 'appointment_id' ),
		'appointment_type_id' => array( 'appointment_type_id' ),
		'user_id'             => array( 'user_id' ),
		'staff_id'            => array( 'staff_id' ),
		'async_action_id'     => array( 'async_action_id' ),
		'date_created'        => array( 'date_created' ),
		'action'              => array( 'action' ),
		'context'             => array( 'context' ),
		'sub_context'         => array( 'sub_context' ),
	);

	// TODO IMPORTANT: if each action only has one foreign id populated, 2 or more of the below where conditions will eliminate every result
	public function filter_where_conditions( $where, $args ) {
		if ( ! empty( $args['appointment_id'] ) ) {
			$where .= ' AND appointment_id="' . sanitize_text_field( $args['appointment_id'] ) . '"';
		}

		if ( ! empty( $args['appointment_type_id'] ) ) {
			$where .= ' AND appointment_type_id="' . sanitize_text_field( $args['appointment_type_id'] ) . '"';
		}

		if ( ! empty( $args['user_id'] ) ) {
			$where .= ' AND user_id="' . sanitize_text_field( $args['user_id'] ) . '"';
		}

		if ( ! empty( $args['staff_id'] ) ) {
			$where .= ' AND staff_id="' . sanitize_text_field( $args['staff_id'] ) . '"';
		}

		if ( ! empty( $args['async_action_id'] ) ) {
			$where .= ' AND async_action_id="' . sanitize_text_field( $args['async_action_id'] ) . '"';
		}

		if ( ! empty( $args['date_created'] ) ) {
			$where .= ' AND date_created="' . sanitize_text_field( $args['date_created'] ) . '"';
		}

		if ( ! empty( $args['action'] ) ) {
			$where .= ' AND action="' . sanitize_text_field( $args['action'] ) . '"';
		}

		if ( ! empty( $args['context'] ) ) {
			$where .= ' AND context="' . sanitize_text_field( $args['context'] ) . '"';
		}

		if ( ! empty( $args['sub_context'] ) ) {
			$where .= ' AND sub_context="' . sanitize_text_field( $args['sub_context'] ) . '"';
		}

		// Only query where the action_title is set, this will help querying after the revisions table had been updated
		$where .= " AND `action_title` IS NOT NULL AND `action_title` != ''";

		return $where;

	}

	// =================================================================================
	//
	// Section: Revision insertion function definitions
	//
	// Pattern: functions restructure the data available then pass it to insert_revision
	// ==================================================================================


	public function insert_revision_gcal_after_sync( $result, $appointment_id, $action, $action_summary, $calendar_id, $calendar_event_id, $event = null ) {
		$revision_meta = array(
			array(
				'meta_key'   => 'calendar_id',
				'meta_value' => $calendar_id,
			),
			array(
				'meta_key'   => 'calendar_event_id',
				'meta_value' => $calendar_event_id,
			),
		);

		( ! empty( $event ) ) && $revision_meta[] = array(
			'meta_key'   => 'event',
			'meta_value' => $event,
		);

		// below: hints for WP.org to pick up phrases for translation
		// __( 'Could not find existing event details for appointment ID {{ appointment_id }}', 'simply-schedule-appointments' );
		// __( 'Error while creating event for appointment ID {{ appointment_id }}', 'simply-schedule-appointments' );
		// __( 'Deleted group event for appointment ID {{ appointment_id }}', 'simply-schedule-appointments' );
		// __( 'Deleted individual event for appointment ID {{ appointment_id }}', 'simply-schedule-appointments' );
		// __( 'Exception occured while doing sync for appointment ID {{ appointment_id }}', 'simply-schedule-appointments' );
		// __( 'Inserted GCAL event for appointment ID {{ appointment_id }}', 'simply-schedule-appointments' );
		// __( 'Updated GCAL event for appointment ID {{ appointment_id }}', 'simply-schedule-appointments' );

		$this->insert_revision(
			array(
				'result'         => $result,
				'appointment_id' => $appointment_id,
				'action'         => $action,
				'action_title'	 => $this->get_action_title( $action, $result ),
				'action_summary' => $action_summary,
				'summary_vars'   => array(),
				'context'        => 'gcal',
				'sub_context'    => 'sync',
			),
			$revision_meta
		);
	}

	public function insert_revision_abandoned_appointment( $appointment_id, $data_after, $data_before = null ) {
		$params = array(
			'result'				 => 'success',
			'action'				 => 'abandoned',
			'appointment_id' => $appointment_id,
			'data_after' 		 => $data_after,
			'data_before'		 => $data_before
		);
		$this->insert_revision_appointment( $params );
	}

	public function insert_revision_edited_appointment( $appointment_id, $data_after, $data_before = null ) {
		$params = array(
			'result'				 => 'success',
			'action'				 => 'edited',
			'appointment_id' => $appointment_id,
			'data_after' 		 => $data_after,
			'data_before'		 => $data_before
		);
		$this->insert_revision_appointment( $params );
	}

	public function insert_revision_booked_appointment( $appointment_id, $data_after, $data_before = null ) {
		$params = array(
			'result'				 => 'success',
			'action'				 => 'booked',
			'appointment_id' => $appointment_id,
			'data_after' 		 => $data_after,
			'data_before'		 => $data_before
		);
		$this->insert_revision_appointment( $params );
	}

	public function insert_revision_canceled_appointment( $appointment_id, $data_after, $data_before = null ) {
		$params = array(
			'result'				 => 'success',
			'action'				 => 'canceled',
			'appointment_id' => $appointment_id,
			'data_after' 		 => $data_after,
			'data_before'		 => $data_before
		);
		$this->insert_revision_appointment( $params );
	}

	public function insert_revision_pending_appointment( $appointment_id, $data_after, $data_before = null ) {
		$params = array(
			'result'				 => 'success',
			'action'				 => $data_after['status'], // pending_form or pending_payment
			'appointment_id' => $appointment_id,
			'data_after' 		 => $data_after,
			'data_before'		 => null
		);
		$this->insert_revision_appointment( $params );
	}

	public function insert_revision_appointment( $params ) {

		// below: hints for WP.org to pick up phrases for translation
		// __( '{{ user }} changed the appointment status to {{ action }}', 'simply-schedule-appointments' );
		$revision = array(
			'result'         => $params['result'],
			'appointment_id' => $params['appointment_id'],
			'action'         => $params['action'],
			'action_title'	 => $this->get_action_title( $params['action'] ),
			'action_summary' => '{{ user }} changed the appointment status to {{ action }}',
			'summary_vars'   => array(
				'user'   => $this->get_user_name(),
				'action' => $params['action'],
			),
			'context'        => 'booking',
		);

		// in the revision_meta
		// only set meta_value_before if it has a value.
		// because if the field is set to null, the db will not insert the record.
		$revision_meta = array();

		// append appointment status changes
		$status_meta = array(
				'meta_key'          => 'status',
				'meta_value'        => $params['data_after']['status'],
		);
		if ( isset( $params['data_before']['status'] ) ) {
			$status_meta['meta_value_before'] = $params['data_before']['status'];
		}
		$revision_meta[] = $status_meta;

		// append appointment raw data changes
		$raw_data_meta = array(
				'meta_key'          => 'raw_data',
				'meta_value'        => $params['data_after'],
		);
		if ( isset( $params['data_before'] ) ) {
			$raw_data_meta['meta_value_before'] = $params['data_before'];
		}
		$revision_meta[] = $raw_data_meta;

		// insert revision
		$this->insert_revision( $revision, $revision_meta );
	}

	// signarture of function that renders the action summary
	// $this->plugin->templates->render_template_string('{{ user }} changed the appointment status to {{ action }}',['user'=>'name','action'=>'action'])

	// =========================================================================
	//
	// main re-usable function - use to insert revisions and revisions meta data
	//
	// =========================================================================
	public function insert_revision( $revision = array(), $revision_meta = array() ) {
		if ( ! isset( $revision['result'] ) ) {
			ssa_debug_log( 'must specify result to create revision', 10 );
			ssa_debug_log( print_r( $revision, true ), 10 ); //phpcs:ignore
			return;
		}
		if (
			! isset( $revision['appointment_id'] ) &&
			! isset( $revision['appointment_type_id'] ) &&
			! isset( $revision['staff_id'] ) &&
			! isset( $revision['payment_id'] ) &&
			! isset( $revision['async_action_id'] ) ) {
				ssa_debug_log( 'must reference at least one foreign key to create revision', 10 );
				ssa_debug_log( print_r( $revision, true ), 10 ); //phpcs:ignore
				return;
		}
		if ( ! isset( $revision['action'] ) ) {
			ssa_debug_log( "action field must be set to create revision\n", 10 );
			ssa_debug_log( print_r( $revision, true ), 10 ); //phpcs:ignore
			return;
		}
		if ( ! isset( $revision['action_summary'] ) ) {
			ssa_debug_log( "action_summary field must be set to create revision\n", 10 );
			ssa_debug_log( print_r( $revision, true ), 10 ); //phpcs:ignore
			return;
		}
		if ( ! isset( $revision['context'] ) ) {
			ssa_debug_log( "context field must be set to create revision\n", 10 );
			ssa_debug_log( print_r( $revision, true ), 10 ); //phpcs:ignore
			return;
		}

		// merge with default values
		$revision = wp_parse_args(
			$revision,
			array(
				'user_id'      => get_current_user_id(),
				'summary_vars' => array(),
			)
		);

		// insert revision and get its id
		$revision_id = $this->insert( $revision );

		// pass on an array of meta data to be batch inserted under this revision's id in the meta table
		if ( ! empty( $revision_meta ) && $revision_id ) {
			$this->plugin->revision_meta_model->insert_revision_meta( $revision_id, $revision_meta );
		}

		return $revision_id;
	}

	public function prepare_item_for_response( $item, $recursive = 0 ) {
		$item = parent::prepare_item_for_response( $item, $recursive );

		if ( $recursive >= 0 ) {
			$item['action_title'] = __( $item['action_title'], 'simply-schedule-appointments' );
			$item['action_summary_populated'] = $this->popuplate_action_summary_for_response( $item );
		}

		return $item;
	}

	public function get_action_title( $action, $result = 'success' ) {
		// Only get/edit action titles here
		$action_titles = array(
			'synced_successfully' => 'Appointment Synced',
			'failed_to_sync' => 'Appointment Failed to Sync',
			'booked' => 'Appointment Booked',
			'canceled' => 'Appointment Canceled',
			'rescheduled' => 'Appointment Rescheduled',
			'edited'	=> 'Appointment Edited',
			'abandoned' => 'Appointment Abandoned',
			'pending_payment' => 'Appointment\'s Payment Pending',
			'pending_form' => 'Appointment\'s Form Pending',
		);

		// Update the array below whenever needed
		if ( in_array( $action, array( 'sync_appointment_to_calendar' ) ) ) {
			return $result === 'success' ? $action_titles['synced_successfully'] : $action_titles['failed_to_sync'];
		}

		if ( isset( $action_titles[ $action ] ) ) {
			return $action_titles[$action];
		}

		// We shouldn't really end up here
		ssa_debug_log( "Action does not exist in get_action_title\n", 10 );
		return 'Unknown Action';
	}

	public function create_item_permissions_check( $request ) {
			// only ssa code should interact with this class
			return false;
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
			// only ssa code should interact with this class
			return false;
	}

	/**
	 * Check and return the logged in user name
	 * Otherwise return 'A logged out user'
	 *
	 * @param array $data
	 * @return string
	 */
	public function get_user_name() {

		$current_user = wp_get_current_user();

		// We don't have a logged in user
		if ( empty( $current_user->ID ) ) {
			return __( 'A logged out user', 'simply-schedule-appointments');
		}

		// First check if who's booking/editing is a staff member
		if( class_exists( 'SSA_Staff_Model' ) ) {
			$staff_id = $this->plugin->staff_model->get_staff_id_for_user_id( $current_user->ID );

			if( ! empty( $staff_id ) ) {
				$staff = new SSA_Staff_Object( $staff_id );
				return $staff->display_name;
			}
		}

		if( ! empty( $current_user->display_name ) ) {
			return $current_user->display_name;
		}

		if( ! empty( $current_user->user_login ) ) {
			return $current_user->user_login;
		}

		// Just in case if all have failed
		return __( 'A logged out user', 'simply-schedule-appointments');

	}

	public function popuplate_action_summary_for_response( $item ) {
		/* translators: If found, between double curly braces {{ Should not be translated }}. Actions: booked, canceled, pending_payment.. */
		$action_summary = esc_html__( $item['action_summary'], 'simply-schedule-appointments' );
		$summary_vars = $item['summary_vars'];

		// Regular expression to match placeholders wrapped inside double curly braces
		$pattern = '/{{\s*(.*?)\s*}}/';

		// Check if the action_summary contains placeholders
		if ( preg_match( $pattern, $action_summary ) ) {
				// Replace the placeholders with actual values
				$action_summary = preg_replace_callback($pattern, function($matches) use ($summary_vars) {
						$placeholder = $matches[1];
						// Check if the placeholder corresponds to a valid variable name
						if ( isset( $summary_vars[ $placeholder ] ) ) {
								if ( $placeholder === 'action' ) {
									// Only allow translations for actions
									return __( $summary_vars[ $placeholder ], 'simply-schedule-appointments' );
								}
								return $summary_vars[ $placeholder ];
						} else {
								// Placeholder does not correspond to a valid variable name
								return $matches[0]; // return the original placeholder
						}
				}, $action_summary );
		}
		return $action_summary;

	}

}
