<?php
/**
 * Source-neutral, resumable import engine.
 *
 * @package WP_Logos
 */

namespace WP_Logos\Import;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Engine errors are report data; interfaces escape for their output context.

/**
 * Each call maps categories, then processes one bounded batch of logos.
 */
class Engine {
	/**
	 * Preview by default. Interfaces must explicitly opt into writes.
	 *
	 * @param Source_Interface $source  Read-only adapter.
	 * @param array            $options dry_run, mode, collision, offset and limit.
	 * @throws \InvalidArgumentException If options or source identifiers are invalid.
	 * @throws \RuntimeException If initialisation, locking or source reads fail.
	 */
	public function run_batch( Source_Interface $source, array $options = array() ): Result {
		$options = array_merge(
			array(
				'dry_run'   => true,
				'mode'      => 'skip',
				'collision' => 'reuse',
				'offset'    => 0,
				'limit'     => 50,
			),
			$options
		);
		if ( ! in_array( $options['mode'], array( 'skip', 'update' ), true ) || ! in_array( $options['collision'], array( 'reuse', 'copy' ), true ) || ! is_bool( $options['dry_run'] ) || ! is_int( $options['offset'] ) || $options['offset'] < 0 || ! is_int( $options['limit'] ) || $options['limit'] < 1 ) {
			throw new \InvalidArgumentException( __( 'Invalid import options.', 'yolo-logos' ) );
		}
		if ( '' === $source->id() || sanitize_key( $source->id() ) !== $source->id() ) {
			throw new \InvalidArgumentException( __( 'Invalid import source identifier.', 'yolo-logos' ) );
		}
		if ( ! post_type_exists( 'wp_logo' ) || ! taxonomy_exists( 'wp_logo_category' ) ) {
			throw new \RuntimeException( __( 'YOLO Logos must finish initialising before importing.', 'yolo-logos' ) );
		}
		$result          = new Result();
		$result->dry_run = $options['dry_run'];
		$result->total   = $source->count_logos();
		$mapper          = new Target_Mapper();
		$lock            = 'wp_logos_import_lock_' . $source->id();
		if ( ! $result->dry_run && ! add_option( $lock, time(), '', false ) ) {
			throw new \RuntimeException( __( 'Another import for this source is running. If it was interrupted, remove its wp_logos_import_lock option before retrying.', 'yolo-logos' ) );
		}
		$was_deferred = wp_defer_term_counting();
		if ( ! $result->dry_run ) {
			wp_defer_term_counting( true );
		}
		try {
			do_action( 'wp_logos_import_before', $source->id(), $options );
			$result->term_map = $this->map_categories( $source, $options, $result, $mapper );
			$processed        = 0;
			foreach ( $source->get_logos( $options['offset'], min( 500, $options['limit'] ) ) as $logo ) {
				++$processed;
				try {
					$this->import_logo( $source, $logo, $options, $result, $mapper );
				} catch ( \RuntimeException $exception ) {
					$result->errors[] = array(
						'source_id' => $logo->source_id,
						'message'   => $exception->getMessage(),
					);
				}
			}
			$result->next_offset = $options['offset'] + $processed;
			$result->done        = $result->next_offset >= $result->total;
			if ( ! $result->done && 0 === $processed ) {
				throw new \RuntimeException( __( 'The source returned no logos before the expected end of the import.', 'yolo-logos' ) );
			}
			foreach ( $source->get_legacy_references() as $reference ) {
				$old                         = (string) ( $reference['category_source_id'] ?? '' );
				$reference['target_term_id'] = '0' === $old ? 0 : ( $result->term_map[ $old ] ?? null );
				$result->legacy_references[] = $reference;
			}
			do_action( 'wp_logos_import_after', $result, $source->id(), $options );
			return $result;
		} finally {
			if ( ! $result->dry_run ) {
				wp_defer_term_counting( $was_deferred );
				delete_option( $lock );
			}
		}
	}

	/**
	 * Validate the entire hierarchy before writing any term.
	 *
	 * @param Source_Interface $source Adapter.
	 * @return Import_Category[] Parents before children.
	 * @throws \UnexpectedValueException If the source hierarchy is invalid.
	 */
	private function ordered_categories( Source_Interface $source ): array {
		$categories = array();
		foreach ( $source->get_categories() as $category ) {
			if ( '' === $category->source_id || isset( $categories[ $category->source_id ] ) ) {
				throw new \UnexpectedValueException( __( 'Source category IDs must be unique and nonempty.', 'yolo-logos' ) );
			}
			$categories[ $category->source_id ] = $category;
		}
		$ordered = array();
		$states  = array();
		$visit   = function ( string $id ) use ( &$visit, &$states, &$ordered, $categories ): void {
			if ( 2 === ( $states[ $id ] ?? 0 ) ) {
				return;
			}
			if ( ! isset( $categories[ $id ] ) || 1 === ( $states[ $id ] ?? 0 ) ) {
				throw new \UnexpectedValueException( __( 'The source category hierarchy contains a cycle or a missing parent.', 'yolo-logos' ) );
			}
			$states[ $id ] = 1;
			$category      = $categories[ $id ];
			if ( null !== $category->parent_source_id ) {
				$visit( $category->parent_source_id );
			}
			$states[ $id ] = 2;
			$ordered[]     = $category;
		};
		foreach ( array_keys( $categories ) as $id ) {
			$visit( (string) $id );
		}
		return $ordered;
	}

