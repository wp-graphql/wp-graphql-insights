<?php

namespace WPGraphQL\Extensions\Insights;

/**
 * Class Tracing
 *
 * @package WPGraphQL\Extensions\Insights
 */
class Tracing {

	/**
	 * Stores the resolver traces
	 *
	 * @var
	 */
	protected static $resolvers;

	/**
	 * Stores the start of the request as microtime
	 *
	 * @var
	 */
	protected static $request_start_microtime;

	/**
	 * Stores the start of the request as an RFC 3339 Timestamp
	 *
	 * @var
	 */
	protected static $request_start_timestamp;

	/**
	 * Stores the end of the request as microtime
	 *
	 * @var
	 */
	protected static $request_end_microtime;

	/**
	 * Stores the end of the request as an RFC 3339 Timestamp
	 *
	 * @var
	 */
	protected static $request_end_timestamp;

	/**
	 * Stores the duration of the request
	 *
	 * @var
	 */
	protected static $request_duration;

	/**
	 * Stores sanitized resolver traces to be returned in the tracing response
	 * @var array
	 */
	protected static $sanitized_resolver_traces;

	/**
	 * Formats a timestamp to be RFC 3339 compliant
	 *
	 * @see https://github.com/apollographql/apollo-tracing
	 *
	 * @param $time
	 *
	 * @return string
	 */
	public static function _format_timestamp( $time ) {
		$timestamp = \DateTime::createFromFormat( 'U.u', $time );

		return $timestamp->format( "Y-m-d\TH:i:s.uP" );
	}

	/**
	 * @param array  $results        The results of a GraphQL request
	 * @param object $schema         The Schema for the GraphQL request
	 * @param string $operation_name The name of the GraphQL operation
	 * @param array  $request        The incoming request
	 * @param array  $variables      The variables for the request
	 *
	 * @return bool
	 */
	protected static function include_tracing_in_response( $results, $schema, $operation_name, $request, $variables ) {
		$include = apply_filters( 'graphql_insights_include_tracing_in_response', true, $results, $schema, $operation_name, $request, $variables );

		return $include;
	}

	/**
	 * Returns the timestamp for the start of the request
	 * @return string
	 */
	public static function get_request_start_timestamp() {
		return ! empty( self::$request_start_timestamp ) ? self::$request_start_timestamp : '';
	}

	/**
	 * Returns the microtime for the start of the request
	 * @return string
	 */
	public static function get_request_start_microtime() {
		return ! empty( self::$request_start_microtime ) ? self::$request_start_microtime : '';
	}

	/**
	 * Returns the timestamp for the end of the request
	 * @return string
	 */
	public static function get_request_end_timestamp() {
		return ! empty( self::$request_end_timestamp ) ? self::$request_end_timestamp : '';
	}

	/**
	 * Returns the microtime for the end of the request
	 * @return string
	 */
	public static function get_request_end_microtime() {
		return ! empty( self::$request_end_microtime ) ? self::$request_end_microtime : '';
	}

	/**
	 * Sets the timestamp and microtime for the start of the request
	 * @return string
	 */
	public static function set_request_start_time() {
		self::$request_start_microtime = microtime( true );
		self::$request_start_timestamp = self::_format_timestamp( self::$request_start_microtime );
	}

	/**
	 * Sets the timestamp and microtime for the end of the request
	 * @return string
	 */
	public static function set_request_end_time() {
		self::$request_end_microtime = microtime( true );
		self::$request_end_timestamp = self::_format_timestamp( self::$request_end_microtime );
	}

	/**
	 * Returns the duration of the request in nanoseconds
	 * @return string
	 */
	public static function get_request_duration() {
		return ( self::$request_end_microtime - self::$request_start_microtime ) * 1000000;
	}

	/**
	 * Returns the start offset for a resolver
	 * @return mixed
	 */
	public static function get_resolver_start_offset() {
		return ( microtime( true ) - self::$request_start_microtime ) * 1000000;
	}

	/**
	 * Given a resolver start time, returns the duration of a resolver
	 * @param string $resolver_start The microtime for the resolver start time
	 * @return mixed
	 */
	public static function get_resolver_duration( $resolver_start ) {
		$resolver_end = microtime( true );

		return ( $resolver_end - $resolver_start ) * 1000000;
	}

	/**
	 * Trace a resolver
	 * @param $trace
	 */
	public static function trace_resolver( $trace ) {
		if ( empty( $trace ) || ! is_array( $trace ) ) {
			return;
		}
		self::$sanitized_resolver_traces[] = self::_sanitize_resolver_trace( $trace );
	}

	/**
	 * Given a trace, sanitizes the values and returns the sanitized_trace
	 * @param array $trace
	 *
	 * @return mixed
	 */
	public static function _sanitize_resolver_trace( array $trace ) {

		$sanitized_trace = [];

		$sanitized_trace['path'] = ! empty( $trace['path'] && is_array( $trace['path'] ) ) ? array_map( ['WPGraphQL\Extensions\Insights\Tracing', '_sanitize_trace_resolver_path' ], $trace['path'] ) : [];
		$sanitized_trace['parentType'] = ! empty( $trace['parentType'] ) ? esc_html( $trace['parentType'] ) : '';
		$sanitized_trace['fieldName'] = ! empty( $trace['fieldName'] ) ? esc_html( $trace['fieldName'] ) : '';
		$sanitized_trace['returnType'] = ! empty( $trace['returnType'] ) ? esc_html( $trace['returnType'] ) : '';
		$sanitized_trace['startOffset'] = ! empty( $trace['startOffset'] ) ? absint( $trace['startOffset'] ) : '';
		$sanitized_trace['duration'] = ! empty( $trace['duration'] ) ? absint( $trace['duration'] ) : '';

		return $sanitized_trace;
	}

	/**
	 * Given input from a Resolver Path, this sanitizes the input for output in the trace
	 * @param $input
	 *
	 * @return int|null|string
	 */
	public static function _sanitize_trace_resolver_path( $input ) {
		$sanitized_input = null;
		if ( is_numeric( $input ) ) {
			$sanitized_input = absint( $input );
		} else {
			$sanitized_input = esc_html( $input );
		}
		return $sanitized_input;
	}

	/**
	 * This adds the "tracing" to the GraphQL response extensions.
	 *
	 * @param $results
	 *
	 * @return mixed
	 */
	public static function add_tracing_to_response_extensions( $results, $schema, $operation_name, $request, $variables ) {

		/**
		 * If tracing should be included in the response
		 */
		if ( true === self::include_tracing_in_response( $results, $schema, $operation_name, $request, $variables ) ) {
			$results->extensions['tracing'] = [
				'version'   => absint( GRAPHQL_TRACING_SPEC_VERSION ),
				'startTime' => esc_html( self::$request_start_timestamp ),
				'endTime'   => esc_html( self::$request_end_timestamp ),
				'duration'  => absint( self::get_request_duration() ),
				'execution' => [
					'resolvers' => self::$sanitized_resolver_traces,
				]
			];
		}

		/**
		 * Return the GraphQL Results, with or without the tracing extension added
		 */
		return $results;
	}

}