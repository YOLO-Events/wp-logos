<?php

namespace WP_Logos\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logo_Full_Cards extends \WP_Logos\Display_Base {
	public static function block_name(): string {
		return 'yolo-logos/logo-full-cards';
	}

	public static function shortcode(): string {
		return 'wp_logos_full_cards';
	}

	public static function wpbakery_shortcode(): string {
		return 'yolo_vc_logo_full_cards';
	}
}
