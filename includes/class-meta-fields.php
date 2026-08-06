<?php
/**
 * Meta Fields
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers post meta fields for the wp_logo post type.
 */
class Meta_Fields {

	/**
	 * List of meta field definitions.
	 */
	private const FIELDS = array(
		array(
			'key'         => '_logo_url',
			'type'        => 'string',
			'description' => 'The URL to link the logo to.',
			'sanitize'    => 'esc_url_raw',
		),
		array(
			'key'         => '_logo_main_id',
			'type'        => 'integer',
			'description' => 'Attachment ID of the main logo image.',
			'sanitize'    => 'absint',
		),
		array(
			'key'         => '_logo_light_id',
			'type'        => 'integer',
			'description' => 'Attachment ID of the light version logo image.',
			'sanitize'    => 'absint',
		),
		array(
			'key'         => '_logo_dark_id',
			'type'        => 'integer',
			'description' => 'Attachment ID of the dark version logo image.',
			'sanitize'    => 'absint',
		),
		array(
			'key'         => '_logo_alt',
			'type'        => 'string',
			'description' => 'Alt text for the logo (overrides image alt).',
			'sanitize'    => 'sanitize_text_field',
		),
		array(
			'key'         => '_logo_order',
			'type'        => 'integer',
			'description' => 'Custom sort order for the logo.',
			'sanitize'    => 'absint',
		),
	);

	/**
	 * Register all meta fields.
	 */
	public function register(): void {
		foreach ( self::FIELDS as $field ) {
			register_post_meta(
				'wp_logo',
				$field['key'],
				array(
					'type'              => $field['type'],
					'description'       => $field['description'],
					'single'            => true,
					'default'           => 'integer' === $field['type'] ? 0 : '',
					'show_in_rest'      => true,
					'sanitize_callback' => $field['sanitize'],
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}
