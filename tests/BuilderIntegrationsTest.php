<?php

class BuilderIntegrationsTest extends WP_UnitTestCase {

	public function test_wpbakery_elements_cover_all_displays(): void {
		$elements = WP_Logos\WPBakery::elements();
		$this->assertSame(
			array( 'yolo_logo_showcase', 'yolo_logo_categories', 'yolo_logo_full_cards' ),
			array_column( $elements, 'base' )
		);
		$this->assertStringContainsString( 'categoryOrder', wp_json_encode( $elements[1]['params'] ) );
		$this->assertStringContainsString( 'columns_desktop', wp_json_encode( $elements[0]['params'] ) );
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
		$category_param = array_column( $elements[0]['params'], null, 'param_name' )['categoryId'];
		$this->assertSame( (string) $category, $category_param['value']['Builder sponsors'] );

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
				'showLabel'     => true,
				'dividerStyle'  => 'dashed',
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
			array( 'categoryId' => 12, 'columns' => array( 'mobile' => 1, 'desktop' => 4 ) ),
			WP_Logos\Builder_Attributes::convert(
				'yolo-logos/logo-showcase',
				array( 'categoryId' => '12', 'columns' => array( 'mobile' => '1', 'desktop' => '4' ) )
			)
		);
	}
}
