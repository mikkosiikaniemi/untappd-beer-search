<?php
/**
 * Untappd Beer Search — admin settings page
 *
 * @package           UBS
 * @author            Mikko Siikaniemi
 */

/**
 * Add settings menu page.
 *
 * @return void
 */
function ubs_add_settings_menu_page() {
	// Add settings page.
	add_submenu_page( 'edit.php?post_type=beer', __( 'Untappd Beer Search Settings', 'ubs' ), __( 'Settings', 'ubs' ), 'edit_posts', 'ubs-settings', 'ubs_render_options_page' );
}
add_action( 'admin_menu', 'ubs_add_settings_menu_page' );

/**
 * Initialize settings.
 *
 * @return void
 */
function ubs_initialize_settings() {

	register_setting( 'ubs_settings', 'ubs_settings' );

	// Untappd API settings.
	add_settings_section(
		'ubs_settings_section_untappd',
		__( 'Untappd API settings', 'ubs' ),
		'ubs_settings_section_untappd_callback',
		'ubs_settings'
	);

	add_settings_field(
		'ubs_setting_client_id',
		__( 'Untappd Client ID', 'ubs' ),
		'ubs_setting_client_id',
		'ubs_settings',
		'ubs_settings_section_untappd'
	);

	add_settings_field(
		'ubs_setting_client_secret',
		__( 'Untappd Client Secret', 'ubs' ),
		'ubs_setting_client_secret',
		'ubs_settings',
		'ubs_settings_section_untappd'
	);

	// Alko price sheet.
	add_settings_section(
		'ubs_settings_section_alko',
		__( 'Alko settings', 'ubs' ),
		'ubs_settings_section_alko_callback',
		'ubs_settings'
	);

	add_settings_field(
		'ubs_setting_alko_price_sheet',
		__( 'Alko price sheet URL', 'ubs' ),
		'ubs_setting_alko_price_sheet',
		'ubs_settings',
		'ubs_settings_section_alko'
	);

	add_settings_field(
		'ubs_setting_alko_favorite_store',
		__( 'Favorite Alko store (ID)', 'ubs' ),
		'ubs_setting_alko_favorite_store',
		'ubs_settings',
		'ubs_settings_section_alko'
	);
}
add_action( 'admin_init', 'ubs_initialize_settings' );

/**
 * Render Client ID setting.
 *
 * @return void
 */
function ubs_setting_client_id() {
	$options = get_option( 'ubs_settings' );
	?>
	<input type="text" size="46" name="ubs_settings[ubs_setting_client_id]" value="<?php echo isset( $options['ubs_setting_client_id'] ) ? esc_attr( $options['ubs_setting_client_id'] ) : ''; ?>" />
	<?php
}

/**
 * Render Client Secret setting.
 *
 * @return void
 */
function ubs_setting_client_secret() {
	$options = get_option( 'ubs_settings' );
	?>
	<input type="text" size="46" name="ubs_settings[ubs_setting_client_secret]" value="<?php echo isset( $options['ubs_setting_client_secret'] ) ? esc_attr( $options['ubs_setting_client_secret'] ) : ''; ?>" />
	<?php
}

/**
 * Render Client Secret setting.
 *
 * @return void
 */
function ubs_setting_alko_price_sheet() {
	$options = get_option( 'ubs_settings' );

	if ( is_array( $options ) && false === empty( $options['ubs_setting_alko_price_sheet'] ) ) {
		$alko_price_sheet_url = $options['ubs_setting_alko_price_sheet'];
	} else {
		$alko_price_sheet_url = 'https://www.alko.fi/INTERSHOP/static/WFS/Alko-OnlineShop-Site/-/Alko-OnlineShop/fi_FI/Alkon%20Hinnasto%20Tekstitiedostona/alkon-hinnasto-tekstitiedostona.xlsx';
	}
	?>
	<input type="url" name="ubs_settings[ubs_setting_alko_price_sheet]" size="46" value="<?php echo esc_url( $alko_price_sheet_url ); ?>" />
	<p class="description ubs-settings-description"><?php esc_html_e( 'Enter Alko price sheet URL. The default location is pre-filled.', 'ubs' ); ?></p>
	<p class="ubs-fetch-wrapper">
	<button class="button button-secondary" id="ubs-refetch-alko-prices"><?php esc_html_e( 'Fetch and process sheet', 'ubs' ); ?></button>
	<span id="ubs-price-sheet-message">
	<?php
	$last_fetched = get_option( 'ubs_beers_fetched' );
	if ( empty( $last_fetched ) ) :
		?>
		<?php echo wp_kses_post( '<span class="dashicons dashicons-warning"></span> Price sheet has not been fetched and processed yet. Click the button!', 'ubs' ); ?>
		<?php else : ?>
			<?php echo wp_kses_post( '<span class="dashicons dashicons-yes-alt"></span> Last fetched:', 'ubs' ); ?> <span id="ubs-price-sheet-fetched"><?php echo esc_attr( wp_date( 'j.n.Y H:i', get_option( 'ubs_beers_fetched' ) ) ); ?></span>
		<?php	endif; ?>
		</span>
		<span class="spinner"></span>
		</p>
	<?php
	wp_nonce_field( 'ubs_settings', 'ubs_settings_nonce' );
}

