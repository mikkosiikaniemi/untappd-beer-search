<?php
/**
 * Untappd Beer Search — top available beers
 *
 * @package           UBS
 * @author            Mikko Siikaniemi
 */

/**
 * Add top available beers menu page.
 *
 * @return void
 */
function ubs_add_top_available_beers_menu_page() {
	add_submenu_page( 'edit.php?post_type=beer', __( 'Top available beers', 'ubs' ), __( 'Top available beers', 'ubs' ), 'edit_posts', 'ubs-top-beers', 'ubs_render_top_beers_page' );
}
add_action( 'admin_menu', 'ubs_add_top_available_beers_menu_page' );

/**
 * Render top beers page content.
 *
 * @return void
 */
function ubs_render_top_beers_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Top beers available', 'ubs' ); ?></h1>
	<?php

	$untappd_settings = get_option( 'ubs_settings' );

	// Don't add the action if favorite store has not been defined.
	if ( empty( $untappd_settings['ubs_setting_alko_favorite_store'] ) ) {
		echo '<p>' . esc_html__( 'Please enter favorite Alko store ID in settings first. After that, update beer availabilities to see a list here.', 'ubs' ) . '</p>';
		echo '</div>';
		return;
	}

	$beer_query = new WP_Query(
		array(
			'posts_per_page'         => 20,
			'post_type'              => 'beer',
			'meta_key'               => 'rating_score',
			'orderby'                => 'meta_value_num',
			'order'                  => 'DESC',
			'meta_query'             => array(
				array(
					'key'     => 'availability_' . $untappd_settings['ubs_setting_alko_favorite_store'],
					'value'   => 0,
					'compare' => '>',
				),
			),
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);

	?>

		<p><?php esc_html_e( 'These beers are available & ranked highest in your favorite Alko store.', 'ubs' ); ?></p>
		<table class="widefat striped">
			<thead>
				<th><?php esc_html_e( 'Beer Name', 'ubs' ); ?></th>
				<th><?php esc_html_e( 'Rating', 'ubs' ); ?></th>
				<th><?php esc_html_e( 'ABV%', 'ubs' ); ?></th>
				<th><?php esc_html_e( 'Availability', 'ubs' ); ?></th>
			</thead>
			<tbody>
	<?php
	foreach ( $beer_query->posts as $post ) {
		echo '<tr>';
		echo '<td><a target="_blank" href="https://www.alko.fi/tuotteet/' . absint( get_post_meta( $post->ID, 'alko_id', true ) ) . '">' . esc_attr( $post->post_excerpt ) . '</a></td>';
		echo '<td>' . number_format( get_post_meta( $post->ID, 'rating_score', true ), 2 ) . '</td>';
		echo '<td>' . number_format( get_post_meta( $post->ID, 'beer_abv', true ), 1 ) . '</td>';
		echo '<td>' . absint( get_post_meta( $post->ID, 'availability_' . $untappd_settings['ubs_setting_alko_favorite_store'], true ) ) . '</td>';
		echo '</tr>';
	}
	?>
			</tbody>
		</table>
	</div>
	<?php
}
