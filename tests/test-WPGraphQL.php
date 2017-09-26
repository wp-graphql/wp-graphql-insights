<?php

class Test_WPGraphQL_Integration extends WP_UnitTestCase {

	/**
	 * Let's ensure that Tracing is output as expected in the extensions of the GraphQL Response
	 */
	function testGraphQLQueryTracingInResponse() {
		$query = '{posts{edges{node{id}}}}';
		$results = do_graphql_request( $query );
		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertArrayHasKey( 'tracing', $results['extensions'] );
	}

}