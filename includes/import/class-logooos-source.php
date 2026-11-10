<?php
/**
 * Logooos v1.9 adapter.
 *
 * @package WP_Logos
 */

namespace WP_Logos\Import;

/**
 * Logooos stores everything in standard WordPress tables.
 */
class Logooos_Source extends Generic_CPT_Source {
	/** Configure the verified Logooos v1.9 storage schema. */
	public function __construct() {
		parent::__construct(
			'logooos',
			array(
				'label'     => __( 'Logooos', 'yolo-logos' ),
				'post_type' => 'logooo',
				'taxonomy'  => 'logooocategory',
				'meta_map'  => array(
					'link'        => 'link',
					'link_target' => 'link_target',
					'description' => 'description',
					'order'       => 'menu_order',
				),
				'extra_map' => array( 'image_size' => 'imageSize' ),
			)
		);
	}

	/** Include the warning about legacy display references. */
	public function summary(): array {
		$summary               = parent::summary();
		$summary['warnings'][] = __( 'Logooos presets and shortcodes are not replaced. Use the term mapping report to add Logo Showcase blocks.', 'yolo-logos' );
		return $summary;
	}

	/** Report presets and shortcode occurrences without modifying them. */
	public function get_legacy_references(): array {
		global $wpdb;
		$references = array();
		$offset     = 0;
		do {
			$presets = $this->read_posts( 'logooo_sc', $offset, 200 );
			$meta    = $this->read_meta( array_column( $presets, 'ID' ) );
			foreach ( $presets as $preset ) {
				$references[] = array(
					'type'               => 'preset',
					'post_id'            => (int) $preset['ID'],
					'title'              => $preset['post_title'],
					'category_source_id' => (string) absint( $meta[ $preset['ID'] ]['category'] ?? 0 ),
				);
			}
			$count   = count( $presets );
			$offset += $count;
		} while ( 200 === $count );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Read-only legacy content scan; source shortcode need not be registered.
		$posts = $this->checked( $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status NOT IN ('trash', 'auto-draft') AND post_content LIKE %s ORDER BY ID ASC", '%' . $wpdb->esc_like( '[logooos' ) . '%' ), ARRAY_A ) );
		$regex = '/' . get_shortcode_regex( array( 'logooos' ) ) . '/s';
		foreach ( $posts as $post ) {
			preg_match_all( $regex, $post['post_content'], $matches, PREG_SET_ORDER );
			foreach ( $matches as $match ) {
				if ( '[' === $match[1] && ']' === $match[6] ) {
					continue;
				}
				$attributes   = shortcode_parse_atts( $match[3] );
				$references[] = array(
					'type'               => 'shortcode',
					'post_id'            => (int) $post['ID'],
					'title'              => $post['post_title'],
					'category_source_id' => (string) absint( $attributes['category'] ?? 0 ),
				);
			}
		}
		return $references;
	}
}
