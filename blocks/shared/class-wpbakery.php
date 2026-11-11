<?php
/**
 * Shared WPBakery integration.
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the logo displays as WPBakery elements.
 */
class WPBakery {

	/**
	 * Register elements only when WPBakery is available.
	 */
	public function register(): void {
		if ( ! function_exists( 'vc_map' ) ) {
			return;
		}

		add_filter( 'vc_autocomplete_yolo_logo_categories_categoryOrder_callback', array( self::class, 'suggest_categories' ) );
		add_filter( 'vc_autocomplete_yolo_logo_categories_categoryOrder_render', array( self::class, 'render_category' ) );

		foreach ( self::elements() as $element ) {
			vc_map( $element );
		}
		foreach ( Builder_Configs::for_editor( 'wpbakery' ) as $config ) {
			$block_name = $config['block'];
			add_shortcode(
				$config['shortcode'],
				static function ( $attributes = array(), $content = '' ) use ( $block_name ) {
					return self::render( is_array( $attributes ) ? $attributes : array(), (string) $content, $block_name );
				}
			);
		}
	}

	/**
	 * WPBakery element definitions.
	 *
	 * @return array
	 */
	public static function elements(): array {
		$elements = array();
		foreach ( Builder_Configs::for_editor( 'wpbakery' ) as $config ) {
			$params = array(
				'showcase'   => self::showcase_params(),
				'categories' => self::categories_params(),
				'full-cards' => self::full_cards_params(),
			)[ $config['display'] ] ?? array();
			$elements[] = self::element( $config['shortcode'], $config['title'], $params );
		}
		return $elements;
	}

	/**
	 * Render an element through its registered dynamic block renderer.
	 *
	 * @param array  $attributes Shortcode attributes.
	 * @param string $content    Inner shortcode content.
	 * @param string $block_name Block name.
	 * @return string
	 */
	public static function render( array $attributes, string $content, string $block_name ): string {
		if ( isset( $attributes['columns'] ) && is_array( $attributes['columns'] ) ) {
			$attributes['columns'] = $attributes['columns'];
		} else {
			$attributes['columns'] = array();
			foreach ( array( 'mobile', 'tablet', 'laptop', 'desktop' ) as $size ) {
				if ( isset( $attributes[ 'columns_' . $size ] ) ) {
					$attributes['columns'][ $size ] = $attributes[ 'columns_' . $size ];
				}
			}
		}
		return render_block(
			array(
				'blockName'    => $block_name,
				'attrs'        => Builder_Attributes::convert( $block_name, $attributes ),
				'innerBlocks'  => array(),
				'innerHTML'    => $content,
				'innerContent' => array( $content ),
			)
		);
	}

	/**
	 * Element metadata and parameter definitions.
	 *
	 * @param string $base       Shortcode name.
	 * @param string $name       Element name.
	 * @param array  $params     Element parameters.
	 * @return array
	 */
	private static function element( string $base, string $name, array $params ): array {
		return array(
			'name'        => __( $name, 'yolo-logos' ),
			'base'        => $base,
			'category'    => __( 'YOLO Logos', 'yolo-logos' ),
			'description' => __( 'Display logos using the YOLO Logos block renderer.', 'yolo-logos' ),
			'params'      => $params,
		);
	}

