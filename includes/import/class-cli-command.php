<?php
/**
 * Import WP-CLI commands.
 *
 * @package WP_Logos
 */

namespace WP_Logos\Import;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI exception messages are plain text; no HTML output.
// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter -- WP-CLI callbacks keep positional and named arguments, including commands that do not use both.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adapter-neutral WP-CLI import interface.
 */
class CLI_Command {

	/**
	 * Register only when called by the WP-CLI bootstrap.
	 */
	public static function register(): void {
		$command = new self();
		\WP_CLI::add_command( 'yolo-logos import list', array( $command, 'list_' ) );
		\WP_CLI::add_command( 'yolo-logos import run', array( $command, 'run' ) );
		\WP_CLI::add_command( 'yolo-logos import map', array( $command, 'map' ) );
	}

	/**
	 * List detected sources, logo/category counts, and warnings.
	 *
	 * ## EXAMPLES
	 *
	 *     wp yolo-logos import list
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function list_( array $args, array $assoc_args ): void {
		try {
			$rows = array();
			foreach ( ( new Registry() )->detected() as $source ) {
				$summary = $source->summary();
				$rows[]  = array(
					'source'     => $source->id(),
					'label'      => $source->label(),
					'logos'      => $summary['logos'],
					'categories' => $summary['categories'],
					'warnings'   => implode( '; ', $summary['warnings'] ),
				);
			}
			if ( empty( $rows ) ) {
				\WP_CLI::log( __( 'No supported logo sources were detected.', 'yolo-logos' ) );
				return;
			}
			\WP_CLI\Utils\format_items( 'table', $rows, array( 'source', 'label', 'logos', 'categories', 'warnings' ) );
		} catch ( \Throwable $exception ) {
			\WP_CLI::error( $exception->getMessage() );
		}
	}

	/**
	 * Preview or execute an import. Dry run is the default.
	 *
	 * Links open in a new tab. Presets and content are not replaced.
	 * Update legacy blocks/shortcodes manually using the final report.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : Detected source ID from `wp yolo-logos import list`.
	 *
	 * [--execute]
	 * : Write changes. Without this flag the command is a dry run.
	 *
	 * [--dry-run]
	 * : Preview without writing. Cannot be combined with --execute.
	 *
	 * [--update-existing]
	 * : Update previously imported logos instead of skipping them.
	 *
	 * [--batch=<n>]
	 * : Logos per batch (1–500). Default: 50.
	 *
	 * [--offset=<n>]
	 * : Start at this nonnegative offset to resume. Default: 0.
	 *
	 * [--copy-categories]
	 * : Copy colliding categories instead of reusing them.
	 *
	 * ## EXAMPLES
	 *
	 *     wp yolo-logos import run example
	 *     wp yolo-logos import run example --execute --batch=100
	 *     wp yolo-logos import run example --execute --offset=100 --update-existing
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Named arguments.
	 * @throws \RuntimeException If a source batch makes no progress.
	 * @throws \InvalidArgumentException If conflicting execution flags are supplied.
	 */
	public function run( array $args, array $assoc_args ): void {
		try {
			if ( isset( $assoc_args['execute'], $assoc_args['dry-run'] ) ) {
				throw new \InvalidArgumentException( __( 'Use either --execute or --dry-run, not both.', 'yolo-logos' ) );
			}
			$source = $this->source( $args );
			$batch  = $this->integer_option( $assoc_args, 'batch', 50, 1, 500 );
			$offset = $this->integer_option( $assoc_args, 'offset', 0, 0, PHP_INT_MAX );
			$dry    = ! isset( $assoc_args['execute'] );
			$totals = array(
				'created' => 0,
				'updated' => 0,
				'skipped' => 0,
			);
			$issues = array(
				'missing_images'  => array(),
				'errors'          => array(),
				'slug_collisions' => array(),
			);
			$engine = new Engine();
			\WP_CLI::log( $dry ? __( 'Dry run: no changes will be written.', 'yolo-logos' ) : __( 'Executing import.', 'yolo-logos' ) );
			\WP_CLI::warning( __( 'Links open in a new tab. Presets and content are not replaced; update legacy references manually.', 'yolo-logos' ) );
			foreach ( $source->summary()['warnings'] as $warning ) {
				\WP_CLI::warning( $warning );
			}
			do {
				$result = $engine->run_batch(
					$source,
					array(
						'dry_run'   => $dry,
						'mode'      => isset( $assoc_args['update-existing'] ) ? 'update' : 'skip',
						'collision' => isset( $assoc_args['copy-categories'] ) ? 'copy' : 'reuse',
						'offset'    => $offset,
						'limit'     => $batch,
					)
				);
				foreach ( $totals as $key => $value ) {
					$totals[ $key ] += $result->$key;
				}
				foreach ( $issues as $key => $value ) {
					$issues[ $key ] = array_merge( $value, $result->$key );
				}
				\WP_CLI::log(
					sprintf(
						/* translators: 1: processed offset, 2: total logos. */
						__( 'Progress: %1$d / %2$d', 'yolo-logos' ),
						$result->next_offset,
						$result->total
					)
				);
				if ( ! $result->done && $result->next_offset <= $offset ) {
					throw new \RuntimeException( __( 'The importer made no progress.', 'yolo-logos' ) );
				}
				$offset = $result->next_offset;
			} while ( ! $result->done );

			\WP_CLI::log(
				sprintf(
					/* translators: 1: created count, 2: updated count, 3: skipped count. */
					__( 'Created: %1$d; Updated: %2$d; Skipped: %3$d', 'yolo-logos' ),
					$totals['created'],
					$totals['updated'],
					$totals['skipped']
				)
			);
			foreach ( $issues as $key => $records ) {
				$labels = array(
					'missing_images'  => __( 'Missing images', 'yolo-logos' ),
					'errors'          => __( 'Errors', 'yolo-logos' ),
					'slug_collisions' => __( 'Slug collisions', 'yolo-logos' ),
				);
				\WP_CLI::log( $labels[ $key ] . ': ' . count( $records ) );
				foreach ( $records as $record ) {
					\WP_CLI::log( $this->text( $record ) );
				}
			}
			$this->print_map( $result->term_map );
			$this->print_legacy( $result->legacy_references );
			if ( ! empty( $issues['errors'] ) ) {
				\WP_CLI::warning( __( 'Completed with errors; some logos may not have been imported.', 'yolo-logos' ) );
				\WP_CLI::halt( 1 );
				return;
			}
			\WP_CLI::success( $dry ? __( 'Preview complete. No changes were written.', 'yolo-logos' ) : __( 'Import complete.', 'yolo-logos' ) );
		} catch ( \Throwable $exception ) {
			\WP_CLI::error(
				$exception->getMessage() . ( isset( $offset ) ? ' ' . sprintf(
					/* translators: %d: resume offset. */
					__( 'Resume with --offset=%d using the same options.', 'yolo-logos' ),
					$offset
				) : '' )
			);
		}
	}

