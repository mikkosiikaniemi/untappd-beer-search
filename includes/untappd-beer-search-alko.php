<?php
/**
 * Untappd Beer Search — fetch and process Alko price sheet (XLSX)
 *
 * Price sheet is available at:
 * https://www.alko.fi/valikoimat-ja-hinnasto/hinnasto
 *
 * XLSX processing is done with help of Shuchkin\SimpleXLSX.
 * https://github.com/shuchkin/simplexlsx
 *
 * @package           UBS
 * @author            Mikko Siikaniemi
 */

// Load composerized dependencies.
require plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';
use Shuchkin\SimpleXLSX;

/**
 * Fetch and process Alko price sheet.
 *
 * @return string|WP_Error Update timestamp, if successful. WP_Error if errored.
 */
function ubs_process_alko_price_sheet() {

	// Get settings to determine the Alko price sheet URL.
	$options              = get_option( 'ubs_settings' );
	$alko_price_sheet_url = esc_url( $options['ubs_setting_alko_price_sheet'] );

	// Remotely get the price sheet file.
	$response      = wp_remote_get( $alko_price_sheet_url );
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );

	if ( 200 !== $response_code || empty( $response_body ) ) {
		return new WP_Error( -2, esc_html__( '<span class="dashicons dashicons-warning"></span> Remote request to Alko price sheet failed. URL: ', 'ubs' ) . esc_url( $alko_price_sheet_url ) );
	}

	// Determine uploads directory path.
	$upload_dir = wp_upload_dir();

	// Check if the uploads directory id writable.
	if ( true !== wp_is_writable( $upload_dir['basedir'] ) ) {
		return new WP_Error( -3, esc_html__( '<span class="dashicons dashicons-warning"></span> Uploads directory not writable. Alko price sheet cannot be downloaded.', 'ubs' ) );
	}

	// Define the name and path for the price sheet file to be saved.
	$alko_file = trailingslashit( $upload_dir['basedir'] ) . 'alko_price_sheet.xlsx';

	// Get filesystem and credentials.
	global $wp_filesystem;
	if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base' ) ) {
		$creds = request_filesystem_credentials( site_url() );
		wp_filesystem( $creds );
	}

	// Save remote response body to file.
	$file_saved = $wp_filesystem->put_contents( $alko_file, $response_body );

	// Parse the XLSX file.
	$alko_prices_data = SimpleXLSX::parse( $alko_file );

	// If parsing was successful, process the data.
	if ( $alko_prices_data ) {

		$header_row_found          = false;
		$price_list_category_index = false;
		$beer_wort_abv_index       = false;
		$beers                     = array();

		// Remove certain suffixes from beer name.
		// This makes searching Untappd more reliable.
		$remove_these_suffixes = array(
			'tölkki',
			'viinipussi',
			'hanapakkaus',
			'muovipullo',
		);

		// Skip products containing certain suffix (mainly multipacks).
		$skip_products_with_these_suffixes = array(
			'6-pack',
			'8-pack',
			'12-pack',
			'18-pack',
			'24-pack',
			'tynnyri',
		);

		foreach ( $alko_prices_data->rows() as $row_index => $row ) {

			// We expect to find a row with first cell saying "Numero".
			// This way we identify the row with header data.
			if ( 'Numero' !== $row[0] && false === $header_row_found ) {
				continue;
			}

			if ( false === $header_row_found ) {
				$price_list_category_index = array_search( 'Hinnastojärjestyskoodi', $row, true );
				$beer_wort_abv_index       = array_search( 'Kantavierrep-%', $row, true );
				$header_row_found          = true;
				continue;
			}

			// Beers are categorized with code '600'.
			if ( '600' === $row[ $price_list_category_index ] || false === empty( $row[ $beer_wort_abv_index ] ) ) {
				$beer_name = esc_attr( $row[1] );
				$brewery   = esc_attr( $row[2] );

				// Check if product name contains suffix to skip.
				$skip_product = false;
				foreach ( $skip_products_with_these_suffixes as $suffix ) {
					if ( false !== stripos( $beer_name, $suffix ) ) {
						$skip_product = true;
						continue;
					}
				}

				// If product name contains certain suffix, skip it.
				if ( true === $skip_product ) {
					continue;
				}

				foreach ( $remove_these_suffixes as $suffix ) {
					$suffix_position = strrpos( $beer_name, $suffix );
					if ( false !== $suffix_position ) {
						$beer_name = trim( substr( $beer_name, 0, -strlen( $suffix ) ) );
					}
				}

				$beers[ absint( $row[0] ) ] = $beer_name;
			}
		}

		// Save Alko catalog to 'wp_options' table.
		$updated_beers_option    = update_option( 'ubs_beers', $beers, false );
		$updated_beers_timestamp = update_option( 'ubs_beers_fetched', time(), false );

		// If options updated successfully, return date when updated.
		if ( true === $updated_beers_option && true === $updated_beers_timestamp ) {
			return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), time() );
		} else {
			return new WP_Error( -4, __( '⚠ Error saving Alko catalog to database.', 'ubs' ) );
		}
	} else {
		// If processing failed, return WP_Error.
		return new WP_Error( -1, __( '<span class="dashicons dashicons-warning"></span> SimpleXLSX error: ', 'ubs' ) . esc_html( SimpleXLSX::parseError() ) );
	}
}

/**
 * Process AJAX request to search for a beer.
 *
 * Echo results HTML.
 *
 * @return void
 */
function ubs_fetch_process_alko_price_sheet() {

	if ( false === isset( $_POST['ubs_nonce'] ) || false === wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubs_nonce'] ) ), 'ubs_settings' ) ) {
		wp_die( esc_attr__( 'Permission check failed. Please reload the page and try again.', 'ubs' ) );
	}

	$prices_fetched = ubs_process_alko_price_sheet();

	if ( is_wp_error( $prices_fetched ) ) {
		wp_send_json_error( $prices_fetched );
	}

	wp_send_json_success(
		array(
			'sheet_updated' => $prices_fetched,
		)
	);
}
add_action( 'wp_ajax_ubs_fetch_alko_price_sheet', 'ubs_fetch_process_alko_price_sheet' );
