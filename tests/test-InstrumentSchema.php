<?php
/**
 * Class TestInstrumentSchema
 *
 * @package WPGraphQL_Insights
 */

class _InstrumentSchema extends \WPGraphQL\Extensions\Insights\InstrumentSchema {
	public static function set_resolver_traces( $traces ) {
		self::$resolver_traces = $traces;
	}
}

class TestInstrumentSchema extends WP_UnitTestCase {

	/**
	 * @return \WPGraphQL\WPSchema
	 */
	public function _get_test_schema() {
		$schema = [
			'query' => new \WPGraphQL\Type\WPObjectType([
				'name' => 'test',
				'resolve' => function( $root, $args, $context, $info ) {
					return 'test';
				}
			])
		];

		return new \WPGraphQL\WPSchema( $schema );
	}

	/**
	 * Test getting the resolver traces
	 */
	public function testGetResolver() {
		$test_array = [
			'test' => 'test'
		];
		_InstrumentSchema::set_resolver_traces( $test_array );
		$actual = \WPGraphQL\Extensions\Insights\InstrumentSchema::get_resolver();
		$this->assertEquals( $test_array, $actual );
	}

	/**
	 * Ensure that an instrumented schema still returns a WPSchema
	 */
	public function test_InstrumentSchemaReturnsValidSchema() {
		$test_schema = self::_get_test_schema();
		$this->assertTrue( $test_schema instanceof \WPGraphQL\WPSchema );

		$instrumented_schema = \WPGraphQL\Extensions\Insights\InstrumentSchema::instrument( $test_schema );
		$this->assertTrue( $instrumented_schema instanceof \WPGraphQL\WPSchema );
	}

}