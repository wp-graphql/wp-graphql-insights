<?php
namespace WPGraphQL\Extensions\Insights;

/**
 * Class Data
 *
 * @package WPGraphQL\Extensions\Insights
 */
class Data {

	/**
	 *
	 * @var string The GraphQL request operation name
	 * @access public
	 */
	public static $operation_name;

	/**
	 * @var string $document The GraphQL request document
	 * @access public
	 */
	public static $document;

	/**
	 * @var array $variables The GraphQL request variables
	 * @access public
	 */
	public static $variables;

	/**
	 * @var array $trace_report The trace report compiled as the GraphQL request is executed
	 * @access public
	 */
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
