<?php
/**
 * WordPress REST API Menu Settings controller class.
 *
 * @package wpscholar\API\Menus;
 */

namespace wpscholar\API\Menus;

/**
 * Class WP_REST_Menu_Settings_Controller
 *
 * @package wpscholar\API\Menus
 */
class WP_REST_Menu_Settings_Controller extends \WP_REST_Controller {

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
			'/menus/(?P<id>[\d]+)/settings',
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
					'args'                => array(
						'auto-add-pages' => array(
							'default'           => false,
							'sanitize_callback' => 'wp_validate_boolean',
						),
					),
				),
			)
		);

	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {

		$id   = $request->get_param( 'id' );
		$menu = wp_get_nav_menu_object( $id );

		if ( ! $menu ) {
			return new \WP_Error(
				'rest_invalid_menu_id',
				__( 'Invalid menu ID' ),
				array( 'status' => 400 )
			);
		}

		$item = array(
			'auto-add-pages' => false,
		);

		$nav_menu_options = (array) get_option( 'nav_menu_options' );

		if ( isset( $nav_menu_options['auto_add'] ) ) {
			if ( in_array( $id, $nav_menu_options['auto_add'], true ) ) {
				$item['auto-add-pages'] = true;
			}
		}

		return $this->prepare_item_for_response( (object) $item, $request );
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
		$id   = $request->get_param( 'id' );
		$menu = wp_get_nav_menu_object( $id );

		if ( ! $menu ) {
			return new \WP_Error(
				'rest_invalid_menu_id',
				__( 'Invalid menu ID' ),
				array( 'status' => 400 )
			);
		}

		$item = array(
			'auto-add-pages' => false,
		);

		$nav_menu_options = (array) get_option( 'nav_menu_options' );

		if ( isset( $nav_menu_options['auto_add'] ) ) {
			if ( in_array( $id, $nav_menu_options['auto_add'], true ) ) {
				$item['auto-add-pages'] = true;
			}
		} else {
			$nav_menu_options['auto_add'] = array();
		}

		// Update auto add pages setting
		$auto_add_pages         = $request->get_param( 'auto-add-pages' );
		$item['auto-add-pages'] = $auto_add_pages;
		if ( $auto_add_pages ) {
			$nav_menu_options['auto_add'][] = $id;
		} else {
			$key = array_search( $id, $nav_menu_options['auto_add'], true );
			if ( false !== $key ) {
				unset( $nav_menu_options['auto_add'][ $key ] );
			}
		}
		update_option( 'nav_menu_options', $nav_menu_options );

		return $this->prepare_item_for_response( (object) $item, $request );
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
	 * Prepares the item for the REST response.
	 *
	 * @param mixed            $item Menu settings
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function prepare_item_for_response( $item, $request ) {
		$response = rest_ensure_response( $item );

		return apply_filters( 'rest_prepare_nav_menu_settings', $response, $item, $request );
	}

}
