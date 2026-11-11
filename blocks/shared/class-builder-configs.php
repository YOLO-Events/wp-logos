<?php
/**
 * Loads per-display page-builder definitions.
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the editor-specific definitions for each logo display.
 */
class Builder_Configs {

	/**
	 * Load one editor's configuration from each display directory.
	 *
	 * @param string $editor Editor identifier.
	 * @return array
	 */
	public static function for_editor( string $editor ): array {
		if ( ! in_array( $editor, array( 'elementor', 'wpbakery' ), true ) ) {
			return array();
		}

		$configs = array();
		foreach ( array( 'logo-showcase', 'logo-categories', 'logo-full-cards' ) as $directory ) {
			$path = WP_LOGOS_PLUGIN_DIR . 'blocks/' . $directory . '/' . $editor . '.php';
			if ( is_file( $path ) ) {
				$config = include $path;
				if ( is_array( $config ) ) {
					$configs[] = $config;
				}
			}
		}

		return $configs;
	}
}
