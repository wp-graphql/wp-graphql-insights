<?php
namespace WPGraphQL\Extensions\Insights;

use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Type\WPObjectType;

/**
 * Class InstrumentSchema
 *
 * @package WPGraphQL\Extensions\Insights\Schema
 */
class InstrumentSchema {

	/**
	 * Stores the resolver traces
	 * @var array
	 */
	protected static $resolver_traces = [];

	/**
	 * Returns the traces for the resolvers
	 * @return array
	 */
	public static function get_resolver() {
		return ! empty( self::$resolver_traces ) ? self::$resolver_traces : [];
	}

	/**
	 * This takes an instance of WPSchema and wraps each resolver with logging.
	 *
	 * @param \WPGraphQL\WPSchema $schema
	 *
	 * @return \WPGraphQL\WPSchema
	 */
	public static function instrument( \WPGraphQL\WPSchema $schema ) {

		$new_types = [];
		$types = $schema->getTypeMap();

		if ( ! empty( $types ) && is_array( $types ) ) {
			foreach ( $types as $type_name => $type_object ) {
				if ( $type_object instanceof ObjectType || $type_object instanceof WPObjectType ) {
					$fields = $type_object->getFields();
					$new_fields = self::wrap_field_resolvers( $fields, $type_name );
					$new_type_object = $type_object;
					$new_type_object->config['fields'] = $new_fields;
					$new_types[ $type_name ] = $new_type_object;
				}
			}
		}

		if ( ! empty( $new_types ) && is_array( $new_types ) ) {
			$schema->config['types'] = $new_types;
		}

		return $schema;

	}

	/**
	 * This wraps field resolvers to gather reporting data
	 *
	 * @since 0.0.1
	 * @param $fields
	 * @param $type_name
	 *
	 * @return array
	 */
	public static function wrap_field_resolvers( $fields, $type_name ) {

		if ( ! empty( $fields ) && is_array( $fields ) ) {

			foreach ( $fields as $field_key => $field ) {

				$start_offset = Tracing::get_resolver_start_offset();
				$resolver_start = null;
				$resolver_start = microtime( true );

				if ( $field instanceof FieldDefinition ) {

					/**
					 * Get the fields resolve function
					 * @since 0.0.1
					 */
					$field_resolver = ! empty( $field->resolveFn ) ? $field->resolveFn : null;

					/**
					 * Replace the existing field resolve method with a new function that captures data about
					 * the resolver to be stored in the resolver_report
					 * @since 0.0.1
					 *
					 * @param $source
					 * @param array $args
					 * @param AppContext $context
					 * @param ResolveInfo $info
					 *
					 * @use function|null $field_resolve_function
					 * @use string $type_name
					 * @use string $field_key
					 * @use object $field
					 *
					 * @return mixed
					 * @throws \Exception
					 */
					$field->resolveFn = function( $source, array $args, AppContext $context, ResolveInfo $info ) use ( $field_resolver, $type_name, $field_key, $field, $start_offset, $resolver_start ) {

						$trace = [
							'path' => $info->path,
							'parentType' => $info->parentType,
							'fieldName' => $info->fieldName,
							'returnType' => $info->returnType,
							'startOffset' => $start_offset,
						];

						try {

							/**
							 * If the current field doesn't have a resolve function, use the defaultFieldResolver,
							 * otherwise use the $field_resolver
							 */
							if ( null === $field_resolver || ! is_callable( $field_resolver ) ) {
								$result = Executor::defaultFieldResolver( $source, $args, $context, $info );
							} else {
								$result = call_user_func( $field_resolver, $source, $args, $context, $info );
							}

						} catch ( \Exception $error ) {

							/**
							 * Throw an exception for the error that was returned from the resolver
							 * @since 0.0.1
							 */
							throw new \Exception( $error );
						}

						$trace['duration'] = Tracing::get_resolver_duration( $resolver_start );
						Tracing::trace_resolver( $trace );
						return $result;

					};

				}
			}
		}

		/**
		 * Return the fields
		 * @since 0.0.1
		 */
		return $fields;

	}

}