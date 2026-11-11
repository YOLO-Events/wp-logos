<?php

class LogoCategoriesTest extends WP_UnitTestCase {
	private function category( string $name, int $parent = 0 ): int {
		return self::factory()->term->create(
			array( 'taxonomy' => 'wp_logo_category', 'name' => $name, 'parent' => $parent )
		);
	}

	private function logo( int $category, string $title, array $meta = array(), string $status = 'publish' ): int {
		$image = self::factory()->post->create(
			array(
				'post_type' => 'attachment',
				'post_mime_type' => 'image/png',
				'guid' => 'https://example.org/' . sanitize_title( $title ) . '.png',
				'meta_input' => array(
					'_wp_attached_file' => sanitize_title( $title ) . '.png',
					'_wp_attachment_metadata' => array(
						'file' => sanitize_title( $title ) . '.png',
						'width' => 320,
						'height' => 160,
					),
				),
			)
		);
		$id = self::factory()->post->create(
			array(
				'post_type' => 'wp_logo',
				'post_status' => $status,
				'post_title' => $title,
				'meta_input' => array_merge( array( '_logo_main_id' => $image ), $meta ),
			)
		);
		wp_set_object_terms( $id, array( $category ), 'wp_logo_category' );
		return $id;
	}

	private function render( array $attributes ): string {
		return render_block(
			array(
				'blockName' => 'yolo-logos/logo-categories',
				'attrs' => $attributes,
				'innerBlocks' => array(),
				'innerHTML' => '',
				'innerContent' => array(),
			)
		);
	}

	public function test_registered_with_saved_defaults(): void {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( 'yolo-logos/logo-categories' );
		$this->assertNotNull( $type );
		$this->assertTrue( $type->is_dynamic() );
		$this->assertSame( array(), $type->attributes['categoryOrder']['default'] );
	}

	public function test_selection_order_and_empty_categories(): void {
		$first = $this->category( 'First sponsors' );
		$second = $this->category( 'Second sponsors' );
		$empty = $this->category( 'Empty sponsors' );
		$draft = $this->category( 'Draft sponsors' );
		$missing = $this->category( 'Missing image sponsors' );
		$this->logo( $first, 'First logo' );
		$this->logo( $second, 'Second logo' );
		$this->logo( $draft, 'Unpublished logo', array(), 'draft' );
		$missing_logo = $this->logo( $missing, 'No image' );
		delete_post_meta( $missing_logo, '_logo_main_id' );
		$html = $this->render(
			array( 'categoryOrder' => array( $second, $empty, $draft, $missing, $first, $second, 999999 ) )
		);
		$this->assertSame( 2, substr_count( $html, '<section ' ) );
		$this->assertLessThan( strpos( $html, 'First sponsors' ), strpos( $html, 'Second sponsors' ) );
		$this->assertStringNotContainsString( 'Empty sponsors', $html );
		$this->assertStringNotContainsString( 'Unpublished logo', $html );
		$this->assertStringNotContainsString( 'Missing image sponsors', $html );
		$html = $this->render( array( 'categoryOrder' => array( $second ) ) );
		$this->assertStringNotContainsString( 'First sponsors', $html );
	}

	public function test_child_categories_are_not_duplicated_under_parent(): void {
		$parent = $this->category( 'Parent category' );
		$child = $this->category( 'Child category', $parent );
		$this->logo( $child, 'Child logo' );
		$html = $this->render( array( 'categoryOrder' => array( $parent, $child ) ) );
		$this->assertStringNotContainsString( 'Parent category', $html );
		$this->assertSame( 1, substr_count( $html, '<img ' ) );
		$legacy = render_block(
			array(
				'blockName' => 'yolo-logos/logo-showcase',
				'attrs' => array( 'categoryId' => $parent ),
				'innerBlocks' => array(),
				'innerHTML' => '',
				'innerContent' => array(),
			)
		);
		$this->assertSame( 1, substr_count( $legacy, '<img ' ) );
	}

