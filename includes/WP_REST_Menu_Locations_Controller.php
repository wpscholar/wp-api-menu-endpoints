<?php
/**
 * WordPress REST API Menu Locations controller class.
 *
 * @package wpscholar\API\Menus;
 */

namespace wpscholar\API\Menus;

/**
 * Class WP_REST_Menu_Locations_Controller
 *
 * @package wpscholar\API\Menus
 */
class WP_REST_Menu_Locations_Controller extends \WP_REST_Controller {

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
			'/menus/locations',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/menus/locations/(?P<slug>[-_[:alnum:]]+)',
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
						'id' => array(
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

	}

	/**
	 * Get a collection of locations.
	 *
	 * @return array
	 */
	protected function get_locations() {
		$locations = [];
		$labels    = get_registered_nav_menus();
		$ids       = get_nav_menu_locations();
		$slugs     = array_keys( $labels );
		foreach ( $slugs as $slug ) {
			$locations[ $slug ] = [
				'id'    => isset( $ids[ $slug ] ) ? absint( $ids[ $slug ] ) : 0,
				'label' => $labels[ $slug ],
				'slug'  => $slug,
			];
		}

		return $locations;
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		return rest_ensure_response( $this->get_locations() );
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

		return apply_filters( 'rest_nav_menu_location_collection_params', $query_params );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param \WP_REST_Request $request REST request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {

		$slug      = $request->get_param( 'slug' );
		$locations = $this->get_locations();

		if ( ! isset( $locations[ $slug ] ) ) {
			return new \WP_Error(
				'rest_not_found',
				__( 'Nav menu location not found.' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response( $locations[ $slug ] );
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

		$slug      = $request->get_param( 'slug' );
		$locations = $this->get_locations();

		if ( ! isset( $locations[ $slug ] ) ) {
			return new \WP_Error(
				'rest_not_found',
				__( 'Nav menu location not found.' ),
				array( 'status' => 404 )
			);
		}

		$id = $request->get_param( 'id' );

		if ( $id && ! is_nav_menu( $id ) ) {
			return new \WP_Error(
				'rest_invalid_menu_id',
				__( 'Invalid menu ID' ),
				array( 'status' => 400 )
			);
		}

		// Update theme mod
		$theme_mod          = get_nav_menu_locations();
		$theme_mod[ $slug ] = $id;
		set_theme_mod( 'nav_menu_locations', $theme_mod );

		return rest_ensure_response(
			[
				'id'    => $id,
				'label' => $locations[ $slug ]['label'],
				'slug'  => $slug,
			]
		);
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

}
