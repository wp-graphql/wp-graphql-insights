<?php

namespace WPGraphQL\Extensions\Insights;

/**
 * Class Insights
 *
 * @package WPGraphQL\Extensions\Insights
 */
final class Plugin {

	/**
	 * Holds the instance of the Plugin class
	 *
	 * @var self|null
	 */
	private static $instance;

	public static function instance() {

		if ( self::$instance instanceof Plugin ) {
			return;
		}

		self::$instance = new Plugin();
		self::$instance->setup_constants();
		self::$instance->actions();
		self::$instance->filters();

	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @access public
	 * @return void
	 */
	public function __wakeup() {

		// De-serializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'De-serializing instances of the WPGraphQL class is not allowed', 'wp-graphql' ), '0.0.1' );

	}

	/**
	 * Setup plugin constants.
	 *
	 * @access public
	 * @return void
	 */
	public function __clone() {

		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'The WPGraphQL class should not be cloned.', 'wp-graphql' ), '0.0.1' );

	}

	/**
	 * Setup plugin constants.
	 *
	 * @access private
	 * @return void
	 */
	private static function setup_constants() {

		// Plugin version.
		if ( ! defined( 'WPGRAPHQL_INSIGHTS_VERSION' ) ) {
			define( 'WPGRAPHQL_INSIGHTS_VERSION', '0.3.1' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'WPGRAPHQL_INSIGHTS_PLUGIN_DIR' ) ) {
			define( 'WPGRAPHQL_INSIGHTS_PLUGIN_DIR', plugin_dir_path( __DIR__ ) );
		}

		// Plugin Folder URL.
		if ( ! defined( 'WPGRAPHQL_INSIGHTS_PLUGIN_URL' ) ) {
			define( 'WPGRAPHQL_INSIGHTS_PLUGIN_URL', plugin_dir_url( __DIR__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'WPGRAPHQL_INSIGHTS_PLUGIN_FILE' ) ) {
			define( 'WPGRAPHQL_INSIGHTS_PLUGIN_FILE', __DIR__ . '/wp-graphql-insights.php' );
		}

		// The version of the Tracing spec that's being used.
		// @see: https://github.com/apollographql/apollo-tracing
		if ( ! defined( 'GRAPHQL_TRACING_SPEC_VERSION' ) ) {
			define( 'GRAPHQL_TRACING_SPEC_VERSION', 1 );
		}

	}

	/**
	 * Sets up actions to run at certain spots throughout WordPress and the WPGraphQL execution cycle
	 *
	 * @access private
	 * @return void
	 */
	private function actions() {

		/**
		 * Initialize the trace when the GraphQL request begins
		 */
		add_action( 'do_graphql_request', [ '\WPGraphQL\Extensions\Insights\Tracing', 'init_trace' ], 99, 3 );

		/**
		 * Initialize the Query Trace when execution begins
		 */
		add_action( 'graphql_execute', [ '\WPGraphQL\Extensions\Insights\QueryTrace', 'init_trace' ], 99, 3 );

		/**
		 * Close the trace when execution completes
		 */
		add_action( 'graphql_execute', [ '\WPGraphQL\Extensions\Insights\Tracing', 'close_trace' ], 99, 5 );

		/**
		 * Initialize each resolver trace
		 */
		add_action( 'graphql_before_resolve_field', [ 'WPGraphQL\Extensions\Insights\Tracing', 'init_field_resolver_trace' ], 10, 8 );

		/**
		 * Close each resolver trace
		 */
		add_action( 'graphql_after_resolve_field', [ 'WPGraphQL\Extensions\Insights\Tracing', 'close_field_resolver_trace' ], 10, 8 );

	}

	/**
	 * Setup filters
	 *
	 * @access private
	 * @return void
	 */
	private function filters() {

		/**
		 * Filter the request_results to include Tracing in the extensions
		 */
		add_filter( 'graphql_request_results', [ 'WPGraphQL\Extensions\Insights\Tracing', 'add_tracing_to_response_extensions' ], 10, 5 );
		add_filter( 'graphql_request_results', [ 'WPGraphQL\Extensions\Insights\Tracing', 'add_tracked_queries_to_response_extensions' ], 10, 5 );
		add_filter( 'graphql_access_control_allow_headers', [ $this, 'return_tracing_headers' ] );

	}

	/**
	 * Filter the headers that WPGraphQL returns to include headers that indicate the WPGraphQL server supports
	 * Apollo Tracing and Credentials
	 *
	 * @param array $headers
	 *
	 * @return mixed
	 */
	public function return_tracing_headers( $headers ) {

		$headers[] = 'X-Insights-Include-Tracing';
		$headers[] = 'X-Apollo-Tracing';
		$headers[] = 'Credentials';

		return $headers;
	}

}
