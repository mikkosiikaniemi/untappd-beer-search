<?php
/**
 * Untappd Beer Search
 *
 * @package           UBS
 * @author            Mikko Siikaniemi
 *
 * @wordpress-plugin
 * Plugin Name:       Untappd Beer Search
 * Description:       Search beers in Untappd, save info/ratings into custom post type. Requires Untappd API access.
 * Version:           1.0
 * Author:            Mikko Siikaniemi
 * Text Domain:       ubs
 * Domain Path:       /languages
 */

DEFINE( 'UNTAPPD_API_BASE', 'https://api.untappd.com/v4/' );

/**
 * Register a custom post type for beers.
 */
function ubs_register_post_type() {

	$labels = array(
		'name'               => _x( 'Beers', 'Post type general name', 'ubs' ),
		'singular_name'      => _x( 'Beer', 'Post type singular name', 'ubs' ),
		'menu_name'          => _x( 'Untappd Beers', 'Admin Menu text', 'ubs' ),
		'name_admin_bar'     => _x( 'Beer', 'Add New on Toolbar', 'ubs' ),
		'edit_item'          => __( 'Edit Beer', 'ubs' ),
		'view_item'          => __( 'View Beer', 'ubs' ),
		'all_items'          => __( 'All Beers', 'ubs' ),
		'search_items'       => __( 'Search Beers', 'ubs' ),
		'not_found'          => __( 'No beers found. Use "Search Untappd" to add new.', 'ubs' ),
		'not_found_in_trash' => __( 'No beers found in Trash.', 'ubs' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'beer' ),
		'capability_type'    => 'post',
		'capabilities'       => array(
			'create_posts' => false, // Removes support for the "Add New" function.
		),
		'map_meta_cap'       => true, // Set to "false", if users are not allowed to edit/delete existing posts.
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'menu_icon'          => 'dashicons-beer',
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
	);

	register_post_type( 'beer', $args );
}
add_action( 'init', 'ubs_register_post_type' );

add_action( 'admin_menu', 'ubs_add_menu_pages' );
add_action( 'admin_init', 'ubs_initialize_settings' );

/**
 * Add search and settings menu pages.
 *
 * @return void
 */
function ubs_add_menu_pages() {
	// Add search page.
	global $ubs_search_page;
	$ubs_search_page = add_submenu_page( 'edit.php?post_type=beer', __( 'Search Untappd', 'ubs' ), __( 'Search Untappd', 'ubs' ), 'edit_posts', 'ubs-search', 'ubs_render_search_page' );

	// Add settings page.
	add_submenu_page( 'edit.php?post_type=beer', __( 'Untappd Beer Search Settings', 'ubs' ), __( 'Settings', 'ubs' ), 'edit_posts', 'ubs-settings', 'ubs_render_options_page' );
}

/**
 * Get Untappd API settings (authentication details) as array.
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

/**
 * Search for a beer in Untappd (by name).
 * Make the remote request to Untappd API.
 *
 * @param  string $beer_name    Beer name.
 * @return array  $return_array Array of results (raw).
 */
function ubs_search_beer_in_untappd( $beer_name ) {

	$untappd_search_url = UNTAPPD_API_BASE . 'search/beer';

	$query_args = array_merge(
		ubs_get_api_settings(),
		array( 'q' => $beer_name ),
	);

	$untappd_search_url = add_query_arg( $query_args, $untappd_search_url );

	$untappd_response = wp_remote_get( $untappd_search_url );

	if ( is_wp_error( $untappd_response ) ) {
		return new WP_Error( -1, __( 'Untappd API request failed.', 'ubs' ) );
	}

	// Get the API request response body.
	$untappd_response_body = wp_remote_retrieve_body( $untappd_response );

	// Get the API rate limit remaining amount (100 per hour to start with).
	$untappd_api_limit_remaining = wp_remote_retrieve_header( $untappd_response, 'x-ratelimit-remaining' );

	$decoded_response = json_decode( $untappd_response_body, true );

	$return_array                    = $decoded_response['response'];
	$return_array['limit_remaining'] = $untappd_api_limit_remaining;

	return $return_array;
}

/**
 * Render the beer search page in wp-admin.
 *
 * @return void
 */
