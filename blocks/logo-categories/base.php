<?php

namespace WP_Logos\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logo_Categories extends \WP_Logos\Display_Base {
	public static function block_name(): string {
		return 'yolo-logos/logo-categories';
	}

	public static function shortcode(): string {
		return 'wp_logos_categories';
	}

	public static function wpbakery_shortcode(): string {
		return 'yolo_vc_logo_categories';
	}
}
