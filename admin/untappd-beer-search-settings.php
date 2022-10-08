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
	<input type="password" size="46" name="ubs_settings[ubs_setting_client_secret]" value="<?php echo isset( $options['ubs_setting_client_secret'] ) ? esc_attr( $options['ubs_setting_client_secret'] ) : ''; ?>" />
	<?php
}

/**
 * Render Client Secret setting.
 *
 * @return void
 */
function ubs_setting_alko_price_sheet() {
	$options = get_option( 'ubs_settings' );

	$xlsx_attachments = new WP_Query(
		array(
			'post_type'              => 'attachment',
			'post_status'            => 'inherit',
			'post_mime_type'         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	?>

<select name="ubs_settings[ubs_setting_alko_price_sheet]">
	<?php if ( $xlsx_attachments->have_posts() ) : ?>
		<option value=""><?php esc_html_e( 'Please select a sheet...', 'ubs' ); ?></option>
		<?php
		foreach ( $xlsx_attachments->posts as $attachment ) {
			echo '<option value="' . absint( $attachment->ID ) . '"';
			if ( absint( $options['ubs_setting_alko_price_sheet'] ) === $attachment->ID ) {
				echo ' selected';
			}
			echo '>';
			echo esc_attr( basename( $attachment->guid ) );
			echo ' (' . esc_attr( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $attachment->post_date_gmt ) ) ) . ')';
			echo '</option>';
		};
		?>
		<?php else : ?>
			<option value=""><?php esc_html_e( 'Upload price sheet to Media Library first.', 'ubs' ); ?></option>
			<?php endif; ?>
	</select>
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
	<?php echo wp_kses_post( __( 'Alko price sheet (XLSX) can be used to autocomplete beer search against the Alko catalog. <a href="https://www.alko.fi/valikoimat-ja-hinnasto/hinnasto">Download Alko price sheet</a> and upload it to Media Library for use here.', 'ubs' ) ); ?>
	</p>
	<?php
}

/**
 * Render settings page.
 *
 * @return void
 */
function ubs_render_options_page() {

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
