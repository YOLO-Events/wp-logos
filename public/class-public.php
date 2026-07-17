<?php
/**
 * Public Class
 *
 * @package WP_Logos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles public-facing functionality.
 *
 * Assets are primarily enqueued via the block render callback in Blocks::render_logo_showcase().
 * This class registers the handles so they are available for enqueuing.
 */
class WP_Logos_Public {

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register (but don't enqueue) public assets so they can be enqueued
	 * on-demand by the block render callback.
	 */
	public function register_assets(): void {
		wp_register_style(
			'wp-logos-public',
			WP_LOGOS_PLUGIN_URL . 'public/css/wp-logos-public.css',
			array(),
			WP_LOGOS_VERSION
		);

		wp_register_script(
			'wp-logos-public',
			WP_LOGOS_PLUGIN_URL . 'public/js/wp-logos-public.js',
			array(),
			WP_LOGOS_VERSION,
			true
		);
	}
}
