<?php

use WP_Logos\Import\Engine;
use WP_Logos\Import\Generic_CPT_Source;
use WP_Logos\Import\Registry;

class GenericSourceTest extends WP_UnitTestCase {
	private function source_term( string $taxonomy, string $name, string $slug ): array {
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
				'taxonomy'    => $taxonomy,
				'description' => '',
				'parent'      => 0,
				'count'       => 1,
			)
		);
		return array( $id, (int) $wpdb->insert_id );
	}

	public function test_generic_adapter_reads_unregistered_post_type_and_taxonomy(): void {
		global $wpdb;
		$this->assertFalse( post_type_exists( 'legacy_client' ) );
		$this->assertFalse( taxonomy_exists( 'legacy_client_cat' ) );
		list( $term_id, $tt_id ) = $this->source_term( 'legacy_client_cat', 'Client category', 'client-category' );
		$post_id                 = wp_insert_post(
			array(
				'post_type'   => 'legacy_client',
				'post_status' => 'publish',
				'post_title'  => 'Legacy client',
				'menu_order'  => 4,
			)
		);
		$this->assertIsInt( $post_id );
		$wpdb->insert(
			$wpdb->term_relationships,
			array(
				'object_id'        => $post_id,
				'term_taxonomy_id' => $tt_id,
				'term_order'       => 0,
			)
		);
		update_post_meta( $post_id, 'client_url', 'https://example.org/client' );
		update_post_meta( $post_id, 'client_size', 'large' );

		$source = new Generic_CPT_Source(
			'generic-test',
			array(
				'label'     => 'Generic test',
				'post_type' => 'legacy_client',
				'taxonomy'  => 'legacy_client_cat',
				'image'     => 'featured',
				'meta_map'  => array( 'link' => 'client_url' ),
				'extra_map' => array( 'image_size' => 'client_size' ),
			)
		);
		$this->assertSame( 'generic-test', $source->id() );
		$this->assertSame( 'Generic test', $source->label() );
		$this->assertTrue( $source->detect() );
		$this->assertSame( 1, $source->count_logos() );
		$logos = iterator_to_array(
			( function () use ( $source ) {
				yield from $source->get_logos( 0, 10 );
			} )()
		);
		$this->assertCount( 1, $logos );
		$this->assertSame( (string) $post_id, $logos[0]->source_id );
		$this->assertSame( 'https://example.org/client', $logos[0]->link );
		$this->assertSame( array( (string) $term_id ), $logos[0]->category_source_ids );
		$this->assertSame( 'large', $logos[0]->extra['image_size'] );
		$result = ( new Engine() )->run_batch( $source, array( 'dry_run' => false ) );
		$this->assertSame( 1, $result->created );
		$this->assertArrayHasKey( (string) $term_id, $result->term_map );
	}

	public function test_legacy_myclients_preset_is_discoverable_without_registration(): void {
		global $wpdb;
		$this->assertFalse( post_type_exists( 'myclients' ) );
		$this->assertFalse( taxonomy_exists( 'clientscategory' ) );
		list( $term_id, $tt_id ) = $this->source_term( 'clientscategory', 'Legacy clients', 'legacy-clients' );
		$post_id                 = wp_insert_post(
			array(
				'post_type'   => 'myclients',
				'post_status' => 'publish',
				'post_title'  => 'My client',
			)
		);
		$wpdb->insert(
			$wpdb->term_relationships,
			array(
				'object_id'        => $post_id,
				'term_taxonomy_id' => $tt_id,
				'term_order'       => 0,
			)
		);
		update_post_meta( $post_id, 'husamrayan_clientWebsite', 'https://example.org/client' );
		update_post_meta( $post_id, 'client_link_target', '_self' );
		update_post_meta( $post_id, 'mc_imageSize', 'large' );
		$registry = new Registry();
		$source   = $registry->get( 'logooos-legacy' );
		$this->assertInstanceOf( Generic_CPT_Source::class, $source );
		$this->assertSame( 'logooos-legacy', $source->id() );
		$this->assertTrue( $source->detect() );
		$this->assertSame( 1, $source->count_logos() );
		$this->assertArrayHasKey( 'logooos-legacy', $registry->all() );
		$this->assertArrayHasKey( 'logooos-legacy', $registry->detected() );
		$logos = iterator_to_array( $source->get_logos( 0, 10 ) );
		$this->assertSame( 'https://example.org/client', $logos[0]->link );
		$this->assertSame( '_self', $logos[0]->link_target );
		$this->assertSame( 'large', $logos[0]->extra['image_size'] );
		$this->assertSame( array( (string) $term_id ), $logos[0]->category_source_ids );
		$result = ( new Engine() )->run_batch( $source, array( 'dry_run' => false ) );
		$this->assertSame( 1, $result->created );
		$this->assertArrayHasKey( (string) $term_id, $result->term_map );
	}

	public function test_config_filter_and_local_url_resolution_reuse_existing_attachment(): void {
		$attachment = self::factory()->attachment->create( array( 'post_mime_type' => 'image/png' ) );
		update_post_meta( $attachment, '_wp_attached_file', 'legacy/logo.png' );
		$url = wp_get_attachment_url( $attachment );
		$id  = wp_insert_post(
			array(
				'post_type'    => 'url_logo',
				'post_status'  => 'publish',
				'post_title'   => 'URL logo',
				'post_content' => '<p>Content</p>',
			)
		);
		update_post_meta( $id, '_image_url', $url );
		$filter = static function ( array $sources ): array {
			$sources['url-test'] = array(
				'post_type' => 'url_logo',
				'image'     => 'meta_url:_image_url',
				'meta_map'  => array( 'description' => 'post_content' ),
			);
			return $sources;
		};
		add_filter( 'wp_logos_import_sources', $filter );
		try {
			$source = ( new Registry() )->get( 'url-test' );
			$result = ( new Engine() )->run_batch( $source, array( 'dry_run' => false ) );
			$this->assertSame( 1, $result->created );
			$this->assertEmpty( $result->missing_images );
			$posts = get_posts(
				array(
					'post_type' => 'wp_logo',
					'title'     => 'URL logo',
				)
			);
			$this->assertCount( 1, $posts );
			$this->assertSame( $attachment, (int) get_post_meta( $posts[0]->ID, '_logo_main_id', true ) );
			$this->assertSame( '<p>Content</p>', $posts[0]->post_content );
		} finally {
			remove_filter( 'wp_logos_import_sources', $filter );
		}
	}
}
