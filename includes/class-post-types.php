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
			'name'                  => _x( 'Logos', 'Post type general name', 'wp-logos' ),
			'singular_name'         => _x( 'Logo', 'Post type singular name', 'wp-logos' ),
			'menu_name'             => _x( 'Logos', 'Admin menu text', 'wp-logos' ),
			'name_admin_bar'        => _x( 'Logo', 'Add new on toolbar', 'wp-logos' ),
			'add_new'               => __( 'Add New', 'wp-logos' ),
			'add_new_item'          => __( 'Add New Logo', 'wp-logos' ),
			'new_item'              => __( 'New Logo', 'wp-logos' ),
			'edit_item'             => __( 'Edit Logo', 'wp-logos' ),
			'view_item'             => __( 'View Logo', 'wp-logos' ),
			'all_items'             => __( 'All Logos', 'wp-logos' ),
			'search_items'          => __( 'Search Logos', 'wp-logos' ),
			'parent_item_colon'     => __( 'Parent Logos:', 'wp-logos' ),
			'not_found'             => __( 'No logos found.', 'wp-logos' ),
			'not_found_in_trash'    => __( 'No logos found in Trash.', 'wp-logos' ),
			'featured_image'        => __( 'Logo Image', 'wp-logos' ),
			'set_featured_image'    => __( 'Set logo image', 'wp-logos' ),
			'remove_featured_image' => __( 'Remove logo image', 'wp-logos' ),
			'use_featured_image'    => __( 'Use as logo image', 'wp-logos' ),
			'archives'              => __( 'Logo archives', 'wp-logos' ),
			'insert_into_item'      => __( 'Insert into logo', 'wp-logos' ),
			'uploaded_to_this_item' => __( 'Uploaded to this logo', 'wp-logos' ),
			'items_list'            => __( 'Logos list', 'wp-logos' ),
			'items_list_navigation' => __( 'Logos list navigation', 'wp-logos' ),
			'filter_items_list'     => __( 'Filter logos list', 'wp-logos' ),
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
			'rest_base'          => 'wp-logos',
		);

		register_post_type( 'wp_logo', $args );
	}
}
