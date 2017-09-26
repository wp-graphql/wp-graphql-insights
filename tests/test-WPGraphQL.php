<?php

class Test_WPGraphQL extends WP_UnitTestCase {

	function testGraphQLQuery() {
		$query = '{posts{edges{node{id}}}}';
		$results = do_graphql_request( $query );
		var_dump( $results );
		$this->assertArrayNotHasKey( 'errors', $results );
	}

}