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
 *
 * @package           UBS
 * @author            Mikko Siikaniemi
 */

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
use Shuchkin\SimpleXLSX;

$alko_price_sheet_file = plugin_dir_path( __FILE__ ) . 'alko.xlsx';

$alko_prices_data = new SimpleXLSX( $alko_price_sheet_file );
if ( $alko_prices_data->success() ) {
	$row_counter               = 0;
	$header_row_found          = false;
	$price_list_category_index = false;
	foreach ( $xlsx->rows() as $row_index => $row ) {

		// We expect to find a row with first cell saying "Numero".
		// This way we identify the row with header data.
		if ( $row[0] !== 'Numero' && $header_row_found === false ) {
			continue;
		}

		if ( $header_row_found === false ) {
			$price_list_category_index = array_search( 'Hinnastojärjestyskoodi', $row, true );
			$header_row_found = true;
		}


		if ( $row_counter < 5 ) {
			print_r( $row[ $price_list_category_index ] );
		}
		$row_counter++;
	}
} else {
	echo 'xlsx error: ' . $alko_prices_data->error();
}
