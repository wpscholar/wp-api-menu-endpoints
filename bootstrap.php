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

	add_filter(
		'register_post_type_args',
		function ( $args, $post_type ) {
			if ( 'nav_menu_item' === $post_type ) {
				$args['show_in_rest']    = true;
				$args['rest_base']       = 'menu-items';
				$args['rest_controller'] = 'wpscholar\API\Menus\WP_REST_Menu_Items_Controller';
			}

			return $args;
		},
		10,
		2
	);

	add_action(
		'rest_api_init',
		function () {

			global $wp_taxonomies;
			$wp_taxonomies['nav_menu']->show_in_rest          = true;
			$wp_taxonomies['nav_menu']->rest_base             = 'menus';
			$wp_taxonomies['nav_menu']->rest_controller_class = 'WP_REST_Menus_Controller';

			// Menu items
			$menu_items_controller = new WP_REST_Menu_Items_Controller( 'nav_menu_item' );
			$menu_items_controller->register_routes();

			$menus_controller = new WP_REST_Menus_Controller( 'nav_menu' );
			$menus_controller->register_routes();

			$menu_locations_controller = new WP_REST_Menu_Locations_Controller();
			$menu_locations_controller->register_routes();

			$menu_settings_controller = new WP_REST_Menu_Settings_Controller();
			$menu_settings_controller->register_routes();

		}
	);

}
