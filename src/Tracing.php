<?php

namespace WPGraphQL\Extensions\Insights;

/**
 * Class Tracing
 *
 * @package WPGraphQL\Extensions\Insights
 */
class Tracing {

	/**
	 * Stores an individual trace for a field resolver
	 * @var array
	 */
	protected static $field_resolver_trace = [];

	/**
	 * Stores whether tracing is enabled or not
	 *
	 * @var bool
	 */
	public static $store_data = true;

	/**
	 * Stores the start microtime of a resolver
	 * @var string
	 */
	protected static $resolver_start = null;

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
	public static function init_trace( $request, $operation_name, $variables ) {

		Data::$document = $request;
		Data::$operation_name = $operation_name;
		Data::$variables = $variables;

		self::$request_start_microtime = microtime( true );
		self::$request_start_timestamp = self::_format_timestamp( self::$request_start_microtime );
	}

	/**
	 * Sets the timestamp and microtime for the end of the request
	 * @return string
	 */
	public static function close_trace() {
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
	 * @return mixed
	 */
	public static function get_resolver_duration() {
		$resolver_end = microtime( true );
		return ( $resolver_end - self::$request_start_microtime ) * 1000000;
	}

	/**
	 * Trace a resolver
	 * @param $trace
	 */
	public static function trace_resolver( $trace ) {
		self::$sanitized_resolver_traces[] = ( ! empty( $trace ) || is_array( $trace ) ) ? self::_sanitize_resolver_trace( $trace ) : [];
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
	 * Returns an array of trace data
	 * @return array
	 */
	public static function get_trace() {

		$trace = [
			'version'   => absint( GRAPHQL_TRACING_SPEC_VERSION ),
			'startTime' => esc_html( self::$request_start_timestamp ),
			'endTime'   => esc_html( self::$request_end_timestamp ),
			'duration'  => absint( self::get_request_duration() ),
			'execution' => [
				'resolvers' => self::$sanitized_resolver_traces,
			]
		];



		/**
		 * Filer and return the trace data
		 * @param array $trace An array of Trace data
		 */
		Data::$trace_report = apply_filters( 'graphql_insights_get_trace', $trace );

		return Data::$trace_report;

	}

	/**
	 * This adds the "tracing" to the GraphQL response extensions.
	 *
	 * @param $response
	 *
	 * @return mixed
	 */
	public static function add_tracing_to_response_extensions( $response, $schema, $operation_name, $request, $variables ) {

		/**
		 * Filter whether the tracing should be included in the response or not.
		 *
		 * @param bool $include_in_response
		 * @param object $response
		 * @param object $schema
		 * @param string $operation_name
		 * @param string $request
		 * @param array $variables
		 */
		$include_in_response = apply_filters( 'graphql_tracing_include_in_response', true, $response, $schema, $operation_name, $request, $variables );

		if ( true !== $include_in_response ) {
			return $response;
		}

		/**
		 * If tracing should be included in the response
		 */
		if ( ! empty( $response ) ) {
			if ( is_array( $response ) ) {
				$response['extensions']['tracing'] = self::get_trace();
			} else if ( is_object( $response ) ) {
				$response->extensions['tracing'] = self::get_trace();
			}
		}

		/**
		 * Return the GraphQL Results, with or without the tracing extension added
		 */
		return $response;
	}

	/**
	 * Adds the tracked queries to the extensions response
	 *
	 * @param object $response
	 * @param object $schema
	 * @param string $operation_name
	 * @param string $request
	 * @param array $variables
	 *
	 * @return mixed
	 */
	public static function add_tracked_queries_to_response_extensions( $response, $schema, $operation_name, $request, $variables ) {

		/**
		 * Filter whether the queryLog should be included in the response or not.
		 *
		 * @param bool $include_in_response
		 * @param object $response
		 * @param object $schema
		 * @param string $operation_name
		 * @param string $request
		 * @param array $variables
		 */
		$include_in_response = apply_filters( 'graphql_query_log_include_in_response', true, $response, $schema, $operation_name, $request, $variables );

		if ( true !== $include_in_response ) {
			return $response;
		}

		if ( ! empty( $response ) ) {
			if( is_array( $response ) ) {
				$response['extensions']['queryLog'] = QueryTrace::get_trace();
			} else if ( is_object( $response ) ) {
				$response->extensions['queryLog'] = QueryTrace::get_trace();
			}
		}

		return $response;
	}

	/**
	 * @param $source
	 * @param $args
	 * @param $context
	 * @param $info
	 * @param $field_resolver
	 * @param $type_name
	 * @param $field_key
	 * @param $field
	 */
	public static function init_field_resolver_trace( $source, $args, $context, $info, $field_resolver, $type_name, $field_key, $field ) {

		$start_offset = Tracing::get_resolver_start_offset();
		self::$resolver_start = microtime( true );

		self::$field_resolver_trace = [
			'path' => $info->path,
			'parentType' => $info->parentType->name,
			'fieldName' => $info->fieldName,
			'returnType' => $info->returnType->name,
			'startOffset' => $start_offset,
		];

	}

	/**
	 * @param $source
	 * @param $args
	 * @param $context
	 * @param $info
	 * @param $field_resolver
	 * @param $type_name
	 * @param $field_key
	 * @param $field
	 */
	public static function close_field_resolver_trace( $source, $args, $context, $info, $field_resolver, $type_name, $field_key, $field ) {
		self::$field_resolver_trace['duration'] = Tracing::get_resolver_duration();
		Tracing::trace_resolver( self::$field_resolver_trace );
		self::reset_field_resolver_trace();
	}

	/**
	 *
	 */
	protected static function reset_field_resolver_trace() {
		self::$field_resolver_trace = [];
		self::$resolver_start = null;
	}

}