	public function test_logo_order_variants_and_appearance(): void {
		$category = $this->category( 'Partners & friends' );
		$first = $this->logo( $category, 'Earlier logo', array( '_logo_order' => 1, '_logo_url' => 'https://example.org/partner', '_logo_alt' => 'Custom alt' ) );
		$this->logo( $category, 'Later logo', array( '_logo_order' => 10 ) );
		$variant = get_post_meta( $first, '_logo_main_id', true );
		update_post_meta( $first, '_logo_light_id', $variant );
		delete_post_meta( $first, '_logo_main_id' );
		$html = $this->render(
			array(
				'categoryOrder' => array( $category ),
				'headingPosition' => 'top',
				'headingLevel' => 2,
				'dividerStyle' => 'dashed',
				'dividerWidth' => 3,
				'dividerColor' => '#123456',
				'theme' => 'light',
				'grayscale' => true,
				'showTitle' => true,
				'logoRatio' => 'fixed',
			)
		);
		$this->assertStringContainsString( 'wp-logos-headings-top', $html );
		$this->assertStringContainsString( '<h2 ', $html );
		$this->assertStringContainsString( 'Partners &amp; friends', $html );
		$this->assertStringContainsString( '--wp-logos-divider-style:dashed', $html );
		$this->assertStringContainsString( '--wp-logos-divider-width:3px', $html );
		$this->assertStringContainsString( '--wp-logos-divider-color:#123456', $html );
		$this->assertStringContainsString( 'wp-logos-grayscale', $html );
		$this->assertStringContainsString( 'alt="Custom alt"', $html );
		$this->assertStringContainsString( 'rel="noopener noreferrer"', $html );
		$this->assertLessThan( strpos( $html, 'Later logo' ), strpos( $html, 'Earlier logo' ) );
	}

	public function test_logo_labels_are_optional_and_displayed_when_enabled(): void {
		$category = $this->category( 'Nutrition partners' );
		$this->logo( $category, 'Example nutrition', array( '_logo_label' => 'Official Nutrition' ) );

		$html = $this->render( array( 'categoryOrder' => array( $category ), 'showTitle' => true ) );
		$this->assertStringContainsString( 'Example nutrition', $html );
		$this->assertStringNotContainsString( 'Official Nutrition', $html );

		$html = $this->render( array( 'categoryOrder' => array( $category ), 'showLabel' => true ) );
		$this->assertStringContainsString( '<span class="wp-logos-label">Official Nutrition</span>', $html );
	}

	public function test_full_cards_block_is_registered_and_renders_cards(): void {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( 'yolo-logos/logo-full-cards' );
		$this->assertNotNull( $type );
		$this->assertTrue( $type->is_dynamic() );
		$this->assertTrue( $type->attributes['showTitle']['default'] );
		$this->assertTrue( $type->attributes['showLabel']['default'] );

		$category = $this->category( 'Full card sponsors' );
		$this->logo( $category, 'Card partner', array( '_logo_label' => 'Official Nutrition' ) );
		$html = render_block(
			array(
				'blockName' => 'yolo-logos/logo-full-cards',
				'attrs' => array(
					'categoryId' => $category,
					'showTitle' => true,
					'showLabel' => true,
				),
				'innerBlocks' => array(),
				'innerHTML' => '',
				'innerContent' => array(),
			)
		);
		$this->assertStringContainsString( 'wp-logos-full-cards', $html );
		$this->assertStringContainsString( 'Card partner', $html );
		$this->assertStringContainsString( '<span class="wp-logos-label">Official Nutrition</span>', $html );
	}

