<?php
/**
 * Source adapter contract.
 *
 * @package WP_Logos
 */

namespace WP_Logos\Import;

/**
 * Sources are read-only and must not require their plugin to be active.
 */
interface Source_Interface {
	/** Return the stable registry key. */
	public function id(): string;
	/** Return the translated display name. */
	public function label(): string;
	/** Detect source data without requiring its plugin. */
	public function detect(): bool;
	/** Return logo/category counts and warning strings. */
	public function summary(): array;
	/**
	 * Read source categories.
	 *
	 * @return iterable<Import_Category>
	 */
	public function get_categories(): iterable;
	/** Count eligible source logos. */
	public function count_logos(): int;
	/**
	 * Read a stable batch of logos.
	 *
	 * @param int $offset Source offset.
	 * @param int $limit Batch size.
	 * @return iterable<Import_Logo>
	 */
	public function get_logos( int $offset, int $limit ): iterable;
	/** Return legacy presentation references, or an empty array. */
	public function get_legacy_references(): array;
}
