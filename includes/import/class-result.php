<?php
/**
 * Serializable batch result.
 *
 * @package WP_Logos
 */

namespace WP_Logos\Import;

/**
 * Errors and diagnostics retain source IDs for remediation.
 */
class Result {
	/**
	 * Created logos (would create on preview).
	 *
	 * @var int
	 */
	public int $created = 0;
	/**
	 * Updated logos (would update on preview).
	 *
	 * @var int
	 */
	public int $updated = 0;
	/**
	 * Skipped logos.
	 *
	 * @var int
	 */
	public int $skipped = 0;
	/**
	 * Source IDs and error messages.
	 *
	 * @var array
	 */
	public array $errors = array();
	/**
	 * Unresolved image references.
	 *
	 * @var array
	 */
	public array $missing_images = array();
	/**
	 * Slug conflicts and selected policy.
	 *
	 * @var array
	 */
	public array $slug_collisions = array();
	/**
	 * Source-to-target term IDs; negative IDs are preview placeholders.
	 *
	 * @var array
	 */
	public array $term_map = array();
	/**
	 * Legacy references annotated with target term IDs.
	 *
	 * @var array
	 */
	public array $legacy_references = array();
	/**
	 * Whether writes are disabled.
	 *
	 * @var bool
	 */
	public bool $dry_run = true;
	/**
	 * Resume offset.
	 *
	 * @var int
	 */
	public int $next_offset = 0;
	/**
	 * Total eligible source logos.
	 *
	 * @var int
	 */
	public int $total = 0;
	/**
	 * Whether all source logos have been visited.
	 *
	 * @var bool
	 */
	public bool $done = false;

	/** Return JSON-safe report fields. */
	public function to_array(): array {
		return get_object_vars( $this );
	}
}
