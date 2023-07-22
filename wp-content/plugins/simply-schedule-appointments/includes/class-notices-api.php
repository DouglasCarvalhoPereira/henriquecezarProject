<?php
/**
 * Simply Schedule Appointments Notices Api.
 *
 * @since   0.1.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Notices Api.
 *
 * @since 0.1.0
 */
class SSA_Notices_Api extends WP_REST_Controller {
	/**
	 * Parent plugin class
	 *
	 * @var   class
	 * @since 1.0.0
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 * @param  object $plugin Main plugin object.
	 * @return void
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function hooks() {
		$this->register_routes();
	}


	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$version = '1';
		$namespace = 'ssa/v' . $version;
		$base = 'notices';
		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => array(

				),
			),
		) );
		register_rest_route( $namespace, '/' . $base . '/(?P<id>[a-zA-Z0-9_-]+)', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            => array(
					'context'          => array(
						'default'      => 'view',
					),
				),
			),
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'            => array(
					'global' => array(
						'required' => true,
					),
				),
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'     => array(
					'global'    => array(
						'required'      => true,
					),
				),
			),
		) );
		register_rest_route( $namespace, '/' . $base . '/schema', array(
			'methods'         => WP_REST_Server::READABLE,
			'callback'        => array( $this, 'get_public_item_schema' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( $namespace, '/' . $base . '/pinned/(?P<id>[a-zA-Z0-9_-]+)', array(
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_item_pinned_notices' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => array(),
			),
		) );
		register_rest_route( $namespace, '/' . $base . '/pinned/delete/(?P<id>[a-zA-Z0-9_-]+)', array(
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'remove_item_pinned_notices' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => array(),
			),
		) );
		register_rest_route( $namespace, '/' . $base . '-errors', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_item_error_notices' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => array(

				),
			),
		) );
	}

	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$notice_to_display = $this->plugin->notices->get_the_one_notice_to_display();

		$pinned_notices = $this->plugin->notices->get_pinned_notices();

		return array(
			'response_code' => 200,
			'error' => '',
			'data' => array(
				'notice_to_display' => $notice_to_display,
				'pinned_notices' => $pinned_notices,
			),
		);
	}

	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item_error_notices( $request ) {

		$error_notices = $this->plugin->error_notices->get_error_notices();
		return array(
			'response_code' => 200,
			'error' => '',
			'data' => array(
				'error_notices' => $error_notices,
			),
		);
	}

	/**
	 * Get one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		$params = $request->get_params();
		$notice_name = sanitize_text_field( $params['id'] );

		// TODO set logic for this method when needed
		// This function is not being called or used at the moment

		return array(
			'response_code' => 200,
			'error' => '',
			'data' => array(
				'noticeName' => $notice_name,			
			),
		);
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_item( $request ) {
		$params = $request->get_params();
		$notice_name = sanitize_text_field( $params['id'] );

		$dismissed_notices = get_option( 'ssa_dismissed_notices', array() );
		if ( !in_array( $notice_name, $dismissed_notices ) ) {
			$dismissed_notices[] = $notice_name;
			update_option( 'ssa_dismissed_notices', $dismissed_notices );

			// Delete top notice transient since we have new changes
			$this->plugin->notices->delete_top_notice_cached_transient();
		}

		return array(
			'response_code' => 200,
			'error' => '',
			'data' => array(
				'noticeName' => $notice_name,
			),
		);
	}

	/**
	 * Update one item from the collection for pinned notices
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_item_pinned_notices( $request ) {
		$params = $request->get_params();
		$notice_name = sanitize_text_field( $params['id'] );

		$pinned_notices = get_option( 'ssa_pinned_notices', array() );

		if ( !in_array( $notice_name, $pinned_notices ) ) {
			$pinned_notices[] = $notice_name;
			update_option( 'ssa_pinned_notices', $pinned_notices );

			// Delete top notice transient since we have new changes
			$this->plugin->notices->delete_top_notice_cached_transient();
		}
		
		return array(
			'response_code' => 200,
			'error' => '',
			'data' => array(
				'pinned_notice' => $notice_name,
			),
		);
	}

	/**
	 * Remove one item from the collection for pinned notices
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function remove_item_pinned_notices( $request ) {
		$params = $request->get_params();
		$notice_name = sanitize_text_field( $params['id'] );

		$pinned_notices = get_option( 'ssa_pinned_notices', array() );

		if ( in_array( $notice_name, $pinned_notices ) ) {
			unset( $pinned_notices[ array_search( $notice_name, $pinned_notices ) ] );
			// Reseting the indexes
			$pinned_notices = array_values($pinned_notices);
			update_option( 'ssa_pinned_notices', $pinned_notices );

			// Delete top notice transient since we have new changes
			$this->plugin->notices->delete_top_notice_cached_transient();
		}
		
		return array(
			'response_code' => 200,
			'error' => '',
			'data' => array(
				'pinned_notice' => $notice_name,
			),
		);
	}

	/**
	 * Delete one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function delete_item( $request ) {
		$params = $request->get_params();
		$notice_name = sanitize_text_field( $params['id'] );

		$dismissed_notices = get_option( 'ssa_dismissed_notices', array() );
		if ( in_array( $notice_name, $dismissed_notices ) ) {
			$pos = array_search( $notice_name, $dismissed_notices );
			unset( $dismissed_notices[$pos] );
			update_option( 'ssa_dismissed_notices', $dismissed_notices );
		}

		return array(
			'response_code' => 200,
			'error' => '',
			'data' => array(
				'noticeName' => $notice_name,
			),
		);
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		return TD_API_Model::nonce_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		return TD_API_Model::nonce_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		if ( is_user_logged_in() ) {
			return true;
		}
	}

	/**
	 * Check if a given request has access to delete a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function delete_item_permissions_check( $request ) {
		if ( is_user_logged_in() ) {
			return true;
		}
	}

	/**
	 * Prepare the item for create or update operation
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_Error|object $prepared_item
	 */
	protected function prepare_item_for_database( $request ) {
		return array();
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @param mixed $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_item_for_response( $item, $request ) {
		return array();
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'page'                   => array(
				'description'        => 'Current page of the collection.',
				'type'               => 'integer',
				'default'            => 1,
				'sanitize_callback'  => 'absint',
			),
			'per_page'               => array(
				'description'        => 'Maximum number of items to be returned in result set.',
				'type'               => 'integer',
				'default'            => 10,
				'sanitize_callback'  => 'absint',
			),
			'search'                 => array(
				'description'        => 'Limit results to those matching a string.',
				'type'               => 'string',
				'sanitize_callback'  => 'sanitize_text_field',
			),
		);
	}
}
