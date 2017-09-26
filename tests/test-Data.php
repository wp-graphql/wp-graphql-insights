<?php
/**
 * Class TestData
 *
 * @package WPGraphQL_Insights
 */

/**
 * Class _testData
 * Extends the data class to provide testable methods
 */
class _testData extends \WPGraphQL\Extensions\Insights\Data {
	public static function _get_resolver_name( $resolver ) {
		return self::get_resolver_name( $resolver );
	}
}

class TestData extends WP_UnitTestCase {

	function testGetDocument() {
		\WPGraphQL\Extensions\Insights\Data::$document = 'Test';
		$actual = \WPGraphQL\Extensions\Insights\Data::get_document();
		$this->assertEquals( 'Test', $actual );
	}

	function testGetOperationName() {
		\WPGraphQL\Extensions\Insights\Data::$operation_name = 'Test';
		$actual = \WPGraphQL\Extensions\Insights\Data::get_operation_name();
		$this->assertEquals( 'Test', $actual );
	}

	function testGetResolverName() {

		$resolver = [
			'fieldName' => 'test'
		];

		$actual = _testData::_get_resolver_name( $resolver );

		$this->assertEquals( 'test', $actual );

	}

	function testGetResolverNameFallbackToParentType() {

		$resolver = [
			'fieldName' => 'testField',
			'parentType' => 'testParent'
		];

		$actual = _testData::_get_resolver_name( $resolver );

		$this->assertEquals( 'testParent.testField', $actual );

	}

	function testGetResolverNameFallbackToEmpty() {

		$resolver = [
			'no_parentType_or_fieldName' => true
		];

		$actual = _testData::_get_resolver_name( $resolver );

		$this->assertEquals( '', $actual );

	}

}
