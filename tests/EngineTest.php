<?php

use WP_Logos\Import\Engine;
use WP_Logos\Import\Import_Category;
use WP_Logos\Import\Import_Logo;
use WP_Logos\Import\Source_Interface;

class Test_Import_Source implements Source_Interface {
	public array $categories = array();
	public array $logos      = array();
	public array $references = array();

	public function id(): string {
		return 'test-source';
	}

	public function label(): string {
		return 'Test source';
	}

	public function detect(): bool {
		return true;
	}

	public function summary(): array {
		return array();
	}

	public function get_categories(): iterable {
		return $this->categories;
	}

	public function count_logos(): int {
		return count( $this->logos );
	}

	public function get_logos( int $offset, int $limit ): iterable {
		return array_slice( $this->logos, $offset, $limit );
	}

	public function get_legacy_references(): array {
		return $this->references;
	}
}

class EngineTest extends WP_UnitTestCase {
	private function source(): Test_Import_Source {
		$source             = new Test_Import_Source();
		$source->categories = array(
			new Import_Category(
				array(
					'source_id'        => 'child',
					'name'             => 'Child',
					'slug'             => 'test-child',
					'description'      => 'Child description',
					'parent_source_id' => 'parent',
					'extra'            => array(),
				)
			),
			new Import_Category(
				array(
					'source_id'        => 'parent',
					'name'             => 'Parent',
					'slug'             => 'test-parent',
					'description'      => '',
					'parent_source_id' => null,
					'extra'            => array(),
				)
			),
		);
		$source->logos      = array( $this->logo( 'one', 'First' ), $this->logo( 'two', 'Second' ) );
		return $source;
	}

	private function logo( string $id, string $title, array $overrides = array() ): Import_Logo {
		return new Import_Logo(
			array_merge(
				array(
					'source_id'           => $id,
					'title'               => $title,
					'status'              => 'publish',
					'date'                => '2024-01-02 12:00:00',
					'author_id'           => 1,
					'menu_order'          => 0,
					'main_attachment_id'  => null,
					'light_attachment_id' => null,
					'dark_attachment_id'  => null,
					'image_url'           => null,
					'link'                => '',
					'link_target'         => null,
					'alt'                 => null,
					'description_html'    => '',
					'category_source_ids' => array( 'child' ),
					'extra'               => array(),
				),
				$overrides
			)
		);
	}

	private function imported( string $source_id ): array {
		return get_posts(
			array(
				'post_type'   => 'wp_logo',
				'post_status' => 'any',
				'numberposts' => -1,
				'meta_query'  => array(
					array(
						'key'   => '_wp_logos_import_source',
						'value' => 'test-source',
					),
					array(
						'key'   => '_wp_logos_import_source_id',
						'value' => $source_id,
					),
				),
			)
		);
	}

	public function test_dry_run_is_the_default_and_makes_no_database_writes(): void {
		global $wpdb;
		$tables = array( $wpdb->posts, $wpdb->postmeta, $wpdb->terms, $wpdb->term_taxonomy, $wpdb->term_relationships, $wpdb->options );
		$before = array();
		foreach ( $tables as $table ) {
			$before[ $table ] = $wpdb->get_results( "SELECT * FROM `$table` ORDER BY 1", ARRAY_A );
		}

		$result = ( new Engine() )->run_batch( $this->source() );

		$this->assertTrue( $result->dry_run );
		$this->assertSame( 2, $result->created );
		$this->assertLessThan( 0, $result->term_map['parent'] );
		$this->assertLessThan( 0, $result->term_map['child'] );
		$this->assertNotSame( $result->term_map['parent'], $result->term_map['child'] );
		foreach ( $tables as $table ) {
			$this->assertSame( $before[ $table ], $wpdb->get_results( "SELECT * FROM `$table` ORDER BY 1", ARRAY_A ), $table . ' changed during dry run' );
		}
		$this->assertFalse( get_option( 'wp_logos_import_map_test-source' ) );
	}

	public function test_resume_preserves_hierarchy_map_and_imports_each_logo_once(): void {
		$source = $this->source();
		$engine = new Engine();
		$first  = $engine->run_batch(
			$source,
			array(
				'dry_run' => false,
				'limit'   => 1,
			)
		);
		$this->assertSame( 1, $first->created );
		$this->assertSame( 2, $first->total );
		$this->assertSame( 1, $first->next_offset );
		$this->assertFalse( $first->done );
		$this->assertSame( $first->term_map, get_option( 'wp_logos_import_map_test-source' ) );
		$this->assertSame( $first->term_map['parent'], (int) get_term( $first->term_map['child'], 'wp_logo_category' )->parent );
		$this->assertSame( 'Child description', get_term( $first->term_map['child'], 'wp_logo_category' )->description );

		$second = $engine->run_batch(
			$source,
			array(
				'dry_run' => false,
				'offset'  => $first->next_offset,
				'limit'   => 1,
			)
		);
		$this->assertTrue( $second->done );
		$this->assertSame( 2, $second->next_offset );
		$this->assertSame( $first->term_map, $second->term_map );
		$this->assertSame( 1, $second->created );
		foreach ( array( 'one', 'two' ) as $id ) {
			$posts = $this->imported( $id );
			$this->assertCount( 1, $posts );
			$this->assertSame( array( $first->term_map['child'] ), wp_get_object_terms( $posts[0]->ID, 'wp_logo_category', array( 'fields' => 'ids' ) ) );
		}
	}

