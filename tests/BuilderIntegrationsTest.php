<?php

class BuilderIntegrationsTest extends WP_UnitTestCase {

	public function test_wpbakery_elements_cover_all_displays(): void {
		$elements = WP_Logos\WPBakery::elements();
		$this->assertSame(
			array( 'yolo_logo_showcase', 'yolo_logo_categories', 'yolo_logo_full_cards' ),
			array_column( $elements, 'base' )
		);
		$category_param = array_column( $elements[1]['params'], null, 'param_name' )['categoryorder'];
		$this->assertSame( 'autocomplete', $category_param['type'] );
		$this->assertTrue( $category_param['settings']['multiple'] );
		$this->assertTrue( $category_param['settings']['sortable'] );
		$this->assertTrue( $category_param['settings']['unique_values'] );
		$this->assertStringContainsString( 'columns_desktop', wp_json_encode( $elements[0]['params'] ) );
		foreach ( $elements as $element ) {
			foreach ( $element['params'] as $param ) {
				$parsed = shortcode_parse_atts( $param['param_name'] . '="saved"' );
				$this->assertArrayHasKey( $param['param_name'], $parsed );
			}
		}
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_wpbakery_lifecycle_maps_elements_and_registers_renderers(): void {
		if ( function_exists( 'vc_map' ) ) {
			$this->markTestSkipped( 'WPBakery is installed in this test environment.' );
		}

		function vc_map( $element ) {
			$GLOBALS['wp_logos_test_vc_maps'][] = $element;
		}

		$GLOBALS['wp_logos_test_vc_maps'] = array();
		$category = self::factory()->term->create(
			array( 'taxonomy' => 'wp_logo_category', 'name' => 'Builder sponsors' )
		);

		// Replay early init, when WPBakery fires vc_before_init.
		unregister_taxonomy( 'wp_logo_category' );
		global $wp_filter;
		$this->assertArrayHasKey( 0, $wp_filter['init']->callbacks );
		foreach ( $wp_filter['init']->callbacks[0] as $callback ) {
			call_user_func_array( $callback['function'], array() );
		}
		$this->assertTrue( taxonomy_exists( 'wp_logo_category' ) );
		do_action( 'vc_before_init' );

		$elements = $GLOBALS['wp_logos_test_vc_maps'];
		$this->assertSame(
			array( 'yolo_logo_showcase', 'yolo_logo_categories', 'yolo_logo_full_cards' ),
			array_column( $elements, 'base' )
		);
		$category_param = array_column( $elements[0]['params'], null, 'param_name' )['categoryid'];
		$this->assertSame( (string) $category, $category_param['value']['Builder sponsors'] );

		$this->assertSame(
			array( array( 'value' => (string) $category, 'label' => 'Builder sponsors' ) ),
			apply_filters( 'vc_autocomplete_yolo_logo_categories_categoryorder_callback', 'Builder sponsors' )
		);
		$this->assertSame(
			array( 'value' => (string) $category, 'label' => 'Builder sponsors' ),
			apply_filters( 'vc_autocomplete_yolo_logo_categories_categoryorder_render', array( 'value' => (string) $category ) )
		);
		$this->assertFalse(
			apply_filters( 'vc_autocomplete_yolo_logo_categories_categoryorder_render', array( 'value' => '999999' ) )
		);

		foreach ( $elements as $element ) {
			$this->assertArrayNotHasKey( 'html_template', $element );
			$this->assertTrue( shortcode_exists( $element['base'] ) );
			$output = do_shortcode( '[' . $element['base'] . ']' );
			$this->assertNotSame( '', $output );
			$this->assertStringNotContainsString( '[' . $element['base'], $output );
		}
		$this->assertStringContainsString(
			'wp-logos-empty',
			do_shortcode( '[yolo_logo_categories]' )
		);

		$second_category = self::factory()->term->create(
			array( 'taxonomy' => 'wp_logo_category', 'name' => 'Second sponsors' )
		);
		$image = self::factory()->post->create(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/png',
				'meta_input'     => array( '_wp_attached_file' => 'builder-logo.png' ),
			)
		);
		foreach ( array( $category, $second_category ) as $term_id ) {
			$logo = self::factory()->post->create(
				array(
					'post_type'   => 'wp_logo',
					'post_status' => 'publish',
					'meta_input'  => array( '_logo_main_id' => $image ),
				)
			);
			wp_set_object_terms( $logo, array( $term_id ), 'wp_logo_category' );
		}

		$post = self::factory()->post->create(
			array(
				'post_content' => sprintf(
					'[yolo_logo_categories categoryorder="%d,%d" headingposition="top" headinglevel="2"]',
					$second_category,
					$category
				),
			)
		);
		$saved = get_post_field( 'post_content', $post );
		$parsed = shortcode_parse_atts( substr( $saved, strlen( '[yolo_logo_categories ' ), -1 ) );
		$this->assertSame(
			$second_category . ',' . $category,
			$parsed['categoryorder']
		);
		$this->assertSame(
			array( 'value' => (string) $second_category, 'label' => 'Second sponsors' ),
			apply_filters(
				'vc_autocomplete_yolo_logo_categories_categoryorder_render',
				array( 'value' => explode( ',', $parsed['categoryorder'] )[0] )
			)
		);
		$output = do_shortcode( $saved );
		$this->assertStringNotContainsString( 'wp-logos-empty', $output );
		$this->assertSame( 2, substr_count( $output, '<section ' ) );
		$this->assertLessThan( strpos( $output, 'Builder sponsors' ), strpos( $output, 'Second sponsors' ) );
		$this->assertStringContainsString( 'wp-logos-headings-top', $output );
		$this->assertStringContainsString( '<h2 ', $output );

		foreach ( array( 'yolo_logo_showcase', 'yolo_logo_full_cards' ) as $shortcode ) {
			$output = do_shortcode( '[' . $shortcode . ' categoryid="' . $category . '" showtitle="false"]' );
			$this->assertSame( 1, substr_count( $output, '<img ' ) );
			$this->assertStringNotContainsString( 'class="wp-logos-title"', $output );
			if ( 'yolo_logo_full_cards' === $shortcode ) {
				$this->assertStringContainsString( '--wp-logos-cols-mobile:1;', $output );
				$this->assertStringContainsString( '--wp-logos-cols-desktop:3;', $output );
				$output = do_shortcode( '[yolo_logo_full_cards columns_mobile="2"]' );
				$this->assertStringContainsString( '--wp-logos-cols-mobile:2;', $output );
				$this->assertStringContainsString( '--wp-logos-cols-tablet:2;', $output );
				$this->assertStringContainsString( '--wp-logos-cols-desktop:3;', $output );
			}
		}
	}

