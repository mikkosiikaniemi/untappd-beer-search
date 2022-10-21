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
	global $ubs_top_page;
	$ubs_top_page = add_submenu_page( 'edit.php?post_type=beer', __( 'Top available beers', 'ubs' ), __( 'Top available beers', 'ubs' ), 'edit_posts', 'ubs-top-beers', 'ubs_render_top_beers_page' );
}
add_action( 'admin_menu', 'ubs_add_top_available_beers_menu_page' );

/**
 * Enqueue JavaScript files for AJAXifying requests.
 *
 * @param  string $hook Admin page's hook suffix.
 * @return void
 */
function ubs_enqueue_update_availability_scripts( $hook ) {
	global $ubs_top_page;

	// This is relevant only for the search page. Return early if not there.
	if ( $hook !== $ubs_top_page ) {
		return;
	}
	wp_enqueue_script( 'ubs-update-availability', plugin_dir_url( __FILE__ ) . '/js/untappd-beer-update-availability.js', array( 'jquery' ), filemtime( plugin_dir_path( __FILE__ ) . 'js/untappd-beer-update-availability.js' ), true );
	wp_enqueue_style( 'ubs-update-styles', plugin_dir_url( __FILE__ ) . '/css/untappd-beer-update-availability.css', array(), filemtime( plugin_dir_path( __FILE__ ) . 'css/untappd-beer-update-availability.css' ) );
}
add_action( 'admin_enqueue_scripts', 'ubs_enqueue_update_availability_scripts' );

/**
 * Render top beers page content.
 *
 * @return void
 */
