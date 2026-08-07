<?php
/**
 * Admin Class
 *
 * @package WP_Logos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin-specific functionality: meta boxes, scripts, styles.
 */
class WP_Logos_Admin {

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_wp_logo', array( $this, 'save_meta_boxes' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'manage_wp_logo_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_wp_logo_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'plugin_action_links_' . WP_LOGOS_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Register admin meta boxes.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'wp-logos-images',
			__( 'Logo Images', 'yolo-logos' ),
			array( $this, 'render_images_meta_box' ),
			'wp_logo',
			'normal',
			'high'
		);

		add_meta_box(
			'wp-logos-details',
			__( 'Logo Details', 'yolo-logos' ),
			array( $this, 'render_details_meta_box' ),
			'wp_logo',
			'normal',
			'high'
		);
	}

	/**
	 * Render the Logo Images meta box.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_images_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'wp_logos_save_meta', 'wp_logos_nonce' );

		$main_id  = (int) get_post_meta( $post->ID, '_logo_main_id', true );
		$light_id = (int) get_post_meta( $post->ID, '_logo_light_id', true );
		$dark_id  = (int) get_post_meta( $post->ID, '_logo_dark_id', true );
		?>
		<table class="wp-logos-meta-table form-table">
			<tbody>
				<?php
				$this->render_image_field( $main_id, '_logo_main_id', __( 'Main Logo', 'yolo-logos' ), __( 'The primary logo image.', 'yolo-logos' ) );
				$this->render_image_field( $light_id, '_logo_light_id', __( 'Light Version', 'yolo-logos' ), __( 'Optional: light/white logo for dark backgrounds.', 'yolo-logos' ) );
				$this->render_image_field( $dark_id, '_logo_dark_id', __( 'Dark Version', 'yolo-logos' ), __( 'Optional: dark logo for light backgrounds.', 'yolo-logos' ) );
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render a single image upload field row.
	 *
	 * @param int    $attachment_id Current attachment ID.
	 * @param string $meta_key      Meta key.
	 * @param string $label         Field label.
	 * @param string $description   Field description.
	 */
	private function render_image_field( int $attachment_id, string $meta_key, string $label, string $description ): void {
		$img_src = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
		?>
		<tr>
			<th scope="row">
				<label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $label ); ?></label>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			</th>
			<td>
				<div class="wp-logos-image-uploader" data-field="<?php echo esc_attr( $meta_key ); ?>">
					<div class="wp-logos-preview" style="<?php echo $img_src ? '' : 'display:none'; ?>">
						<img src="<?php echo esc_url( $img_src ); ?>" style="max-width:200px;max-height:120px;" alt="" />
					</div>
					<input type="hidden"
						id="<?php echo esc_attr( $meta_key ); ?>"
						name="<?php echo esc_attr( $meta_key ); ?>"
						value="<?php echo esc_attr( $attachment_id ?: '' ); ?>" />
					<button type="button" class="button wp-logos-upload-btn">
						<?php echo $img_src ? esc_html__( 'Change Image', 'yolo-logos' ) : esc_html__( 'Upload / Select Image', 'yolo-logos' ); ?>
					</button>
					<?php if ( $img_src ) : ?>
						<button type="button" class="button wp-logos-remove-btn">
							<?php esc_html_e( 'Remove', 'yolo-logos' ); ?>
						</button>
					<?php else : ?>
						<button type="button" class="button wp-logos-remove-btn" style="display:none;">
							<?php esc_html_e( 'Remove', 'yolo-logos' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render the Logo Details meta box.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_details_meta_box( \WP_Post $post ): void {
		$url   = get_post_meta( $post->ID, '_logo_url', true );
		$alt   = get_post_meta( $post->ID, '_logo_alt', true );
		$order = get_post_meta( $post->ID, '_logo_order', true );
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="_logo_url"><?php esc_html_e( 'Website URL', 'yolo-logos' ); ?></label>
					</th>
					<td>
						<input type="url"
							id="_logo_url"
							name="_logo_url"
							value="<?php echo esc_attr( $url ); ?>"
							class="regular-text"
							placeholder="https://example.com" />
						<p class="description"><?php esc_html_e( 'Optional: link to the brand\'s website.', 'yolo-logos' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_logo_alt"><?php esc_html_e( 'Alt Text', 'yolo-logos' ); ?></label>
					</th>
					<td>
						<input type="text"
							id="_logo_alt"
							name="_logo_alt"
							value="<?php echo esc_attr( $alt ); ?>"
							class="regular-text" />
						<p class="description"><?php esc_html_e( 'Accessible alt text for the logo image. Falls back to the logo title if empty.', 'yolo-logos' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_logo_order"><?php esc_html_e( 'Sort Order', 'yolo-logos' ); ?></label>
					</th>
					<td>
						<input type="number"
							id="_logo_order"
							name="_logo_order"
							value="<?php echo esc_attr( $order ); ?>"
							class="small-text"
							min="0" />
						<p class="description"><?php esc_html_e( 'Lower numbers appear first. Logos with the same sort order are ordered by title.', 'yolo-logos' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_meta_boxes( int $post_id, \WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST['wp_logos_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_logos_nonce'] ) ), 'wp_logos_save_meta' ) ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Don't save on auto-save.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$fields = array(
			'_logo_url'      => 'esc_url_raw',
			'_logo_alt'      => 'sanitize_text_field',
		);

		foreach ( $fields as $key => $sanitizer ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, $sanitizer( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		$int_fields = array( '_logo_main_id', '_logo_light_id', '_logo_dark_id', '_logo_order' );
		foreach ( $int_fields as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$val = absint( wp_unslash( $_POST[ $key ] ) );
				if ( $val > 0 ) {
					update_post_meta( $post_id, $key, $val );
				} else {
					delete_post_meta( $post_id, $key );
				}
			}
		}
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$screen = get_current_screen();

		if ( ! $screen || 'wp_logo' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'wp-logos-admin',
			WP_LOGOS_PLUGIN_URL . 'admin/css/wp-logos-admin.css',
			array(),
			WP_LOGOS_VERSION
		);

		wp_enqueue_script(
			'wp-logos-admin',
			WP_LOGOS_PLUGIN_URL . 'admin/js/wp-logos-admin.js',
			array( 'jquery' ),
			WP_LOGOS_VERSION,
			true
		);

		wp_localize_script(
			'wp-logos-admin',
			'wpLogosAdmin',
			array(
				'uploadTitle'  => __( 'Select Logo Image', 'yolo-logos' ),
				'uploadButton' => __( 'Use This Image', 'yolo-logos' ),
			)
		);
	}

	/**
	 * Add custom columns to the logo list table.
	 *
	 * @param array $columns Existing columns.
	 *
	 * @return array Modified columns.
	 */
	public function add_columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['wp_logos_thumbnail'] = __( 'Logo', 'yolo-logos' );
			}
		}
		return $new;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column slug.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( string $column, int $post_id ): void {
		if ( 'wp_logos_thumbnail' !== $column ) {
			return;
		}

		$main_id = (int) get_post_meta( $post_id, '_logo_main_id', true );
		if ( ! $main_id ) {
			$main_id = (int) get_post_thumbnail_id( $post_id );
		}

		if ( $main_id ) {
			echo wp_get_attachment_image( $main_id, array( 60, 40 ), false, array( 'style' => 'max-height:40px;width:auto;' ) );
		} else {
			echo '<span class="dashicons dashicons-images-alt" style="font-size:30px;color:#ccc;"></span>';
		}
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing links.
	 *
	 * @return array Modified links.
	 */
	public function plugin_action_links( array $links ): array {
		$plugin_links = array(
			'<a href="' . admin_url( 'options-general.php?page=yolo-logos-settings' ) . '">' . __( 'Settings', 'yolo-logos' ) . '</a>',
			'<a href="' . admin_url( 'edit.php?post_type=wp_logo' ) . '">' . __( 'Logos', 'yolo-logos' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}
}
