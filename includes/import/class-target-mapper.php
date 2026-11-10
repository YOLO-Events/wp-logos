<?php
/**
 * The only writer of target logo records.
 *
 * @package WP_Logos
 */

namespace WP_Logos\Import;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exceptions are escaped at the admin boundary or printed as plain CLI text.

/**
 * Keeps WordPress writes separate from adapter and batching concerns.
 */
class Target_Mapper {
	/**
	 * Prepare and validate the post and metadata, including on dry runs.
	 *
	 * @param Import_Logo $logo      Source logo.
	 * @param string      $source_id Source identifier.
	 * @param array       $images    Verified attachment IDs by main/light/dark.
	 * @param int         $post_id   Existing target ID, or zero.
	 * @throws \UnexpectedValueException If a status or filtered image ID is invalid.
	 */
	public function prepare( Import_Logo $logo, string $source_id, array $images, int $post_id ): array {
		if ( ! get_post_status_object( $logo->status ) || in_array( $logo->status, array( 'inherit', 'auto-draft', 'trash' ), true ) ) {
			throw new \UnexpectedValueException( __( 'The source logo has an unsupported post status.', 'yolo-logos' ) );
		}
		$postarr = array(
			'post_type'    => 'wp_logo',
			'post_title'   => $logo->title,
			'post_content' => wp_kses_post( $logo->description_html ),
			'post_status'  => $logo->status,
			'post_author'  => $logo->author_id,
			'menu_order'   => $logo->menu_order,
		);
		if ( '' !== $logo->date ) {
			$postarr['post_date'] = $logo->date;
		}
		$postarr = apply_filters( 'wp_logos_import_logo_postarr', $postarr, $logo, $source_id );
		// Extensions may customise fields but cannot redirect writes to source posts.
		unset( $postarr['ID'], $postarr['meta_input'], $postarr['tax_input'] );
		$postarr['post_type']    = 'wp_logo';
		$postarr['post_content'] = wp_kses_post( $postarr['post_content'] ?? '' );
		if ( $post_id ) {
			$postarr['ID'] = $post_id;
		}
		$meta = array(
			'_logo_main_id'  => $images['main'],
			'_thumbnail_id'  => $images['main'],
			'_logo_light_id' => $images['light'],
			'_logo_dark_id'  => $images['dark'],
			'_logo_url'      => esc_url_raw( $logo->link ),
			'_logo_alt'      => sanitize_text_field( $logo->alt ?? '' ),
			'_logo_order'    => $logo->menu_order > 0 ? $logo->menu_order : null,
		);
		foreach ( $logo->extra as $key => $value ) {
			$meta[ '_wp_logos_import_' . sanitize_key( (string) $key ) ] = $value;
		}
		$meta['_wp_logos_import_link_target'] = $logo->link_target;
		$meta                                 = apply_filters( 'wp_logos_import_logo_meta', $meta, $logo, $source_id );
		foreach ( array( '_logo_main_id', '_logo_light_id', '_logo_dark_id' ) as $key ) {
			if ( ! empty( $meta[ $key ] ) && 'attachment' !== get_post_type( absint( $meta[ $key ] ) ) ) {
				throw new \UnexpectedValueException( __( 'A filtered image ID is not a Media Library attachment.', 'yolo-logos' ) );
			}
		}
		$meta['_thumbnail_id'] = $meta['_logo_main_id'] ?? null;
		if ( empty( $meta['_logo_order'] ) ) {
			$meta['_logo_order'] = null;
		}
		$meta['_wp_logos_import_source']    = $source_id;
		$meta['_wp_logos_import_source_id'] = $logo->source_id;
		return array( $postarr, $meta );
	}

	/**
	 * Write metadata after inserting the post; never uses media upload APIs.
	 *
	 * @param array $postarr Prepared post.
	 * @param array $meta    Prepared metadata (null deletes a previously mapped value).
	 * @param int[] $terms   Target term IDs.
	 * @throws \RuntimeException If a post, metadata or relationship write fails.
	 */
	public function write( array $postarr, array $meta, array $terms ): int {
		$post_id = wp_insert_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $post_id ) ) {
			throw new \RuntimeException( $post_id->get_error_message() );
		}
		// Save identity first, so retrying an interrupted batch reuses this post.
		$identity = array( '_wp_logos_import_source', '_wp_logos_import_source_id' );
		foreach ( array_merge( array_fill_keys( $identity, '' ), $meta ) as $key => $value ) {
			if ( null === $value ) {
				delete_post_meta( $post_id, $key );
				if ( metadata_exists( 'post', $post_id, $key ) ) {
					throw new \RuntimeException( __( 'Could not remove obsolete imported logo metadata.', 'yolo-logos' ) );
				}
			} else {
				$updated  = update_post_meta( $post_id, $key, wp_slash( $value ) );
				$expected = sanitize_meta( $key, $value, 'post', 'wp_logo' );
				$stored   = get_post_meta( $post_id, $key, true );
				if ( false === $updated && ( is_scalar( $expected ) ? (string) $stored !== (string) $expected : $stored !== $expected ) ) {
					throw new \RuntimeException( __( 'Could not save imported logo metadata.', 'yolo-logos' ) );
				}
			}
		}
		$assigned = wp_set_object_terms( $post_id, array_map( 'intval', $terms ), 'wp_logo_category', false );
		if ( is_wp_error( $assigned ) ) {
			throw new \RuntimeException( $assigned->get_error_message() );
		}
		return $post_id;
	}

	/**
	 * Prepare category arguments for creation and collision detection.
	 *
	 * @param Import_Category $category Source category.
	 * @param string          $source_id Source adapter ID.
	 * @param int             $parent_id Target parent ID.
	 * @param string          $slug Target slug.
	 */
	public function term_args( Import_Category $category, string $source_id, int $parent_id, string $slug ): array {
		return apply_filters(
			'wp_logos_import_term_args',
			array(
				'slug'        => $slug,
				'description' => wp_kses_post( $category->description ),
				'parent'      => $parent_id,
			),
			$category,
			$source_id
		);
	}

	/**
	 * Create a target category and preserve its source provenance.
	 *
	 * @param Import_Category $category Source category.
	 * @param string          $source_id Adapter identity.
	 * @param array           $args Filtered target arguments.
	 * @throws \RuntimeException If the term or its metadata cannot be saved.
	 */
	public function write_category( Import_Category $category, string $source_id, array $args ): int {
		$inserted = wp_insert_term( $category->name, 'wp_logo_category', $args );
		if ( is_wp_error( $inserted ) ) {
			throw new \RuntimeException( $inserted->get_error_message() );
		}
		$target = (int) $inserted['term_id'];
		$meta   = array();
		foreach ( $category->extra as $key => $value ) {
			$meta[ sanitize_key( (string) $key ) ] = $value;
		}
		$meta['source']    = $source_id;
		$meta['source_id'] = $category->source_id;
		foreach ( $meta as $key => $value ) {
			$saved = update_term_meta( $target, '_wp_logos_import_' . $key, wp_slash( $value ) );
			if ( is_wp_error( $saved ) || false === $saved ) {
				throw new \RuntimeException( __( 'Could not save imported category provenance.', 'yolo-logos' ) );
			}
		}
		return $target;
	}
}