	public function test_full_cards_show_details_in_order_and_respect_visibility_settings(): void {
		$category = $this->category( 'Partners & friends' );
		$second_category = $this->category( 'Nutrition' );
		$id = $this->logo(
			$category,
			'Card & partner',
			array(
				'_logo_label' => 'Official <partner>',
				'_logo_url' => 'https://www.example.org/path?source=logos',
			)
		);
		wp_set_object_terms( $id, array( $category, $second_category ), 'wp_logo_category' );
		wp_update_post(
			array(
				'ID' => $id,
				'post_content' => '<p>Our <strong>description</strong> with <a href="https://example.net">a link</a>.</p><script>alert("bad")</script>',
			)
		);
		$block = array(
			'blockName' => 'yolo-logos/logo-full-cards',
			'attrs' => array( 'categoryId' => $category ),
			'innerBlocks' => array(),
			'innerHTML' => '',
			'innerContent' => array(),
		);
		$html = render_block( $block );
		$this->assertStringContainsString( 'Partners &amp; friends', $html );
		$this->assertStringContainsString( 'Nutrition', $html );
		$this->assertStringContainsString( 'Official &lt;partner&gt;', $html );
		$this->assertStringContainsString( '<h3 class="wp-logos-title">Card &amp; partner</h3>', $html );
		$this->assertStringContainsString( '<strong>description</strong>', $html );
		$this->assertStringContainsString( '<a href="https://example.net">a link</a>', $html );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '<a href="https://www.example.org/path?source=logos" class="wp-logos-card-website" target="_blank" rel="noopener noreferrer">www.example.org</a>', $html );
		$this->assertLessThan( strpos( $html, 'wp-logos-card-categories' ), strpos( $html, '<img ' ) );
		$this->assertLessThan( strpos( $html, 'wp-logos-label' ), strpos( $html, 'wp-logos-card-categories' ) );
		$this->assertLessThan( strpos( $html, 'wp-logos-title' ), strpos( $html, 'wp-logos-label' ) );
		$this->assertLessThan( strpos( $html, 'wp-logos-card-description' ), strpos( $html, 'wp-logos-title' ) );
		$this->assertLessThan( strpos( $html, 'wp-logos-card-website' ), strpos( $html, 'wp-logos-card-description' ) );

		$block['attrs']['showTitle'] = false;
		$block['attrs']['showLabel'] = false;
		$html = render_block( $block );
		$this->assertStringNotContainsString( 'wp-logos-title', $html );
		$this->assertStringNotContainsString( 'wp-logos-label', $html );
		$this->assertStringContainsString( 'wp-logos-card-description', $html );
	}

	public function test_full_card_omits_missing_details_and_invalid_website(): void {
		$category = $this->category( 'No website' );
		$id = $this->logo( $category, 'No details', array( '_logo_url' => 'mailto:test@example.org' ) );
		wp_set_object_terms( $id, array(), 'wp_logo_category' );
		$html = render_block(
			array(
				'blockName' => 'yolo-logos/logo-full-cards',
				'attrs' => array(),
				'innerBlocks' => array(),
				'innerHTML' => '',
				'innerContent' => array(),
			)
		);
		$this->assertStringNotContainsString( 'wp-logos-card-categories', $html );
		$this->assertStringNotContainsString( 'wp-logos-label', $html );
		$this->assertStringNotContainsString( 'wp-logos-card-description', $html );
		$this->assertStringNotContainsString( 'wp-logos-card-website', $html );
	}

	public function test_empty_selection_and_unusable_variant(): void {
		$this->assertStringNotContainsString( '<section ', $this->render( array() ) );
		$category = $this->category( 'Light only' );
		$id = $this->logo( $category, 'Light logo' );
		update_post_meta( $id, '_logo_light_id', get_post_meta( $id, '_logo_main_id', true ) );
		delete_post_meta( $id, '_logo_main_id' );
		$this->assertStringNotContainsString( '<section ', $this->render( array( 'categoryOrder' => array( $category ), 'theme' => 'dark' ) ) );
		$this->assertStringContainsString( '<section ', $this->render( array( 'categoryOrder' => array( $category ), 'theme' => 'light' ) ) );
	}
}
