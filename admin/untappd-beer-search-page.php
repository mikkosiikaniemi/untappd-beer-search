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
	wp_enqueue_style( 'ubs-styles', plugin_dir_url( __FILE__ ) . '/css/untappd-beer-search.css', array(), filemtime( plugin_dir_path( __FILE__ ) . 'css/untappd-beer-search.css' ) );
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
		<p>
			<?php
			// translators: number of beers in Alko catalog not yet saved.
			echo wp_kses_post( sprintf( __( 'There are %d beers in Alko product catalog that are not yet saved. Use the "Populate" button to search for one of those.', 'ubs' ), ( count( get_option( 'ubs_beers', true ) ) - wp_count_posts( 'beer' )->publish ) ) );
			?>
		</p>
		<form id="ubs-search" action="" method="post">
			<label for="beer-name" class="screen-reader-text"><?php esc_attr_e( 'Beer Name', 'ubs' ); ?></label>
			<input type="text" name="beer_name" id="beer-name" placeholder="<?php esc_attr_e( 'Beer name...', 'ubs' ); ?>" required list="beer-names" autofocus />
			<datalist id="beer-names">
				<?php
				$beers = get_option( 'ubs_beers' );
				if ( false !== $beers && false === empty( $beers ) ) {
					foreach ( $beers as $alko_id => $beer_name ) {
						echo '<option data-alko-id="' . absint( $alko_id ) . '">' . esc_attr( $beer_name ) . '</option>';
					}
				}
				?>
			</datalist>
			<?php wp_nonce_field( 'ubs_search', 'ubs_search_nonce' ); ?>
			<button id="ubs-search-submit" class="button button-primary" type="submit"><?php esc_attr_e( 'Search', 'ubs' ); ?></button>
			<button id="ubs-alko-populate" class="button button-secondary" type=""><?php esc_attr_e( 'Populate', 'ubs' ); ?></button>
			<span class="spinner"></span>
		</form>
		<div id="ubs-untappd-response"></div>
	</div>
	<?php
}

/**
 * Generate the HTML code to render the search results.
 *
 * @param  array  $result_array Search results.
 * @param  int    $alko_id      Alko product ID pre-selected in search input.
 * @param  string $beer_name    Beer name that was searched for.
 * @return mixed  $html         HTML-formatted table.
 */