	/**
	 * Show the persisted source-category to target-term mapping.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : Registered source ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp yolo-logos import map example
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function map( array $args, array $assoc_args ): void {
		try {
			$source = $this->source( $args, false );
			$map    = get_option( 'wp_logos_import_map_' . $source->id(), array() );
			$this->print_map( is_array( $map ) ? $map : array() );
		} catch ( \Throwable $exception ) {
			\WP_CLI::error( $exception->getMessage() );
		}
	}

	/**
	 * Resolve a registered source, optionally requiring detection.
	 *
	 * @param array $args Command arguments.
	 * @param bool  $detected Require a detected source.
	 * @return Source_Interface Source adapter.
	 * @throws \InvalidArgumentException If the source is unknown or not detected.
	 */
	private function source( array $args, bool $detected = true ): Source_Interface {
		if ( 1 !== count( $args ) || ! is_string( $args[0] ) || '' === $args[0] ) {
			throw new \InvalidArgumentException( __( 'Provide exactly one source ID.', 'yolo-logos' ) );
		}
		$registry = new Registry();
		$source   = $registry->get( $args[0] );
		if ( $detected ) {
			foreach ( $registry->detected() as $candidate ) {
				if ( $candidate->id() === $source->id() ) {
					return $source;
				}
			}
			throw new \InvalidArgumentException( __( 'This source is not currently detected.', 'yolo-logos' ) );
		}
		return $source;
	}

