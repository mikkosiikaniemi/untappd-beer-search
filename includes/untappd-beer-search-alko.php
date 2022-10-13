<?php
/**
 * Untappd Beer Search — process Alko price sheet (XLSX)
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
 * Process Alko price sheet when settings updated.
 *
 * @param  mixed  $old_value Old option value.
 * @param  mixed  $value     New option value.
 * @param  string $option    Option name.
 * @return void
 */
function ubs_process_alko_price_sheet( $old_value, $value, $option ) {
	$price_sheet_attachment_id = $value['ubs_setting_alko_price_sheet'];

	if ( empty( $price_sheet_attachment_id ) ) {
		return;
	}

	$alko_price_sheet_file = get_attached_file( $price_sheet_attachment_id );

	$alko_prices_data = new SimpleXLSX( $alko_price_sheet_file );

	if ( $alko_prices_data->success() ) {
		$row_counter               = 0;
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
			'tynnyri',
		);

		// Skip products containing certain suffix (mainly multipacks).
		$skip_products_with_these_suffixes = array(
			'6-pack',
			'8-pack',
			'12-pack',
			'18-pack',
			'24-pack',
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
						vincit_wp_debug( $beer_name );
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

				$beers[ $row[0] ] = $brewery . ' ' . $beer_name;
			}

			$row_counter++;
		}
		update_option( 'ubs_beers', $beers, false );

	} else {
		wp_die( esc_html( $alko_prices_data->error() ) );
	}
}
add_action( 'update_option_ubs_settings', 'ubs_process_alko_price_sheet', 10, 3 );

