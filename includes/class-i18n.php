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
		// WordPress 4.6+ autoloads plugin translations for this text domain.
	}
}
