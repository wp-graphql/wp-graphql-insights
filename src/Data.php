<?php
namespace WPGraphQL\Extensions\Insights;

class Data {

	public static $operation_name;
	public static $document;
	public static $variables;
	public static $trace_report;

	/**
	 * Returns the GraphQL Request Document
	 * @return string
	 */
	public static function get_document() {
		return ! empty( self::$document ) ? self::$document : '';
	}

	/**
	 * Returns the GraphQL Operation name, falls back to the document if no operation name was provided
	 * @return string
	 */
	public static function get_operation_name( ) {
		return ! empty( self::$operation_name ) ? self::$operation_name : self::get_document();
	}

	/**
	 * Returns the name of the resolver
	 * @param $resolver
	 *
	 * @return string
	 */
	protected static function get_resolver_name( $resolver ) {
		$name = '';
		if ( ! empty( $resolver['fieldName'] ) ) {
			$name = $resolver['fieldName'];
		}
		if ( ! empty( $resolver['parentType'] ) ) {
			$name = $resolver['parentType'] . '.' . $name;
		}
		return $name;
	}

}