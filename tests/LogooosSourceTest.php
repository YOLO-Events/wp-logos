<?php

use WP_Logos\Import\Engine;
use WP_Logos\Import\Logooos_Source;

class LogooosSourceTest extends WP_UnitTestCase {
	private string $image_file = '';

	public function tear_down(): void {
		if ( $this->image_file && is_file( $this->image_file ) ) {
			unlink( $this->image_file );
		}
		parent::tear_down();
	}

	private function source_term( string $name, string $slug, int $parent = 0 ): array {
		global $wpdb;
		$wpdb->insert(
			$wpdb->terms,
			array(
				'name'       => $name,
				'slug'       => $slug,
				'term_group' => 0,
			)
		);
		$id = (int) $wpdb->insert_id;
		$wpdb->insert(
			$wpdb->term_taxonomy,
			array(
				'term_id'     => $id,
				'taxonomy'    => 'logooocategory',
				'description' => 'Legacy ' . $name,
				'parent'      => $parent,
				'count'       => 1,
			)
		);
		return array( $id, (int) $wpdb->insert_id );
	}

	private function attachment(): int {
		$upload = wp_upload_dir();
		wp_mkdir_p( $upload['path'] );
		$this->image_file = $upload['path'] . '/' . wp_unique_filename( $upload['path'], 'legacy-logo.png' );
		file_put_contents( $this->image_file, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lX8AAAAASUVORK5CYII=' ) );
		return (int) wp_insert_attachment(
			array(
				'post_mime_type' => 'image/png',
				'post_title'     => 'Legacy image',
				'post_status'    => 'inherit',
				'guid'           => $upload['url'] . '/' . basename( $this->image_file ),
			),
			$this->image_file
		);
	}

	public function test_import_reuses_media_preserves_source_and_reports_references(): void {
		global $wpdb;
		$this->assertFalse( post_type_exists( 'logooo' ) );
		$this->assertFalse( taxonomy_exists( 'logooocategory' ) );
		list( $parent_id )           = $this->source_term( 'Parent', 'legacy-parent' );
		list( $child_id, $child_tt ) = $this->source_term( 'Child', 'legacy-child', $parent_id );
		$attachment_id               = $this->attachment();
		$source_id                   = wp_insert_post(
			array(
				'post_type'   => 'logooo',
				'post_status' => 'publish',
				'post_title'  => 'Legacy logo',
				'menu_order'  => 7,
			)
		);
		$wpdb->insert(
			$wpdb->term_relationships,
			array(
				'object_id'        => $source_id,
				'term_taxonomy_id' => $child_tt,
				'term_order'       => 0,
			)
		);
		update_post_meta( $source_id, '_thumbnail_id', $attachment_id );
		update_post_meta( $source_id, 'description', '<p>Legacy description</p>' );
		update_post_meta( $source_id, 'link', 'https://example.org/legacy' );
		update_post_meta( $source_id, 'link_target', '_self' );
		update_post_meta( $source_id, 'imageSize', '70%' );
		$before_image_hash = hash_file( 'sha256', $this->image_file );
		$page_id           = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Legacy page',
				'post_content' => '[logooos category="' . $child_id . '"]',
			)
		);
		$preset_id         = wp_insert_post(
			array(
				'post_type'   => 'logooo_sc',
				'post_status' => 'publish',
				'post_title'  => 'Legacy preset',
			)
		);
		update_post_meta( $preset_id, 'category', $child_id );

		$before_posts         = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID IN (%d,%d,%d,%d) ORDER BY ID", $source_id, $attachment_id, $page_id, $preset_id ), ARRAY_A );
		$before_meta          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id IN (%d,%d,%d,%d) ORDER BY meta_id", $source_id, $attachment_id, $page_id, $preset_id ), ARRAY_A );
		$before_terms         = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id WHERE tt.taxonomy=%s ORDER BY t.term_id", 'logooocategory' ), ARRAY_A );
		$before_relationships = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE object_id=%d", $source_id ), ARRAY_A );

