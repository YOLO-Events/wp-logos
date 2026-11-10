<?php
/**
 * Explicit importer loader; no autoloader or runtime directory scanning.
 *
 * @package WP_Logos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( array( 'import-category', 'import-logo', 'source-interface', 'abstract-source', 'generic-cpt-source', 'logooos-source', 'registry', 'result', 'target-mapper', 'engine', 'admin-page' ) as $wp_logos_import_class ) {
	require_once __DIR__ . '/class-' . $wp_logos_import_class . '.php';
}
unset( $wp_logos_import_class );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/class-cli-command.php';
}
