<?php
/**
 * Untappd Beer Search — search page
 *
 * @package           UBS
 * @author            Mikko Siikaniemi
 */

/**
 * Add search menu page.
 *
 * @return void
 */
function ubs_add_search_menu_page() {
	// Add search page.
	global $ubs_search_page;
	$ubs_search_page = add_submenu_page( 'edit.php?post_type=beer', __( 'Search Untappd', 'ubs' ), __( 'Search Untappd', 'ubs' ), 'edit_posts', 'ubs-search', 'ubs_render_search_page' );
}
add_action( 'admin_menu', 'ubs_add_search_menu_page' );

/**
 * Enqueue JavaScript files for AJAXifying requests.
 *
 * @param  string $hook Admin page's hook suffix.
 * @return void
 */
function ubs_enqueue_scripts( $hook ) {
	global $ubs_search_page;

	// This is relevant only for the search page. Return early if not there.
	if ( $hook !== $ubs_search_page ) {
		return;
	}
	wp_enqueue_script( 'ubs-ajax', plugin_dir_url( __FILE__ ) . '/js/untappd-beer-search.js', array( 'jquery' ), filemtime( plugin_dir_path( __FILE__ ) . 'js/untappd-beer-search.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'ubs_enqueue_scripts' );

/**
 * Render the beer search page in wp-admin.
 *
 * @return void
 */
function ubs_render_search_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Search Untappd', 'ubs' ); ?></h1>
		<p><?php echo wp_kses_post( 'Search Untappd for a beer (by name). For best results, include brewery name in the beginning, e.g. <em>Mallaskoski Jeriko Cherry Sour Wild Ale</em>.', 'ubs' ); ?></p>
		<form id="ubs-search" action="" method="post">
			<label for="beer-name" class="screen-reader-text"><?php esc_attr_e( 'Beer Name', 'ubs' ); ?></label>
			<input type="text" name="beer_name" size="40" id="beer-name" placeholder="<?php esc_attr_e( 'Beer name...', 'ubs' ); ?>" required />
			<?php wp_nonce_field( 'ubs_search', 'ubs_search_nonce' ); ?>
			<button id="ubs-search-submit" class="button button-primary" type="submit"><?php esc_attr_e( 'Search', 'ubs' ); ?></button>
			<span class="spinner" style="float:none;"></span>
		</form>
		<div id="ubs-untappd-response" style="margin-top: 1em;"></div>
	</div>
	<?php
}

/**
 * Generate the HTML code to render the search results.
 *
 * @param  array $result_array Search results.
 * @return mixed $html         HTML-formatted table.
 */
function ubs_render_search_results( $result_array ) {

	// If no beers found, return empty result early.
	if ( 0 === $result_array['beers']['count'] ) {
		$html = '<p>';
		// translators: amount of search results.
		$html .= __( 'No beers found.', 'ubs' );
		$html .= '</p>';
		return $html;
	}

	$html = '<p>';
	// translators: amount of search results.
	$html .= sprintf( __( 'Found %d results.', 'ubs' ), $result_array['beers']['count'] );
	$html .= '</p>';

	$html .= '<form id="ubs-search-results" action="" method="post">';
	$html .= '<table style="margin-bottom: .5em;" class="widefat striped">';

	$html .= '<thead>';
	$html .= '<th>';
	$html .= __( 'Save', 'ubs' );
	$html .= '</th>';
	$html .= '<th>';
	$html .= __( 'Beer ID', 'ubs' );
	$html .= '</th>';
	$html .= '<th>';
	$html .= __( 'Brewery', 'ubs' );
	$html .= '</th>';
	$html .= '<th>';
	$html .= __( 'Beer Name', 'ubs' );
	$html .= '</th>';
	$html .= '<th>';
	$html .= __( 'Beer Style', 'ubs' );
	$html .= '</th>';
	$html .= '<th>';
	$html .= __( 'ABV%', 'ubs' );
	$html .= '</th>';
	$html .= '<th>';
	$html .= __( 'Already saved?', 'ubs' );
	$html .= '</th>';
	$html .= '</thead>';

	$html .= '<tbody>';
	foreach ( $result_array['beers']['items'] as $beer ) {
		// Get the Untappd beer ID.
		$beer_id = absint( $beer['beer']['bid'] );

		// Start forming the HTML output.
		$html .= '<tr>';
		$html .= '<td>';
		$html .= '<input type="checkbox" name="beer-id[]" value="' . $beer_id . '" id="beer-check-' . $beer_id . '"';
		if ( false === get_post_status( $beer_id ) ) {
			$html .= ' checked="checked"';
		}
		$html .= '>';
		$html .= '</td>';

		$html .= '<td>';
		$html .= $beer_id;
		$html .= '</td>';
		$html .= '<td>';
		$html .= $beer['brewery']['brewery_name'];
		$html .= '</td>';
		$html .= '<td>';
		$html .= $beer['beer']['beer_name'];
		$html .= '</td>';
		$html .= '<td>';
		$html .= $beer['beer']['beer_style'];
		$html .= '</td>';
		$html .= '<td>';
		$html .= number_format( $beer['beer']['beer_abv'], 1 );
		$html .= '</td>';
		$html .= '<td id="beer-save-' . $beer_id . '">';

		$beer_post_id = ubs_maybe_get_beer_cpt_id( $beer_id );
		if ( false !== $beer_post_id ) {
			$html .= __( '☑️', 'ubs' );
			$html .= ' ';
			$html .= __( 'Rating:', 'ubs' );
			$html .= ' ';
			$html .= number_format( get_post_meta( $beer_post_id, 'rating_score', true ), 2 );
		} else {
			$html .= __( '—', 'ubs' );
		}
		$html .= '</td>';
		$html .= '</tr>';
	}
	$html .= '</tbody>';

	$html .= '</table>';
	$html .= wp_nonce_field( 'ubs_save', 'ubs_save_nonce', true, false );
	$html .= '<button class="button button-secondary" type="submit">' . __( 'Save selected', 'ubs' ) . '</button>';
	$html .= '<span class="spinner" style="float:none;"></span>';
	$html .= '<button class="button button-link" style="margin-right: 1em;" id="ubs-select-all">Select all</button>';
	$html .= '<button class="button button-link" id="ubs-select-none">Select none</button>';
	$html .= '</form>';

	$html .= '<p>Hourly API requests limit remaining: ' . $result_array['limit_remaining'] . '</p>';

	return $html;
}

/**
 * Save the selected beers into custom posts in a loop.
 *
 * @param  array $beer_ids Beer  IDs to save to CPT.
 * @return array $return_results Save results.
 */
function ubs_save_beers( $beer_ids ) {

	$return_results = array();
	$status         = '';

	foreach ( $beer_ids as $beer_id ) {

		// Get beer info from API.
		$beer_info = ubs_get_beer_info( absint( $beer_id ) );

		// Process the returned data.
		if ( is_wp_error( $beer_info ) ) {
			$status = $beer_info->get_error_message();
		} elseif ( empty( $beer_info ) ) {
			$status = __( 'No beer found with this ID.', 'ubs' );
		} elseif ( false === empty( $beer_info ) ) {
			$saved_beer = ubs_save_beer( $beer_info['beer'] );
			if ( is_wp_error( $saved_beer ) ) {
				$status = $saved_beer->get_error_message();
			} elseif ( 0 === $saved_beer ) {
				$status = __( 'Saving failed.', 'ubs' );
			} else {
				$status = get_post_meta( $saved_beer, 'rating_score', true );
			}
		}
		$return_results[ $beer_id ] = $status;
	}
	return $return_results;
}

/**
 * Process AJAX request to search for a beer.
 *
 * Echo results HTML.
 *
 * @return void
 */
function ubs_process_ajax_search_results() {

	if ( false === isset( $_POST['ubs_nonce'] ) || false === wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubs_nonce'] ) ), 'ubs_search' ) ) {
		wp_die( esc_attr__( 'Permission check failed. Please reload the page and try again.', 'ubs' ) );
	}

	if ( false === isset( $_POST['beer_name'] ) ) {
		wp_die( esc_attr__( 'Please enter a search term.', 'ubs' ) );
	}

	$beer_name = sanitize_text_field( wp_unslash( $_POST['beer_name'] ) );

	$search_result = ubs_search_beer_in_untappd( $beer_name );
	$results_html  = ubs_render_search_results( $search_result );

	echo $results_html;
	wp_die();
}
add_action( 'wp_ajax_ubs_get_search_results', 'ubs_process_ajax_search_results' );

/**
 * Process AJAX request to search for a beer.
 *
 * Echo results HTML.
 *
 * @return void
 */
function ubs_process_ajax_save_results() {

	if ( false === isset( $_POST['ubs_nonce'] ) || false === wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubs_nonce'] ) ), 'ubs_save' ) ) {
		wp_die( esc_attr__( 'Permission check failed. Please reload the page and try again.', 'ubs' ) );
	}

	// Explode beer IDs from form data.
	$beer_ids = array();
	if ( isset( $_POST['beer_ids'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --
		parse_str( wp_unslash( $_POST['beer_ids'] ), $beer_ids );
	}

	$save_response = ubs_save_beers( $beer_ids['beer-id'] );
	echo wp_json_encode( $save_response );

	wp_die();
}
add_action( 'wp_ajax_ubs_save_selected_results', 'ubs_process_ajax_save_results' );
