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
		__( 'Alko price sheet', 'ubs' ),
		'ubs_settings_section_alko_callback',
		'ubs_settings'
	);

	add_settings_field(
		'ubs_setting_alko_price_sheet',
		__( 'Alko price sheet', 'ubs' ),
		'ubs_setting_alko_price_sheet',
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
	<input type="text" size="46" name="ubs_settings[ubs_setting_client_id]" value="<?php echo esc_attr( $options['ubs_setting_client_id'] ); ?>" />
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
	<input type="password" size="46" name="ubs_settings[ubs_setting_client_secret]" value="<?php echo esc_attr( $options['ubs_setting_client_secret'] ); ?>" />
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
	<?php echo wp_kses_post( __( 'Upload Alko price sheet (XLSX) for autocomplete for beer search against the Alko catalog. <a href="https://www.alko.fi/valikoimat-ja-hinnasto/hinnasto">Download Alko price sheet</a>.', 'ubs' ) ); ?>
	</p>
	<?php
}

/**
 * Render Client Secret setting.
 *
 * @return void
 */
function ubs_setting_alko_price_sheet() {
	$options = get_option( 'ubs_settings' );
	?>
	<input type="file" name="demo-file" />
	<?php
}

/**
 * Render settings page.
 *
 * @return void
 */
function ubs_render_options_page() {
	?>
	<div class="wrap">
		<form action='options.php' method='post'>

			<h1><?php esc_html_e( 'Untappd Beer Search Settings', 'ubs' ); ?></h1>

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
