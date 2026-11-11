<?php
/**
 * Shared page-builder attribute conversion.
 *
 * @package WP_Logos
 */

namespace WP_Logos;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts page-builder values to registered block attributes.
 */
class Builder_Attributes {

	/**
	 * Convert builder values to the attributes accepted by a block.
	 *
	 * @param string $block_name Registered block name.
	 * @param array  $values     Page-builder values.
	 * @return array
	 */
	public static function convert( string $block_name, array $values ): array {
		if ( ! class_exists( '\WP_Block_Type_Registry' ) ) {
			return array();
		}

		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $block_name );
		if ( ! $block_type || ! is_array( $block_type->attributes ) ) {
			return array();
		}

		$converted = array();
		foreach ( $block_type->attributes as $name => $definition ) {
			if ( ! array_key_exists( $name, $values ) ) {
				continue;
			}

			$value = $values[ $name ];
			switch ( $name ) {
				case 'categoryId':
					$converted[ $name ] = absint( $value );
					break;
				case 'categoryOrder':
					$converted[ $name ] = self::category_order( $value );
					break;
				case 'columns':
					$converted[ $name ] = self::columns( $value );
					break;
				default:
					$converted[ $name ] = self::typed_value( $value, $definition['type'] ?? '' );
			}
		}

		return $converted;
	}

	/**
	 * Normalize a category list while preserving its selected order.
	 *
	 * @param mixed $value List or comma-separated term IDs.
	 * @return int[]
	 */
	private static function category_order( $value ): array {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
	}

	/**
	 * Normalize responsive columns, including individual builder controls.
	 *
	 * @param mixed $value Responsive values.
	 * @return array
	 */
	private static function columns( $value ): array {
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			$value   = is_array( $decoded ) ? $decoded : array();
		}
		$value = is_array( $value ) ? $value : array();

		foreach ( array( 'mobile', 'tablet', 'laptop', 'desktop' ) as $size ) {
			$alias = 'columns_' . $size;
			if ( isset( $value[ $alias ] ) ) {
				$value[ $size ] = $value[ $alias ];
				unset( $value[ $alias ] );
			}
		}

		foreach ( $value as $size => $count ) {
			if ( in_array( $size, array( 'mobile', 'tablet', 'laptop', 'desktop' ), true ) ) {
				$value[ $size ] = max( 1, absint( $count ) );
			} else {
				unset( $value[ $size ] );
			}
		}

		return $value;
	}

	/**
	 * Cast a builder value according to the block attribute schema.
	 *
	 * @param mixed  $value      Value to cast.
	 * @param string $type       Block attribute type.
	 * @return mixed
	 */
	private static function typed_value( $value, string $type ) {
		if ( 'boolean' === $type ) {
			return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		}
		if ( 'number' === $type || 'integer' === $type ) {
			return 'integer' === $type ? (int) $value : (float) $value;
		}
		if ( 'string' === $type ) {
			return is_scalar( $value ) ? (string) $value : '';
		}
		return $value;
	}
}
