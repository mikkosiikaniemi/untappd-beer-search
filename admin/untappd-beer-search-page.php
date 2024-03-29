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
			// Get Alko beers catalog.
			$beers_alko = get_option( 'ubs_beers' );

			if ( false === $beers_alko ) {
				// translators: plugin settings page URL.
				echo wp_kses_post( sprintf( __( '<span class="dashicons dashicons-warning"></span> Alko price sheet has not been fetched. Go to <a href="%s">Settings</a> and fetch the data first.', 'ubs' ), menu_page_url( 'ubs-settings', false ) ) );
				return;
			}

			// Get all saved beers.
			$saved_beers = ubs_get_saved_beers_alko_ids();

			// Determine the number of unsaved beers.
			foreach ( $beers_alko as $beer_alko_id => $beer_name ) {
				if ( false !== in_array( $beer_alko_id, $saved_beers, true ) ) {
					unset( $beers_alko[ $beer_alko_id ] );
				}
			}

			if ( count( $beers_alko ) >= 1 ) {
				// translators: number of beers in Alko catalog not yet saved.
				echo wp_kses_post( sprintf( _n( 'There is <kbd><span id="ubs-beers-left-to-save">%d</span></kbd> beer in Alko product catalog that is not yet saved. Use the "Populate" button to search for it.', 'There are <kbd><span id="ubs-beers-left-to-save">%d</span></kbd> beers in Alko product catalog that are not yet saved. Use the "Populate" button to search for one of those.', count( $beers_alko ), 'ubs' ), count( $beers_alko ) ) );
			} else {
				esc_html_e( 'You have saved all Alko beers. Congratulations! 👍', 'ubs' );
			}

			?>
		</p>
		<form id="ubs-search" action="" method="post">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="beer-name"><?php esc_attr_e( 'Beer Name', 'ubs' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" name="beer_name" id="beer-name" placeholder="<?php esc_attr_e( 'Beer name...', 'ubs' ); ?>" data-placeholder-empty="<?php esc_attr_e( 'Beer name...', 'ubs' ); ?>" data-placeholder-populating="<?php esc_attr_e( 'Populating...', 'ubs' ); ?>" required list="beer-names" autofocus />
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
					</td>
				</tr>
			</table>
			<?php wp_nonce_field( 'ubs_search', 'ubs_search_nonce' ); ?>
			<p class="submit">
				<input id="ubs-search-submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Search', 'ubs' ); ?>" />
				<button id="ubs-alko-populate" class="button button-secondary"
				<?php
				if ( count( $beers_alko ) < 1 ) {
					echo 'disabled';}
				?>
				><?php esc_attr_e( 'Populate and search', 'ubs' ); ?></button>
				<span class="spinner"></span>
				</p>
		</form>
		<div id="ubs-untappd-response"></div>
	</div>
	<?php
}

/**
 * Generate the HTML code to render the search results.
 *
 * @param  array  $result_array Search results.
 * @param  string $beer_name    Beer name that was searched for.
 * @param  int    $alko_id      Alko product ID pre-selected in search input.
 * @return mixed  $html         HTML-formatted table.
 */
