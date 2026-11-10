<?php
/**
 * Import administration screen.
 *
 * @package WP_Logos
 */

namespace WP_Logos\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Presents adapter-neutral, resumable import batches.
 */
class Admin_Page {

	/**
	 * WordPress screen hook suffix.
	 *
	 * @var string
	 */
	private string $page_hook = '';

	/**
	 * Register WordPress hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_wp_logos_import_batch', array( $this, 'ajax_batch' ) );
	}

	/**
	 * Register the importer submenu.
	 */
	public function add_page(): void {
		$this->page_hook = (string) add_submenu_page(
			'edit.php?post_type=wp_logo',
			__( 'Import', 'yolo-logos' ),
			__( 'Import', 'yolo-logos' ),
			'manage_options',
			'yolo-logos-import',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Load assets on this screen only.
	 *
	 * @param string $hook Current screen hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( '' === $this->page_hook || $hook !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style( 'wp-logos-import', WP_LOGOS_PLUGIN_URL . 'admin/css/wp-logos-import.css', array(), WP_LOGOS_VERSION );
		wp_enqueue_script( 'wp-logos-import', WP_LOGOS_PLUGIN_URL . 'admin/js/wp-logos-import.js', array(), WP_LOGOS_VERSION, true );
		wp_localize_script(
			'wp-logos-import',
			'wpLogosImport',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'wp_logos_import_batch' ),
				'text'  => array(
					'running'       => __( 'Processing…', 'yolo-logos' ),
					'previewDone'   => __( 'Preview complete. Review the report before importing.', 'yolo-logos' ),
					'importDone'    => __( 'Import complete.', 'yolo-logos' ),
					'withErrors'    => __( 'Completed with errors. Review the report; some logos may not have been imported.', 'yolo-logos' ),
					'failed'        => __( 'Request failed. Retry to resume at the same offset. If the response was lost after a write, already imported logos will follow the selected skip/update policy.', 'yolo-logos' ),
					'retry'         => __( 'Retry / resume', 'yolo-logos' ),
					'newCategory'   => __( 'new on import', 'yolo-logos' ),
					'unmapped'      => __( 'not mapped', 'yolo-logos' ),
					'created'       => __( 'Created', 'yolo-logos' ),
					'updated'       => __( 'Updated', 'yolo-logos' ),
					'skipped'       => __( 'Skipped', 'yolo-logos' ),
					'missingImages' => __( 'Missing images', 'yolo-logos' ),
					'errors'        => __( 'Errors', 'yolo-logos' ),
					'collisions'    => __( 'Slug collisions', 'yolo-logos' ),
					'termMap'       => __( 'Source category → target term ID', 'yolo-logos' ),
					'legacy'        => __( 'Legacy references (source category → target term ID)', 'yolo-logos' ),
					'noProgress'    => __( 'The importer made no progress. Retry or check the source.', 'yolo-logos' ),
					'invalidReply'  => __( 'The server returned an invalid batch response.', 'yolo-logos' ),
				),
			)
		);
	}

