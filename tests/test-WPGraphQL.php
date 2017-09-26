<?php

class Test_WPGraphQL_Integration extends WP_UnitTestCase {

	/**
	 * Let's ensure that Tracing is output as expected in the extensions of the GraphQL Response
	 */
	function testGraphQLQueryTracingInResponse() {
		\WPGraphQL\Extensions\Insights\Tracing::$include_in_response = true;
		$query = '{posts{edges{node{id}}}}';
		$results = do_graphql_request( $query );
		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertArrayHasKey( 'tracing', $results['extensions'] );
	}

	function testGraphQLQueryTracingNotInResponseWhenDisabled() {

		// Disable including tracing in the response
		\WPGraphQL\Extensions\Insights\Tracing::$include_in_response = false;

		// Run a query
		$query = '{posts{edges{node{id}}}}';
		$results = do_graphql_request( $query );

		// Make sure the query didn't respond with any errors
		$this->assertArrayNotHasKey( 'errors', $results );

		// There's a chance there might be other extensions at some point, so let's not be
		// too strict on the assertion here. We want to make sure that if tracing is disabled that
		// either no extensions are present in the results OR tracing _at least_ is not present
		if ( ! empty( $results['extensions'] ) ) {
			$this->assertArrayNotHasKey( 'tracing', $results['extensions'] );
		} else {
			$this->assertArrayNotHasKey( 'extensions', $results );
		}

	}

}