/**
 * Render favorite Alko store setting.
 *
 * @return void
 */
function ubs_setting_alko_favorite_store() {
	$options = get_option( 'ubs_settings' );
	?>
	<input type="text" size="6" name="ubs_settings[ubs_setting_alko_favorite_store]" value="<?php echo isset( $options['ubs_setting_alko_favorite_store'] ) ? esc_attr( $options['ubs_setting_alko_favorite_store'] ) : ''; ?>" />
	<p class="description"><?php echo wp_kses_post( sprintf( 'Enter Alko store ID. You can extract the numeric ID from Alko store URL by first <a target="_blank" href="%s">selecting a store</a>.', esc_url( __( 'https://www.alko.fi/myymalat-palvelut', 'ubs' ) ) ) ); ?></p>
	<?php
}

/**
 * Render settings description.
 *
 * @return void
 */
function ubs_settings_section_untappd_callback() {
	?>
	<p>
	<?php echo wp_kses_post( __( 'Untappd API access is required. Please enter your ID and secret from <a href="https://untappd.com/api/dashboard">Untappd API dashboard</a> page.', 'ubs' ) ); ?>
	</p>
	<?php
}

/**
 * Render settings description.
 *
 * @return void
 */
function ubs_settings_section_alko_callback() {
	?>
	<p>
	<?php echo wp_kses_post( __( 'Alko price sheet (XLSX) can be used to autocomplete beer search against the Alko catalog.', 'ubs' ) ); ?>
	</p>
	<p>
	<?php echo wp_kses_post( __( 'You can also enter the numeric ID of your favorite local Alko store to obtain availability information.', 'ubs' ) ); ?>
	</p>
	<?php
}

/**
 * Render settings page.
 *
 * @return void
 */
function ubs_render_options_page() {

	// Enqueue scripts and styles.
	wp_enqueue_style( 'ubs-settings-styles', plugin_dir_url( __FILE__ ) . '/css/untappd-beer-settings.css', array(), filemtime( plugin_dir_path( __FILE__ ) . 'css/untappd-beer-settings.css' ) );
	wp_enqueue_script( 'ubs-settings-scripts', plugin_dir_url( __FILE__ ) . '/js/untappd-beer-settings.js', array( 'jquery' ), filemtime( plugin_dir_path( __FILE__ ) . 'js/untappd-beer-settings.js' ), true );

	// Check if the user have submitted the settings.
	// WordPress will add the "settings-updated" $_GET parameter to the url.
	if ( isset( $_GET['settings-updated'] ) ) {
		// Add settings saved message with the class of "updated".
		add_settings_error( 'ubs_messages', 'ubs_message', __( 'Settings Saved', 'ubs' ), 'updated' );
	}

	// Show error/update messages.
	settings_errors( 'ubs_messages' );
	?>
	<div class="wrap">
		<form action='options.php' method='post' enctype='multipart/form-data'>

			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php
			settings_fields( 'ubs_settings' );
			do_settings_sections( 'ubs_settings' );
			submit_button();
			?>

		</form>
	</div>
	<?php
}

/**
 * Get Untappd API settings (authentication details) as array.
 * A helper function.
 *
 * @return array API settings.
 */
function ubs_get_api_settings() {
	$untappd_settings      = get_option( 'ubs_settings' );
	$untappd_client_id     = $untappd_settings['ubs_setting_client_id'];
	$untappd_client_secret = $untappd_settings['ubs_setting_client_secret'];

	return array(
		'client_id'     => $untappd_client_id,
		'client_secret' => $untappd_client_secret,
	);
}