	public function test_skip_and_update_are_idempotent(): void {
		$source  = $this->source();
		$engine  = new Engine();
		$created = $engine->run_batch( $source, array( 'dry_run' => false ) );
		$this->assertSame( 2, $created->created );
		$original         = $this->imported( 'one' )[0];
		$source->logos[0] = $this->logo( 'one', 'Changed' );
		$skipped          = $engine->run_batch(
			$source,
			array(
				'dry_run' => false,
				'mode'    => 'skip',
			)
		);
		$this->assertSame( 2, $skipped->skipped );
		$this->assertSame( 'First', get_post( $original->ID )->post_title );
		$updated = $engine->run_batch(
			$source,
			array(
				'dry_run' => false,
				'mode'    => 'update',
			)
		);
		$this->assertSame( 2, $updated->updated );
		$this->assertSame( 'Changed', get_post( $original->ID )->post_title );
		$this->assertFalse( metadata_exists( 'post', $original->ID, '_logo_order' ) );
		$this->assertCount( 1, $this->imported( 'one' ) );
		$this->assertCount( 1, $this->imported( 'two' ) );
	}

	public function test_category_collision_can_reuse_or_copy(): void {
		$existing = wp_insert_term( 'Existing', 'wp_logo_category', array( 'slug' => 'test-parent' ) );
		$this->assertNotWPError( $existing );
		$source = $this->source();
		$reuse  = ( new Engine() )->run_batch(
			$source,
			array(
				'dry_run'   => false,
				'collision' => 'reuse',
			)
		);
		$this->assertSame( (int) $existing['term_id'], $reuse->term_map['parent'] );
		$this->assertNotEmpty( $reuse->slug_collisions );

		delete_option( 'wp_logos_import_map_test-source' );
		$copy = ( new Engine() )->run_batch(
			$source,
			array(
				'dry_run'   => false,
				'collision' => 'copy',
			)
		);
		$this->assertNotSame( (int) $existing['term_id'], $copy->term_map['parent'] );
		$this->assertNotSame( 'test-parent', get_term( $copy->term_map['parent'], 'wp_logo_category' )->slug );
		$this->assertSame( $copy->term_map['parent'], (int) get_term( $copy->term_map['child'], 'wp_logo_category' )->parent );
	}

	public function test_missing_attachment_does_not_prevent_import(): void {
		$source        = $this->source();
		$source->logos = array( $this->logo( 'missing', 'No image', array( 'main_attachment_id' => 99999999 ) ) );
		$result        = ( new Engine() )->run_batch( $source, array( 'dry_run' => false ) );
		$this->assertSame( 1, $result->created );
		$this->assertNotEmpty( $result->missing_images );
		$posts = $this->imported( 'missing' );
		$this->assertCount( 1, $posts );
		$this->assertSame( 0, (int) get_post_meta( $posts[0]->ID, '_logo_main_id', true ) );
		$this->assertSame( 0, (int) get_post_thumbnail_id( $posts[0]->ID ) );
	}

	public function test_updates_clear_obsolete_images_and_order_without_touching_attachments(): void {
		$attachment    = self::factory()->attachment->create();
		$before        = get_post( $attachment )->to_array();
		$source        = $this->source();
		$source->logos = array(
			$this->logo(
				'one',
				'Variants',
				array(
					'main_attachment_id'  => $attachment,
					'light_attachment_id' => $attachment,
					'dark_attachment_id'  => $attachment,
					'menu_order'          => 8,
				)
			),
		);
		$engine        = new Engine();
		$result        = $engine->run_batch( $source, array( 'dry_run' => false ) );
		$this->assertSame( 1, $result->created );
		$post_id = $this->imported( 'one' )[0]->ID;
		$this->assertSame( 8, (int) get_post( $post_id )->menu_order );
		$this->assertFalse( metadata_exists( 'post', $post_id, '_logo_order' ) );
		foreach ( array( '_logo_main_id', '_thumbnail_id', '_logo_light_id', '_logo_dark_id' ) as $key ) {
			$this->assertSame( $attachment, (int) get_post_meta( $post_id, $key, true ) );
		}
		$source->logos = array( $this->logo( 'one', 'No variants' ) );
		$result        = $engine->run_batch(
			$source,
			array(
				'dry_run' => false,
				'mode'    => 'update',
			)
		);
		$this->assertSame( 1, $result->updated );
		$this->assertSame( 0, (int) get_post( $post_id )->menu_order );
		foreach ( array( '_logo_main_id', '_thumbnail_id', '_logo_light_id', '_logo_dark_id', '_logo_order' ) as $key ) {
			$this->assertFalse( metadata_exists( 'post', $post_id, $key ) );
		}
		$this->assertSame( $before, get_post( $attachment )->to_array() );
	}

	public function test_bad_hierarchy_fails_before_writing_and_releases_lock(): void {
		$source                                  = $this->source();
		$source->categories[1]->parent_source_id = 'child';
		try {
			( new Engine() )->run_batch( $source, array( 'dry_run' => false ) );
			$this->fail( 'A category cycle must fail explicitly.' );
		} catch ( UnexpectedValueException $exception ) {
			$this->assertStringContainsString( 'cycle', $exception->getMessage() );
		}
		$this->assertFalse( get_option( 'wp_logos_import_lock_test-source' ) );
		$this->assertFalse( get_option( 'wp_logos_import_map_test-source' ) );
		$this->assertFalse( wp_defer_term_counting() );
		$this->assertSame(
			0,
			(int) wp_count_terms(
				array(
					'taxonomy'   => 'wp_logo_category',
					'hide_empty' => false,
				)
			)
		);
	}
}
