<?php
/**
 * Settings Page
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates the plugin Settings page under Settings → WP Logos.
 */
class Settings {

	/**
	 * Option name used to store global defaults.
	 */
	const OPTION_KEY = 'wp_logos_settings';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add the settings page under Settings.
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'WP Logos Settings', 'wp-logos' ),
			__( 'WP Logos', 'wp-logos' ),
			'manage_options',
			'wp-logos-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register settings sections and fields.
	 */
	public function register_settings(): void {
		register_setting(
			'wp_logos_settings_group',
			self::OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_defaults(),
			)
		);

		add_settings_section(
			'wp_logos_defaults_section',
			__( 'Global Defaults', 'wp-logos' ),
			null,
			'wp-logos-settings'
		);

		$fields = array(
			array(
				'id'    => 'default_type',
				'label' => __( 'Default Showcase Type', 'wp-logos' ),
				'type'  => 'select',
				'opts'  => array(
					'grid'     => __( 'Grid', 'wp-logos' ),
					'carousel' => __( 'Carousel', 'wp-logos' ),
					'flexbox'  => __( 'Flexbox', 'wp-logos' ),
				),
			),
			array(
				'id'    => 'default_theme',
				'label' => __( 'Default Logo Theme', 'wp-logos' ),
				'type'  => 'select',
				'opts'  => array(
					'standard' => __( 'Standard', 'wp-logos' ),
					'light'    => __( 'Light', 'wp-logos' ),
					'dark'     => __( 'Dark', 'wp-logos' ),
				),
			),
			array(
				'id'    => 'default_gap',
				'label' => __( 'Default Gap (px)', 'wp-logos' ),
				'type'  => 'number',
			),
			array(
				'id'    => 'default_logo_max_height',
				'label' => __( 'Default Logo Max Height (px)', 'wp-logos' ),
				'type'  => 'number',
			),
			array(
				'id'    => 'default_logo_max_width',
				'label' => __( 'Default Logo Max Width (px)', 'wp-logos' ),
				'type'  => 'number',
			),
			array(
				'id'    => 'default_grayscale',
				'label' => __( 'Grayscale Logos by Default', 'wp-logos' ),
				'type'  => 'checkbox',
			),
			array(
				'id'    => 'default_autoplay',
				'label' => __( 'Enable Carousel Autoplay by Default', 'wp-logos' ),
				'type'  => 'checkbox',
			),
			array(
				'id'    => 'default_autoplay_speed',
				'label' => __( 'Default Autoplay Speed (ms)', 'wp-logos' ),
				'type'  => 'number',
			),
		);

		foreach ( $fields as $field ) {
			add_settings_field(
				'wp_logos_' . $field['id'],
				$field['label'],
				array( $this, 'render_field' ),
				'wp-logos-settings',
				'wp_logos_defaults_section',
				$field
			);
		}
	}

	/**
	 * Return default values.
	 */
	public function get_defaults(): array {
		return array(
			'default_type'             => 'grid',
			'default_theme'            => 'standard',
			'default_gap'              => 20,
			'default_logo_max_height'  => 80,
			'default_logo_max_width'   => 160,
			'default_grayscale'        => false,
			'default_autoplay'         => true,
			'default_autoplay_speed'   => 3000,
		);
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value.
	 *
	 * @return mixed
	 */
	public static function get( string $key, $default = null ) {
		$options = get_option( self::OPTION_KEY, array() );
		return $options[ $key ] ?? $default;
	}

	/**
	 * Sanitise all submitted settings.
	 *
	 * @param array $input Raw POST data.
	 *
	 * @return array Sanitised values.
	 */
	public function sanitize_settings( array $input ): array {
		$output   = array();
		$defaults = $this->get_defaults();

		$output['default_type']  = in_array( $input['default_type'] ?? '', array( 'grid', 'carousel', 'flexbox' ), true )
			? $input['default_type']
			: $defaults['default_type'];

		$output['default_theme'] = in_array( $input['default_theme'] ?? '', array( 'standard', 'light', 'dark' ), true )
			? $input['default_theme']
			: $defaults['default_theme'];

		$output['default_gap']              = absint( $input['default_gap'] ?? $defaults['default_gap'] );
		$output['default_logo_max_height']  = absint( $input['default_logo_max_height'] ?? $defaults['default_logo_max_height'] );
		$output['default_logo_max_width']   = absint( $input['default_logo_max_width'] ?? $defaults['default_logo_max_width'] );
		$output['default_grayscale']        = ! empty( $input['default_grayscale'] );
		$output['default_autoplay']         = ! empty( $input['default_autoplay'] );
		$output['default_autoplay_speed']   = absint( $input['default_autoplay_speed'] ?? $defaults['default_autoplay_speed'] );

		return $output;
	}

	/**
	 * Render an individual settings field.
	 *
	 * @param array $field Field definition.
	 */
	public function render_field( array $field ): void {
		$options = get_option( self::OPTION_KEY, $this->get_defaults() );
		$value   = $options[ $field['id'] ] ?? '';
		$name    = esc_attr( self::OPTION_KEY . '[' . $field['id'] . ']' );

		switch ( $field['type'] ) {
			case 'select':
				echo '<select name="' . $name . '">';
				foreach ( $field['opts'] as $k => $label ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $k ),
						selected( $value, $k, false ),
						esc_html( $label )
					);
				}
				echo '</select>';
				break;

			case 'checkbox':
				printf(
					'<input type="checkbox" name="%s" value="1"%s>',
					$name,
					checked( $value, true, false )
				);
				break;

			case 'number':
			default:
				printf(
					'<input type="number" name="%s" value="%s" class="small-text">',
					$name,
					esc_attr( $value )
				);
				break;
		}
	}

	/**
	 * Render the settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Logos Settings', 'wp-logos' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wp_logos_settings_group' );
				do_settings_sections( 'wp-logos-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