function ubs_render_search_results( $result_array, $alko_id = false, $beer_name ) {

	// If rate limit reached, return early.
	if ( is_wp_error( $result_array ) ) {
		$html  = '<p>';
		$html .= esc_attr( $result_array->get_error_message() );
		$html .= '</p>';
		return $html;
	}

	// If no beers found, return empty result early.
	if ( 0 === $result_array['beers']['count'] ) {
		$html = '<p>';
		// translators: amount of search results.
		$html .= __( 'No beers found.', 'ubs' );
		$html .= '</p>';
		$html .= '<p>Hourly API requests limit remaining: ' . $result_array['limit_remaining'] . '</p>';
		return $html;
	}

	$html = '<p>';
	// translators: amount of search results.
	$html .= sprintf( __( 'Found %d results.', 'ubs' ), $result_array['beers']['count'] );
	$html .= ' ';
	$html .= __( 'The beer with most Untappd check-ins has been preselected.', 'ubs' );
	$html .= '</p>';

	$html .= '<form id="ubs-search-results" action="" method="post">';

	$html .= '<p>';
	if ( false !== $alko_id ) {
		// translators: Alko product number.
		$html .= sprintf( __( 'Alko product number <kbd>%1$d</kbd> (<a target="_blank" href="https://www.alko.fi/tuotteet/%1$d">Alko product link<span class="dashicons dashicons-external"></span></a>) will be associated with the selected beer.', 'ubs' ), $alko_id );
		$html .= '<input type="hidden" id="alko_id" name="alko_id" value="' . $alko_id . '" />';
	} else {
		$html .= '<label for="alko_id">';
		$html .= __( 'Please select Alko product number to associate with the beer.', 'ubs' );
		$html .= '</label>';

		// Provide options from Alko catalog to select ID from.
		$html .= '<select id="alko_id" name="alko_id" required>';

		// Make fuzzy search for name against Alko catalog.
		$fuzzy_matches = ubs_search_alko_catalog_for_name( $beer_name );
		foreach ( $fuzzy_matches as $match_id => $match_data ) {
			$html .= '<option value="' . $match_data['alko_id'] . '">';
			$html .= esc_attr( $match_data['alko_id'] . ' — ' . $match_data['beer_name'] );
			$html .= '</option>';
		}
		$html .= '</select>';
	}
	$html .= '</p>';

	$html .= '<table class="ubs-search-results widefat striped">';

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
	$html .= __( 'IBU', 'ubs' );
	$html .= '</th>';
	$html .= '<th>';
	$html .= __( 'Already saved?', 'ubs' );
	$html .= '</th>';
	$html .= '</thead>';

	$beer_with_most_checkins = false;
	$highest_checkin_count   = false;

	// Determine the beer with most checkins.
	foreach ( $result_array['beers']['items'] as $beer ) {
		$checkin_count = $beer['checkin_count'];
		if ( $checkin_count > $highest_checkin_count ) {
			$highest_checkin_count   = $checkin_count;
			$beer_with_most_checkins = absint( $beer['beer']['bid'] );
		}
	}

	$html .= '<tbody>';
	foreach ( $result_array['beers']['items'] as $beer ) {
		// Get the Untappd beer ID.
		$beer_id = absint( $beer['beer']['bid'] );

		// Start forming the HTML output.
		$html .= '<tr>';
		$html .= '<td>';
		$html .= '<input type="radio" name="beer-id[]" value="' . $beer_id . '" id="beer-check-' . $beer_id . '"';

		$beer_post_id = ubs_maybe_get_beer_cpt_id( $beer_id );
		if ( $beer_id === $beer_with_most_checkins && false === get_post_status( $beer_post_id ) ) {
			$html .= ' checked="checked"';
		} elseif ( false !== get_post_status( $beer_post_id ) ) {
			$html .= ' disabled="disabled"';
		}
		$html .= '>';
		$html .= '</td>';

		$html .= '<td>';
		$html .= $beer_id;
		$html .= '</td>';
		$html .= '<td>';
		$html .= esc_attr( $beer['brewery']['brewery_name'] );
		$html .= '</td>';
		$html .= '<td>';

		$beer_url = esc_url( 'https://untappd.com/b/' . $beer['beer']['beer_slug'] . '/' . $beer_id );

		$html .= '<a target="_blank" href="' . $beer_url . '">' . esc_attr( $beer['beer']['beer_name'] ) . '<span class="dashicons dashicons-external"></span></a>';
		$html .= '</td>';
		$html .= '<td>';
		$html .= esc_attr( $beer['beer']['beer_style'] );
		$html .= '</td>';
		$html .= '<td>';
		$html .= number_format( $beer['beer']['beer_abv'], 1 );
		$html .= '</td>';
		$html .= '<td>';
		$html .= number_format( $beer['beer']['beer_ibu'], 1 );
		$html .= '</td>';
		$html .= '<td id="beer-save-' . $beer_id . '">';

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
	$html .= '<span class="spinner"></span>';
	$html .= '</form>';

	$html .= '<p>Hourly API requests limit remaining: ' . $result_array['limit_remaining'] . '</p>';

	return $html;
}

/**
 * Save the selected beer into a custom post .
 *
 * @param  array $beer_id        Beer ID to save to CPT.
 * @param  int   $alko_id        Alko product number.
 * @return array $return_results Save results.
 */
function ubs_preprocess_beer_for_saving( $beer_id, $alko_id ) {

	$return_results = array();
	$status         = '';

	// Get beer info from API.
	$beer_info = ubs_get_beer_info( absint( $beer_id ) );

	// Process the returned data.
	if ( is_wp_error( $beer_info ) ) {
		$status = $beer_info->get_error_message();
	} elseif ( empty( $beer_info ) ) {
		$status = __( 'No beer found with this ID.', 'ubs' );
	} elseif ( false === empty( $beer_info ) ) {
		$saved_beer = ubs_save_beer( $beer_info['beer'], $alko_id );
		if ( is_wp_error( $saved_beer ) ) {
			$status = $saved_beer->get_error_message();
		} elseif ( 0 === $saved_beer ) {
			$status = __( 'Saving failed.', 'ubs' );
		} else {
			$status = get_post_meta( $saved_beer, 'rating_score', true );
		}
	}
	$return_results[ $beer_id ] = $status;

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
	$alko_id   = false;

	// Get the Alko product number.
	if ( false === empty( $_POST['alko_id'] ) ) {
		$alko_id = absint( wp_unslash( $_POST['alko_id'] ) );
	}

	// Search Untappd by beer name.
	$search_result = ubs_search_beer_in_untappd( $beer_name );

	// Render search results, hang on to the Alko product number.
	$results_html = ubs_render_search_results( $search_result, $alko_id, $beer_name );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

	// Get beer ID from form data.
	$beer_id = false;
	if ( isset( $_POST['beer_id'] ) ) {
		$beer_id = absint( $_POST['beer_id'] );
	}

	// Get Alko product number from form data.
	$alko_id = false;
	if ( isset( $_POST['alko_id'] ) ) {
		$alko_id = absint( $_POST['alko_id'] );
	}

	$save_response = ubs_preprocess_beer_for_saving( $beer_id, $alko_id );
	echo wp_json_encode( $save_response );

	wp_die();
}
add_action( 'wp_ajax_ubs_save_selected_results', 'ubs_process_ajax_save_results' );

/**
 * Process AJAX request to search for a beer.
 *
 * Echo results HTML.
 *
 * @return void
 */
function ubs_populate_search_field_with_alko_product() {

	if ( false === isset( $_POST['ubs_nonce'] ) || false === wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubs_nonce'] ) ), 'ubs_search' ) ) {
		wp_die( esc_attr__( 'Permission check failed. Please reload the page and try again.', 'ubs' ) );
	}

	$beers = get_option( 'ubs_beers', true );

	while ( $beers ) {
		$random_beer = array_rand( $beers );
		if ( false === get_post_status( $random_beer ) ) {
			echo html_entity_decode( $beers[ $random_beer ], ENT_QUOTES, 'UTF-8' );
			wp_die();
		}
	}

	wp_die();
}
add_action( 'wp_ajax_ubs_populate_alko_product', 'ubs_populate_search_field_with_alko_product' );