	public function test_elementor_configs_cover_all_displays(): void {
		$this->assertSame(
			array( 'showcase', 'categories', 'full-cards' ),
			array_column( WP_Logos\Builder_Configs::for_editor( 'elementor' ), 'display' )
		);
	}

	public function test_elementor_absence_is_safe(): void {
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$this->markTestSkipped( 'Elementor is installed in this test environment.' );
		}

		$integration = new WP_Logos\Elementor();
		$integration->register();
		$this->assertFalse( has_action( 'elementor/widgets/register', array( $integration, 'register_widgets' ) ) );
	}

	public function test_wpbakery_absence_is_safe(): void {
		if ( function_exists( 'vc_map' ) ) {
			$this->markTestSkipped( 'WPBakery is installed in this test environment.' );
		}

		$integration = new WP_Logos\WPBakery();
		$integration->register();
		$this->assertFalse( shortcode_exists( 'yolo_logo_showcase' ) );
	}

	public function test_builder_attributes_are_typed_and_ordered(): void {
		$this->assertSame(
			array(
				'categoryOrder' => array( 7, 3 ),
				'headingLevel'  => 4.0,
				'dividerStyle'  => 'dashed',
				'showLabel'     => true,
			),
			WP_Logos\Builder_Attributes::convert(
				'yolo-logos/logo-categories',
				array(
					'categoryOrder' => '7,3,7,0',
					'headingLevel'  => '4',
					'showLabel'     => 'true',
					'dividerStyle'  => 'dashed',
					'unknown'       => 'ignored',
				)
			)
		);
		$this->assertSame(
			array( 'categoryId' => 12, 'columns' => array( 'mobile' => 1, 'tablet' => 3, 'laptop' => 4, 'desktop' => 4 ) ),
			WP_Logos\Builder_Attributes::convert(
				'yolo-logos/logo-showcase',
				array( 'categoryId' => '12', 'columns' => array( 'mobile' => '1', 'desktop' => '4' ) )
			)
		);
	}
}
