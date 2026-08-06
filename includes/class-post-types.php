<?php
/**
 * Custom Post Types
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the wp_logo custom post type.
 */
class Post_Types {

	/**
	 * Register post types.
	 */
	public function register(): void {
		$labels = array(
			'name'                  => _x( 'Logos', 'Post type general name', 'yolo-logos' ),
			'singular_name'         => _x( 'Logo', 'Post type singular name', 'yolo-logos' ),
			'menu_name'             => _x( 'Logos', 'Admin menu text', 'yolo-logos' ),
			'name_admin_bar'        => _x( 'Logo', 'Add new on toolbar', 'yolo-logos' ),
			'add_new'               => __( 'Add New', 'yolo-logos' ),
			'add_new_item'          => __( 'Add New Logo', 'yolo-logos' ),
			'new_item'              => __( 'New Logo', 'yolo-logos' ),
			'edit_item'             => __( 'Edit Logo', 'yolo-logos' ),
			'view_item'             => __( 'View Logo', 'yolo-logos' ),
			'all_items'             => __( 'All Logos', 'yolo-logos' ),
			'search_items'          => __( 'Search Logos', 'yolo-logos' ),
			'parent_item_colon'     => __( 'Parent Logos:', 'yolo-logos' ),
			'not_found'             => __( 'No logos found.', 'yolo-logos' ),
			'not_found_in_trash'    => __( 'No logos found in Trash.', 'yolo-logos' ),
			'featured_image'        => __( 'Logo Image', 'yolo-logos' ),
			'set_featured_image'    => __( 'Set logo image', 'yolo-logos' ),
			'remove_featured_image' => __( 'Remove logo image', 'yolo-logos' ),
			'use_featured_image'    => __( 'Use as logo image', 'yolo-logos' ),
			'archives'              => __( 'Logo archives', 'yolo-logos' ),
			'insert_into_item'      => __( 'Insert into logo', 'yolo-logos' ),
			'uploaded_to_this_item' => __( 'Uploaded to this logo', 'yolo-logos' ),
			'items_list'            => __( 'Logos list', 'yolo-logos' ),
			'items_list_navigation' => __( 'Logos list navigation', 'yolo-logos' ),
			'filter_items_list'     => __( 'Filter logos list', 'yolo-logos' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-images-alt',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields' ),
			'show_in_rest'       => true,
			'rest_base'          => 'yolo-logos',
		);

		register_post_type( 'wp_logo', $args );
	}
}
