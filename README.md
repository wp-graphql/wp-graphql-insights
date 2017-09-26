![Logo](https://www.wpgraphql.com/wp-content/uploads/2017/06/wpgraphql-logo-e1502819081849.png)

# WPGraphQL Insights

[![Build Status](https://travis-ci.org/wp-graphql/wp-graphql-insights.svg?branch=master)](https://travis-ci.org/wp-graphql/wp-graphql-insights)
[![Coverage Status](https://coveralls.io/repos/github/wp-graphql/wp-graphql-insights/badge.svg?branch=master)](https://coveralls.io/github/wp-graphql/wp-graphql-insights?branch=master)

This adds tracing to WPGraphQL, per the proposed Apollo Tracing Spec: https://github.com/apollographql/apollo-tracing.

![Demo GIF showing usage in GraphiQL](https://github.com/wp-graphql/wp-graphql-insights/blob/master/img/wp-graphql-insights-tracing-demo.gif)

## Install / Activate the Plugin

To install/activate the plugin, download from Github, unzip, and place in your plugins directory as `wp-graphql-insights` 
then activate like any other plugin.

There is no admin screen, the plugin will automatically add tracing to your GraphQL (v0.0.18+) requests. 

## Use Trace data on the server, exclude it from the GraphQL response

You might want to have Tracing enabled on the server to allow for tools to make use of that data, but you might *_not_* 
want to include tracing in the response. 

Here's an example of disabling the trace from the response of the GraphQL Request (so the consumer won't see it), but 
making use of the trace data on the server, in this case saving the trace to an options table. But you could do anything 
like send the trace to a remote service, or schedule a Cron to do something with it.

```
add_filter( 'graphql_tracing_include_in_response', '__return_false' );
add_action( 'graphql_execute', function() {
	$trace = \WPGraphQL\Extensions\Insights\Tracing::get_trace();
	update_option( 'graphql_trace_yo', $trace );
}, 100 );
```

## Using with Apollo Optics

Currently, there is no built-in solution for sending data to Apollo Optics, but there has been discussion regarding 
potential solutions for getting WPGraphQL Insights trace data over to Optics, so hopefully there will be official 
Apollo Optics support soon!
