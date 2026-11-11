<?php

class AdminTest extends WP_UnitTestCase {
	public function test_order_column_displays_saved_and_unset_orders(): void {
		$admin = new WP_Logos_Admin();
		$admin->init();
		$columns = apply_filters( 'manage_wp_logo_posts_columns', array( 'title' => 'Title' ) );
		$this->assertArrayHasKey( 'wp_logos_order', $columns );
		$this->assertSame(
			'wp_logos_order',
			apply_filters( 'manage_edit-wp_logo_sortable_columns', array() )['wp_logos_order']
		);

		$post = self::factory()->post->create( array( 'post_type' => 'wp_logo' ) );
		foreach ( array( 0, 12 ) as $order ) {
			if ( $order ) {
				update_post_meta( $post, '_logo_order', $order );
			}
			ob_start();
			do_action( 'manage_wp_logo_posts_custom_column', 'wp_logos_order', $post );
			$this->assertSame( (string) $order, ob_get_clean() );
		}
	}

	public function test_order_sort_is_numeric_and_includes_logos_without_meta(): void {
		$admin = new WP_Logos_Admin();
		$admin->init();
		$ids = array();
		foreach ( array( 10, 2, 0 ) as $order ) {
			$ids[ $order ] = self::factory()->post->create(
				array( 'post_type' => 'wp_logo', 'post_status' => 'publish' )
			);
			update_post_meta( $ids[ $order ], '_logo_main_id', 9999 );
			if ( $order ) {
				update_post_meta( $ids[ $order ], '_logo_order', $order );
			}
		}

		$original_query = $GLOBALS['wp_the_query'];
		set_current_screen( 'edit-wp_logo' );
		try {
			foreach ( array( 'ASC', 'DESC' ) as $direction ) {
				$query = new WP_Query();
				$GLOBALS['wp_the_query'] = $query;
				$query->query(
					array(
						'post_type'      => 'wp_logo',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'orderby'        => 'wp_logos_order',
						'order'          => $direction,
					)
				);
				$expected = array( $ids[0], $ids[2], $ids[10] );
				$this->assertSame( 'ASC' === $direction ? $expected : array_reverse( $expected ), $query->posts );
			}
		} finally {
			$GLOBALS['wp_the_query'] = $original_query;
			set_current_screen( 'front' );
		}
	}

	public function test_order_sort_does_not_change_other_queries(): void {
		$admin = new WP_Logos_Admin();
		$admin->init();
		set_current_screen( 'edit-wp_logo' );
		try {
			$query = new WP_Query(
				array( 'post_type' => 'wp_logo', 'orderby' => 'wp_logos_order' )
			);
			$this->assertEmpty( $query->get( 'meta_query' ) );
		} finally {
			set_current_screen( 'front' );
		}
	}
}
