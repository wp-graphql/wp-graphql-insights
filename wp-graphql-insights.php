<?php
/**
 * Plugin Name:     WPGraphQL Insights
 * Plugin URI:      https://www.wpgraphql.com
 * Description:     Performance and error logging for WPGraphQL
 * Author:          WPGraphQL, Jason Bahl
 * Author URI:      https://www.wpgraphql.com
 * Text Domain:     wp-graphql-insights
 * Domain Path:     /languages
 * Version:         0.3.0
 *
 * @package         WPGraphQL_Insights
 */

namespace WPGraphQL\Extensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WPGraphQL\Extensions\Insights' ) ) {

	/**
	 * Class Insights
	 *
	 * @package WPGraphQL\Extensions
	 */
	final class Insights {

		/**
		 * Holds the instance of the Insights class
		 * @var \WPGraphQL\Extensions\Insights
		 */
		private static $instance;

		/**
		 * @return object \WPGraphQL\Extensions\WPGraphQL_Insights
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof \WPGraphQL\Extensions\Insights ) ) {
				self::$instance = new Insights();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->actions();
				self::$instance->filters();
			}

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
				define( 'WPGRAPHQL_INSIGHTS_VERSION', '0.3.0' );
			}

			// Plugin Folder Path.
			if ( ! defined( 'WPGRAPHQL_INSIGHTS_PLUGIN_DIR' ) ) {
				define( 'WPGRAPHQL_INSIGHTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin Folder URL.
			if ( ! defined( 'WPGRAPHQL_INSIGHTS_PLUGIN_URL' ) ) {
				define( 'WPGRAPHQL_INSIGHTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin Root File.
			if ( ! defined( 'WPGRAPHQL_INSIGHTS_PLUGIN_FILE' ) ) {
				define( 'WPGRAPHQL_INSIGHTS_PLUGIN_FILE', __FILE__ );
			}

			// The version of the Tracing spec that's being used.
			// @see: https://github.com/apollographql/apollo-tracing
			if ( ! defined( 'GRAPHQL_TRACING_SPEC_VERSION' ) ) {
				define( 'GRAPHQL_TRACING_SPEC_VERSION', 1 );
			}

		}

		/**
		 * Include required files.
		 * Uses composer's autoload
		 *
		 * @access private
		 * @return void
		 */
		private function includes() {
			require_once( WPGRAPHQL_INSIGHTS_PLUGIN_DIR . 'vendor/autoload.php' );
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
		 * @param $headers
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

}

/**
 * Initialize the plugin
 * @return mixed|object|bool
 */
function graphql_insights_init() {

	/**
	 * If SAVEQUERIES hasn't already been defined, define it now
	 */
	if ( ! defined( 'SAVEQUERIES' ) && true === apply_filters( 'wpgraphql_insights_track_queries', true ) ) {
		define( 'SAVEQUERIES', true );
	}

	/**
	 * If the version of WPGraphQL isn't up to date, don't instantiate tracing as it won't work properly
	 * @todo: consider displaying an Admin Notice or something to that tune if the versions aren't compatible
	 */
	if ( defined( 'WPGRAPHQL_VERSION' ) && version_compare( WPGRAPHQL_VERSION, '0.0.20', '<=' ) ) {
		return false;
	}

	/**
	 * Return the instance of the Insights plugin to kick off functionality
	 */
	return \WPGraphQL\Extensions\Insights::instance();
}

add_action( 'init', '\WPGraphQL\Extensions\graphql_insights_init' );
