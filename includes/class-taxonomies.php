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
			'name'                       => _x( 'Logo Categories', 'Taxonomy general name', 'wp-logos' ),
			'singular_name'              => _x( 'Logo Category', 'Taxonomy singular name', 'wp-logos' ),
			'search_items'               => __( 'Search Logo Categories', 'wp-logos' ),
			'popular_items'              => __( 'Popular Logo Categories', 'wp-logos' ),
			'all_items'                  => __( 'All Logo Categories', 'wp-logos' ),
			'parent_item'                => __( 'Parent Logo Category', 'wp-logos' ),
			'parent_item_colon'          => __( 'Parent Logo Category:', 'wp-logos' ),
			'edit_item'                  => __( 'Edit Logo Category', 'wp-logos' ),
			'update_item'                => __( 'Update Logo Category', 'wp-logos' ),
			'add_new_item'               => __( 'Add New Logo Category', 'wp-logos' ),
			'new_item_name'              => __( 'New Logo Category Name', 'wp-logos' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'wp-logos' ),
			'add_or_remove_items'        => __( 'Add or remove logo categories', 'wp-logos' ),
			'choose_from_most_used'      => __( 'Choose from the most used logo categories', 'wp-logos' ),
			'not_found'                  => __( 'No logo categories found.', 'wp-logos' ),
			'menu_name'                  => __( 'Categories', 'wp-logos' ),
			'back_to_items'              => __( '← Back to Logo Categories', 'wp-logos' ),
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
