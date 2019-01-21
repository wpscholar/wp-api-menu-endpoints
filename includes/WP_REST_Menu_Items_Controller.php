<?php
/**
 * WordPress REST API Menu Items controller class.
 *
 * @package wpscholar\API\Menus;
 */

namespace wpscholar\API\Menus;

/**
 * Class WP_REST_Menu_Items_Controller
 *
 * @package wpscholar\API\Menus
 */
class WP_REST_Menu_Items_Controller extends \WP_REST_Controller {

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = 'wp/v2';

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/menus/(?P<parent>[\d]+)/items',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => self::get_menu_item_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/menus/(?P<parent>[\d]+)/items/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => self::get_menu_item_args(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
			)
		);

	}

	/**
	 * Get menu item args
	 *
	 * @return array
	 */
	public static function get_menu_item_args() {
		return array(
			'menu-item-object-id'   => array(
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'menu-item-object'      => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'menu-item-parent-id'   => array(
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'menu-item-position'    => array(
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'menu-item-type'        => array(
				'default'           => 'custom',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'menu-item-title'       => array(
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'menu-item-url'         => array(
				'sanitize_callback' => 'esc_url_raw',
			),
			'menu-item-description' => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'menu-item-attr-title'  => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'menu-item-target'      => array(
				'sanitize_callback' => 'sanitize_key',
			),
			'menu-item-classes'     => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'menu-item-xfn'         => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'menu-item-status'      => array(
				'default'           => 'publish',
				'validate_callback' => function ( $value ) {
					return in_array( $value, get_post_stati(), true );
				},
			),
		);
	}

	/**
	 * Creates one item.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {

		$parent = $request->get_param( 'parent' );
		$menu   = wp_get_nav_menu_object( $parent );

		if ( ! $menu ) {
			return new \WP_Error(
				'rest_invalid_menu_id',
				__( 'Invalid menu ID' ),
				array( 'status' => 400 )
			);
		}

		$menu_item_id = wp_update_nav_menu_item( $parent, 0, $request->get_params() );

		if ( is_wp_error( $menu_item_id ) ) {
			$menu_item_id->add_data( array( 'status' => 409 ) );

			return $menu_item_id;
		}

		$post = get_post( $menu_item_id );
		$item = wp_setup_nav_menu_item( $post );

		return $this->prepare_item_for_response( $item, $request );
	}

	/**
	 * Checks if a given request has access to create items.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return bool|\WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new \WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to create a menu.' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$items  = array();
		$parent = $request->get_param( 'parent' );
		$menu   = wp_get_nav_menu_object( $parent );

		if ( ! $menu ) {
			return new \WP_Error(
				'rest_invalid_menu_id',
				__( 'Invalid menu ID' ),
				array( 'status' => 400 )
			);
		}

		$menu_items = wp_get_nav_menu_items( $parent, $request->get_query_params() );
		foreach ( $menu_items as $menu_item ) {
			$items[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $menu_item, $request ) );
		}

		return rest_ensure_response( $items );
	}

	/**
	 * Checks if a given request has access to get items.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return bool|\WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new \WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to view menus.' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params() {
		$query_params                       = parent::get_collection_params();
		$query_params['context']['default'] = 'view';

		return apply_filters( 'rest_nav_menu_item_collection_params', $query_params );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {

		$parent = $request->get_param( 'parent' );
		$menu   = wp_get_nav_menu_object( $parent );

		if ( ! $menu ) {
			return new \WP_Error(
				'rest_invalid_menu_id',
				__( 'Invalid menu ID' ),
				array( 'status' => 400 )
			);
		}

		$id            = $request->get_param( 'id' );
		$menu_item_ids = wp_list_pluck( wp_get_nav_menu_items( $parent ), 'ID', 'ID' );

		if ( ! array_key_exists( $id, $menu_item_ids ) || ! is_nav_menu_item( $id ) ) {
			return new \WP_Error(
				'rest_invalid_menu_item_id',
				__( 'Invalid menu item ID' ),
				array( 'status' => 400 )
			);
		}

		$item = wp_setup_nav_menu_item( get_post( $id ) );

		return $this->prepare_item_for_response( $item, $request );
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return bool|\WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new \WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to view this menu.' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_item( $request ) {
		$parent = $request->get_param( 'parent' );
		$menu   = wp_get_nav_menu_object( $parent );

		if ( ! $menu ) {
			return new \WP_Error(
				'rest_invalid_menu_id',
				__( 'Invalid menu ID' ),
				array( 'status' => 400 )
			);
		}

		$id = $request->get_param( 'id' );

		if ( ! is_nav_menu_item( $id ) ) {
			return new \WP_Error(
				'rest_invalid_menu_item_id',
				__( 'Invalid menu item ID' ),
				array( 'status' => 400 )
			);
		}

		$menu_item_id = wp_update_nav_menu_item( $parent, $id, $request->get_json_params() );

		if ( is_wp_error( $menu_item_id ) ) {
			$menu_item_id->add_data( array( 'status' => 409 ) );

			return $menu_item_id;
		}

		$item = wp_setup_nav_menu_item( get_post( $menu_item_id ) );

		return $this->prepare_item_for_response( $item, $request );
	}

	/**
	 * Checks if a given request has access to update a specific item.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return bool|\WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new \WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to update this menu.' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$parent = $request->get_param( 'parent' );
		$menu   = wp_get_nav_menu_object( $parent );

		if ( ! $menu ) {
			return new \WP_Error(
				'rest_invalid_menu_id',
				__( 'Invalid menu ID' ),
				array( 'status' => 400 )
			);
		}

		$id = $request->get_param( 'id' );

		if ( ! is_nav_menu_item( $id ) ) {
			return new \WP_Error(
				'rest_invalid_menu_item_id',
				__( 'Invalid menu item ID' ),
				array( 'status' => 400 )
			);
		}

		$item = wp_setup_nav_menu_item( get_post( $id ) );
		wp_delete_post( $id, true );

		$response = $this->prepare_item_for_response( $item, $request );
		$response->set_status( 410 );

		return $response;
	}

	/**
	 * Checks if a given request has access to delete a specific item.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return bool|\WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new \WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to delete this menu.' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @param mixed            $item Menu item
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function prepare_item_for_response( $item, $request ) {
		$response = rest_ensure_response( $item );

		return apply_filters( 'rest_prepare_nav_menu_item', $response, $item, $request );
	}

}
