<?php
/**
 * Configurable adapter for logo CPT plugins.
 *
 * @package WP_Logos
 */

namespace WP_Logos\Import;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exceptions become escaped admin output or plain-text CLI diagnostics.

/**
 * Maps source fields to normalised values without writing any records.
 */
class Generic_CPT_Source extends Abstract_Source {
	/**
	 * Stable adapter identity.
	 *
	 * @var string
	 */
	protected string $source_id;
	/**
	 * Verified source storage configuration.
	 *
	 * @var array
	 */
	protected array $config;

	/**
	 * Configure a source without registering its CPT or taxonomy.
	 *
	 * @param string $id     Stable source identifier.
	 * @param array  $config CPT, taxonomy and field mappings.
	 * @throws \InvalidArgumentException If required mappings are invalid.
	 */
	public function __construct( string $id, array $config ) {
		if ( empty( $config['post_type'] ) || ! is_string( $config['post_type'] ) ) {
			throw new \InvalidArgumentException( __( 'Import source requires a post_type.', 'yolo-logos' ) );
		}
		$this->source_id = $id;
		$this->config    = array_merge(
			array(
				'label'           => $id,
				'taxonomy'        => '',
				'image'           => 'featured',
				'light_image_key' => '',
				'dark_image_key'  => '',
				'meta_map'        => array(),
				'extra_map'       => array(),
			),
			$config
		);
		if ( ! preg_match( '/^(featured|meta_id:.+|meta_url:.+)$/', $this->config['image'] ) ) {
			throw new \InvalidArgumentException( __( 'Unsupported import image mapping.', 'yolo-logos' ) );
		}
	}

	/** Return the stable registry key. */
	public function id(): string {
		return $this->source_id;
	}

	/** Return the source display name. */
	public function label(): string {
		return $this->config['label'];
	}

	/** Detect remaining source data, even if its plugin was deleted. */
	public function detect(): bool {
		return $this->count_logos() > 0 || count( iterator_to_array( $this->get_categories() ) ) > 0;
	}

	/** Return source counts and migration caveats. */
	public function summary(): array {
		return array(
			'logos'      => $this->count_logos(),
			'categories' => count( iterator_to_array( $this->get_categories() ) ),
			'warnings'   => array(
				__( 'Only existing Media Library attachments are reused. Missing images will be reported.', 'yolo-logos' ),
				__( 'Imported links open in a new tab. Original link targets are preserved as metadata only.', 'yolo-logos' ),
			),
		);
	}

	/** Count eligible source logos. */
	public function count_logos(): int {
		return $this->count_posts( $this->config['post_type'] );
	}

	/** Read source category values. */
	public function get_categories(): iterable {
		return $this->read_categories( $this->config['taxonomy'] );
	}

	/**
	 * Read a mapped scalar field, ignoring malformed array-valued source meta.
	 *
	 * @param array  $post    Raw post.
	 * @param array  $meta    Raw metadata.
	 * @param string $field   Normalised field.
	 * @param string $fallback Default source value.
	 * @throws \UnexpectedValueException If a mapped field is not scalar.
	 */
	protected function field( array $post, array $meta, string $field, string $fallback = '' ): string {
		$key   = $this->config['meta_map'][ $field ] ?? '';
		$value = in_array( $key, array( 'post_content', 'menu_order' ), true ) ? $post[ $key ] : ( $meta[ $key ] ?? $fallback );
		if ( ! is_scalar( $value ) && null !== $value ) {
			throw new \UnexpectedValueException( __( 'A mapped source field contains a non-scalar value.', 'yolo-logos' ) );
		}
		return (string) $value;
	}

	/**
	 * Normalise a source batch using bulk metadata and relationship reads.
	 *
	 * @param int $offset Source offset.
	 * @param int $limit Batch size.
	 */
	public function get_logos( int $offset, int $limit ): iterable {
		$posts         = $this->read_posts( $this->config['post_type'], $offset, $limit );
		$ids           = array_column( $posts, 'ID' );
		$metadata      = $this->read_meta( $ids );
		$relationships = $this->read_relationships( $ids, $this->config['taxonomy'] );
		foreach ( $posts as $post ) {
			$meta       = $metadata[ $post['ID'] ] ?? array();
			$image      = $this->config['image'];
			$image_meta = 'featured' === $image ? '_thumbnail_id' : substr( $image, strpos( $image, ':' ) + 1 );
			$image_id   = str_starts_with( $image, 'meta_url:' ) ? null : absint( $meta[ $image_meta ] ?? 0 );
			$light_id   = absint( $meta[ $this->config['light_image_key'] ] ?? 0 );
			$dark_id    = absint( $meta[ $this->config['dark_image_key'] ] ?? 0 );
			$target     = $this->field( $post, $meta, 'link_target' );
			$alt        = $this->field( $post, $meta, 'alt' );
			$extra      = array();
			foreach ( $this->config['extra_map'] as $name => $key ) {
				$extra[ $name ] = $meta[ $key ] ?? '';
			}
			yield new Import_Logo(
				array(
					'source_id'           => $post['ID'],
					'title'               => $post['post_title'],
					'status'              => $post['post_status'],
					'date'                => $post['post_date'],
					'author_id'           => $post['post_author'],
					'menu_order'          => (int) $this->field( $post, $meta, 'order', (string) $post['menu_order'] ),
					'main_attachment_id'  => $image_id ? $image_id : null,
					'light_attachment_id' => $light_id ? $light_id : null,
					'dark_attachment_id'  => $dark_id ? $dark_id : null,
					'image_url'           => str_starts_with( $image, 'meta_url:' ) ? (string) ( $meta[ $image_meta ] ?? '' ) : null,
					'link'                => $this->field( $post, $meta, 'link' ),
					'link_target'         => '' !== $target ? $target : null,
					'alt'                 => '' !== $alt ? $alt : null,
					'description_html'    => $this->field( $post, $meta, 'description' ),
					'category_source_ids' => $relationships[ $post['ID'] ] ?? array(),
					'extra'               => $extra,
				)
			);
		}
	}
}
