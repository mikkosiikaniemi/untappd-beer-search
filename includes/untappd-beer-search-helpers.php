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

/**
 * Fuzzy search for Alko catalog for a beer name.
 *
 * @param  string $beer_name_to_search Beer name to search for.
 * @return array  $return_results      Results (best 10).
 */
function ubs_search_alko_catalog_for_name( $beer_name_to_search ) {

	// Get Alko catalog.
	$beers = get_option( 'ubs_beers', true );

	$matches        = array();
	$return_results = array();

	// Loop through beers and calculate Levenshtein distance between names.
	foreach ( $beers as $alko_id => $beer_name ) {

		$matches[ $beer_name ]['alko_id']      = $alko_id;
		$matches[ $beer_name ]['beer_name']    = $beer_name;
		$matches[ $beer_name ]['levenshtein']  = levenshtein( $beer_name_to_search, $beer_name, 1, 5, 5 );
		$matches[ $beer_name ]['similar_text'] = similar_text( $beer_name_to_search, $beer_name );
	}

	// Sort matches in ascending order by Levenshtein distance.
	usort(
		$matches,
		function( $a, $b ) {
			return $a['levenshtein'] - $b['levenshtein'];
		}
	);

	// Return best 10 matches.
	$array_keys = array_keys( $matches );
	for ( $i = 0; $i < 20; $i++ ) {
		$return_results[ $array_keys[ $i ] ] = $matches[ $array_keys[ $i ] ];
	}

	return $return_results;
}

/**
 * Get favorite Alko store ID, if set.
 *
 * @return false|int Favorite Alko store ID, if set. Otherwise false.
 */
function ubs_get_favorite_alko_store() {
	$untappd_settings = get_option( 'ubs_settings' );

	if ( empty( $untappd_settings['ubs_setting_alko_favorite_store'] ) ) {
		return false;
	} else {
		return absint( $untappd_settings['ubs_setting_alko_favorite_store'] );
	}
}
