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
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
	);

	register_post_type( 'beer', $args );

		// Register brewery taxonomy.
		$brewery_taxonomy_labels = array(
			'name'                       => _x( 'Breweries', 'taxonomy general name', 'ubs' ),
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
	$columns['link']   = __( 'Untappd Link', 'ubs' );
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
		case 'link':
			$beer_slug = get_post_meta( $post_id, 'beer_slug', true );
			$beer_id   = get_post_meta( $post_id, 'bid', true );
			echo '<a target="_blank" href="' . esc_url( 'https://untappd.com/b/' . $beer_slug . '/' . $beer_id ) . '">' . absint( $beer_id ) . ' <span class="dashicons dashicons-external"></span></a>';
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
 * @return int|WP_Error Insert post status.
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
	$beer_post_id = ubs_maybe_get_beer_cpt_id( $beer_data['bid'] );
	if ( false !== $beer_post_id ) {
		$post_data['ID'] = $beer_post_id;
	}

	return wp_insert_post( $post_data );
}
