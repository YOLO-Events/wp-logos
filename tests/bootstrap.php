<?php

$tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/wordpress-phpunit';
$polyfills = dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills';

if ( ! is_file( $tests_dir . '/includes/functions.php' ) ) {
	throw new RuntimeException( 'WordPress test suite not found at ' . $tests_dir );
}
if ( ! is_file( $polyfills . '/phpunitpolyfills-autoload.php' ) ) {
	throw new RuntimeException( 'PHPUnit polyfills not found; run composer install.' );
}

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $polyfills );
require_once $tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__ ) . '/wp-logos.php';
	}
);

require $tests_dir . '/includes/bootstrap.php';
