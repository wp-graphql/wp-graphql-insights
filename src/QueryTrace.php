<?php

namespace WPGraphQL\Extensions\Insights;

/**
 * Class QueryTrace
 *
 * @package WPGraphQL\Extensions\Insights
 */
class QueryTrace {

	/**
	 * Holds the trace data for queries
	 * @access protected
	 * @var array
	 */
	protected static $trace = [];

	/**
	 * Initialize the trace if SAVEQUERIES is enabled
	 * @access public
	 */
	public static function init_trace() {

		if ( ! defined( 'SAVEQUERIES' ) || true !== SAVEQUERIES ) {
			return;
		}

	}

	/**
	 * Returns the query trace for output with the Extensions
	 * @access public
	 * @return array
	 */
	public static function get_trace() {

		if ( ! empty( $GLOBALS['wpdb']->queries ) && is_array( $GLOBALS['wpdb']->queries ) ) {
			$queries = array_map( function( $query ) {
				$output = [
					'sql' => $query[0],
					'time' => $query[1],
					'stack' => $query[2],
				];
				return $output;
			}, $GLOBALS['wpdb']->queries );

			self::$trace['queryCount'] = count( $queries );
			$totalTime = 0;
			foreach ( $queries as $query ) {
				$totalTime = $totalTime + $query['time'];
			}
			self::$trace['totalTime'] = $totalTime;
			self::$trace['queries'] = $queries;
		}

		return self::$trace;
	}

}
