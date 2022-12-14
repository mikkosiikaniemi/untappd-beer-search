<?php
/**
 * Untappd Beer Search — custom post type for beer
 *
 * @package           UBS
 * @author            Mikko Siikaniemi
 */

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
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
		'show_in_rest'       => true,
	);

	register_post_type( 'beer', $args );

	// Register brewery taxonomy.
	$brewery_taxonomy_labels = array(
		'name'                       => _x( 'Brewery', 'taxonomy general name', 'ubs' ),
		'singular_name'              => _x( 'Brewery', 'taxonomy singular name', 'ubs' ),
		'menu_name'                  => __( 'Breweries', 'ubs' ),
		'all_items'                  => __( 'All Breweries', 'ubs' ),
		'edit_item'                  => __( 'Edit Brewery', 'ubs' ),
		'update_item'                => __( 'Update Brewery', 'ubs' ),
		'search_items'               => __( 'Search Breweries', 'ubs' ),
		'popular_items'              => __( 'Popular Breweries', 'ubs' ),
		'add_new_item'               => __( 'Add New Brewery', 'ubs' ),
		'new_item_name'              => __( 'New Brewery Name', 'ubs' ),
		'separate_items_with_commas' => __( 'Separate breweries with commas', 'ubs' ),
		'add_or_remove_items'        => __( 'Add or remove breweries', 'ubs' ),
		'choose_from_most_used'      => __( 'Choose from the most used breweries', 'ubs' ),
		'not_found'                  => __( 'No breweries found.', 'ubs' ),
		'back_to_items'              => __( '← Back to Breweries', 'ubs' ),
	);

	$brewery_taxonomy_args = array(
		'labels'                => $brewery_taxonomy_labels,
		'show_admin_column'     => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'brewery' ),
	);

	register_taxonomy( 'brewery', 'beer', $brewery_taxonomy_args );

	// Register beer style taxonomy.
	$style_taxonomy_labels = array(
		'name'                       => _x( 'Style', 'taxonomy general name', 'ubs' ),
		'singular_name'              => _x( 'Style', 'taxonomy singular name', 'ubs' ),
		'menu_name'                  => __( 'Styles', 'ubs' ),
		'all_items'                  => __( 'All Styles', 'ubs' ),
		'edit_item'                  => __( 'Edit Style', 'ubs' ),
		'update_item'                => __( 'Update Style', 'ubs' ),
		'search_items'               => __( 'Search Styles', 'ubs' ),
		'popular_items'              => __( 'Popular Styles', 'ubs' ),
		'add_new_item'               => __( 'Add New Style', 'ubs' ),
		'new_item_name'              => __( 'New Style Name', 'ubs' ),
		'separate_items_with_commas' => __( 'Separate Styles with commas', 'ubs' ),
		'add_or_remove_items'        => __( 'Add or remove Styles', 'ubs' ),
		'choose_from_most_used'      => __( 'Choose from the most used Styles', 'ubs' ),
		'not_found'                  => __( 'No Styles found.', 'ubs' ),
		'back_to_items'              => __( '← Back to Styles', 'ubs' ),
	);

	$style_taxonomy_args = array(
		'labels'                => $style_taxonomy_labels,
		'show_admin_column'     => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'style' ),
		'hierarchical'          => true,
	);

	register_taxonomy( 'style', 'beer', $style_taxonomy_args );

	// Register brewery country taxonomy.
	$country_taxonomy_labels = array(
		'name'                       => _x( 'Country', 'taxonomy general name', 'ubs' ),
		'singular_name'              => _x( 'Country', 'taxonomy singular name', 'ubs' ),
		'menu_name'                  => __( 'Countries', 'ubs' ),
		'all_items'                  => __( 'All Countries', 'ubs' ),
		'edit_item'                  => __( 'Edit Country', 'ubs' ),
		'update_item'                => __( 'Update Country', 'ubs' ),
		'search_items'               => __( 'Search Countries', 'ubs' ),
		'popular_items'              => __( 'Popular Countries', 'ubs' ),
		'add_new_item'               => __( 'Add New Country', 'ubs' ),
		'new_item_name'              => __( 'New Country Name', 'ubs' ),
		'separate_items_with_commas' => __( 'Separate Countries with commas', 'ubs' ),
		'add_or_remove_items'        => __( 'Add or remove Countries', 'ubs' ),
		'choose_from_most_used'      => __( 'Choose from the most used Countries', 'ubs' ),
		'not_found'                  => __( 'No Countries found.', 'ubs' ),
		'back_to_items'              => __( '← Back to Countries', 'ubs' ),
	);

	$country_taxonomy_args = array(
		'labels'                => $country_taxonomy_labels,
		'show_admin_column'     => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'country' ),
	);

	register_taxonomy( 'country', 'beer', $country_taxonomy_args );
}
add_action( 'init', 'ubs_register_post_type' );

