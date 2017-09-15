<?php
/**
 * Plugin Name:     WPGraphQL Insights
 * Plugin URI:      https://www.wpgraphql.com
 * Description:     Performance and error logging for WPGraphQL
 * Author:          WPGraphQL, Jason Bahl
 * Author URI:      https://www.wpgraphql.com
 * Text Domain:     wp-graphql-insights
 * Domain Path:     /languages
 * Version:         0.0.1
 *
 * @package         WPGraphQL_Insights
 */

namespace WPGraphQL\Extensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WPGraphQL\Extensions\Insights' ) ) {

	final class Insights {

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

		public function __wakeup() {

			// De-serializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'De-serializing instances of the WPGraphQL class is not allowed', 'wp-graphql' ), '0.0.1' );

		}

		public function __clone() {

			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'The WPGraphQL class should not be cloned.', 'wp-graphql' ), '0.0.1' );

		}

		private static function setup_constants() {

			// Plugin version.
			if ( ! defined( 'WPGRAPHQL_APOLLO_OPTICS_VERSION' ) ) {
				define( 'WPGRAPHQL_APOLLO_OPTICS_VERSION', '0.0.1' );
			}

			// Plugin Folder Path.
			if ( ! defined( 'WPGRAPHQL_APOLLO_OPTICS_PLUGIN_DIR' ) ) {
				define( 'WPGRAPHQL_APOLLO_OPTICS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin Folder URL.
			if ( ! defined( 'WPGRAPHQL_APOLLO_OPTICS_PLUGIN_URL' ) ) {
				define( 'WPGRAPHQL_APOLLO_OPTICS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin Root File.
			if ( ! defined( 'WPGRAPHQL_APOLLO_OPTICS_PLUGIN_FILE' ) ) {
				define( 'WPGRAPHQL_APOLLO_OPTICS_PLUGIN_FILE', __FILE__ );
			}

			// The version of the Tracing spec that's being used.
			// @see: https://github.com/apollographql/apollo-tracing
			if ( ! defined( 'GRAPHQL_TRACING_SPEC_VERSION' ) ) {
				define( 'GRAPHQL_TRACING_SPEC_VERSION', 1 );
			}

		}

		private function includes() {
			require_once( WPGRAPHQL_APOLLO_OPTICS_PLUGIN_DIR . 'vendor/autoload.php' );
		}

		private function actions() {
			add_action( 'do_graphql_request', [ '\WPGraphQL\Extensions\Insights\Tracing', 'set_request_start_time' ] );
			add_action( 'graphql_execute', [ '\WPGraphQL\Extensions\Insights\Tracing', 'set_request_end_time' ] );
		}

		private function filters() {
			add_filter( 'graphql_schema', [ 'WPGraphQL\Extensions\Insights\InstrumentSchema', 'instrument' ], 10, 1 );

			/**
			 * Filter the request_results to include Tracing in the extensions
			 */
			add_filter( 'graphql_request_results', [ 'WPGraphQL\Extensions\Insights\Tracing', 'add_tracing_to_response_extensions' ], 10, 5 );
		}

	}

}

function graphql_insights_init() {
	return \WPGraphQL\Extensions\Insights::instance();
}

add_action( 'plugins_loaded', '\WPGraphQL\Extensions\graphql_insights_init' );

