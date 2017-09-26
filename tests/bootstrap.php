<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WPGraphQL_Insights
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wp-graphql-insights/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	$_wp_content_dir = getenv( 'WP_CORE_DIR' );
	if ( ! $_wp_content_dir ) {
		$_wp_content_dir = '/tmp/wp-graphql-insights/wordpress/wp-content';
	}

	// Require WPGraphQL and WPGraphQL Insights
	require_once $_wp_content_dir . '/plugins/wp-graphql/wp-graphql.php';
	require_once( dirname( dirname( __FILE__ ) ) . '/wp-graphql-insights.php' );
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