/**
 * Add custom colums to beer post type admin listing.
 *
 * @param  array $columns Original colums.
 * @return array $columns Modified columns.
 */
function ubs_set_custom_beer_columns( $columns ) {
	unset( $columns['date'] );
	$columns['link'] = __( 'Links', 'ubs' );
	$columns['abv']  = __( 'ABV%', 'ubs' );

	$favorite_alko_store = ubs_get_favorite_alko_store();

	// Don't add the column if favorite store has not been defined.
	if ( false !== $favorite_alko_store ) {
		$columns['availability_alko_store'] = __( 'Favorite Alko', 'ubs' );
	}

	$columns['availability_alko_online'] = __( 'Alko online', 'ubs' );
	$columns['rating']                   = __( 'Rating', 'ubs' );

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
		case 'link':
			$beer_slug = get_post_meta( $post_id, 'beer_slug', true );
			$beer_id   = get_post_meta( $post_id, 'bid', true );
			esc_html_e( 'Untappd', 'ubs' );
			echo ' ';
			echo '<a target="_blank" href="' . esc_url( 'https://untappd.com/b/' . $beer_slug . '/' . $beer_id ) . '">' . absint( $beer_id ) . ' <span class="dashicons dashicons-external"></span></a><br/>';

			$alko_id             = get_post_meta( $post_id, 'alko_id', true );
			$additional_alko_ids = get_post_meta( $post_id, 'additional_alko_id' );
			esc_html_e( 'Alko' );
			echo ' ';
			if ( false === empty( $alko_id ) ) {
				$padded_alko_id = str_pad( $alko_id, 6, '0', STR_PAD_LEFT );
				echo '<a target="_blank" href="https://www.alko.fi/tuotteet/' . esc_attr( $padded_alko_id ) . '">' . esc_attr( $padded_alko_id ) . ' <span class="dashicons dashicons-external"></span></a>';

				foreach ( $additional_alko_ids as $additional_alko_id ) {
					echo ', ';
					$padded_alko_id = str_pad( $additional_alko_id, 6, '0', STR_PAD_LEFT );
					echo '<a target="_blank" href="https://www.alko.fi/tuotteet/' . esc_attr( $padded_alko_id ) . '">' . esc_attr( $padded_alko_id ) . ' <span class="dashicons dashicons-external"></span></a>';
				}
			} else {
				esc_html_e( 'N/A', 'ubs' );
			}
			break;
		case 'availability_alko_store':
			$favorite_alko_store = ubs_get_favorite_alko_store();

			// Don't add the action if favorite store has not been defined.
			if ( false === $favorite_alko_store ) {
				esc_html_e( 'N/A', 'ubs' );
			} else {
				$amount = get_post_meta( $post_id, 'availability_' . $favorite_alko_store, true );

				if ( empty( $amount ) && '0' !== $amount ) {
					esc_html_e( 'N/A', 'ubs' );
				} else {
					echo '<kbd>' . absint( $amount ) . '</kbd>';
					echo ' <small>(' . esc_attr( date( 'j.n.Y H:i', get_post_meta( $post_id, 'availability_updated_' . $favorite_alko_store, true ) ) ) . ')</small>';
				}
			}
			break;
		case 'availability_alko_online':
			$amount = get_post_meta( $post_id, 'availability_online', true );

			if ( empty( $amount ) && '0' !== $amount ) {
				esc_html_e( 'N/A', 'ubs' );
			} else {
				echo '<kbd>' . absint( $amount ) . '</kbd>';
				echo ' <small>(' . esc_attr( date( 'j.n.Y H:i', get_post_meta( $post_id, 'availability_updated_online', true ) ) ) . ')</small>';
			}
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
 * @param  object $query WP Query object.
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
 * Create (or update) a beer as custom post.
 *
 * @param  array $beer_data Raw beer data from Untappd.
 * @param  int   $alko_id   Alko product number.
 * @return int|WP_Error Insert post status.
 */
function ubs_save_beer( $beer_data, $alko_id = false ) {

	// Determine basic taxonomy data.
	$tax_input = array(
		'brewery' => wp_strip_all_tags( $beer_data['brewery']['brewery_name'] ),
		'country' => wp_strip_all_tags( $beer_data['brewery']['country_name'] ),
	);

	/**
	 * Determine beer style taxonomy data. This requires some work to build hierarchy.
	 *
	 * Untappd has at least three different templates for style;
	 * 1. One main style (alone)
	 * 2. Several main styles (separated by slash)
	 * 3. Main style & substyles (main style followed by dash,
	 *    substyles separated by slash)
	 *
	 * We process these in the following if-structure in reverse order.
	 */

	$style_term_ids = array();

	if ( false !== strpos( wp_strip_all_tags( $beer_data['beer_style'] ), ' - ' ) ) {

		list( $main_style, $sub_style_string ) = explode( ' - ', wp_strip_all_tags( $beer_data['beer_style'] ) );

		if ( false !== strpos( $sub_style_string, ' / ' ) ) {
			$sub_styles = explode( ' / ', $sub_style_string );
		} else {
			$sub_styles = array( $sub_style_string );
		}

		// Check if the main style term exists.
		$main_style_term = term_exists( $main_style, 'style', 0 );

		// Create main style term if it doesn't exist.
		if ( null === $main_style_term ) {
			$main_style_term = wp_insert_term( $main_style, 'style', array( 'parent' => 0 ) );
		}

		$style_term_ids[] = $main_style_term['term_taxonomy_id'];

		// Check if any of the substyles exist.
		foreach ( $sub_styles as $sub_style ) {
			$sub_style_term = term_exists( $sub_style, 'style', $main_style_term['term_taxonomy_id'] );

			// Create sub style term if it doesn't exist.
			if ( null === $sub_style_term ) {
				$sub_style_term = wp_insert_term( $sub_style, 'style', array( 'parent' => $main_style_term['term_taxonomy_id'] ) );
			}

			$style_term_ids[] = $sub_style_term['term_taxonomy_id'];
		}

		$tax_input['style'] = $style_term_ids;

	} elseif ( false !== strpos( wp_strip_all_tags( $beer_data['beer_style'] ), ' / ' ) ) {
		$main_styles = explode( ' / ', wp_strip_all_tags( $beer_data['beer_style'] ) );

		foreach ( $main_styles as $main_style ) {
			$main_style_term = term_exists( $main_style, 'style', 0 );

			// Create main style term if it doesn't exist.
			if ( null === $main_style_term ) {
				$main_style_term = wp_insert_term( $main_style, 'style', array( 'parent' => 0 ) );
			}

			$style_term_ids[] = $main_style_term['term_taxonomy_id'];
		}

		$tax_input['style'] = $style_term_ids;
	} else {
		$main_style_term = term_exists( wp_strip_all_tags( $beer_data['beer_style'] ), 'style', 0 );

		// Create main style term if it doesn't exist.
		if ( null === $main_style_term ) {
			$main_style_term = wp_insert_term( wp_strip_all_tags( $beer_data['beer_style'] ), 'style', array( 'parent' => 0 ) );
		}

		$tax_input['style'] = array( $main_style_term['term_taxonomy_id'] );
	}

	$post_data = array(
		'post_type'    => 'beer',
		'post_title'   => wp_strip_all_tags( $beer_data['beer_name'] ),
		'post_excerpt' => wp_strip_all_tags( $beer_data['brewery']['brewery_name'] ) . ' ' . wp_strip_all_tags( $beer_data['beer_name'] ),
		'post_content' => wp_strip_all_tags( $beer_data['beer_description'] ),
		'post_status'  => 'publish',
		'tax_input'    => $tax_input,
	);

	// Make a copy of the beer data, to be saved as post meta.
	$post_meta = $beer_data;

	// Unset certain meta keys as these are unnecessary to be saved.
	unset( $post_meta['media'] );
	unset( $post_meta['checkins'] );
	unset( $post_meta['similar'] );
	unset( $post_meta['friends'] );
	$post_data['meta_input'] = $post_meta;

	/**
	 * If Alko product number known, try and use it as the post ID for the post
	 * to be created.
	 */
	if ( false !== $alko_id ) {
		$post_data['import_id']             = $alko_id;
		$post_data['meta_input']['alko_id'] = absint( $alko_id );
	}

	// Initialize a variable to store the post ID of the newly created/updated post.
	$beer_post = false;

	// Determine if the beer has been saved before.
	$beer_post_id = ubs_maybe_get_beer_cpt_id( $beer_data['bid'] );
	if ( false !== $beer_post_id ) {
		// Set the ID so that the existing post can be updated.
		$post_data['ID'] = $beer_post_id;
		$beer_post       = wp_update_post( $post_data );
	} else {
		// Insert a new beer post.
		$beer_post = wp_insert_post( $post_data );
	}

	// Update beer's Alko availability, if favorite Alko store set.
	if ( 0 !== $beer_post && false === is_wp_error( $beer_post ) ) {
		ubs_update_store_availability_for_beer( $beer_post );
	}

	return $beer_post;
}

/**
 * Add "re-fetch from Untappd" to beer CPT row actions.
 *
 * @param  array  $actions Original post row actions.
 * @param  object $post    Post object.
 * @return array  $actions New actions.
 */
function ubs_add_refetch_action( $actions, $post ) {

	// Check that we're on beer post type.
	if ( 'beer' === $post->post_type ) {

		// Build link URL.
		$url = admin_url( 'post.php?post_type=beer&post=' . $post->ID );

		// Add new action argument.
		$edit_link = add_query_arg( array( 'action' => 'refetch' ), $url );
		$edit_link = add_query_arg( '_wpnonce', wp_create_nonce( 'refetched' ), $edit_link );

		// Define new action link.
		$actions['refetch'] = '<a href="' . esc_url( $edit_link ) . '">' . __( 'Re-fetch from Untappd', 'ubs' ) . '</a>';
	}
	return $actions;
}
add_filter( 'post_row_actions', 'ubs_add_refetch_action', 10, 2 );

/**
 * Re-fetch beer info from Untappd.
 *
 * @param  int $post_id Post ID.
 * @return void
 */
function ubs_refetch_beer_info( $post_id ) {

	// Get beer ID from post meta.
	$beer_id = get_post_meta( $post_id, 'bid', true );

	// Get Alko ID if exists.
	$alko_id = get_post_meta( $post_id, 'alko_id', true );

	// Get beer info from Untappd.
	$beer_info = ubs_get_beer_info( $beer_id );

	// Save beer info.
	ubs_save_beer( $beer_info['beer'], $alko_id );
}

/**
 * Handle "refetch" post row action.
 *
 * @param  int $post_id Post ID.
 * @return void
 */
function ubs_handle_refetch_action( $post_id ) {

	ubs_refetch_beer_info( $post_id );

	// Remove "refetch" query string argument.
	$redirect_url = remove_query_arg( array( 'refetch' ), wp_get_referer() );

	// Add "refetched" query string argument to enable admin notice display.
	$redirect_url = add_query_arg( array( 'refetched' => 'true' ), $redirect_url );

	// Make redirect.
	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'post_action_refetch', 'ubs_handle_refetch_action' );

/**
 * Add "refetch" to beer CPT bulk actions.
 *
 * @param  array $bulk_array Array of bulk actions.
 * @return array $bulk_array New array of bulk actions.
 */
function ubs_add_refetch_bulk_action( $bulk_array ) {
	$bulk_array['refetch'] = __( 'Re-fetch from Untappd', 'ubs' );
	return $bulk_array;
}
add_filter( 'bulk_actions-edit-beer', 'ubs_add_refetch_bulk_action' );

/**
 * Handle "refetch" as bulk action.
 *
 * @param  string $redirect   URL to be redirected to after action.
 * @param  string $doaction   Action name.
 * @param  array  $object_ids Array of object IDs to perform bulk action for.
 * @return string $redirect   New URL to be redirected to.
 */
function ubs_handle_refetch_bulk_action( $redirect, $doaction, $object_ids ) {

	// Let's remove the "refetch" query arg first.
	$redirect = remove_query_arg( 'refetch', $redirect );

	// If "refetch" bulk action initiated, refetch beer infos.
	if ( 'refetch' === $doaction ) {
		foreach ( $object_ids as $post_id ) {
			ubs_refetch_beer_info( $post_id );
		}
	}

	// Add query arg in order to display admin notice.
	$redirect = add_query_arg( array( 'refetched' => 'true' ), $redirect );

	return $redirect;
}
add_filter( 'handle_bulk_actions-edit-beer', 'ubs_handle_refetch_bulk_action', 10, 3 );

/**
 * Display admin notice after beer info has been refetched from Untappd.
 *
 * @return void
 */
function ubs_display_refetched_admin_notice() {

	// Check that we are on beer CPT list. If not, bail out.
	$screen = get_current_screen();
	if ( 'edit-beer' !== $screen->id ) {
		return;
	}

	// Display admin notice after beer info re-fetched.
	if ( isset( $_GET['refetched'] ) && 'true' === $_GET['refetched'] ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Beer info re-fetched.', 'ubs' ); ?></p>
		</div>
		<?php
	endif;
}
add_action( 'admin_notices', 'ubs_display_refetched_admin_notice' );

/**
 * Add "re-fetch from Untappd" to beer CPT row actions.
 *
 * @param  array  $actions Original post row actions.
 * @param  object $post    Post object.
 * @return array  $actions New actions.
 */
function ubs_add_update_store_availability_action( $actions, $post ) {

	// Check that we're on beer post type.
	if ( 'beer' === $post->post_type ) {

		$favorite_alko_store = ubs_get_favorite_alko_store();

		// Don't add the action if favorite store has not been defined.
		if ( false === $favorite_alko_store ) {
			return $actions;
		}

		// Build link URL.
		$url = admin_url( 'post.php?post_type=beer&post=' . $post->ID );

		// Add new action argument.
		$edit_link = add_query_arg( array( 'action' => 'update_store_availability' ), $url );
		$edit_link = add_query_arg( '_wpnonce', wp_create_nonce( 'updated_store_availability' ), $edit_link );

		// Define new action link.
		$actions['update_store_availability'] = '<a href="' . esc_url( $edit_link ) . '">' . __( 'Update store availability', 'ubs' ) . '</a>';
	}
	return $actions;
}
add_filter( 'post_row_actions', 'ubs_add_update_store_availability_action', 10, 2 );

/**
 * Update availability of the product in favorite Alko store.
 *
 * @param  int $post_id Post ID.
 * @return void
 */
function ubs_update_store_availability_for_beer( $post_id ) {

	// Get Alko ID if exists.
	$alko_id = get_post_meta( $post_id, 'alko_id', true );

	// Get beer info from Untappd.
	$alko_availability   = ubs_update_alko_availability( $alko_id, $post_id );
	$online_availability = ubs_update_alko_online_availability( $alko_id, $post_id );
}

/**
 * Handle "update_store_availability" post row action.
 *
 * @param  int $post_id Post ID.
 * @return void
 */
function ubs_handle_update_store_availability_action( $post_id ) {

	ubs_update_store_availability_for_beer( $post_id );

	// Remove "update_store_availability" query string argument.
	$redirect_url = remove_query_arg( array( 'update_store_availability' ), wp_get_referer() );

	// Add "updated_store_availability" query string argument to enable admin notice display.
	$redirect_url = add_query_arg( array( 'updated_store_availability' => 'true' ), $redirect_url );

	// Make redirect.
	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'post_action_update_store_availability', 'ubs_handle_update_store_availability_action' );

/**
 * Add "refetch" to beer CPT bulk actions.
 *
 * @param  array $bulk_array Array of bulk actions.
 * @return array $bulk_array New array of bulk actions.
 */
function ubs_add_update_store_availability_bulk_action( $bulk_array ) {
	$bulk_array['update_store_availability'] = __( 'Update store availability', 'ubs' );
	return $bulk_array;
}
add_filter( 'bulk_actions-edit-beer', 'ubs_add_update_store_availability_bulk_action' );

/**
 * Handle "update_store_availability" as bulk action.
 *
 * @param  string $redirect   URL to be redirected to after action.
 * @param  string $doaction   Action name.
 * @param  array  $object_ids Array of object IDs to perform bulk action for.
 * @return string $redirect   New URL to be redirected to.
 */
function ubs_handle_update_store_availability_bulk_action( $redirect, $doaction, $object_ids ) {

	// Let's remove the "update_store_availability" query arg first.
	$redirect = remove_query_arg( 'update_store_availability', $redirect );

	// If "update_store_availability" bulk action initiated, update_store_availability beer infos.
	if ( 'update_store_availability' === $doaction ) {
		foreach ( $object_ids as $post_id ) {
			$alko_id = get_post_meta( $post_id, 'alko_id', true );
			ubs_update_alko_availability( $alko_id, $post_id );
		}
	}

	// Add query arg in order to display admin notice.
	$redirect = add_query_arg( array( 'updated_store_availability' => 'true' ), $redirect );

	return $redirect;
}
add_filter( 'handle_bulk_actions-edit-beer', 'ubs_handle_update_store_availability_bulk_action', 10, 3 );

/**
 * Add beer rating to data returned by REST API.
 *
 * @return void
 */
function ubs_add_beer_rating_to_rest_data() {
	register_rest_field(
		'beer',
		'rating',
		array(
			'get_callback' => 'ubs_get_beer_rating',
			'schema'       => null,
		)
	);
}
add_action( 'rest_api_init', 'ubs_add_beer_rating_to_rest_data' );

/**
 * Return more then default 10 results when requesting REST for beers.
 */
function rest_posts_per_page( $args, $request ) {
	$max                    = max( (int) $request->get_param( 'per_page' ), 2000 );
	$args['posts_per_page'] = $max;
	return $args;
}
add_filter( 'rest_beer_query', 'rest_posts_per_page', 10, 2 );
