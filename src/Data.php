<?php
namespace WPGraphQL\Extensions\Insights;

class Data {

	public static $operation_name;
	public static $document;
	public static $variables;
	public static $trace_report;

	public function store_trace_report() {

		/**
		 * Bail if this is not a GraphQL Request
		 */
		if ( ! defined( 'GRAPHQL_REQUEST' ) || ! GRAPHQL_REQUEST ) {
			return;
		}

		/**
		 * Ensure the $trace_report is a populated Array before continuing
		 */
		if ( empty( self::$trace_report ) || ! is_array( self::$trace_report ) ) {
			return;
		}

		/**
		 * Don't store the trace data if it's not enabled
		 */
		if ( false === Tracing::$store_data ) {
			return;
		}


//		update_option( 'graphql_trace', [
//			'timestamp' => strtotime( 'now' ),
//			'operation_name' => self::get_operation_name(),
//			'trace_report' => self::$trace_report,
//			'document' => self::get_document(),
//			'variables' => self::$variables,
//		] );
//
		if ( ! empty( self::get_operation_name() ) ) {
			self::store_operation( self::get_operation_name() );
			self::store_trace();
		}

	}

	/**
	 * Returns the GraphQL Request Document
	 * @return string
	 */
	public static function get_document() {
		return ! empty( self::$document ) ? self::$document : '';
	}

	/**
	 * Returns the GraphQL Operation name, falls back to the document if no operation name was provided
	 * @return string
	 */
	public static function get_operation_name( ) {
		return ! empty( self::$operation_name ) ? self::$operation_name : self::get_document();
	}

	/**
	 * @param string $operation_name The name of the operation
	 */
	public static function store_operation( $operation_name ) {

		$operation_term = get_term_by( 'name', $operation_name, 'graphql_operations' );

		if ( empty( $operation_term ) ) {
			$operation_term_response = wp_insert_term( $operation_name, 'graphql_operations' );
			if ( is_wp_error( $operation_term_response ) ) {
				return;
			}
			$operation_terms[] = ! empty( $operation_term_response['term_id'] ) ? $operation_term_response['term_id'] : $operation_term;
		} else {
			$operation_terms[] = $operation_term->term_id;
		}

		$request_id = wp_insert_post([
			'post_type' => 'graphql_requests',
			'post_title' => $operation_name,
			'post_content' => wp_json_encode([
				'trace' => self::$trace_report,
				'document' => self::get_document()
			]),
			'post_status' => 'publish',
		]);

		wp_set_post_terms( $request_id, $operation_terms, 'graphql_operations', false );

		/**
		 * Store the Variables for the Operation
		 */
		update_post_meta( $request_id, 'variables', wp_json_encode( self::$variables ) );

		if ( ! empty( self::$trace_report['startTime'] ) ) {
			update_post_meta( $request_id, 'start_time', esc_html( self::$trace_report['startTime'] ) );
		}

		if ( ! empty( self::$trace_report['endTime'] ) ) {
			update_post_meta( $request_id, 'end_time', esc_html( self::$trace_report['startTime'] ) );
		}

		if ( ! empty( self::$trace_report['version'] ) ) {
			update_post_meta( $request_id, 'trace_spec_version', absint( self::$trace_report['version'] ) );
		}

		if ( ! empty( self::$trace_report['duration'] ) ) {
			update_post_meta( $request_id, 'duration', absint( self::$trace_report['duration'] ) );
		}

	}

	protected static function store_trace() {

		if ( empty( self::$trace_report ) || ! is_array( self::$trace_report  ) ) {
			return;
		}

		if ( ! empty( self::$trace_report['execution']['resolvers'] ) && is_array( self::$trace_report['execution']['resolvers'] ) ) {
			array_map( [ 'WPGraphQL\Extensions\Insights\Data', 'store_resolver' ],  self::$trace_report['execution']['resolvers'] );
		}

	}

	protected static function get_resolver_name( $resolver ) {
		$name = '';
		if ( ! empty( $resolver['fieldName'] ) ) {
			$name = $resolver['fieldName'];
		}
		if ( ! empty( $resolver['parentType'] ) ) {
			$name = $resolver['parentType'] . '.' . $name;
		}
		return $name;
	}

	protected static function store_resolver( $resolver ) {
		$resolver_id = wp_insert_post([
			'post_type' => 'graphql_resolvers',
			'post_title' => self::get_operation_name() . ' | ' . self::get_resolver_name( $resolver ),
			'post_content' => wp_json_encode( $resolver ),
			'post_status' => 'publish',
		]);

		$field_name = ! empty( $resolver['fieldName'] ) ? $resolver['fieldName'] : 0;
		$parent_type = ! empty( $resolver['parentType'] ) ? $resolver['parentType'] : 0;

		/**
		 * Set the terms for the article
		 */
		if ( ! empty( $field_name ) ) {
			$existing_field_term = get_term_by( 'name', $field_name, 'graphql_fields' );
			if ( ! empty( $existing_field_term->term_id ) ) {
				$field_terms[] = $existing_field_term->term_id;
			} else {
				$args = [];
				if ( ! empty( $parent_type ) ) {
					$existing_parent_term = get_term_by( 'name', $parent_type, 'graphql_fields' );
					if ( empty( $existing_parent_term ) ) {
						$new_field_parent = wp_insert_term( $parent_type, 'graphql_fields' );
						$args['parent'] = $new_field_parent['term_id'];
					} else {
						$args['parent'] = $existing_parent_term->term_id;
					}
				} else {
					$args['parent'] = 0;
				}
				$new_field = wp_insert_term( $field_name, 'graphql_fields', $args );
				$field_terms[] = $new_field['term_id'];
			}
		}

		if ( ! empty( $field_terms ) ) {
			wp_set_object_terms( $resolver_id, $field_terms, 'graphql_fields', false );
		}
	}

}