	/**
	 * Category terms for selection controls.
	 *
	 * @return array
	 */
	private static function category_options(): array {
		$options = array( __( 'All categories', 'yolo-logos' ) => '0' );
		$terms   = get_terms(
			array(
				'taxonomy'   => 'wp_logo_category',
				'hide_empty' => false,
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->name ] = (string) $term->term_id;
			}
		}
		return $options;
	}

	/**
	 * Find logo categories for WPBakery's autocomplete control.
	 *
	 * @param string $query Search text.
	 * @return array
	 */
	public static function suggest_categories( string $query ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'wp_logo_category',
				'hide_empty' => false,
				'search'     => $query,
			)
		);
		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$results = array();
		foreach ( $terms as $term ) {
			$results[] = array(
				'value' => (string) $term->term_id,
				'label' => $term->name,
			);
		}
		return $results;
	}

	/**
	 * Resolve a saved category ID for WPBakery's autocomplete control.
	 *
	 * @param array $value Saved autocomplete value.
	 * @return array|false
	 */
	public static function render_category( array $value ) {
		$term = get_term( absint( $value['value'] ?? 0 ), 'wp_logo_category' );
		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}

		return array(
			'value' => (string) $term->term_id,
			'label' => $term->name,
		);
	}

	/**
	 * Base setting definitions for showcase and full cards.
	 *
	 * @param bool $full_cards Whether the settings target full cards.
	 * @return array
	 */
	private static function common_params( bool $full_cards = false ): array {
		$params = array(
			self::select_param( 'categoryId', 'Category', self::category_options() ),
			self::select_param( 'theme', 'Image theme', array( 'Standard' => 'standard', 'Light' => 'light', 'Dark' => 'dark' ) ),
			self::select_param( 'logoRatio', 'Logo sizing', array( 'Original' => 'original', 'Fixed' => 'fixed' ) ),
			self::number_param( 'logoMaxHeight', 'Maximum logo height', $full_cards ? 100 : 80 ),
			self::number_param( 'logoMaxWidth', 'Maximum logo width', $full_cards ? 220 : 160 ),
			self::number_param( 'gap', 'Gap', $full_cards ? 24 : 20 ),
			self::number_param( 'padding', 'Item padding', $full_cards ? 24 : 15 ),
			self::toggle_param( 'showTitle', 'Show name', $full_cards ),
			self::toggle_param( 'showLabel', 'Show label', $full_cards ),
			self::toggle_param( 'grayscale', 'Grayscale logos', false ),
			self::color_param( 'backgroundColor', 'Background color' ),
			self::number_param( 'borderWidth', 'Border width', $full_cards ? 1 : 0 ),
			self::color_param( 'borderColor', 'Border color', '#e0e0e0' ),
			self::number_param( 'borderRadius', 'Border radius', $full_cards ? 8 : 0 ),
		);

		foreach ( self::column_params( $full_cards ) as $param ) {
			$params[] = $param;
		}

		return $params;
	}

	private static function showcase_params(): array {
		$params   = self::common_params();
		$params[] = self::select_param( 'showcaseType', 'Layout', array( 'Grid' => 'grid', 'Carousel' => 'carousel', 'Flexbox' => 'flexbox' ) );
		$params[] = self::number_param( 'carouselSpeed', 'Carousel speed (ms)', 3000 );
		$params[] = self::toggle_param( 'carouselAutoplay', 'Carousel autoplay', true );
		$params[] = self::toggle_param( 'carouselInfinite', 'Infinite carousel', true );
		$params[] = self::toggle_param( 'carouselArrows', 'Show carousel arrows', true );
		$params[] = self::toggle_param( 'carouselDots', 'Show carousel dots', true );
		$params[] = self::toggle_param( 'carouselTicker', 'Ticker mode', false );
		$params[] = self::number_param( 'carouselTickerSpeed', 'Ticker speed', 1 );
		$params[] = self::select_param( 'flexboxAlign', 'Flexbox alignment', array( 'Left' => 'left', 'Center' => 'center', 'Right' => 'right' ) );
		$params[] = self::number_param( 'flexboxLogoWidth', 'Flexbox logo width', 160 );
		return $params;
	}

	private static function categories_params(): array {
		$params = array(
			array(
				'type'        => 'autocomplete',
				'heading'     => __( 'Categories (selection order)', 'yolo-logos' ),
				'param_name'  => 'categoryOrder',
				'settings'    => array(
					'multiple'      => true,
					'sortable'      => true,
					'unique_values' => true,
					'min_length'    => 1,
				),
				'description' => __( 'Search for categories and drag them into the order they should appear.', 'yolo-logos' ),
			),
			self::select_param( 'headingPosition', 'Heading position', array( 'Left' => 'left', 'Top' => 'top' ) ),
			self::select_param( 'headingLevel', 'Heading level', array( 'H2' => 2, 'H3' => 3, 'H4' => 4, 'H5' => 5, 'H6' => 6 ) ),
			self::number_param( 'headingWidth', 'Heading width', 200 ),
			self::number_param( 'categoryGap', 'Category gap', 32 ),
			self::select_param( 'dividerStyle', 'Divider style', array( 'None' => 'none', 'Solid' => 'solid', 'Dashed' => 'dashed', 'Dotted' => 'dotted' ) ),
			self::number_param( 'dividerWidth', 'Divider width', 1 ),
			self::color_param( 'dividerColor', 'Divider color', '#e0e0e0' ),
		);
		$shared = array_filter(
			self::common_params(),
			static function ( $param ) {
				return 'categoryId' !== ( $param['param_name'] ?? '' );
			}
		);
		return array_merge( $params, array_values( $shared ) );
	}

	private static function full_cards_params(): array {
		return self::common_params( true );
	}

	private static function column_params( bool $full_cards ): array {
		$defaults = $full_cards ? array( 1, 2, 3, 3 ) : array( 2, 3, 4, 5 );
		$params   = array();
		foreach ( array( 'mobile', 'tablet', 'laptop', 'desktop' ) as $index => $size ) {
			$params[] = self::number_param( 'columns_' . $size, ucfirst( $size ) . ' columns', $defaults[ $index ] );
		}
		return $params;
	}

	private static function select_param( string $name, string $heading, array $options ): array {
		return array( 'type' => 'dropdown', 'heading' => __( $heading, 'yolo-logos' ), 'param_name' => $name, 'value' => $options );
	}

	private static function number_param( string $name, string $heading, $default ): array {
		return array( 'type' => 'textfield', 'heading' => __( $heading, 'yolo-logos' ), 'param_name' => $name, 'std' => $default );
	}

	private static function toggle_param( string $name, string $heading, bool $default ): array {
		return array( 'type' => 'checkbox', 'heading' => __( $heading, 'yolo-logos' ), 'param_name' => $name, 'value' => array( __( 'Yes', 'yolo-logos' ) => 'true' ), 'std' => $default ? 'true' : '' );
	}

	private static function color_param( string $name, string $heading, string $default = '' ): array {
		return array( 'type' => 'colorpicker', 'heading' => __( $heading, 'yolo-logos' ), 'param_name' => $name, 'std' => $default );
	}
}
