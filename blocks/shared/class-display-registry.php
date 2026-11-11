<?php
/**
 * Logo display registry.
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps public display identifiers to their per-block base classes.
 */
class Display_Registry {

	/**
	 * Registered displays.
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		return array(
			'showcase'   => Blocks\Logo_Showcase::class,
			'categories' => Blocks\Logo_Categories::class,
			'full-cards' => Blocks\Logo_Full_Cards::class,
		);
	}

	/**
	 * Get a base class by display ID.
	 *
	 * @param string $display Display identifier.
	 * @return string|null
	 */
	public static function get( string $display ): ?string {
		$all = self::all();
		return $all[ $display ] ?? null;
	}
}
