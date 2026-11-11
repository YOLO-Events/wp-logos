<?php
/**
 * Shared Elementor integration.
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers widgets only when Elementor is active.
 */
class Elementor {

	/**
	 * Add the widget registration hook when Elementor is available.
	 */
	public function register(): void {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register the three display widgets.
	 *
	 * @param object $widgets_manager Elementor widget manager.
	 */
	public function register_widgets( $widgets_manager ): void {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}
		foreach ( Builder_Configs::for_editor( 'elementor' ) as $config ) {
			$widgets_manager->register( new Elementor_Logo_Widget( $config['display'] ) );
		}
	}
}

if ( class_exists( '\Elementor\Widget_Base' ) ) {
	/**
	 * Elementor widget backed by a registered dynamic block renderer.
	 */
	class Elementor_Logo_Widget extends \Elementor\Widget_Base {

		/**
		 * Display identifier.
		 *
		 * @var string
		 */
		private $display;

		/**
		 * @param string $display Display identifier.
		 */
		public function __construct( string $display ) {
			$this->display = $display;
			parent::__construct();
		}

		public function get_name(): string {
			return 'yolo-logo-' . $this->display;
		}

		public function get_title(): string {
			$titles = array(
				'showcase'   => __( 'Logo Showcase', 'yolo-logos' ),
				'categories' => __( 'Logo Categories', 'yolo-logos' ),
				'full-cards' => __( 'Logo Full Cards', 'yolo-logos' ),
			);
			return $titles[ $this->display ] ?? __( 'Logo Display', 'yolo-logos' );
		}

		public function get_icon(): string {
			return 'eicon-gallery-grid';
		}

		public function get_categories(): array {
			return array( 'general' );
		}

		protected function register_controls(): void {
			$this->start_controls_section(
				'content_section',
				array( 'label' => __( 'Display settings', 'yolo-logos' ) )
			);

			if ( 'categories' === $this->display ) {
				$this->add_control(
					'categoryOrder',
					array(
						'label'       => __( 'Categories (selection order)', 'yolo-logos' ),
						'type'        => \Elementor\Controls_Manager::SELECT2,
						'multiple'    => true,
						'options'     => $this->category_options( false ),
						'default'     => array(),
						'label_block' => true,
					)
				);
				$this->select_control( 'headingPosition', __( 'Heading position', 'yolo-logos' ), array( 'left' => __( 'Left', 'yolo-logos' ), 'top' => __( 'Top', 'yolo-logos' ) ), 'left' );
				$this->select_control( 'headingLevel', __( 'Heading level', 'yolo-logos' ), array( 2 => 'H2', 3 => 'H3', 4 => 'H4', 5 => 'H5', 6 => 'H6' ), 3 );
				$this->number_control( 'headingWidth', __( 'Heading width', 'yolo-logos' ), 200 );
				$this->number_control( 'categoryGap', __( 'Category gap', 'yolo-logos' ), 32 );
				$this->select_control( 'dividerStyle', __( 'Divider style', 'yolo-logos' ), array( 'none' => 'None', 'solid' => 'Solid', 'dashed' => 'Dashed', 'dotted' => 'Dotted' ), 'solid' );
				$this->number_control( 'dividerWidth', __( 'Divider width', 'yolo-logos' ), 1 );
				$this->color_control( 'dividerColor', __( 'Divider color', 'yolo-logos' ), '#e0e0e0' );
			} else {
				$this->add_control(
					'categoryId',
					array(
						'label'   => __( 'Category', 'yolo-logos' ),
						'type'    => \Elementor\Controls_Manager::SELECT,
						'options' => $this->category_options(),
						'default' => 0,
					)
				);
			}

			$this->select_control(
				'theme',
				__( 'Image theme', 'yolo-logos' ),
				array( 'standard' => 'Standard', 'light' => 'Light', 'dark' => 'Dark' ),
				'standard'
			);
			$this->select_control(
				'logoRatio',
				__( 'Logo sizing', 'yolo-logos' ),
				array( 'original' => 'Original', 'fixed' => 'Fixed' ),
				'original'
			);
			$this->number_control( 'logoMaxHeight', __( 'Maximum logo height', 'yolo-logos' ), 'full-cards' === $this->display ? 100 : 80 );
			$this->number_control( 'logoMaxWidth', __( 'Maximum logo width', 'yolo-logos' ), 'full-cards' === $this->display ? 220 : 160 );

			$columns = 'full-cards' === $this->display ? array( 1, 2, 3, 3 ) : array( 2, 3, 4, 5 );
			foreach ( array( 'mobile', 'tablet', 'laptop', 'desktop' ) as $index => $size ) {
				$this->number_control( 'columns_' . $size, ucfirst( $size ) . ' columns', $columns[ $index ], 1, 12 );
			}

			if ( 'showcase' === $this->display ) {
				$this->select_control( 'showcaseType', __( 'Layout', 'yolo-logos' ), array( 'grid' => 'Grid', 'carousel' => 'Carousel', 'flexbox' => 'Flexbox' ), 'grid' );
				$this->number_control( 'carouselSpeed', __( 'Carousel speed (ms)', 'yolo-logos' ), 3000 );
				$this->switch_control( 'carouselAutoplay', __( 'Carousel autoplay', 'yolo-logos' ), true );
				$this->switch_control( 'carouselInfinite', __( 'Infinite carousel', 'yolo-logos' ), true );
				$this->switch_control( 'carouselArrows', __( 'Show carousel arrows', 'yolo-logos' ), true );
				$this->switch_control( 'carouselDots', __( 'Show carousel dots', 'yolo-logos' ), true );
				$this->switch_control( 'carouselTicker', __( 'Ticker mode', 'yolo-logos' ), false );
				$this->number_control( 'carouselTickerSpeed', __( 'Ticker speed', 'yolo-logos' ), 1, 0.1, 10, 0.1 );
				$this->select_control( 'flexboxAlign', __( 'Flexbox alignment', 'yolo-logos' ), array( 'left' => 'Left', 'center' => 'Center', 'right' => 'Right' ), 'center' );
				$this->number_control( 'flexboxLogoWidth', __( 'Flexbox logo width', 'yolo-logos' ), 160 );
			}

			$this->number_control( 'gap', __( 'Gap', 'yolo-logos' ), 'full-cards' === $this->display ? 24 : 20 );
			$this->number_control( 'padding', __( 'Item padding', 'yolo-logos' ), 'full-cards' === $this->display ? 24 : 15 );
			$this->switch_control( 'showTitle', __( 'Show name', 'yolo-logos' ), 'full-cards' === $this->display );
			$this->switch_control( 'showLabel', __( 'Show label', 'yolo-logos' ), 'full-cards' === $this->display );
			$this->switch_control( 'grayscale', __( 'Grayscale logos', 'yolo-logos' ), false );
			$this->color_control( 'backgroundColor', __( 'Background color', 'yolo-logos' ), 'full-cards' === $this->display ? '#ffffff' : '' );
			$this->number_control( 'borderWidth', __( 'Border width', 'yolo-logos' ), 'full-cards' === $this->display ? 1 : 0 );
			$this->color_control( 'borderColor', __( 'Border color', 'yolo-logos' ), '#e0e0e0' );
			$this->number_control( 'borderRadius', __( 'Border radius', 'yolo-logos' ), 'full-cards' === $this->display ? 8 : 0 );
			$this->end_controls_section();
		}

		protected function render(): void {
			$attributes = $this->get_settings_for_display();
			if ( isset( $attributes['columns'] ) && is_array( $attributes['columns'] ) ) {
				$columns = $attributes['columns'];
			} else {
				$columns = array();
				foreach ( array( 'mobile', 'tablet', 'laptop', 'desktop' ) as $size ) {
					if ( isset( $attributes[ 'columns_' . $size ] ) ) {
						$columns[ $size ] = $attributes[ 'columns_' . $size ];
					}
				}
			}
			$attributes['columns'] = $columns;

			$block_names = array(
				'showcase'   => 'yolo-logos/logo-showcase',
				'categories' => 'yolo-logos/logo-categories',
				'full-cards' => 'yolo-logos/logo-full-cards',
			);
			echo render_block(
				array(
					'blockName'    => $block_names[ $this->display ],
					'attrs'        => Builder_Attributes::convert( $block_names[ $this->display ], $attributes ),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the registered block renderer escapes dynamic output.
		}

		private function category_options( bool $include_all = true ): array {
			$options = $include_all ? array( 0 => __( 'All categories', 'yolo-logos' ) ) : array();
			$terms   = get_terms(
				array(
					'taxonomy'   => 'wp_logo_category',
					'hide_empty' => false,
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$options[ $term->term_id ] = $term->name;
				}
			}
			return $options;
		}

		private function select_control( string $name, string $label, array $options, $default ): void {
			$this->add_control( $name, array( 'label' => $label, 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $options, 'default' => $default ) );
		}

		private function number_control( string $name, string $label, $default, $min = 0, $max = 2000, $step = 1 ): void {
			$this->add_control(
				$name,
				array(
					'label'   => $label,
					'type'    => \Elementor\Controls_Manager::NUMBER,
					'default' => $default,
					'min'     => $min,
					'max'     => $max,
					'step'    => $step,
				)
			);
		}

		private function switch_control( string $name, string $label, bool $default ): void {
			$this->add_control( $name, array( 'label' => $label, 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'true', 'default' => $default ? 'true' : '' ) );
		}

		private function color_control( string $name, string $label, string $default ): void {
			$this->add_control( $name, array( 'label' => $label, 'type' => \Elementor\Controls_Manager::COLOR, 'default' => $default ) );
		}
	}
}
