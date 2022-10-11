<?php
/**
 * Untappd Beer Search — helper functions
 *
 * @package           UBS
 * @author            Mikko Siikaniemi
 */

/**
 * Determine if a beer has already been saved as CPT. Perform a meta query.
 *
 * @param  int $beer_id Beer ID.
 * @return bool|int Is beer saved already? Return post ID if yes, otherwise false.
 */
function ubs_maybe_get_beer_cpt_id( $beer_id ) {

	// Formulate the query to search for a beer by Untappd ID.
	// Yes, there is a meta query which may be slow but necessary, thus silencing code sniffer warning.
	// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	$beer_query = new WP_Query(
		array(
			'posts_per_page'         => 1,
			'post_type'              => 'beer',
			'meta_key'               => 'bid',
			'meta_value'             => $beer_id,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);

	// If we found a post with the corresponding beer ID meta, return post ID.
	if ( 1 === count( $beer_query->posts ) ) {
		return $beer_query->posts[0]->ID;
	}

	// If no posts found, return false.
	return false;
}

/**
 * Get beer Untappd rating score (from post meta).
 *
 * @param  object $object Post object.
 * @return string         Rating score.
 */
function ubs_get_beer_rating( $object ) {
	return get_post_meta( $object['id'], 'rating_score', true );
}
