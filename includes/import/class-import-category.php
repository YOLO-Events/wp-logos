<?php
/**
 * Normalised source category.
 *
 * @package WP_Logos
 */

namespace WP_Logos\Import;

/**
 * Read-only by convention: adapters construct values, the engine consumes them.
 */
class Import_Category {
	/**
	 * Stable source identity.
	 *
	 * @var string
	 */
	public string $source_id;
	/**
	 * Term name.
	 *
	 * @var string
	 */
	public string $name;
	/**
	 * Term slug.
	 *
	 * @var string
	 */
	public string $slug;
	/**
	 * Term description.
	 *
	 * @var string
	 */
	public string $description;
	/**
	 * Parent source identity.
	 *
	 * @var string|null
	 */
	public ?string $parent_source_id;
	/**
	 * Unmapped source values.
	 *
	 * @var array
	 */
	public array $extra;

	/**
	 * Construct a normalised category.
	 *
	 * @param array $data Normalised category fields.
	 */
	public function __construct( array $data ) {
		$this->source_id        = (string) $data['source_id'];
		$this->name             = (string) ( $data['name'] ?? '' );
		$this->slug             = (string) ( $data['slug'] ?? '' );
		$this->description      = (string) ( $data['description'] ?? '' );
		$this->parent_source_id = empty( $data['parent_source_id'] ) ? null : (string) $data['parent_source_id'];
		$this->extra            = $data['extra'] ?? array();
	}
}