	/**
	 * Render detected adapters and import controls.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to import logos.', 'yolo-logos' ) );
		}

		$sources = array();
		$error   = '';
		try {
			foreach ( ( new Registry() )->detected() as $source ) {
				$sources[] = array(
					'id'      => $source->id(),
					'label'   => $source->label(),
					'summary' => $source->summary(),
				);
			}
		} catch ( \Throwable $exception ) {
			$error = $exception->getMessage();
		}
		?>
		<div class="wrap wp-logos-import">
			<h1><?php esc_html_e( 'Import logos', 'yolo-logos' ); ?></h1>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Imported logo links always open in a new tab. Existing presets and content are not replaced. Legacy shortcodes and blocks must be updated manually using the mapping report. Preview category placeholders are “new on import”, not real term IDs for blocks.', 'yolo-logos' ); ?></p></div>
			<?php if ( '' !== $error ) : ?>
				<div class="notice notice-error inline"><p><?php echo esc_html( $error ); ?></p></div>
			<?php endif; ?>
			<?php if ( empty( $sources ) ) : ?>
				<p><?php esc_html_e( 'No supported logo sources were detected.', 'yolo-logos' ); ?></p>
			<?php else : ?>
				<h2><?php esc_html_e( 'Detected sources', 'yolo-logos' ); ?></h2>
				<?php foreach ( $sources as $source ) : ?>
					<section class="wp-logos-import-source">
						<h3><?php echo esc_html( $source['label'] ); ?></h3>
						<p>
							<?php
							printf(
								/* translators: 1: logo count, 2: category count. */
								esc_html__( 'Logos: %1$d · Categories: %2$d', 'yolo-logos' ),
								(int) $source['summary']['logos'],
								(int) $source['summary']['categories']
							);
							?>
						</p>
						<?php foreach ( $source['summary']['warnings'] as $warning ) : ?>
							<p class="description"><?php echo esc_html( $warning ); ?></p>
						<?php endforeach; ?>
					</section>
				<?php endforeach; ?>
				<form id="wp-logos-import-form">
					<table class="form-table"><tbody>
						<tr><th scope="row"><label for="wp-logos-import-source"><?php esc_html_e( 'Source', 'yolo-logos' ); ?></label></th><td>
							<select id="wp-logos-import-source" name="source">
								<?php foreach ( $sources as $source ) : ?>
									<option value="<?php echo esc_attr( $source['id'] ); ?>"><?php echo esc_html( $source['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</td></tr>
						<tr><th scope="row"><label for="wp-logos-import-mode"><?php esc_html_e( 'Existing logos', 'yolo-logos' ); ?></label></th><td>
							<select id="wp-logos-import-mode" name="mode">
								<option value="skip"><?php esc_html_e( 'Skip', 'yolo-logos' ); ?></option>
								<option value="update"><?php esc_html_e( 'Update', 'yolo-logos' ); ?></option>
							</select>
						</td></tr>
						<tr><th scope="row"><label for="wp-logos-import-collision"><?php esc_html_e( 'Category slug collisions', 'yolo-logos' ); ?></label></th><td>
							<select id="wp-logos-import-collision" name="collision">
								<option value="reuse"><?php esc_html_e( 'Reuse existing category', 'yolo-logos' ); ?></option>
								<option value="copy"><?php esc_html_e( 'Create a separate category', 'yolo-logos' ); ?></option>
							</select>
						</td></tr>
					</tbody></table>
					<p class="submit">
						<button type="button" class="button" id="wp-logos-import-preview"><?php esc_html_e( 'Preview (dry run)', 'yolo-logos' ); ?></button>
						<button type="button" class="button button-primary" id="wp-logos-import-execute" disabled><?php esc_html_e( 'Import logos', 'yolo-logos' ); ?></button>
						<button type="button" class="button" id="wp-logos-import-retry" hidden><?php esc_html_e( 'Retry / resume', 'yolo-logos' ); ?></button>
					</p>
				</form>
				<progress id="wp-logos-import-progress" value="0" max="1" aria-label="<?php esc_attr_e( 'Import progress', 'yolo-logos' ); ?>"></progress>
				<p id="wp-logos-import-status" role="status" aria-live="polite"></p>
				<div id="wp-logos-import-report"></div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Run one authorized batch with strictly validated scalar options.
	 */
	public function ajax_batch(): void {
		if ( isset( $_REQUEST['_ajax_nonce'] ) && ! is_string( $_REQUEST['_ajax_nonce'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'yolo-logos' ) ), 403 );
			return;
		}
		$valid_nonce = check_ajax_referer( 'wp_logos_import_batch', '_ajax_nonce', false );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to import logos.', 'yolo-logos' ) ), 403 );
			return;
		}
		if ( ! $valid_nonce ) {
			wp_send_json_error( array( 'message' => __( 'The security token expired. Reload this page and preview again.', 'yolo-logos' ) ), 403 );
			return;
		}

		$input = array();
		foreach ( array( 'source', 'mode', 'collision', 'dry_run', 'offset', 'limit' ) as $key ) {
			if ( isset( $_POST[ $key ] ) && ! is_string( $_POST[ $key ] ) ) {
				wp_send_json_error( array( 'message' => __( 'Import options must be scalar values.', 'yolo-logos' ) ), 400 );
				return;
			}
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validate exact enum/integer values below; do not silently coerce invalid options.
			$input[ $key ] = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : null;
		}
		$input['mode']      = $input['mode'] ?? 'skip';
		$input['collision'] = $input['collision'] ?? 'reuse';
		$input['dry_run']   = $input['dry_run'] ?? '1';
		$input['offset']    = $input['offset'] ?? '0';
		$input['limit']     = $input['limit'] ?? '50';
		if (
			empty( $input['source'] ) ||
			! in_array( $input['mode'], array( 'skip', 'update' ), true ) ||
			! in_array( $input['collision'], array( 'reuse', 'copy' ), true ) ||
			! in_array( $input['dry_run'], array( '0', '1' ), true ) ||
			false === filter_var( $input['offset'], FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 0 ) ) ) ||
			false === filter_var( $input['limit'], FILTER_VALIDATE_INT )
		) {
			wp_send_json_error( array( 'message' => __( 'Invalid import options.', 'yolo-logos' ) ), 400 );
			return;
		}

		try {
			$registry = new Registry();
			$source   = $registry->get( $input['source'] );
			$detected = false;
			foreach ( $registry->detected() as $candidate ) {
				if ( $candidate->id() === $source->id() ) {
					$detected = true;
					break;
				}
			}
			if ( ! $detected ) {
				wp_send_json_error( array( 'message' => __( 'This source is not currently detected.', 'yolo-logos' ) ), 400 );
				return;
			}
			$result = ( new Engine() )->run_batch(
				$source,
				array(
					'dry_run'   => '1' === $input['dry_run'],
					'mode'      => $input['mode'],
					'collision' => $input['collision'],
					'offset'    => (int) $input['offset'],
					'limit'     => max( 1, min( 500, (int) $input['limit'] ) ),
				)
			);
			wp_send_json_success( $result->to_array() );
		} catch ( \InvalidArgumentException $exception ) {
			wp_send_json_error( array( 'message' => $exception->getMessage() ), 400 );
		} catch ( \Throwable $exception ) {
			wp_send_json_error( array( 'message' => $exception->getMessage() ), 500 );
		}
	}
}