		$source = new Logooos_Source();
		$this->assertSame( 'logooos', $source->id() );
		$this->assertTrue( $source->detect() );
		$source_logos = iterator_to_array( $source->get_logos( 0, 10 ) );
		$this->assertSame( '70%', $source_logos[0]->extra['image_size'] );
		$this->assertSame( '_self', $source_logos[0]->link_target );
		$result = ( new Engine() )->run_batch( $source, array( 'dry_run' => false ) );
		$this->assertSame( 1, $result->created );
		$this->assertEmpty( $result->errors );
		$this->assertEmpty( $result->missing_images );
		$this->assertSame( $result->term_map[ (string) $parent_id ], (int) get_term( $result->term_map[ (string) $child_id ], 'wp_logo_category' )->parent );

		$imported = get_posts(
			array(
				'post_type'   => 'wp_logo',
				'post_status' => 'any',
				'numberposts' => -1,
				'meta_query'  => array(
					array(
						'key'   => '_wp_logos_import_source',
						'value' => 'logooos',
					),
					array(
						'key'   => '_wp_logos_import_source_id',
						'value' => (string) $source_id,
					),
				),
			)
		);
		$this->assertCount( 1, $imported );
		$logo = $imported[0];
		$this->assertSame( $attachment_id, (int) get_post_meta( $logo->ID, '_logo_main_id', true ) );
		$this->assertSame( $attachment_id, (int) get_post_thumbnail_id( $logo->ID ) );
		$this->assertSame( 'https://example.org/legacy', get_post_meta( $logo->ID, '_logo_url', true ) );
		$this->assertSame( '<p>Legacy description</p>', $logo->post_content );
		$this->assertSame( 7, (int) $logo->menu_order );
		$this->assertSame( 7, (int) get_post_meta( $logo->ID, '_logo_order', true ) );
		$this->assertSame( '_self', get_post_meta( $logo->ID, '_wp_logos_import_link_target', true ) );
		$this->assertSame( '70%', get_post_meta( $logo->ID, '_wp_logos_import_image_size', true ) );
		$this->assertSame( '', get_post_meta( $logo->ID, '_logo_alt', true ) );
		$this->assertFalse( metadata_exists( 'post', $logo->ID, '_logo_light_id' ) );
		$this->assertFalse( metadata_exists( 'post', $logo->ID, '_logo_dark_id' ) );
		$this->assertSame( $before_image_hash, hash_file( 'sha256', $this->image_file ) );

		$references = array_column( $result->legacy_references, null, 'type' );
		foreach ( array(
			'shortcode' => $page_id,
			'preset'    => $preset_id,
		) as $type => $id ) {
			$this->assertArrayHasKey( $type, $references );
			$this->assertSame( $id, $references[ $type ]['post_id'] );
			$this->assertSame( (string) $child_id, $references[ $type ]['category_source_id'] );
			$this->assertSame( $result->term_map[ (string) $child_id ], $references[ $type ]['target_term_id'] );
		}
		$html = render_block(
			array(
				'blockName'    => 'yolo-logos/logo-showcase',
				'attrs'        => array( 'categoryId' => $result->term_map[ (string) $child_id ] ),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
		$this->assertStringContainsString( wp_get_attachment_image_url( $attachment_id, 'full' ), $html );
		$this->assertStringContainsString( 'https://example.org/legacy', $html );
		$this->assertStringContainsString( 'target="_blank" rel="noopener noreferrer"', $html );
		$this->assertSame( $before_posts, $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID IN (%d,%d,%d,%d) ORDER BY ID", $source_id, $attachment_id, $page_id, $preset_id ), ARRAY_A ) );
		$this->assertSame( $before_meta, $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id IN (%d,%d,%d,%d) ORDER BY meta_id", $source_id, $attachment_id, $page_id, $preset_id ), ARRAY_A ) );
		$this->assertSame( $before_terms, $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id WHERE tt.taxonomy=%s ORDER BY t.term_id", 'logooocategory' ), ARRAY_A ) );
		$this->assertSame( $before_relationships, $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE object_id=%d", $source_id ), ARRAY_A ) );
	}
}
