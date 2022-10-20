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

	if ( empty( $decoded_response['response'] ) && 200 !== $decoded_response['meta']['code'] ) {
		return new WP_Error( -2, $decoded_response['meta']['error_detail'] );
	}

	$return_array                    = $decoded_response['response'];
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

/**
 * Scrape single beer availability from Alko website by product & store ID.
 *
 * @param  int $alko_id Alko product number.
 * @param  int $post_id Post ID to update.
 * @return void
 */
function ubs_update_alko_availability( $alko_id, $post_id ) {

	$favorite_alko_store = ubs_get_favorite_alko_store();

	$alko_store_product_availability_url = 'https://www.alko.fi/INTERSHOP/web/WFS/Alko-OnlineShop-Site/fi_FI/-/EUR/ALKO_ViewStore-Stock?SKU=' . $alko_id . '&StoreID=' . $favorite_alko_store;

	$alko_response = wp_remote_get( $alko_store_product_availability_url );

	if ( is_wp_error( $alko_response ) ) {
		return new WP_Error( -1, __( 'Alko availability request failed.', 'ubs' ) );
	}

	// Get the API request response body.
	$alko_response_body = wp_remote_retrieve_body( $alko_response );

	$amount_matching = preg_match( '/(?<=<span>Tuotem&auml;&auml;r&auml; myym&auml;l&auml;ss&auml;<\/span>)(.*)(?=<\/td>)/m', $alko_response_body, $matches );

	if ( false !== strpos( $matches[0], '-' ) ) {
		$amount = absint( explode( '-', $matches[0] )[0] );
	} elseif ( '0' === $matches[0] ) {
		$amount = 0;
	} else {
		$amount = absint( $matches[0] );
	}

	update_post_meta( $post_id, 'availability_' . $favorite_alko_store, $amount );
	update_post_meta( $post_id, 'availability_updated_' . $favorite_alko_store, current_time( 'timestamp' ) );
}
