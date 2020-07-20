<?php
/**
 * Plugin Name:     WPGraphQL Insights
 * Plugin URI:      https://www.wpgraphql.com
 * Description:     Performance and error logging for WPGraphQL
 * Author:          WPGraphQL, Jason Bahl
 * Author URI:      https://www.wpgraphql.com
 * Text Domain:     wp-graphql-insights
 * Domain Path:     /languages
 * Version:         0.3.1
 *
 * @package         WPGraphQL_Insights
 */

namespace WPGraphQL\Extensions\Insights;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Composer autoloader.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Initialize the plugin.
 *
 * @return mixed
 */
function init() {

	/**
	 * Default $graphql_insights_active to false.
	 */
	$graphql_insights_active = false;

	/**
	 * Default capability to activate insights for.
	 *
	 * @param string $capability Capability to show
	 */
	$graphql_insights_default_capability = apply_filters( 'graphql_insights_default_capability', 'manage_options' );

	/**
	 * If GRAPHQL_DEBUG is enabled or the request is authenticated by a user with , enable Insights
	 */
	if ( defined( 'GRAPHQL_DEBUG' ) && true === GRAPHQL_DEBUG || current_user_can( $graphql_insights_default_capability ) ) {
		$graphql_insights_active = true;
	}

	/**
	 * Filter whether insights is active, allowing finer control over when to activate insights.
	 *
	 * Default is to activate when GRAPHQL_DEBUG is true or authenticated requests
	 *
	 * @param boolean $graphql_insights_active Whether insights should be enabled for a request
	 *
	 */
	$graphql_insights_active = apply_filters( 'graphql_insights_active', $graphql_insights_active );

	/**
	 * If $graphql_insights_active is not true, return now before instantiating insights
	 */
	if ( true !== $graphql_insights_active ) {
		return false;
	}

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
	 * Return the instance of the Plugin to kick off functionality
	 */
	return Plugin::instance();
}

add_action( 'init', '\WPGraphQL\Extensions\Insights\init' );
