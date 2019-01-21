<?php
/**
 * Bootstrap file for initializing REST endpoints.
 *
 * @package wpscholar\API\Menus
 */

use wpscholar\API\Menus\WP_REST_Menu_Items_Controller;
use wpscholar\API\Menus\WP_REST_Menu_Locations_Controller;
use wpscholar\API\Menus\WP_REST_Menu_Settings_Controller;
use wpscholar\API\Menus\WP_REST_Menus_Controller;

if ( function_exists( 'add_action' ) ) {

	add_action(
		'rest_api_init',
		function () {

			$menus_controller = new WP_REST_Menus_Controller();
			$menus_controller->register_routes();

			$menu_items_controller = new WP_REST_Menu_Items_Controller();
			$menu_items_controller->register_routes();

			$menu_locations_controller = new WP_REST_Menu_Locations_Controller();
			$menu_locations_controller->register_routes();

			$menu_settings_controller = new WP_REST_Menu_Settings_Controller();
			$menu_settings_controller->register_routes();

		}
	);


}