function ubs_render_search_page() {

	/*
	$beer_name     = '';
	$search_result = '';
	if ( isset( $_GET['beer_name'] ) ) {
		$beer_name     = sanitize_text_field( wp_unslash( $_GET['beer_name'] ) );
		$search_result = ubs_search_beer_in_untappd( $beer_name );
	}

	if ( is_wp_error( $search_result ) ) {
		$search_result = $search_result->get_error_message();
	} elseif ( isset( $search_result['found'] ) && $search_result['found'] === 0 ) {
		$search_result = __( 'No beers found.', 'ubs' );
	} elseif ( false === empty( $search_result ) ) {
		$search_result = ubs_render_search_results( $search_result );
	}
	*/

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
		$html .= '<tr>';

		$html .= '<td>';
		$html .= '<input type="checkbox" name="beer-id[]" value="' . $beer['beer']['bid'] . '"';
		if ( false === get_post_status( $beer['beer']['bid'] ) ) {
			$html .= ' checked="checked"';
		}
		$html .= '>';
		$html .= '</td>';

		$html .= '<td>';
		$html .= $beer['beer']['bid'];
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

		$html .= '<td>';
		if ( false !== get_post_status( $beer['beer']['bid'] ) ) {
			$html .= __( 'Yes', 'ubs' );
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
 * @return void
 */
function ubs_save_beers() {

	if ( isset( $_POST['beer-id'] ) ) {
		foreach ( $_POST['beer-id'] as $beer_id ) {
			$beer_info = ubs_get_beer_info( absint( $beer_id ) );

			if ( is_wp_error( $beer_info ) ) {
				$beer_info = $beer_info->get_error_message();
			} elseif ( empty( $beer_info ) ) {
				$search_result = __( 'No beer found with this ID.', 'ubs' );
			} elseif ( false === empty( $beer_info ) ) {
				ubs_save_beer( $beer_info['beer'] );
			}
		}
	}

	wp_safe_redirect( esc_url_raw( $_POST['_wp_http_referer'] ) );
}
add_action( 'admin_post_ubs_save', 'ubs_save_beers' );

/**
 * Create (or update) a beer as custom post.
 *
 * @param  array $beer_data Raw beer data from Untappd.
 * @return void
 */
function ubs_save_beer( $beer_data ) {

	$post_data = array(
		'post_type'    => 'beer',
		'post_title'   => wp_strip_all_tags( $beer_data['brewery']['brewery_name'] . ' ' . $beer_data['beer_name'] ),
		'post_content' => wp_strip_all_tags( $beer_data['beer_description'] ),
		'import_id'    => absint( $beer_data['bid'] ),
		'post_status'  => 'publish',
	);

	// Make a copy of the beer data, to be saved as post meta.
	$post_meta = $beer_data;

	// Unset certain meta keys as these are unnecessary to be saved.
	unset( $post_meta['media'] );
	unset( $post_meta['checkins'] );
	unset( $post_meta['similar'] );
	unset( $post_meta['friends'] );
	$post_data['meta_input'] = $post_meta;

	// If the beer has been saved before, set the ID so that the post can be updated.
	if ( false !== get_post_status( $beer_data['bid'] ) ) {
		$post_data['ID'] = $beer_data['bid'];
	}

	wp_insert_post( $post_data );
}

/**
 * Get single beer info in Untappd (by ID).
 * Make the remote request to Untappd API.
 *
 * @param  string $beer_id      Beer ID in Untappd.
 * @return array  $return_array Array of results (raw).
 */
function ubs_get_beer_info( $beer_id ) {
	$untappd_search_url = UNTAPPD_API_BASE . 'beer/info/' . $beer_id;

	$untappd_search_url = add_query_arg( ubs_get_api_settings(), $untappd_search_url );

	$untappd_response = wp_remote_get( $untappd_search_url );

	if ( is_wp_error( $untappd_response ) ) {
		return new WP_Error( -1, __( 'Untappd API request failed.', 'ubs' ) );
	}

	// Get the API request response body.
	$untappd_response_body = wp_remote_retrieve_body( $untappd_response );

	// Get the API rate limit remaining amount (100 per hour to start with).
	$untappd_api_limit_remaining = wp_remote_retrieve_header( $untappd_response, 'x-ratelimit-remaining' );

	$decoded_response = json_decode( $untappd_response_body, true );

	$return_array                    = $decoded_response['response'];
	$return_array['limit_remaining'] = $untappd_api_limit_remaining;

	return $return_array;
}

/**
 * Return the search form data, injected as URL parameters.
 *
 * @return void
 */
function ubs_search_untappd() {
	wp_safe_redirect( esc_url_raw( $_POST['_wp_http_referer'] . '&beer_name=' . urlencode( $_POST['beer_name'] ) ) );
}
add_action( 'admin_post_ubs_search', 'ubs_search_untappd' );

/**
 * Initialize settings.
 *
 * @return void
 */
function ubs_initialize_settings() {

	register_setting( 'ubs_plugin_options', 'ubs_settings' );

	add_settings_section(
		'ubs_plugin_options_section',
		'',
		'ubs_settings_section_callback',
		'ubs_plugin_options'
	);

	add_settings_field(
		'ubs_setting_client_id',
		__( 'Untappd Client ID', 'ubs' ),
		'ubs_setting_client_id',
		'ubs_plugin_options',
		'ubs_plugin_options_section'
	);

	add_settings_field(
		'ubs_setting_client_secret',
		__( 'Untappd Client Secret', 'ubs' ),
		'ubs_setting_client_secret',
		'ubs_plugin_options',
		'ubs_plugin_options_section'
	);

}

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
function ubs_settings_section_callback() {
	?>
	<p>
	<?php echo wp_kses_post( __( 'Untappd API access is required. Please enter your ID and secret from <a href="https://untappd.com/api/dashboard">Untappd API dashboard</a> page.', 'ubs' ) ); ?>
	</p>
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

			<h1><?php esc_html_e( 'Untappd API Settings', 'ubs' ); ?></h1>

			<?php
			settings_fields( 'ubs_plugin_options' );
			do_settings_sections( 'ubs_plugin_options' );
			submit_button();
			?>

		</form>
	</div>
	<?php
}

/**
 * Add custom colums to beer post type admin listing.
 *
 * @param  array $columns Original colums.
 * @return array $columns Modified columns.
 */
function ubs_set_custom_beer_columns( $columns ) {
	unset( $columns['date'] );
	$columns['style']  = __( 'Style', 'ubs' );
	$columns['abv']    = __( 'ABV%', 'ubs' );
	$columns['rating'] = __( 'Rating', 'ubs' );

	return $columns;
}
add_filter( 'manage_beer_posts_columns', 'ubs_set_custom_beer_columns' );

/**
 * Populate custom colums to beer post type admin listing.
 *
 * @param  string $column  Column slug.
 * @param  int    $post_id Post ID.
 * @return void
 */
function ubs_populate_custom_beer_columns( $column, $post_id ) {
	switch ( $column ) {
		case 'rating':
			echo number_format( get_post_meta( $post_id, 'rating_score', true ), 2 );
			break;
		case 'abv':
			echo number_format( get_post_meta( $post_id, 'beer_abv', true ), 1 );
			break;
		case 'style':
			echo esc_attr( get_post_meta( $post_id, 'beer_style', true ) );
			break;
	}
}
add_action( 'manage_beer_posts_custom_column', 'ubs_populate_custom_beer_columns', 10, 2 );

/**
 * Register sortable columns.
 *
 * @param  array $columns Original colums.
 * @return array $columns Modified columns.
 */
function ubs_register_sortable_columns( $columns ) {
	$columns['rating'] = 'rating';
	return $columns;
}
add_filter( 'manage_edit-beer_sortable_columns', 'ubs_register_sortable_columns' );

/**
 * Make custom columns (populated from meta data) sortable.
 *
 * @param  [type] $query
 * @return void
 */
function ubs_sort_by_custom_column( $query ) {

	if ( ! is_admin() ) {
		return;
	}

	$orderby = $query->get( 'orderby' );

	if ( 'rating' === $orderby ) {
		$query->set( 'meta_key', 'rating_score' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'ubs_sort_by_custom_column' );

/**
 * Enqueue JavaScript files for AJAXifying requests.
 *
 * @param  string $hook Admin page's hook suffix.
 * @return void
 */
function ubs_enqueue_scripts( $hook ) {
	global $ubs_search_page;
	if ( $hook !== $ubs_search_page ) {
		return;
	}
	wp_enqueue_script( 'ubs-ajax', plugin_dir_url( __FILE__ ) . '/untappd-beer-search.js', array( 'jquery' ), false, true );
}
add_action( 'admin_enqueue_scripts', 'ubs_enqueue_scripts' );

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

	vincit_wp_debug( $_POST );
	parse_str($_POST['beer_name'], $searcharray);
	vincit_wp_debug( $searcharray );

	if ( false === isset( $_POST['ubs_nonce'] ) || false === wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubs_nonce'] ) ), 'ubs_save' ) ) {
		wp_die( esc_attr__( 'Permission check failed. Please reload the page and try again.', 'ubs' ) );
	}

	wp_die();
}
add_action( 'wp_ajax_ubs_save_selected_results', 'ubs_process_ajax_save_results' );
