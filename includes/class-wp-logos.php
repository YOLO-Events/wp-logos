<?php
/**
 * Main Plugin Class
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class using singleton pattern.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Returns (or creates) the singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor – use instance().
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Register all WordPress hooks.
	 */
	private function init_hooks(): void {
		// i18n.
		$i18n = new I18n();
		add_action( 'plugins_loaded', array( $i18n, 'load_plugin_textdomain' ) );

		// Post types & taxonomies.
		$post_types = new Post_Types();
		add_action( 'init', array( $post_types, 'register' ) );

		$taxonomies = new Taxonomies();
		add_action( 'init', array( $taxonomies, 'register' ) );

		// Meta fields.
		$meta_fields = new Meta_Fields();
		add_action( 'init', array( $meta_fields, 'register' ) );

		// Gutenberg blocks.
		$blocks = new Blocks();
		add_action( 'init', array( $blocks, 'register' ) );

		// Admin.
		if ( is_admin() ) {
			$admin = new \WP_Logos_Admin();
			$admin->init();
		}

		// Public.
		$public = new \WP_Logos_Public();
		$public->init();

		// Settings page.
		$settings = new Settings();
		$settings->init();

		// Activation / deactivation hooks.
		register_activation_hook( WP_LOGOS_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( WP_LOGOS_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Plugin activation.
	 */
	public function activate(): void {
		// Flush rewrite rules after registering CPT.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate(): void {
		flush_rewrite_rules();
	}
}
