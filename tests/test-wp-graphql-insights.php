<?php

class Test_WPGraphQLInsights extends WP_UnitTestCase {

	/**
	 * Ensure that the function that instantiates the plugin properly returns the class instance
	 */
	function testGraphQLInsightsInit() {
		$actual = \WPGraphQL\Extensions\graphql_insights_init();
		$this->assertEquals( \WPGraphQL\Extensions\Insights::instance(), $actual );
	}

}