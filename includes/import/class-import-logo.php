<?php
/**
 * Normalised source logo.
 *
 * @package WP_Logos
 */

namespace WP_Logos\Import;

/**
 * Data only; adapters never write target records.
 */
class Import_Logo {
	/**
	 * Stable source identity.
	 *
	 * @var string
	 */
	public string $source_id;
	/**
	 * Logo title.
	 *
	 * @var string
	 */
	public string $title;
	/**
	 * WordPress post status.
	 *
	 * @var string
	 */
	public string $status;
	/**
	 * Local post date.
	 *
	 * @var string
	 */
	public string $date;
	/**
	 * Source author ID.
	 *
	 * @var int
	 */
	public int $author_id;
	/**
	 * Source sort order.
	 *
	 * @var int
	 */
	public int $menu_order;
	/**
	 * Primary attachment ID.
	 *
	 * @var int|null
	 */
	public ?int $main_attachment_id;
	/**
	 * Light attachment ID.
	 *
	 * @var int|null
	 */
	public ?int $light_attachment_id;
	/**
	 * Dark attachment ID.
	 *
	 * @var int|null
	 */
	public ?int $dark_attachment_id;
	/**
	 * Local attachment URL fallback.
	 *
	 * @var string|null
	 */
	public ?string $image_url;
	/**
	 * Website URL.
	 *
	 * @var string
	 */
	public string $link;
	/**
	 * Original link target.
	 *
	 * @var string|null
	 */
	public ?string $link_target;
	/**
	 * Optional alt override.
	 *
	 * @var string|null
	 */
	public ?string $alt;
	/**
	 * Source editor content.
	 *
	 * @var string
	 */
	public string $description_html;
	/**
	 * Source category IDs.
	 *
	 * @var string[]
	 */
	public array $category_source_ids;
	/**
	 * Unmapped source values.
	 *
	 * @var array
	 */
	public array $extra;

	/**
	 * Construct a normalised logo.
	 *
	 * @param array $data Normalised logo fields.
	 */
	public function __construct( array $data ) {
		$this->source_id           = (string) $data['source_id'];
		$this->title               = (string) ( $data['title'] ?? '' );
		$this->status              = (string) ( $data['status'] ?? 'publish' );
		$this->date                = (string) ( $data['date'] ?? '' );
		$this->author_id           = (int) ( $data['author_id'] ?? 0 );
		$this->menu_order          = (int) ( $data['menu_order'] ?? 0 );
		$this->main_attachment_id  = isset( $data['main_attachment_id'] ) ? absint( $data['main_attachment_id'] ) : null;
		$this->light_attachment_id = isset( $data['light_attachment_id'] ) ? absint( $data['light_attachment_id'] ) : null;
		$this->dark_attachment_id  = isset( $data['dark_attachment_id'] ) ? absint( $data['dark_attachment_id'] ) : null;
		$this->image_url           = $data['image_url'] ?? null;
		$this->link                = (string) ( $data['link'] ?? '' );
		$this->link_target         = $data['link_target'] ?? null;
		$this->alt                 = $data['alt'] ?? null;
		$this->description_html    = (string) ( $data['description_html'] ?? '' );
		$this->category_source_ids = array_map( 'strval', $data['category_source_ids'] ?? array() );
		$this->extra               = $data['extra'] ?? array();
	}
}
