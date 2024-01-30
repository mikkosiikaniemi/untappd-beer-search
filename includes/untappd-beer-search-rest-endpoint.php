<?php
/**
 * Untappd Beer Search — custom REST API endpoint to return ratings by Alko ID
 *
 * @package           UBS
 * @author            Mikko Siikaniemi
 */

/**
 * Get Untappd beer ratings by Alko ID. A callback function.
 *
 * Notice that we also process the additional Alko IDs associated,
 * and inject them too, not just the parent IDs.
 *
 * @return array $ratings Beer ratings.
 */
function ubs_get_ratings() {

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

	return $ratings;
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
}
add_action( 'rest_api_init', 'ubs_register_ratings_rest_route' );
