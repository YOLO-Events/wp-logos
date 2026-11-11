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