function ubs_render_top_beers_page() {
	$active_tab = false;
	if ( isset( $_GET['tab'] ) ) {
		$active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Top available', 'ubs' ); ?></h1>

		<nav class="nav-tab-wrapper">
			<a href="?post_type=beer&page=ubs-top-beers" class="nav-tab
			<?php
			if ( false === $active_tab ) {
				echo 'nav-tab-active';}
			?>
			"><?php esc_html_e( 'Favorite Alko', 'ubs' ); ?></a>
			<a href="?post_type=beer&page=ubs-top-beers&tab=alko_online" class="nav-tab
			<?php
			if ( $active_tab ) {
				echo 'nav-tab-active';}
			?>
			"><?php esc_html_e( 'Alko online', 'ubs' ); ?></a>
		</nav>

		<div class="tab-content">
	<?php

	switch ( $active_tab ) :
		case false:
			$favorite_alko_store = ubs_get_favorite_alko_store();

			// Don't add the action if favorite store has not been defined.
			if ( false === $favorite_alko_store ) {
				echo '<p>' . esc_html__( 'Please enter favorite Alko store ID in settings first. After that, update beer availabilities to see a list here.', 'ubs' ) . '</p>';
				echo '</div>';
				echo '</div>';
				return;
			}

			$top_available_beers_query = new WP_Query(
				array(
					'posts_per_page'         => 10,
					'post_type'              => 'beer',
					'meta_key'               => 'rating_score',
					'orderby'                => 'meta_value_num',
					'order'                  => 'DESC',
					'meta_query'             => array(
						array(
							'key'     => 'availability_' . $favorite_alko_store,
							'value'   => 0,
							'compare' => '>',
						),
					),
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
				)
			);

			?>
			<h2><?php esc_html_e( 'Top beers', 'ubs' ); ?></h2>
			<p><?php esc_html_e( 'These beers are available & ranked highest in your favorite Alko store.', 'ubs' ); ?></p>
			<?php ubs_render_beer_listing( $top_available_beers_query, $favorite_alko_store ); ?>

			<?php
			$top_available_medium_beers_query = new WP_Query(
				array(
					'posts_per_page'         => 10,
					'post_type'              => 'beer',
					'meta_key'               => 'rating_score',
					'orderby'                => 'meta_value_num',
					'order'                  => 'DESC',
					'meta_query'             => array(
						'relation' => 'AND',
						array(
							'key'     => 'availability_' . $favorite_alko_store,
							'value'   => 0,
							'compare' => '>',
						),
						array(
							'key'     => 'beer_abv',
							'value'   => 6,
							'compare' => '<=',
							'type'    => 'DECIMAL',
						),
					),
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
				)
			);
			?>
			<h2><?php esc_html_e( 'Top "keskivahva" beers', 'ubs' ); ?></h2>
			<?php ubs_render_beer_listing( $top_available_medium_beers_query, $favorite_alko_store ); ?>

			<?php
			$top_available_sour_beers_query = new WP_Query(
				array(
					'posts_per_page'         => 10,
					'post_type'              => 'beer',
					'meta_key'               => 'rating_score',
					'orderby'                => 'meta_value_num',
					'order'                  => 'DESC',
					'meta_query'             => array(
						'relation' => 'AND',
						array(
							'key'     => 'availability_' . $favorite_alko_store,
							'value'   => 0,
							'compare' => '>',
						),
						array(
							'key'     => 'beer_style',
							'value'   => 'sour',
							'compare' => 'LIKE',
						),
					),
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
				)
			);
			?>
			<h2><?php esc_html_e( 'Top sour beers', 'ubs' ); ?></h2>
			<?php ubs_render_beer_listing( $top_available_sour_beers_query, $favorite_alko_store ); ?>

			<?php
			$top_available_finnish_beers_query = new WP_Query(
				array(
					'posts_per_page'         => 10,
					'post_type'              => 'beer',
					'meta_key'               => 'rating_score',
					'orderby'                => 'meta_value_num',
					'order'                  => 'DESC',
					'meta_query'             => array(
						array(
							'key'     => 'availability_' . $favorite_alko_store,
							'value'   => 0,
							'compare' => '>',
						),
					),
					'tax_query'              => array(
						array(
							'taxonomy' => 'country',
							'field'    => 'slug',
							'terms'    => 'finland',
						),
					),
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
				)
			);
			?>
			<h2><?php esc_html_e( 'Top Finnish beers', 'ubs' ); ?></h2>
			<?php ubs_render_beer_listing( $top_available_finnish_beers_query, $favorite_alko_store ); ?>

			<?php
			$top_available_nonalc_beers_query = new WP_Query(
				array(
					'posts_per_page'         => 10,
					'post_type'              => 'beer',
					'meta_key'               => 'rating_score',
					'orderby'                => 'meta_value_num',
					'order'                  => 'DESC',
					'meta_query'             => array(
						array(
							'key'     => 'availability_' . $favorite_alko_store,
							'value'   => 0,
							'compare' => '>',
						),
						array(
							'key'     => 'beer_abv',
							'value'   => '1',
							'compare' => '<=',
							'type'    => 'DECIMAL',
						),
					),
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
				)
			);
			?>
			<h2><?php esc_html_e( 'Top non-alcoholic beers', 'ubs' ); ?></h2>
			<?php ubs_render_beer_listing( $top_available_nonalc_beers_query, $favorite_alko_store ); ?>

			<?php
			$bottom_available_beers_query = new WP_Query(
				array(
					'posts_per_page'         => 5,
					'post_type'              => 'beer',
					'meta_key'               => 'rating_score',
					'orderby'                => 'meta_value_num',
					'order'                  => 'ASC',
					'meta_query'             => array(
						array(
							'key'     => 'availability_' . $favorite_alko_store,
							'value'   => 0,
							'compare' => '>',
						),
					),
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
				)
			);

			?>
			<h2><?php esc_html_e( 'Worst beers', 'ubs' ); ?></h2>
			<?php ubs_render_beer_listing( $bottom_available_beers_query, $favorite_alko_store ); ?>

			<h2><?php esc_html_e( 'Update availability', 'ubs' ); ?></h2>
			<p><?php esc_html_e( 'You can update availability of all beers in your favorite Alko store. This may take a while.', 'ubs' ); ?></p>
			<form id="ubs-update-availability" action="" method="post">
				<?php wp_nonce_field( 'update-availability' ); ?>
				<button type="submit" id="ubs-update-button" name="ubs-update-button" class="button button-primary"><?php esc_html_e( 'Update availability', 'ubs' ); ?></button>
				<span class="spinner"></span>
			</form>
			<label class="initially-hidden" for="ubs-update-availability-progress"><?php esc_html_e( 'Update progress:', 'ubs' ); ?></label>
			<progress class="initially-hidden" id="ubs-update-availability-progress" value="0" max="100"></progress>

			<?php
			break;
		case 'alko_online':
			$best_alko_beers_query = new WP_Query(
				array(
					'posts_per_page'         => 15,
					'post_type'              => 'beer',
					'meta_key'               => 'rating_score',
					'orderby'                => 'meta_value_num',
					'order'                  => 'DESC',
					'meta_query'             => array(
						array(
							'key'     => 'availability_online',
							'value'   => 0,
							'compare' => '>',
						),
					),
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
				)
			);
			?>
			<h2><?php esc_html_e( 'Top rated beers available online at Alko', 'ubs' ); ?></h2>
			<?php ubs_render_beer_listing( $best_alko_beers_query, 'online' ); ?>
			<?php
			break;
		endswitch;
	?>
	</div>
	</div>
	<?php
}

	/**
	 * Render a table listing of beers based on WP_Query results.
	 *
	 * @param  array $beer_query           WP Query.
	 * @param  int   $favorite_alko_store  Favorite Alko store.
	 * @return void
	 */
function ubs_render_beer_listing( $beer_query, $favorite_alko_store = false ) {
	if ( empty( $beer_query->posts ) ) {
		echo '<p>' . esc_html__( 'No data.', 'ubs' ) . '</p>';
		return;
	}
	?>
	<table class="widefat striped ubs-availability-table">
		<thead>
			<th><?php esc_html_e( 'Beer Name', 'ubs' ); ?></th>
			<th><?php esc_html_e( 'Rating', 'ubs' ); ?></th>
			<th><?php esc_html_e( 'Style', 'ubs' ); ?></th>
			<th><?php esc_html_e( 'ABV%', 'ubs' ); ?></th>
		<?php if ( false !== $favorite_alko_store ) : ?>
			<th><?php esc_html_e( 'Availability', 'ubs' ); ?></th>
			<th><?php esc_html_e( 'Updated', 'ubs' ); ?></th>
			<?php endif; ?>
		</thead>
		<tbody>
		<?php
		foreach ( $beer_query->posts as $beer_post ) {
			echo '<tr>';
			echo '<td><a target="_blank" href="https://www.alko.fi/tuotteet/' . absint( get_post_meta( $beer_post->ID, 'alko_id', true ) ) . '">' . esc_attr( $beer_post->post_excerpt ) . '</a></td>';
			echo '<td>' . number_format( get_post_meta( $beer_post->ID, 'rating_score', true ), 2 ) . '</td>';
			echo '<td>' . esc_attr( get_post_meta( $beer_post->ID, 'beer_style', true ) ) . '</td>';
			echo '<td>' . number_format( get_post_meta( $beer_post->ID, 'beer_abv', true ), 1 ) . '</td>';
			if ( false !== $favorite_alko_store ) {
				echo '<td>' . absint( get_post_meta( $beer_post->ID, 'availability_' . $favorite_alko_store, true ) ) . '</td>';
				echo '<td>' . esc_attr( date( 'j.n.Y H:i', get_post_meta( $beer_post->ID, 'availability_updated_' . $favorite_alko_store, true ) ) ) . '</td>';
			}
			echo '</tr>';
		}
		?>
		</tbody>
	</table>
	<?php
}

	/**
	 * Update Alko store availability in batches with AJAX.
	 *
	 * @return void
	 */
function ubs_update_availability_ajax() {

	// Verify nonce.
	if ( isset( $_REQUEST['nonce'] ) ) {
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'], 'update-availability' ) ) );
	} else {
		wp_send_json_error( __( 'Permission check failed. Please reload the page and try again.', 'ubs' ), 404 );
	}

	if ( isset( $_POST['step'] ) ) {
		$step = absint( $_POST['step'] );
	} else {
		exit;
	}

	// Define the batch size, i.e. how many beers to update per batch.
	$batch_size = 5;

	// Check how many beers there are stored.
	$beer_count = wp_count_posts( 'beer' )->publish;

	// Determine how many steps we need to take.
	$steps_count = ceil( $beer_count / $batch_size );

	// Get beers in batches.
	$beer_query = new WP_Query(
		array(
			'posts_per_page'         => $batch_size,
			'paged'                  => $step,
			'post_type'              => 'beer',
			'meta_key'               => 'rating_score',
			'orderby'                => 'meta_value_num',
			'order'                  => 'DESC',
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
		)
	);

	$favorite_alko_store = ubs_get_favorite_alko_store();

	// For earch beer, update availability.
	foreach ( $beer_query->posts as $beer_post_id ) {
		if ( current_time( 'timestamp' ) - get_post_meta( $beer_post_id, 'availability_updated_' . $favorite_alko_store, true ) > HOUR_IN_SECONDS ) {
			ubs_update_store_availability_for_beer( $beer_post_id );
		}
	};

	if ( $step < $steps_count ) {
		$step++;
		echo wp_json_encode(
			array(
				'step'       => $step,
				'percentage' => number_format( ( ( $step * $batch_size ) / $beer_count ) * 100, 1 ),
			)
		);
	} else {
		echo wp_json_encode(
			array(
				'step'       => 'done',
				'percentage' => 100,
			)
		);
	}
	exit;
}
	add_action( 'wp_ajax_ubs_update_availability_ajax', 'ubs_update_availability_ajax' );
