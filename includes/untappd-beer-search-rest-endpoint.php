<?php
/**
 * Untappd Beer Search — custom REST API endpoint to return ratings by Alko ID
 *
 * @package           UBS
 * @author            Mikko Siikaniemi
 */

/**
 * Log extension-originated REST requests to debug log.
 *
 * @param  WP_REST_Request|null $request Request object.
 * @param  string               $message Log message.
 * @return void
 */
function ubs_log_extension_rest_request( $request, $message ) {
	if ( ! ( $request instanceof WP_REST_Request ) ) {
		return;
	}

	$source = sanitize_key( (string) $request->get_param( 'source' ) );
	if ( 'extension' !== $source ) {
		return;
	}

	error_log( 'UBS REST: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

/**
 * Get Untappd beer ratings by Alko ID. A callback function.
 *
 * Notice that we also process the additional Alko IDs associated,
 * and inject them too, not just the parent IDs.
 *
 * @param  WP_REST_Request|null $request Request data.
 * @return array $ratings Beer ratings.
 */
function ubs_get_ratings( $request = null ) {
	ubs_log_extension_rest_request( $request, 'Bulk ratings request received.' );

	$ratings = array();

	$args = array(
		'post_type'              => 'beer',
		'posts_per_page'         => 5000,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();

			// Get the rating from post meta.
			$rating                      = get_post_meta( $query->post->ID, 'rating_score', true );
			$ratings[ $query->post->ID ] = $rating;

			// Get any additional Alko IDs.
			$additional_alko_ids = get_post_meta( $query->post->ID, 'additional_alko_id' );

			// Inject additional IDs with ratings.
			if ( false === empty( $additional_alko_ids ) ) {
				foreach ( $additional_alko_ids as $alko_id ) {
					$ratings[ $alko_id ] = $rating;
				}
			}
		}
	}

	ubs_log_extension_rest_request( $request, 'Bulk ratings response count: ' . count( $ratings ) );

	return $ratings;
}

/**
 * Get beer post ID by Alko beverage ID with minimal query cost.
 *
 * @param  int $alko_id Alko beverage ID.
 * @return int|false    Matching beer post ID or false.
 */
function ubs_get_beer_post_id_by_alko_id_fast( $alko_id ) {
	// First try the primary Alko ID meta key.
	$query = new WP_Query(
		array(
			'post_type'              => 'beer',
			'posts_per_page'         => 1,
			'post_status'            => 'any',
			'fields'                 => 'ids',
			'meta_key'               => 'alko_id',
			'meta_value'             => $alko_id,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		)
	);

	if ( false === empty( $query->posts ) ) {
		return (int) $query->posts[0];
	}

	// Fallback to additional associated Alko IDs.
	$query = new WP_Query(
		array(
			'post_type'              => 'beer',
			'posts_per_page'         => 1,
			'post_status'            => 'any',
			'fields'                 => 'ids',
			'meta_key'               => 'additional_alko_id',
			'meta_value'             => $alko_id,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		)
	);

	if ( false === empty( $query->posts ) ) {
		return (int) $query->posts[0];
	}

	return false;
}

/**
 * Get one Untappd beer rating by Alko beverage ID.
 *
 * @param  WP_REST_Request $request Request data.
 * @return array|WP_Error
 */
function ubs_get_rating_by_alko_id( $request ) {
	$alko_id = absint( $request->get_param( 'alko_id' ) );
	ubs_log_extension_rest_request( $request, 'Single rating request received for Alko ID: ' . $alko_id );

	if ( 0 === $alko_id ) {
		return new WP_Error(
			'ubs_invalid_alko_id',
			__( 'Invalid Alko beverage ID.', 'ubs' ),
			array( 'status' => 400 )
		);
	}

	$cache_key = 'ubs_rating_by_alko_id_' . $alko_id;
	$cached    = get_transient( $cache_key );

	if ( false !== $cached ) {
		ubs_log_extension_rest_request( $request, 'Single rating cache hit for Alko ID: ' . $alko_id );
		return $cached;
	}

	$beer_post_id = ubs_get_beer_post_id_by_alko_id_fast( $alko_id );

	if ( false === $beer_post_id ) {
		ubs_log_extension_rest_request( $request, 'Single rating not found for Alko ID: ' . $alko_id );
		return new WP_Error(
			'ubs_rating_not_found',
			__( 'Rating not found for the given Alko beverage ID.', 'ubs' ),
			array( 'status' => 404 )
		);
	}

	$rating = get_post_meta( $beer_post_id, 'rating_score', true );

	$response = array(
		'alko_id' => $alko_id,
		'rating'  => (float) $rating,
	);

	// Keep a short cache to speed up repeated requests while limiting stale data.
	set_transient( $cache_key, $response, 10 * MINUTE_IN_SECONDS );
	ubs_log_extension_rest_request( $request, 'Single rating cache miss for Alko ID: ' . $alko_id . '. Rating: ' . $response['rating'] );

	return $response;
}

/**
 * Register REST route for ratings.
 *
 * @return void
 */
function ubs_register_ratings_rest_route() {
	register_rest_route(
		'untappd-beer-search/v1',
		'/ratings',
		array(
			'methods'  => 'GET',
			'callback' => 'ubs_get_ratings',
		)
	);

	register_rest_route(
		'untappd-beer-search/v1',
		'/ratings/(?P<alko_id>\\d+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'ubs_get_rating_by_alko_id',
			'permission_callback' => '__return_true',
			'args'                => array(
				'alko_id' => array(
					'required'          => true,
					'validate_callback' => function( $param ) {
						return is_numeric( $param ) && (int) $param > 0;
					},
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'ubs_register_ratings_rest_route' );

/**
 * Invalidate single-rating REST caches when a beer is updated.
 *
 * @param  int $post_id Beer post ID.
 * @return void
 */
function ubs_invalidate_rating_cache_on_beer_save( $post_id ) {
	$alko_id = absint( get_post_meta( $post_id, 'alko_id', true ) );

	if ( 0 !== $alko_id ) {
		delete_transient( 'ubs_rating_by_alko_id_' . $alko_id );
	}

	$additional_alko_ids = get_post_meta( $post_id, 'additional_alko_id' );

	if ( false === empty( $additional_alko_ids ) ) {
		foreach ( $additional_alko_ids as $additional_alko_id ) {
			$additional_alko_id = absint( $additional_alko_id );
			if ( 0 !== $additional_alko_id ) {
				delete_transient( 'ubs_rating_by_alko_id_' . $additional_alko_id );
			}
		}
	}
}
add_action( 'save_post_beer', 'ubs_invalidate_rating_cache_on_beer_save' );
