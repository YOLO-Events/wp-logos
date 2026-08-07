<?php
/**
 * Custom Taxonomies
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the wp_logo_category taxonomy.
 */
class Taxonomies {

	/**
	 * Register taxonomies.
	 */
	public function register(): void {
		$labels = array(
			'name'                       => _x( 'Logo Categories', 'Taxonomy general name', 'yolo-logos' ),
			'singular_name'              => _x( 'Logo Category', 'Taxonomy singular name', 'yolo-logos' ),
			'search_items'               => __( 'Search Logo Categories', 'yolo-logos' ),
			'popular_items'              => __( 'Popular Logo Categories', 'yolo-logos' ),
			'all_items'                  => __( 'All Logo Categories', 'yolo-logos' ),
			'parent_item'                => __( 'Parent Logo Category', 'yolo-logos' ),
			'parent_item_colon'          => __( 'Parent Logo Category:', 'yolo-logos' ),
			'edit_item'                  => __( 'Edit Logo Category', 'yolo-logos' ),
			'update_item'                => __( 'Update Logo Category', 'yolo-logos' ),
			'add_new_item'               => __( 'Add New Logo Category', 'yolo-logos' ),
			'new_item_name'              => __( 'New Logo Category Name', 'yolo-logos' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'yolo-logos' ),
			'add_or_remove_items'        => __( 'Add or remove logo categories', 'yolo-logos' ),
			'choose_from_most_used'      => __( 'Choose from the most used logo categories', 'yolo-logos' ),
			'not_found'                  => __( 'No logo categories found.', 'yolo-logos' ),
			'menu_name'                  => __( 'Categories', 'yolo-logos' ),
			'back_to_items'              => __( '← Back to Logo Categories', 'yolo-logos' ),
		);

		$args = array(
			'hierarchical'          => true,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'show_in_menu'          => true,
			'query_var'             => false,
			'rewrite'               => false,
			'show_in_rest'          => true,
			'rest_base'             => 'wp-logo-categories',
			'capabilities'          => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			),
		);

		register_taxonomy( 'wp_logo_category', array( 'wp_logo' ), $args );
	}
}