function ubs_render_search_results( $result_array, $beer_name, $alko_id = false ) {

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
		$html .= '</p><p>';
		$html .= __( 'Sometimes trimming vintages or suffixes like IPA or DDH from beer name may help.', 'ubs' );
		$html .= '</p>';
		$html .= '<button class="button button-secondary" id="ubs-search-remove-suffixes">' . __( 'Trim & search again', 'ubs' ) . '</button>';
		$html .= '<p>Hourly API requests limit remaining: <kbd>' . $result_array['limit_remaining'] . '</kbd></p>';
		return $html;
	}

	$html = '<p>';
	// translators: amount of search results.
	$html .= sprintf( _n( 'Found <kbd>%d</kbd> result.', 'Found <kbd>%d</kbd> results.', $result_array['beers']['count'], 'ubs' ), $result_array['beers']['count'] );
	$html .= ' ';
	if ( $result_array['beers']['count'] > 1 ) {
		$html .= __( 'The beer with most Untappd check-ins has been preselected.', 'ubs' );
	}
	$html .= '</p>';

	$html .= '<form id="ubs-search-results" action="" method="post">';

	$html .= '<p>';
	if ( false !== $alko_id ) {
		// translators: Alko product number.
		$html .= sprintf( __( 'Alko product number <kbd>%1$s</kbd> (<a target="_blank" href="https://www.alko.fi/tuotteet/%1$s">Alko product link<span class="dashicons dashicons-external"></span></a>) will be associated with the selected beer.', 'ubs' ), str_pad( $alko_id, 6, '0', STR_PAD_LEFT ) );
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
	$html .= '<th class="ubs-search-results__style">';
	$html .= __( 'Beer Style', 'ubs' );
	$html .= '</th>';
	$html .= '<th>';
	$html .= __( 'ABV%', 'ubs' );
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

	// Initialize a variable to determine if a candidate for saving found.
	$candidate_for_saving_not_found = true;

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
			$html                          .= ' checked="checked"';
			$candidate_for_saving_not_found = false;
		} elseif ( false !== get_post_status( $beer_post_id ) ) {
			$html .= ' disabled="disabled"';
		}
		$html .= '>';
		$html .= '</td>';

		$beer_url = esc_url( 'https://untappd.com/b/' . $beer['beer']['beer_slug'] . '/' . $beer_id );

		$html .= '<td>';
		$html .= '<a target="_blank" href="' . $beer_url . '">' . $beer_id . '<span class="dashicons dashicons-external"></span></a>';
		$html .= '</td>';
		$html .= '<td>';
		$html .= esc_attr( $beer['brewery']['brewery_name'] );
		$html .= '</td>';
		$html .= '<td>';
		$html .= esc_attr( $beer['beer']['beer_name'] );
		$html .= '</td>';
		$html .= '<td  class="ubs-search-results__style">';
		$html .= esc_attr( $beer['beer']['beer_style'] );
		$html .= '</td>';
		$html .= '<td>';
		$html .= number_format( $beer['beer']['beer_abv'], 1 );
		$html .= '</td>';
		$html .= '<td id="beer-save-' . $beer_id . '">';

		if ( false !== $beer_post_id ) {
			$saved_alko_id = get_post_meta( $beer_post_id, 'alko_id', true );
			$html         .= __( '☑️', 'ubs' );
			$html         .= ' <a target="_blank" href="https://www.alko.fi/tuotteet/' . str_pad( absint( $saved_alko_id ), 6, '0', STR_PAD_LEFT ) . '" title="' . absint( $saved_alko_id ) . ' &mdash; ' . get_the_title( $beer_post_id ) . '">' . $beer_post_id . '<span class="dashicons dashicons-external"></span></a>';

			$additional_alko_ids = get_post_meta( $beer_post_id, 'additional_alko_id' );

			if ( $saved_alko_id !== $alko_id && false === in_array( $alko_id, $additional_alko_ids, true ) ) {
				$html .= '<button class="button button-secondary button-small ubs-associate-alko" data-post-id="' . $beer_post_id . '" title="' . esc_html__( 'Associate with this saved beer', 'ubs' ) . '">' . __( 'Associate', 'ubs' ) . '</button>';
			}
		} else {
			$html .= __( '—', 'ubs' );
		}
		$html .= '</td>';
		$html .= '</tr>';
	}
	$html .= '</tbody>';

	$html .= '</table>';
	$html .= wp_nonce_field( 'ubs_save', 'ubs_save_nonce', true, false );

	// Disable save buttons if only one result found and it has already been saved.
	$single_result_already_saved = false;
	if ( 1 === $result_array['beers']['count'] && false !== get_post_status( ubs_maybe_get_beer_cpt_id( $result_array['beers']['items'][0]['beer']['bid'] ) ) ) {
		$single_result_already_saved = true;
	}

	$html .= '<button name="ubs-save" class="button button-primary" type="submit"';
	if ( $single_result_already_saved || $candidate_for_saving_not_found ) {
		$html .= ' disabled="disabled"';
	}
	$html .= '">' . __( 'Save selected', 'ubs' ) . '</button>';
	$html .= '<button name="ubs-save-and-populate" class="button button-secondary" id="ubs-save-and-populate"';
	if ( $single_result_already_saved || $candidate_for_saving_not_found ) {
		$html .= ' disabled="disabled"';
	}
	$html .= '">' . __( 'Save and populate next', 'ubs' ) . '</button>';
	$html .= '<span class="spinner"></span>';
	$html .= '</form>';

	$html .= '<p>Hourly API requests limit remaining: <kbd><span id="ubs-limit-remaining">' . $result_array['limit_remaining'] . '</span></kbd></p>';

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
	$return_results = array(
		'status'          => $status,
		'limit_remaining' => $beer_info['limit_remaining'],
		'beer_id'         => $beer_id,
	);

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
		wp_die( esc_attr__( 'Nonce check failed. Please reload the page and try again.', 'ubs' ) );
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
	$results_html = ubs_render_search_results( $search_result, $beer_name, $alko_id );

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
		wp_die( esc_attr__( 'Nonce check failed. Please reload the page and try again.', 'ubs' ) );
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
		wp_die( esc_attr__( 'Nonce check failed. Please reload the page and try again.', 'ubs' ) );
	}

	// Get Alko beer catalog.
	$beers = get_option( 'ubs_beers' );

	// Get all saved beers.
	$saved_alko_beers_ids = ubs_get_saved_beers_alko_ids();

	// Determine beers that are not yet saved by eliminating already saved ones.
	foreach ( $saved_alko_beers_ids as $alko_id ) {
		unset( $beers[ $alko_id ] );
	}
	asort( $beers );

	$number_of_beers_left_to_save = count( $beers );

	for ( $i = 0; $i < $number_of_beers_left_to_save; $i++ ) {
		$random_beer_id = array_rand( $beers );
		if ( false === get_post_status( ubs_maybe_get_beer_cpt_id( $random_beer_id, 'alko' ) ) ) {
			wp_send_json_success(
				array(
					'alko_id'            => $random_beer_id,
					'beer_name'          => html_entity_decode( $beers[ $random_beer_id ], ENT_QUOTES, 'UTF-8' ),
					'beers_left_to_save' => $number_of_beers_left_to_save,
				)
			);
		}
	}

	if ( $number_of_beers_left_to_save < 1 ) {
		wp_send_json_error( new WP_Error( -1, __( 'Congratulations! All beers have been saved.', 'ubs' ) ) );
	}

	wp_die();
}
add_action( 'wp_ajax_ubs_populate_alko_product', 'ubs_populate_search_field_with_alko_product' );

/**
 * Process AJAX request to search for a beer.
 *
 * Echo results HTML.
 *
 * @return void
 */
function ubs_assosiate_additional_alko_id() {

	if ( false === isset( $_POST['ubs_nonce'] ) || false === wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubs_nonce'] ) ), 'ubs_search' ) ) {
		wp_die( esc_attr__( 'Nonce check failed. Please reload the page and try again.', 'ubs' ) );
	}

	// Get original "mother" ID from form data.
	$original_alko_id = false;
	if ( isset( $_POST['original_alko_id'] ) ) {
		$original_alko_id = absint( $_POST['original_alko_id'] );
	}

	// Get additional Alko product number to associate to original.
	$additional_alko_id = false;
	if ( isset( $_POST['additional_alko_id'] ) ) {
		$additional_alko_id = absint( $_POST['additional_alko_id'] );
	}

	$meta_saved = add_post_meta( $original_alko_id, 'additional_alko_id', $additional_alko_id );

	if ( false !== $meta_saved ) {
		wp_send_json_success();
	} else {
		wp_send_json_error();
	}

	wp_die();
}
add_action( 'wp_ajax_ubs_associate_additional_alko_id', 'ubs_assosiate_additional_alko_id' );
