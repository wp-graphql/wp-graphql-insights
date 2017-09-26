<?php

class _Tracing extends \WPGraphQL\Extensions\Insights\Tracing {
	public static function _include_tracing_in_response( $results, $schema, $operation_name, $request, $variables ) {
		return self::include_tracing_in_response( $results, $schema, $operation_name, $request, $variables );
	}
	public static function _set_request_start_microtime( $time ) {
		self::$request_start_microtime = $time;
		self::$request_start_timestamp = self::_format_timestamp( self::$request_start_microtime );
	}
	public static function _set_request_end_microtime( $time ) {
		self::$request_end_microtime = $time;
		self::$request_end_timestamp = self::_format_timestamp( self::$request_end_microtime );
	}
	public static function _get_sanitized_resolver_traces() {
		return self::$sanitized_resolver_traces;
	}
}

class TestTracing extends WP_UnitTestCase {

	public $microtime = '1506374732.8909';
	public $formatted_time = '2017-09-25T21:25:32.890900+00:00';

	public function testFormatTimestamp() {
		$actual = \WPGraphQL\Extensions\Insights\Tracing::_format_timestamp( $this->microtime );
		$this->assertEquals( $this->formatted_time, $actual );
	}

	public function testIncludeTracingInResponse() {
		$schema = new stdClass();
		$this->assertTrue( _Tracing::_include_tracing_in_response( [], $schema, '', [], []) );
	}

	public function testIncludeTracingInResponseFilter() {
		add_filter( 'graphql_insights_include_tracing_in_response', function() {
			return false;
		} );
		$schema = new stdClass();
		$this->assertFalse( _Tracing::_include_tracing_in_response( [], $schema, '', [], []) );
	}

	public function testGetRequestStartTimestamp() {
		$expected = \WPGraphQL\Extensions\Insights\Tracing::_format_timestamp( $this->microtime );
		_Tracing::_set_request_start_microtime( $this->microtime );
		$actual = \WPGraphQL\Extensions\Insights\Tracing::get_request_start_timestamp();
		$this->assertEquals( $expected, $actual );
	}

	public function testGetRequestStartMicrotime() {
		$expected =  $this->microtime;
		_Tracing::_set_request_start_microtime( $this->microtime );
		$actual = \WPGraphQL\Extensions\Insights\Tracing::get_request_start_microtime();
		$this->assertEquals( $expected, $actual );
	}

	public function testCloseTraceTimestamp() {

		// The times should be empty right now
		$end_timestamp = \WPGraphQL\Extensions\Insights\Tracing::get_request_end_timestamp();
		$end_microtime = \WPGraphQL\Extensions\Insights\Tracing::get_request_end_microtime();

		// Let's assert their emptiness
		$this->assertEquals( '', $end_timestamp );
		$this->assertEquals( '', $end_microtime );

		// Close the trace, should populate the times
		\WPGraphQL\Extensions\Insights\Tracing::close_trace();

		// Let's get the times
		$end_timestamp = \WPGraphQL\Extensions\Insights\Tracing::get_request_end_timestamp();
		$end_microtime = \WPGraphQL\Extensions\Insights\Tracing::get_request_end_microtime();

		// Let's assert that the times are no longer empty
		$this->assertNotEquals( '', $end_timestamp );
		$this->assertNotEquals( '', $end_microtime );
	}

	public function testGetRequestEndMicrotime() {
		$expected =  $this->microtime;
		_Tracing::_set_request_end_microtime( $this->microtime );
		$actual = \WPGraphQL\Extensions\Insights\Tracing::get_request_end_microtime();
		$this->assertEquals( $expected, $actual );
	}

	public function testGetRequestEndTimestamp() {
		$expected =  \WPGraphQL\Extensions\Insights\Tracing::_format_timestamp( $this->microtime );
		_Tracing::_set_request_end_microtime( $this->microtime );
		$actual = \WPGraphQL\Extensions\Insights\Tracing::get_request_end_timestamp();
		$this->assertEquals( $expected, $actual );
	}

	public function testAddTracingToResponseExtensions() {

		\WPGraphQL\Extensions\Insights\Tracing::$include_in_response = true;

		$response = new \GraphQL\Executor\ExecutionResult();
		$this->assertArrayNotHasKey( 'extensions', $response->toArray() );

		$traced_response = \WPGraphQL\Extensions\Insights\Tracing::add_tracing_to_response_extensions( $response, '', '', '', '' )->toArray();
		$this->assertArrayHasKey( 'tracing', $traced_response['extensions'] );

	}

	public function testAddTracingToResponseExtensionsDisabled() {

		\WPGraphQL\Extensions\Insights\Tracing::$include_in_response = false;

		$response = new \GraphQL\Executor\ExecutionResult();
		$this->assertArrayNotHasKey( 'extensions', $response->toArray() );

		$traced_response = \WPGraphQL\Extensions\Insights\Tracing::add_tracing_to_response_extensions( $response, '', '', '', '' )->toArray();
		$this->assertArrayNotHasKey( 'extensions', $traced_response );

	}

	public function testTraceResolverWithInvalidTrace() {
		$trace = '';
		\WPGraphQL\Extensions\Insights\Tracing::trace_resolver( $trace );
		$sanitized_trace = _Tracing::_get_sanitized_resolver_traces();
		$this->assertEmpty( $sanitized_trace[0] );

	}

}