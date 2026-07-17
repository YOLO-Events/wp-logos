<?php
/**
 * Internationalisation
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles loading of the plugin text domain.
 */
class I18n {

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			'wp-logos',
			false,
			dirname( WP_LOGOS_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