	/**
	 * Map categories in parent-first order and persist each real assignment.
	 *
	 * @param Source_Interface $source Adapter.
	 * @param array            $options Batch options.
	 * @param Result           $result Diagnostics.
	 * @param Target_Mapper    $mapper Writer.
	 * @throws \RuntimeException If the map, provenance or term write fails.
	 */
	private function map_categories( Source_Interface $source, array $options, Result $result, Target_Mapper $mapper ): array {
		$option = 'wp_logos_import_map_' . $source->id();
		$map    = get_option( $option, array() );
		if ( ! is_array( $map ) ) {
			throw new \RuntimeException( __( 'The persisted import category map is invalid.', 'yolo-logos' ) );
		}
		foreach ( $map as $old => $target ) {
			if ( ! is_numeric( $target ) || (int) $target < 1 || ! term_exists( (int) $target, 'wp_logo_category' ) ) {
				unset( $map[ $old ] );
			}
		}
		$virtual = -1;
		$slugs   = array();
		foreach ( $this->ordered_categories( $source ) as $category ) {
			if ( isset( $map[ $category->source_id ] ) ) {
				continue;
			}
			$parent = null === $category->parent_source_id ? 0 : $map[ $category->parent_source_id ];
			$slug   = sanitize_title( $category->slug ? $category->slug : $category->name );
			$slug   = $slug ? $slug : sanitize_title( $source->id() . '-' . $category->source_id );
			$args   = $mapper->term_args( $category, $source->id(), $parent, $slug );
			$slug   = sanitize_title( $args['slug'] ?? $slug );
			$term   = get_term_by( 'slug', $slug, 'wp_logo_category' );
			if ( isset( $slugs[ $slug ] ) || $term ) {
				$result->slug_collisions[] = array(
					'source_id' => $category->source_id,
					'slug'      => $slug,
					'action'    => $options['collision'],
				);
				if ( 'reuse' === $options['collision'] ) {
					$target = $term ? (int) $term->term_id : $slugs[ $slug ];
				} else {
					$base   = $slug . '-' . $source->id();
					$suffix = 1;
					$slug   = $base;
					while ( isset( $slugs[ $slug ] ) || get_term_by( 'slug', $slug, 'wp_logo_category' ) ) {
						$slug = $base . '-' . ( ++$suffix );
					}
					$target = 0;
				}
			} else {
				$target = 0;
			}
			if ( ! $target ) {
				if ( $result->dry_run ) {
					$target = $virtual--;
				} else {
					$args['slug'] = $slug;
					$target       = $mapper->write_category( $category, $source->id(), $args );
				}
			}
			// A reused term keeps its existing hierarchy and provenance.
			$map[ $category->source_id ] = $target;
			$slugs[ $slug ]              = $target;
			if ( ! $result->dry_run ) {
				$saved = update_option( $option, $map, false );
				if ( ! $saved && get_option( $option ) !== $map ) {
					throw new \RuntimeException( __( 'Could not persist the import category map.', 'yolo-logos' ) );
				}
			}
		}
		return $map;
	}

	/**
	 * Import one logo without modifying source records or media.
	 *
	 * @param Source_Interface $source Adapter.
	 * @param Import_Logo      $logo Source value.
	 * @param array            $options Batch options.
	 * @param Result           $result Diagnostics.
	 * @param Target_Mapper    $mapper Writer.
	 * @throws \RuntimeException If the identity, mapping or target write fails.
	 * @throws \UnexpectedValueException If a logo lacks a valid identity or category.
	 */
	private function import_logo( Source_Interface $source, Import_Logo $logo, array $options, Result $result, Target_Mapper $mapper ): void {
		if ( '' === $logo->source_id ) {
			throw new \UnexpectedValueException( __( 'A source logo is missing its identifier.', 'yolo-logos' ) );
		}
		$existing = get_posts(
			array(
				'post_type'      => 'wp_logo',
				'post_status'    => array_keys( get_post_stati() ),
				'fields'         => 'ids',
				'posts_per_page' => 2,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Provenance lookup is required for idempotent imports.
				'meta_query'     => array(
					array(
						'key'   => '_wp_logos_import_source',
						'value' => $source->id(),
					),
					array(
						'key'   => '_wp_logos_import_source_id',
						'value' => $logo->source_id,
					),
				),
			)
		);
		if ( count( $existing ) > 1 ) {
			throw new \RuntimeException( __( 'Multiple target logos have the same import identity. Resolve the duplicate before retrying.', 'yolo-logos' ) );
		}
		$post_id = $existing ? (int) $existing[0] : 0;
		if ( $post_id && 'skip' === $options['mode'] ) {
			++$result->skipped;
			return;
		}
		$images = array();
		foreach ( array( 'main', 'light', 'dark' ) as $variant ) {
			$field = $variant . '_attachment_id';
			$id    = $logo->$field;
			$url   = 'main' === $variant && ! $id ? $logo->image_url : null;
			if ( $url ) {
				$id = attachment_url_to_postid( $url );
			}
			if ( ( $id || $url ) && ( ! $id || 'attachment' !== get_post_type( $id ) ) ) {
				$result->missing_images[] = array(
					'source_id'     => $logo->source_id,
					'variant'       => $variant,
					'attachment_id' => $id,
					'url'           => $url,
				);
				$id                       = null;
			}
			$images[ $variant ] = $id ? $id : null;
		}
		$terms = array();
		foreach ( $logo->category_source_ids as $old ) {
			if ( ! isset( $result->term_map[ $old ] ) ) {
				throw new \UnexpectedValueException( __( 'A logo references an unmapped source category.', 'yolo-logos' ) );
			}
			$terms[] = $result->term_map[ $old ];
		}
		list( $postarr, $meta ) = $mapper->prepare( $logo, $source->id(), $images, $post_id );
		if ( ! $result->dry_run ) {
			$saved_id = $mapper->write( $postarr, $meta, $terms );
			do_action( 'wp_logos_import_logo_imported', $saved_id, $logo, $source->id() );
		}
		if ( $post_id ) {
			++$result->updated;
		} else {
			++$result->created;
		}
	}
}
