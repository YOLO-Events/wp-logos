<?php
/**
 * Extensible source registry.
 *
 * @package WP_Logos
 */

namespace WP_Logos\Import;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exceptions are escaped by the admin interface, not while constructing report data.

/**
 * Accepts adapter class names, instances or generic CPT configurations.
 */
class Registry {
	/**
	 * Instantiate registered adapters without source-specific interface logic.
	 *
	 * @throws \InvalidArgumentException If a registration violates the source contract.
	 */
	public function all(): array {
		$definitions = apply_filters(
			'wp_logos_import_sources',
			array(
				'logooos'        => Logooos_Source::class,
				'logooos-legacy' => array(
					'label'     => __( 'Logooos (legacy My Clients)', 'yolo-logos' ),
					'post_type' => 'myclients',
					'taxonomy'  => 'clientscategory',
					'image'     => 'featured',
					'meta_map'  => array(
						'link'        => 'husamrayan_clientWebsite',
						'link_target' => 'client_link_target',
						'order'       => 'menu_order',
					),
					'extra_map' => array( 'image_size' => 'mc_imageSize' ),
				),
			)
		);
		$sources     = array();
		foreach ( $definitions as $id => $definition ) {
			if ( ! is_string( $id ) || sanitize_key( $id ) !== $id || '' === $id ) {
				throw new \InvalidArgumentException( __( 'Import source IDs must be nonempty sanitised keys.', 'yolo-logos' ) );
			}
			$source = is_array( $definition ) ? new Generic_CPT_Source( $id, $definition ) : ( is_string( $definition ) ? new $definition() : $definition );
			if ( ! $source instanceof Source_Interface || $source->id() !== $id ) {
				throw new \InvalidArgumentException( __( 'Import adapters must implement Source_Interface and match their registry key.', 'yolo-logos' ) );
			}
			$sources[ $id ] = $source;
		}
		return $sources;
	}

	/** Return only adapters with existing source records. */
	public function detected(): array {
		return array_filter( $this->all(), static fn( Source_Interface $source ): bool => $source->detect() );
	}

	/**
	 * Resolve a registered source.
	 *
	 * @param string $id Registered source key.
	 * @throws \InvalidArgumentException If the source is unknown.
	 */
	public function get( string $id ): Source_Interface {
		$sources = $this->all();
		if ( ! isset( $sources[ $id ] ) ) {
			throw new \InvalidArgumentException( __( 'Unknown logo import source.', 'yolo-logos' ) );
		}
		return $sources[ $id ];
	}
}
