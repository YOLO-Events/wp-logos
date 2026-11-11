<?php
/**
 * Common block-backed display definitions.
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supplies editor and shortcode fields from a registered block's schema.
 */
abstract class Display_Base {

	abstract public static function block_name(): string;

	abstract public static function shortcode(): string;

	abstract public static function wpbakery_shortcode(): string;

	/**
	 * Read canonical attributes from the registered Gutenberg block type.
	 *
	 * @return array
	 */
	public static function attributes(): array {
		if ( ! class_exists( '\WP_Block_Type_Registry' ) ) {
			return array();
		}
		$type = \WP_Block_Type_Registry::get_instance()->get_registered( static::block_name() );
		return $type && is_array( $type->attributes ) ? $type->attributes : array();
	}

	/**
	 * Convert block attributes into shared builder control descriptions.
	 *
	 * @return array
	 */
	public static function fields(): array {
		$fields = array();
		foreach ( static::attributes() as $name => $definition ) {
			$type    = $definition['type'] ?? 'string';
			$default = $definition['default'] ?? null;

			if ( 'columns' === $name && is_array( $default ) ) {
				foreach ( $default as $size => $count ) {
					$fields[] = array(
						'name'    => 'columns_' . $size,
						'block'   => $name,
						'size'    => $size,
						'type'    => 'number',
						'default' => $count,
						'min'     => 1,
						'max'     => 12,
					);
				}
				continue;
			}

			$field_type = $type;
			if ( in_array( $name, array( 'categoryId', 'categoryOrder' ), true ) ) {
				$field_type = 'category' === $name ? 'category' : ( 'categoryOrder' === $name ? 'categories' : 'category' );
			} elseif ( 'string' === $type && ! empty( $definition['enum'] ) ) {
				$field_type = 'select';
			} elseif ( false !== stripos( $name, 'color' ) ) {
				$field_type = 'color';
			}

			$field = array(
				'name'    => $name,
				'block'   => $name,
				'type'    => $field_type,
				'default' => $default,
			);
			if ( ! empty( $definition['enum'] ) ) {
				$field['options'] = array_combine( $definition['enum'], array_map( 'ucfirst', $definition['enum'] ) );
			}
			if ( 'category' === $field_type || 'categories' === $field_type ) {
				$field['options'] = static::category_options( 'category' === $field_type );
			}
			$fields[] = $field;
		}
		return $fields;
	}

	/**
	 * Get taxonomy options, keyed by term ID.
	 *
	 * @param bool $include_all Include the all-categories choice.
	 * @return array
	 */
	public static function category_options( bool $include_all = true ): array {
		$options = $include_all ? array( 0 => __( 'All categories', 'yolo-logos' ) ) : array();
		$terms   = get_terms(
			array(
				'taxonomy'   => 'wp_logo_category',
				'hide_empty' => false,
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}
		return $options;
	}

	/**
	 * Display label for a field.
	 *
	 * @param array $field Field definition.
	 * @return string
	 */
	public static function field_label( array $field ): string {
		$name = str_replace( array( 'columns_', 'category' ), array( '', 'Category ' ), $field['name'] );
		return ucwords( trim( preg_replace( '/([a-z])([A-Z])/', '$1 $2', $name ) ) );
	}
}