	/**
	 * Validate an integer CLI option without lossy casts.
	 *
	 * @param array  $options Named options.
	 * @param string $key Option name.
	 * @param int    $fallback Default value.
	 * @param int    $min Minimum.
	 * @param int    $max Maximum.
	 * @return int Validated value.
	 * @throws \InvalidArgumentException If the option is not a valid integer.
	 */
	private function integer_option( array $options, string $key, int $fallback, int $min, int $max ): int {
		$value = $options[ $key ] ?? $fallback;
		if ( ! is_int( $value ) && ! is_string( $value ) ) {
			throw new \InvalidArgumentException( __( 'Expected a numeric option value.', 'yolo-logos' ) );
		}
		$value = filter_var(
			$value,
			FILTER_VALIDATE_INT,
			array(
				'options' => array(
					'min_range' => $min,
					'max_range' => $max,
				),
			)
		);
		if ( false === $value ) {
			throw new \InvalidArgumentException(
				sprintf(
					/* translators: 1: option name, 2: minimum, 3: maximum. */
					__( '--%1$s must be an integer between %2$d and %3$d.', 'yolo-logos' ),
					$key,
					$min,
					$max
				)
			);
		}
		return $value;
	}

	/**
	 * Convert a report record to printable text.
	 *
	 * @param mixed $record Report record.
	 * @return string Printable record.
	 */
	private function text( mixed $record ): string {
		return is_scalar( $record ) ? (string) $record : (string) wp_json_encode( $record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Describe target IDs without exposing preview placeholders as real IDs.
	 *
	 * @param mixed $target Target ID.
	 * @return string Target description.
	 */
	private function target( mixed $target ): string {
		if ( null === $target || 0 === (int) $target ) {
			return __( 'not mapped', 'yolo-logos' );
		}
		return (int) $target < 0 ? __( 'new on import', 'yolo-logos' ) : (string) (int) $target;
	}

	/**
	 * Output the category mapping report.
	 *
	 * @param array $map Source category to target term map.
	 */
	private function print_map( array $map ): void {
		\WP_CLI::log( __( 'Source category → target term ID (preview placeholders are not block IDs):', 'yolo-logos' ) );
		$rows = array();
		foreach ( $map as $source => $target ) {
			$rows[] = array(
				'source_category' => (string) $source,
				'target_term'     => $this->target( $target ),
			);
		}
		\WP_CLI\Utils\format_items( 'table', $rows, array( 'source_category', 'target_term' ) );
	}

	/**
	 * Output every legacy reference with its target category.
	 *
	 * @param array $references Legacy reference records.
	 */
	private function print_legacy( array $references ): void {
		\WP_CLI::log( __( 'Legacy references (source category → target term ID):', 'yolo-logos' ) );
		$rows = array();
		foreach ( $references as $reference ) {
			$rows[] = array(
				'type'            => $reference['type'] ?? '',
				'post_id'         => $reference['post_id'] ?? '',
				'title'           => $reference['title'] ?? '',
				'source_category' => $reference['category_source_id'] ?? '',
				'target_term'     => $this->target( $reference['target_term_id'] ?? null ),
			);
		}
		\WP_CLI\Utils\format_items( 'table', $rows, array( 'type', 'post_id', 'title', 'source_category', 'target_term' ) );
	}
}
