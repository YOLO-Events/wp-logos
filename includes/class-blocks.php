<?php
/**
 * Gutenberg Blocks
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the logo-showcase Gutenberg block.
 */
class Blocks {

	/**
	 * Register blocks.
	 */
	public function register(): void {
		$block_dir = WP_LOGOS_PLUGIN_DIR . 'build/logo-showcase';

		if ( ! file_exists( $block_dir . '/block.json' ) ) {
			return;
		}

		register_block_type(
			$block_dir,
			array(
				'render_callback' => array( $this, 'render_logo_showcase' ),
			)
		);

		// Pass data to the block editor.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Enqueue additional data for the block editor.
	 */
	public function enqueue_editor_assets(): void {
		// Provide REST URL and nonce to any block scripts that need them.
		$handle = 'wp-logos-logo-showcase-editor-script';
		if ( wp_script_is( $handle, 'registered' ) ) {
			wp_localize_script(
				$handle,
				'wpLogosData',
				array(
					'restUrl'   => esc_url_raw( rest_url() ),
					'nonce'     => wp_create_nonce( 'wp_rest' ),
					'pluginUrl' => WP_LOGOS_PLUGIN_URL,
				)
			);
		}
	}

	/**
	 * Server-side render callback for the logo-showcase block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block inner content.
	 * @param \WP_Block $block     Block instance.
	 *
	 * @return string Rendered HTML.
	 */
	public function render_logo_showcase( array $attributes, string $content, \WP_Block $block ): string {
		// Enqueue public assets on first render (works with FSE / template parts).
		wp_enqueue_style(
			'wp-logos-public',
			WP_LOGOS_PLUGIN_URL . 'public/css/wp-logos-public.css',
			array(),
			WP_LOGOS_VERSION
		);
		wp_enqueue_script(
			'wp-logos-public',
			WP_LOGOS_PLUGIN_URL . 'public/js/wp-logos-public.js',
			array(),
			WP_LOGOS_VERSION,
			true
		);

		$category_id   = absint( $attributes['categoryId'] ?? 0 );
		$showcase_type = sanitize_key( $attributes['showcaseType'] ?? 'grid' );
		$theme         = sanitize_key( $attributes['theme'] ?? 'standard' );

		$logos = $this->get_logos( $category_id );

		if ( empty( $logos ) ) {
			return sprintf(
				'<div class="wp-logos-empty">%s</div>',
				esc_html__( 'No logos found. Add logos in the WP Logos admin panel.', 'wp-logos' )
			);
		}

		// Build the showcase ID for JS initialisation.
		$showcase_id = 'wp-logos-' . wp_unique_id();

		// Encode attributes for the data attribute (used by frontend JS).
		$data_attrs = esc_attr(
			wp_json_encode(
				array(
					'type'               => $showcase_type,
					'theme'              => $theme,
					'autoplay'           => (bool) ( $attributes['carouselAutoplay'] ?? true ),
					'autoplaySpeed'      => absint( $attributes['carouselSpeed'] ?? 3000 ),
					'infinite'           => (bool) ( $attributes['carouselInfinite'] ?? true ),
					'arrows'             => (bool) ( $attributes['carouselArrows'] ?? true ),
					'dots'               => (bool) ( $attributes['carouselDots'] ?? true ),
					'ticker'             => (bool) ( $attributes['carouselTicker'] ?? false ),
					'tickerSpeed'        => (float) ( $attributes['carouselTickerSpeed'] ?? 1 ),
					'columns'            => $attributes['columns'] ?? array(
						'mobile'  => 2,
						'tablet'  => 3,
						'laptop'  => 4,
						'desktop' => 5,
					),
					'gap'                => absint( $attributes['gap'] ?? 20 ),
					'logoMaxHeight'      => absint( $attributes['logoMaxHeight'] ?? 80 ),
					'logoMaxWidth'       => absint( $attributes['logoMaxWidth'] ?? 160 ),
					'padding'            => absint( $attributes['padding'] ?? 15 ),
					'borderWidth'        => absint( $attributes['borderWidth'] ?? 0 ),
					'borderRadius'       => absint( $attributes['borderRadius'] ?? 0 ),
					'borderColor'        => sanitize_hex_color( $attributes['borderColor'] ?? '#e0e0e0' ),
					'backgroundColor'    => sanitize_hex_color( $attributes['backgroundColor'] ?? '' ),
					'grayscale'          => (bool) ( $attributes['grayscale'] ?? false ),
					'showTitle'          => (bool) ( $attributes['showTitle'] ?? false ),
					'flexboxAlign'       => sanitize_key( $attributes['flexboxAlign'] ?? 'center' ),
					'flexboxLogoWidth'   => absint( $attributes['flexboxLogoWidth'] ?? 160 ),
				)
			)
		);

		$custom_css = wp_strip_all_tags( $attributes['customCSS'] ?? '' );

		// Build CSS custom properties.
		$inline_style = $this->build_inline_style( $attributes );

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $showcase_id ); ?>"
			class="wp-logos-showcase wp-logos-<?php echo esc_attr( $showcase_type ); ?> wp-logos-theme-<?php echo esc_attr( $theme ); ?>"
			data-wp-logos="<?php echo $data_attrs; ?>"
			style="<?php echo esc_attr( $inline_style ); ?>"
		>
			<?php if ( 'carousel' === $showcase_type ) : ?>
				<div class="wp-logos-carousel-wrapper">
					<div class="wp-logos-carousel-track">
						<?php foreach ( $logos as $logo ) : ?>
							<?php echo $this->render_logo_item( $logo, $attributes ); ?>
						<?php endforeach; ?>
					</div>
					<?php if ( ! empty( $attributes['carouselArrows'] ) && ! ( $attributes['carouselTicker'] ?? false ) ) : ?>
						<button class="wp-logos-arrow wp-logos-arrow-prev" aria-label="<?php esc_attr_e( 'Previous', 'wp-logos' ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
						</button>
						<button class="wp-logos-arrow wp-logos-arrow-next" aria-label="<?php esc_attr_e( 'Next', 'wp-logos' ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
						</button>
					<?php endif; ?>
					<?php if ( ! empty( $attributes['carouselDots'] ) && ! ( $attributes['carouselTicker'] ?? false ) ) : ?>
						<div class="wp-logos-dots" role="tablist" aria-label="<?php esc_attr_e( 'Carousel navigation', 'wp-logos' ); ?>"></div>
					<?php endif; ?>
				</div>
			<?php elseif ( 'flexbox' === $showcase_type ) : ?>
				<div class="wp-logos-flexbox-wrapper">
					<?php foreach ( $logos as $logo ) : ?>
						<?php echo $this->render_logo_item( $logo, $attributes ); ?>
					<?php endforeach; ?>
				</div>
			<?php else : // grid ?>
				<div class="wp-logos-grid-wrapper">
					<?php foreach ( $logos as $logo ) : ?>
						<?php echo $this->render_logo_item( $logo, $attributes ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $custom_css ) ) : ?>
				<style>
					#<?php echo esc_html( $showcase_id ); ?> {
						<?php echo wp_strip_all_tags( $custom_css ); ?>
					}
				</style>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single logo item.
	 *
	 * @param array $logo       Logo data array.
	 * @param array $attributes Block attributes.
	 *
	 * @return string HTML string.
	 */
	private function render_logo_item( array $logo, array $attributes ): string {
		$theme      = sanitize_key( $attributes['theme'] ?? 'standard' );
		$show_title = (bool) ( $attributes['showTitle'] ?? false );
		$grayscale  = (bool) ( $attributes['grayscale'] ?? false );
		$max_height = absint( $attributes['logoMaxHeight'] ?? 80 );
		$max_width  = absint( $attributes['logoMaxWidth'] ?? 160 );
		$padding    = absint( $attributes['padding'] ?? 15 );
		$ratio      = sanitize_key( $attributes['logoRatio'] ?? 'original' );

		// Pick the appropriate image based on theme.
		$img_url = $this->get_logo_image_url( $logo, $theme );

		if ( empty( $img_url ) ) {
			return '';
		}

		$img_classes = array( 'wp-logos-img' );
		if ( $grayscale ) {
			$img_classes[] = 'wp-logos-grayscale';
		}

		$img_style = '';
		if ( 'fixed' === $ratio ) {
			$img_style = sprintf( 'width:%dpx;height:%dpx;object-fit:contain;', $max_width, $max_height );
		} else {
			$img_style = sprintf( 'max-height:%dpx;max-width:%dpx;', $max_height, $max_width );
		}

		$alt = ! empty( $logo['alt'] ) ? $logo['alt'] : $logo['title'];

		$inner_html = sprintf(
			'<img src="%s" alt="%s" class="%s" style="%s" loading="lazy" />',
			esc_url( $img_url ),
			esc_attr( $alt ),
			esc_attr( implode( ' ', $img_classes ) ),
			esc_attr( $img_style )
		);

		if ( $show_title ) {
			$inner_html .= sprintf(
				'<span class="wp-logos-title">%s</span>',
				esc_html( $logo['title'] )
			);
		}

		$item_style = sprintf( 'padding:%dpx;', $padding );

		if ( ! empty( $logo['url'] ) ) {
			$item_content = sprintf(
				'<a href="%s" class="wp-logos-link" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( $logo['url'] ),
				$inner_html
			);
		} else {
			$item_content = $inner_html;
		}

		return sprintf(
			'<div class="wp-logos-item" style="%s">%s</div>',
			esc_attr( $item_style ),
			$item_content
		);
	}

	/**
	 * Get the logo image URL for the given theme.
	 *
	 * @param array  $logo  Logo data.
	 * @param string $theme Theme slug.
	 *
	 * @return string Image URL or empty string.
	 */
	private function get_logo_image_url( array $logo, string $theme ): string {
		if ( 'light' === $theme && ! empty( $logo['light_url'] ) ) {
			return $logo['light_url'];
		}
		if ( 'dark' === $theme && ! empty( $logo['dark_url'] ) ) {
			return $logo['dark_url'];
		}
		// Fallback to main URL.
		return $logo['main_url'] ?? '';
	}

	/**
	 * Build inline CSS custom properties from block attributes.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string CSS string.
	 */
	private function build_inline_style( array $attributes ): string {
		$parts = array();

		$columns = $attributes['columns'] ?? array(
			'mobile'  => 2,
			'tablet'  => 3,
			'laptop'  => 4,
			'desktop' => 5,
		);

		$parts[] = '--wp-logos-cols-mobile:'  . absint( $columns['mobile'] ?? 2 );
		$parts[] = '--wp-logos-cols-tablet:'  . absint( $columns['tablet'] ?? 3 );
		$parts[] = '--wp-logos-cols-laptop:'  . absint( $columns['laptop'] ?? 4 );
		$parts[] = '--wp-logos-cols-desktop:' . absint( $columns['desktop'] ?? 5 );
		$parts[] = '--wp-logos-gap:'          . absint( $attributes['gap'] ?? 20 ) . 'px';
		$parts[] = '--wp-logos-border-width:' . absint( $attributes['borderWidth'] ?? 0 ) . 'px';
		$parts[] = '--wp-logos-border-radius:' . absint( $attributes['borderRadius'] ?? 0 ) . 'px';
		$parts[] = '--wp-logos-logo-max-height:' . absint( $attributes['logoMaxHeight'] ?? 80 ) . 'px';
		$parts[] = '--wp-logos-logo-max-width:'  . absint( $attributes['logoMaxWidth'] ?? 160 ) . 'px';
		$parts[] = '--wp-logos-flexbox-logo-width:' . absint( $attributes['flexboxLogoWidth'] ?? 160 ) . 'px';

		if ( ! empty( $attributes['borderColor'] ) ) {
			$parts[] = '--wp-logos-border-color:' . sanitize_hex_color( $attributes['borderColor'] );
		}
		if ( ! empty( $attributes['backgroundColor'] ) ) {
			$parts[] = '--wp-logos-item-bg:' . sanitize_hex_color( $attributes['backgroundColor'] );
		}
		if ( ! empty( $attributes['flexboxAlign'] ) ) {
			$map  = array(
				'left'   => 'flex-start',
				'center' => 'center',
				'right'  => 'flex-end',
			);
			$val  = $map[ $attributes['flexboxAlign'] ] ?? 'center';
			$parts[] = '--wp-logos-flexbox-justify:' . $val;
		}

		// Ticker speed.
		if ( ! empty( $attributes['carouselTicker'] ) ) {
			$ticker_duration = max( 0.1, (float) ( $attributes['carouselTickerSpeed'] ?? 1 ) );
			$parts[] = '--wp-logos-ticker-speed:' . $ticker_duration . 's';
		}

		return implode( ';', $parts );
	}

	/**
	 * Fetch logos for a given category.
	 *
	 * @param int $category_id Taxonomy term ID, or 0 for all logos.
	 *
	 * @return array Array of logo data arrays.
	 */
	private function get_logos( int $category_id ): array {
		$query_args = array(
			'post_type'      => 'wp_logo',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'meta_value_num' => 'ASC',
				'menu_order'     => 'ASC',
				'title'          => 'ASC',
			),
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_logo_order',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_logo_order',
					'value'   => 0,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
		);

		if ( $category_id > 0 ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'wp_logo_category',
					'field'    => 'term_id',
					'terms'    => $category_id,
				),
			);
		}

		$posts = get_posts( $query_args );
		$logos = array();

		foreach ( $posts as $post ) {
			$main_id  = (int) get_post_meta( $post->ID, '_logo_main_id', true );
			$light_id = (int) get_post_meta( $post->ID, '_logo_light_id', true );
			$dark_id  = (int) get_post_meta( $post->ID, '_logo_dark_id', true );

			// Use featured image as fallback for main logo.
			if ( ! $main_id ) {
				$main_id = (int) get_post_thumbnail_id( $post->ID );
			}

			if ( ! $main_id && ! $light_id && ! $dark_id ) {
				continue;
			}

			$logos[] = array(
				'id'        => $post->ID,
				'title'     => $post->post_title,
				'content'   => $post->post_content,
				'url'       => get_post_meta( $post->ID, '_logo_url', true ),
				'alt'       => get_post_meta( $post->ID, '_logo_alt', true ),
				'main_url'  => $main_id ? wp_get_attachment_image_url( $main_id, 'full' ) : '',
				'light_url' => $light_id ? wp_get_attachment_image_url( $light_id, 'full' ) : '',
				'dark_url'  => $dark_id ? wp_get_attachment_image_url( $dark_id, 'full' ) : '',
			);
		}

		return $logos;
	}
}
