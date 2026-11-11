<?php

class AdminTest extends WP_UnitTestCase {
	public function test_order_column_displays_saved_and_unset_orders(): void {
		$admin = new WP_Logos_Admin();
		$admin->init();
		$columns = apply_filters( 'manage_wp_logo_posts_columns', array( 'title' => 'Title' ) );
		$this->assertArrayHasKey( 'wp_logos_order', $columns );
		$this->assertSame(
			'menu_order',
			apply_filters( 'manage_edit-wp_logo_sortable_columns', array() )['wp_logos_order']
		);

		$post = self::factory()->post->create( array( 'post_type' => 'wp_logo' ) );
		update_post_meta( $post, '_logo_order', 55 );
		foreach ( array( 0, 12, 99, 0 ) as $order ) {
			wp_update_post( array( 'ID' => $post, 'menu_order' => $order ) );
			ob_start();
			do_action( 'manage_wp_logo_posts_custom_column', 'wp_logos_order', $post );
			$this->assertSame( (string) $order, ob_get_clean() );
		}
	}

	public function test_order_sort_is_numeric_and_defaults_to_ascending(): void {
		$admin = new WP_Logos_Admin();
		$admin->init();
		$ids = array();
		foreach ( array( 10, 2, 0 ) as $order ) {
			$ids[ $order ] = self::factory()->post->create(
				array( 'post_type' => 'wp_logo', 'post_status' => 'publish', 'menu_order' => $order )
			);
			update_post_meta( $ids[ $order ], '_logo_main_id', 9999 );
			update_post_meta( $ids[ $order ], '_logo_order', 99 - $order );
		}

		$original_query = $GLOBALS['wp_the_query'];
		set_current_screen( 'edit-wp_logo' );
		try {
			foreach ( array( '', 'ASC', 'DESC' ) as $direction ) {
				$query = new WP_Query();
				$GLOBALS['wp_the_query'] = $query;
				$query->query(
					array(
						'post_type'      => 'wp_logo',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'orderby'        => $direction ? 'menu_order' : '',
						'order'          => $direction,
					)
				);
				$expected = array( $ids[0], $ids[2], $ids[10] );
				$this->assertSame( 'DESC' === $direction ? array_reverse( $expected ) : $expected, $query->posts );
				$this->assertEmpty( $query->get( 'meta_query' ) );
			}
		} finally {
			$GLOBALS['wp_the_query'] = $original_query;
			set_current_screen( 'front' );
		}
	}

	public function test_menu_order_is_limited_for_logos_only(): void {
		foreach ( array( -3 => 0, 0 => 0, 42 => 42, 99 => 99, 150 => 99 ) as $input => $expected ) {
			$id = self::factory()->post->create( array( 'post_type' => 'wp_logo', 'menu_order' => $input ) );
			$this->assertSame( $expected, (int) get_post( $id )->menu_order );
			wp_update_post( array( 'ID' => $id, 'menu_order' => $input ) );
			$this->assertSame( $expected, (int) get_post( $id )->menu_order );
		}
		$page = self::factory()->post->create( array( 'post_type' => 'page', 'menu_order' => 150 ) );
		$this->assertSame( 150, (int) get_post( $page )->menu_order );
	}

	public function test_order_sort_does_not_change_other_queries(): void {
		$admin = new WP_Logos_Admin();
		$admin->init();
		set_current_screen( 'edit-wp_logo' );
		try {
			$query = new WP_Query(
				array( 'post_type' => 'wp_logo', 'orderby' => 'wp_logos_order' )
			);
			$this->assertSame( 'wp_logos_order', $query->get( 'orderby' ) );
			$this->assertEmpty( $query->get( 'meta_query' ) );
		} finally {
			set_current_screen( 'front' );
		}
	}
}
