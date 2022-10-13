<?php
/**
 * Untappd Beer Search — remote API requests
 *
 * @package           UBS
 * @author            Mikko Siikaniemi
 */

// Define constants.
DEFINE( 'UNTAPPD_API_BASE', 'https://api.untappd.com/v4/' );

/**
 * Search for a beer in Untappd (by name).
 * Make the remote request to Untappd API.
 *
 * @param  string $beer_name    Beer name.
 * @return array  $return_array Array of results (raw).
 */
function ubs_search_beer_in_untappd( $beer_name ) {

	$untappd_search_url = UNTAPPD_API_BASE . 'search/beer';

	$query_args = array_merge(
		ubs_get_api_settings(),
		array( 'q' => $beer_name ),
	);

	$untappd_search_url = add_query_arg( $query_args, $untappd_search_url );

	$untappd_response = wp_remote_get( $untappd_search_url );

	if ( is_wp_error( $untappd_response ) ) {
		return new WP_Error( -1, __( 'Untappd API request failed.', 'ubs' ) );
	}

	// Get the API request response body.
	$untappd_response_body = wp_remote_retrieve_body( $untappd_response );

	// Get the API rate limit remaining amount (100 per hour to start with).
	$untappd_api_limit_remaining = wp_remote_retrieve_header( $untappd_response, 'x-ratelimit-remaining' );

	$decoded_response = json_decode( $untappd_response_body, true );

	if ( '0' === $untappd_api_limit_remaining ) {
		$return_array = $decoded_response['meta'];
	} else {
		$return_array = $decoded_response['response'];
	}

	$return_array['limit_remaining'] = $untappd_api_limit_remaining;

	return $return_array;
}

/**
 * Get single beer info in Untappd (by ID).
 * Make the remote request to Untappd API.
 *
 * @param  string $beer_id      Beer ID in Untappd.
 * @return array  $return_array Array of results (raw).
 */
function ubs_get_beer_info( $beer_id ) {
	$untappd_search_url = UNTAPPD_API_BASE . 'beer/info/' . $beer_id;

	$untappd_search_url = add_query_arg( ubs_get_api_settings(), $untappd_search_url );

	$untappd_response = wp_remote_get( $untappd_search_url );

	if ( is_wp_error( $untappd_response ) ) {
		return new WP_Error( -1, __( 'Untappd API request failed.', 'ubs' ) );
	}

	// Get the API request response body.
	$untappd_response_body = wp_remote_retrieve_body( $untappd_response );

	// Get the API rate limit remaining amount (100 per hour to start with).
	$untappd_api_limit_remaining = wp_remote_retrieve_header( $untappd_response, 'x-ratelimit-remaining' );

	$decoded_response = json_decode( $untappd_response_body, true );

	$return_array                    = $decoded_response['response'];
	$return_array['limit_remaining'] = $untappd_api_limit_remaining;

	return $return_array;
}
