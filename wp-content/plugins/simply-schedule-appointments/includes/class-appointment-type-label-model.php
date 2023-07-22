<?php
/**
 * Simply Schedule Appointments Appointment Type Label Model.
 *
 * @since   6.0.2
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Appointment Type Label Model.
 *
 * @since   6.0.2
 */
class SSA_Appointment_Type_Label_Model extends SSA_Db_Model {
	protected $slug = 'appointment_type_label';
	protected $version = '1.1.2';

	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.2
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;
	/**
	 * Constructor.
	 *
	 * @since  0.0.2
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
	 * @since  0.0.2
	 */
	public function hooks() {
		add_filter( 'ssa/appointment_type_label/before_insert', array( $this, 'cleanup_input_name_color' ), 10, 1 );
		add_filter( 'ssa/appointment_type_label/before_update', array( $this, 'cleanup_input_name_color' ), 10, 3 );
		add_filter( 'ssa/appointment_type_label/before_delete', array( $this, 'prevent_delete_default_label' ), 10, 1 );
	}

	/**
	 * Cleanup label input fields: name & color before insert to db
	 * Prevent empty values, duplicate labels names
	 * Run strip tags, prevent invalid characters
	 * 
	 * @since 6.2.2
	 *
	 * @param array $data
	 * @return array
	 */
	public function cleanup_input_name_color( $data = array(), $data_before = array(), $label_id = null ) {

		$registered_colors = array('red', 'pink', 'purple', 'deep-purple', 'indigo', 'blue', 'light-blue', 'cyan', 'teal', 'green', 'light-green', 'lime', 'yellow', 'amber', 'orange', 'deep-orange', 'brown', 'grey', 'blue-grey');
		
		if ( empty( $data['color'] ) || ! in_array( $data['color'], $registered_colors ) ) {
			$data['color'] = 'light-green';
		}

		if ( empty( $data['name'] ) ) {
			$data['name'] = 'Default';
		}

		$invalid_characters = array( '"', ',' );

		$data['name'] = str_replace( $invalid_characters, "", $data['name'] );

		$data['name'] = wp_strip_all_tags( trim( $data['name'] ), true );

		// Check/Prevent duplicate names
		$labels = $this->plugin->appointment_type_label_model->query();
		foreach ($labels as $label) {
			if( strtolower( $label['name'] ) === strtolower( $data['name'] ) &&  (int)$label['id'] !== (int)$label_id ) {
				$data['error'] = sprintf( __( 'Invalid Label name: \'%s\'. Label name must be unique.', 'simply-schedule-appointments' ), $data['name'] );
			}
		}
		return $data;
	}

	/**
	 * Prevent delete default appointment type label
	 * 
	 * @since 6.2.2
	 *
	 * @param integer $id
	 * @return integer
	 */
	public function prevent_delete_default_label( $id ){

		return (int)$id !== 1 ? $id : false;
	}

	public function has_many() {
		return array(
			'AppointmentType' => array(
				'model' => $this->plugin->appointment_type_model,
				'foreign_key' => 'label_id',
			),
		);
	}

	protected $schema = array(
		'color' => array(
			'field' => 'color',
			'label' => 'Color',
			'default_value' => '',
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '20',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
    ),
		'name' => array(
			'field' => 'name',
			'label' => 'Name',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '120',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
      )
    );

	public $indexes = array(
		'name' => array( 'name' ),
	);
}
