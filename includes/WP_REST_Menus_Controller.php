<?php
/**
 * WordPress REST API Menus controller class.
 *
 * @package wpscholar\API\Menus
 */

namespace wpscholar\API\Menus;

/**
 * Class WP_REST_Menus_Controller
 *
 * @package wpscholar\API\Menus
 */
class WP_REST_Menus_Controller extends \WP_REST_Controller {

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
			'/menus',
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
					'args'                => array(
						'menu-name'  => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'menu-items' => array(
							'validate_callback' => function ( $menu_items ) {
								if ( ! is_array( $menu_items ) ) {
									return new \WP_Error( 'rest_invalid_argument', __( 'The \'menu-items\' argument must be an array' ), array( 'status' => 400 ) );
								}

								return true;
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/menus/(?P<id>\d+)',
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
	 * Creates one item.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {

		$menu_id = wp_create_nav_menu( $request->get_param( 'menu-name' ) );

		if ( is_wp_error( $menu_id ) ) {
			$menu_id->add_data( array( 'status' => 409 ) );

			return $menu_id;
		}

		// If 'menu-items' is set, create menu items at the same time.
		$menu_items = $request->get_param( 'menu-items' );
		if ( ! empty( $menu_items ) && is_array( $menu_items ) ) {
			global $wpdb;
			$wpdb->query( 'START TRANSACTION;' );
			foreach ( $menu_items as $menu_item ) {
				$menu_item['menu-item-status'] = 'publish';
				$menu_item_id                  = wp_update_nav_menu_item( $menu_id, 0, $menu_item );
				if ( is_wp_error( $menu_item_id ) ) {
					$menu_item_id->add_data( array( 'status' => 409 ) );
					$wpdb->query( 'ROLLBACK;' );

					return $menu_item_id;
				}
			}
			$wpdb->query( 'COMMIT;' );
		}

		$item = wp_get_nav_menu_object( $menu_id );

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
		$items = [];
		$menus = wp_get_nav_menus();
		foreach ( $menus as $menu ) {
			$items[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $menu, $request ) );
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

		return apply_filters( 'rest_nav_menu_collection_params', $query_params );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {

		$item = wp_get_nav_menu_object( $request->get_param( 'id' ) );

		if ( ! $item ) {
			return new \WP_Error(
				'rest_invalid_menu_id',
				__( 'Invalid menu ID' ),
				array( 'status' => 400 )
			);
		}

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

		$id = $request->get_param( 'id' );

		if ( ! is_nav_menu( $id ) ) {
			return new \WP_Error(
				'rest_invalid_menu_id',
				__( 'Invalid menu ID' ),
				array( 'status' => 400 )
			);
		}

		$menu_id = wp_update_nav_menu_object( $id, $request->get_json_params() );

		if ( is_wp_error( $menu_id ) ) {
			$menu_id->add_data( array( 'status' => 409 ) );

			return $menu_id;
		}

		// If 'menu-items' is set, create/update/delete menu items at the same time.
		$menu_items = $request->get_param( 'menu-items' );
		if ( ! empty( $menu_items ) && is_array( $menu_items ) ) {
			global $wpdb;
			$wpdb->query( 'START TRANSACTION;' );
			$existing_menu_items    = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
			$existing_menu_item_ids = wp_list_pluck( $existing_menu_items, 'ID', 'ID' );
			foreach ( $menu_items as $menu_item ) {
				$menu_item['menu-item-status'] = 'publish';
				$menu_item_id                  = wp_update_nav_menu_item(
					$menu_id,
					isset( $menu_item['menu-item-db-id'] ) ? absint( $menu_item['menu-item-db-id'] ) : 0,
					$menu_item
				);
				if ( is_wp_error( $menu_item_id ) ) {
					$menu_item_id->add_data( array( 'status' => 409 ) );
					$wpdb->query( 'ROLLBACK;' );

					return $menu_item_id;
				} else {
					unset( $existing_menu_item_ids[ $menu_item_id ] );
				}
			}
			if ( ! empty( $existing_menu_item_ids ) ) {
				foreach ( $existing_menu_item_ids as $menu_item_id ) {
					wp_delete_post( $menu_item_id, true );
				}
			}
			$wpdb->query( 'COMMIT;' );
		}

		$item = wp_get_nav_menu_object( $menu_id );

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
	 * @return mixed|\WP_Error|\WP_REST_Response
	 */
	public function delete_item( $request ) {
		$id   = $request->get_param( 'id' );
		$item = wp_get_nav_menu_object( $id );

		if ( ! $item ) {
			return new \WP_Error(
				'rest_invalid_menu_id',
				__( 'Invalid menu ID' ),
				array( 'status' => 400 )
			);
		}

		wp_delete_nav_menu( $id );

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
	 * @param mixed            $item Menu
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function prepare_item_for_response( $item, $request ) {
		$response = rest_ensure_response( $item );

		return apply_filters( 'rest_prepare_nav_menu', $response, $item, $request );
	}

}
