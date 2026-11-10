<?php
/**
 * Raw-table source readers for inactive and deleted plugins.
 *
 * @package WP_Logos
 */

namespace WP_Logos\Import;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exceptions are data; admin escapes at output and CLI prints plain text.

/**
 * Never uses taxonomy registration or source plugin APIs.
 */
abstract class Abstract_Source implements Source_Interface {
	/**
	 * Fail explicitly on database errors rather than presenting an empty source.
	 *
	 * @param mixed $value Query result.
	 * @return mixed
	 * @throws \RuntimeException If a read fails.
	 */
	protected function checked( $value ) {
		global $wpdb;
		if ( $wpdb->last_error ) {
			throw new \RuntimeException( __( 'Could not read the import source database tables.', 'yolo-logos' ) );
		}
		return $value;
	}

	/**
	 * Count source posts without relying on registration.
	 *
	 * @param string $post_type Source post type.
	 */
	protected function count_posts( string $post_type ): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Unregistered source post types require raw read-only queries.
		return (int) $this->checked( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status NOT IN ('trash', 'auto-draft', 'inherit')", $post_type ) ) );
	}

	/**
	 * Read source posts in stable ID order.
	 *
	 * @param string $post_type Source post type.
	 * @param int    $offset    Stable ID-ordered offset.
	 * @param int    $limit     Batch size.
	 */
	protected function read_posts( string $post_type, int $offset, int $limit ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Source post types may not be registered; ID ordering makes batches stable.
		return $this->checked( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status NOT IN ('trash', 'auto-draft', 'inherit') ORDER BY ID ASC LIMIT %d OFFSET %d", $post_type, $limit, $offset ), ARRAY_A ) );
	}

	/**
	 * Read a batch of metadata without touching source caches or registrations.
	 *
	 * @param int[] $post_ids Source post IDs.
	 */
	protected function read_meta( array $post_ids ): array {
		global $wpdb;
		if ( ! $post_ids ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Only generated %d placeholders and trusted WP table names are interpolated.
		$rows = $this->checked( $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($placeholders) ORDER BY meta_id ASC", ...array_map( 'absint', $post_ids ) ), ARRAY_A ) );
		$meta = array();
		foreach ( $rows as $row ) {
			// Match get_post_meta(..., true): the first value wins.
			if ( ! array_key_exists( $row['meta_key'], $meta[ $row['post_id'] ] ?? array() ) ) {
				$meta[ $row['post_id'] ][ $row['meta_key'] ] = maybe_unserialize( $row['meta_value'] );
			}
		}
		return $meta;
	}

	/**
	 * Read terms and hierarchy from an unregistered taxonomy.
	 *
	 * @param string $taxonomy Source taxonomy.
	 * @return iterable<Import_Category>
	 */
	protected function read_categories( string $taxonomy ): iterable {
		global $wpdb;
		if ( '' === $taxonomy ) {
			return;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- get_terms() rejects unregistered source taxonomies.
		$rows = $this->checked( $wpdb->get_results( $wpdb->prepare( "SELECT t.term_id, t.name, t.slug, tt.description, tt.parent FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE tt.taxonomy = %s ORDER BY t.term_id ASC", $taxonomy ), ARRAY_A ) );
		foreach ( $rows as $row ) {
			yield new Import_Category(
				array(
					'source_id'        => $row['term_id'],
					'name'             => $row['name'],
					'slug'             => $row['slug'],
					'description'      => $row['description'],
					'parent_source_id' => $row['parent'] ? $row['parent'] : null,
				)
			);
		}
	}

	/**
	 * Read category assignments for a batch of source posts.
	 *
	 * @param int[]  $post_ids Source post IDs.
	 * @param string $taxonomy Source taxonomy.
	 */
	protected function read_relationships( array $post_ids, string $taxonomy ): array {
		global $wpdb;
		if ( ! $post_ids || '' === $taxonomy ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$args         = array_merge( array( $taxonomy ), array_map( 'absint', $post_ids ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Generated placeholders only; taxonomy and IDs are prepared.
		$rows = $this->checked( $wpdb->get_results( $wpdb->prepare( "SELECT tr.object_id, tt.term_id FROM {$wpdb->term_relationships} tr INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy = %s AND tr.object_id IN ($placeholders) ORDER BY tt.term_id ASC", ...$args ), ARRAY_A ) );
		$map  = array();
		foreach ( $rows as $row ) {
			$map[ $row['object_id'] ][] = (string) $row['term_id'];
		}
		return $map;
	}

	/** No legacy presentation configuration by default. */
	public function get_legacy_references(): array {
		return array();
	}
}
