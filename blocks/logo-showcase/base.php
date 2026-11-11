<?php

namespace WP_Logos\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logo_Showcase extends \WP_Logos\Display_Base {
	public static function block_name(): string {
		return 'yolo-logos/logo-showcase';
	}

	public static function shortcode(): string {
		return 'wp_logos_showcase';
	}

	public static function wpbakery_shortcode(): string {
		return 'yolo_vc_logo_showcase';
	}
}
