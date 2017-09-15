<?php
namespace WPGraphQL\Extensions\Insights;

class Setup {

	/**
	 * Register Post Types and Taxonomies
	 */
	public function register() {

		self::register_taxonomies();
		self::register_post_types();

	}

	/**
	 * Register post types where logging is stored
	 */
	private static function register_post_types() {

		/**
		 * Register the "graphql_requests" post_type where logs of GraphQL Requests are stored.
		 *
		 * A GraphQL request is the actual call that instantiates "do_graphql_request".
		 * Each request is logged here.
		 *
		 * Requests are associated with an Operation and Fields.
		 *
		 */
		register_post_type( 'graphql_requests', [
			'labels' => [
				'name' => _x( 'GraphQL Requests', 'Name of the post type used to store request logs for GraphQL requests', 'wp-graphql-insights' ),
				'singular_name' => _x( 'GraphQL Request', 'Singular name of the post type used to store request logs for GraphQL requests', 'wp-graphql-insights' ),
				'menu_name' => _x( 'GraphQL Requests', 'Name shown in the Admin Menu of the post type used to store request logs for GraphQL requests', 'wp-graphql-insights' ),
				'name_admin_bar' => _x( 'GraphQL Requests', 'Name shown in the Admin Bar of the post type used to store request logs for GraphQL requests', 'wp-graphql-insights' ),
			],
			'description' => __( 'Instances of GraphQL requests, used for logging and analytic insights', 'wp-graphql-insights' ),
			'public' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'show_in_graphql' => true,
			'graphql_single_name' => 'GraphQLRequest',
			'graphql_plural_name' => 'GraphQLRequests',
			'taxonomies' => [ 'graphql_operations', 'graphql_fields' ]
		]);

	}

	private static function register_taxonomies() {

		/**
		 * GraphQL Operations.
		 *
		 * This taxonomy is used to organize GraphQL Requests. Requests are grouped by their operation.
		 *
		 * This way, we can look at operations over time and see how their requests are performing.
		 */
		register_taxonomy( 'graphql_operations', 'graphql_requests', [
			'labels' => [
				'name' => _x( 'GraphQL Operations', 'Name of operations being logged for GraphQL requests', 'wp-graphql-insights' ),
				'singular_name' => _x( 'GraphQL Operation', 'Single name of operations being logged for GraphQL requests', 'wp-graphql-insights' ),
			],
			'public' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_graphql' => true,
			'graphql_single_name' => 'GraphqlOperation',
			'graphql_plural_name' => 'GraphQLOperations',
			'hierarchical' => false,
		] );

		/**
		 * GraphQL Fields
		 *
		 * This taxonomy is used to store fields that are used in GraphQL Requests. Each request has fields, and it
		 * can be beneficial to pull up a field and see what requests it's associated with.
		 */
		register_taxonomy( 'graphql_fields', 'graphql_requests', [
			'labels' => [
				'name' => _x( 'GraphQL Fields', 'Name of fields being logged for GraphQL requests', 'wp-graphql-insights' ),
				'singular_name' => _x( 'GraphQL Field', 'Single name of fields being logged for GraphQL requests', 'wp-graphql-insights' ),
			],
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_graphql' => true,
			'graphql_single_name' => 'GraphqlField',
			'graphql_plural_name' => 'GraphQLFields',
			'hierarchical' => true,
		] );

	}



}