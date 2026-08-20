<?php
/**
 * YOLO Logos
 *
 * @package           WP_Logos
 * @author            YOLO Logos
 * @copyright         2024 YOLO Logos
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       YOLO Logos
 * Plugin URI:        https://github.com/YOLO-Events/wp-logos
 * Description:       A modern WordPress plugin for managing and showcasing logos with Carousel, Grid, and Flexbox layouts. Includes a native Gutenberg block, WPML/Polylang/Loco Translate support, and full visual control.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      8.0
 * Author:            YOLO Logos
 * Author URI:        https://github.com/YOLO-Events
 * Text Domain:       yolo-logos
 * Domain Path:       /languages
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_LOGOS_VERSION', '1.0.0' );
define( 'WP_LOGOS_PLUGIN_FILE', __FILE__ );
define( 'WP_LOGOS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_LOGOS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_LOGOS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once WP_LOGOS_PLUGIN_DIR . 'includes/class-i18n.php';
require_once WP_LOGOS_PLUGIN_DIR . 'includes/class-post-types.php';
require_once WP_LOGOS_PLUGIN_DIR . 'includes/class-taxonomies.php';
require_once WP_LOGOS_PLUGIN_DIR . 'includes/class-meta-fields.php';
require_once WP_LOGOS_PLUGIN_DIR . 'includes/class-settings.php';
require_once WP_LOGOS_PLUGIN_DIR . 'includes/class-blocks.php';
require_once WP_LOGOS_PLUGIN_DIR . 'admin/class-admin.php';
require_once WP_LOGOS_PLUGIN_DIR . 'public/class-public.php';
require_once WP_LOGOS_PLUGIN_DIR . 'includes/class-wp-logos.php';

/**
 * Returns the main instance of the plugin.
 */
function wp_logos() {
	return WP_Logos\Plugin::instance();
}

wp_logos();
