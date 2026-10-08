<?php
/**
 * [viktorin_search] – accommodation search bar.
 *
 * Sends the visitor to the results page (Apartments) pre-filtered through
 * JetSmartFilters URL parameters, e.g.
 *   /apartments/?jsf=jet-engine:filter-item&meta=accommodation-types:Vila;accommodation-spa:Pool;accommodation-guests!compare-greater:3
 * Check-in / check-out are passed along as plain `check_in` / `check_out`
 * parameters (Y-m-d). They don't filter yet – reserved for the booking system.
 *
 * Shortcode attributes:
 *   action    Results page URL (default: the page with slug "apartments")
 *   provider  JetSmartFilters provider (default: jet-engine)
 *   query_id  CSS ID of the results listing grid (default: filter-item)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'vks_register_assets' );
function vks_register_assets() {
	wp_register_style( 'flatpickr', VKS_URL . 'assets/vendor/flatpickr/flatpickr.min.css', [], '4.6.13' );
	wp_register_script( 'flatpickr', VKS_URL . 'assets/vendor/flatpickr/flatpickr.min.js', [], '4.6.13', true );

	wp_register_style( 'vks-search', VKS_URL . 'assets/css/search.css', [ 'flatpickr' ], filemtime( VKS_PATH . 'assets/css/search.css' ) );
	wp_register_script( 'vks-search', VKS_URL . 'assets/js/search.js', [ 'flatpickr' ], filemtime( VKS_PATH . 'assets/js/search.js' ), true );

	wp_register_style( 'vks-stay', VKS_URL . 'assets/css/stay.css', [ 'vks-search' ], filemtime( VKS_PATH . 'assets/css/stay.css' ) );
}

/**
 * Highest guest capacity defined in the Guests field options.
 */
function vks_max_guests() {
	$capacities = array_filter( array_map( 'intval', array_keys( vks_get_field_options( VKS_FIELD_GUESTS, [ 2 => 2, 3 => 3, 4 => 4 ] ) ) ) );
	return $capacities ? max( $capacities ) : 4;
}

add_shortcode( 'viktorin_search', 'vks_render_search_form' );
function vks_render_search_form( $atts ) {
	$atts = shortcode_atts(
		[
			'action'   => vks_page_url( 'apartments' ),
			'provider' => 'jet-engine',
			'query_id' => 'filter-item',
		],
		$atts,
		'viktorin_search'
	);

	wp_enqueue_style( 'vks-search' );
	wp_enqueue_script( 'vks-search' );

	$types = vks_get_field_options( VKS_FIELD_TYPE, [ 'Apartment' => 'Apartment', 'Vila' => 'Vila', 'Room' => 'Room' ] );
	$spa   = vks_get_field_options( VKS_FIELD_SPA, [ 'Spa' => 'Spa', 'Sauna' => 'Sauna', 'Jacuzzi' => 'Jacuzzi', 'Pool' => 'Pool' ] );

	ob_start();
	?>
	<form class="vks-search" role="search" method="get" data-mode="search"
		action="<?php echo esc_url( $atts['action'] ); ?>"
		data-provider="<?php echo esc_attr( $atts['provider'] ); ?>"
		data-query-id="<?php echo esc_attr( $atts['query_id'] ); ?>"
		data-field-type="<?php echo esc_attr( VKS_FIELD_TYPE ); ?>"
		data-field-guests="<?php echo esc_attr( VKS_FIELD_GUESTS ); ?>"
		data-field-spa="<?php echo esc_attr( VKS_FIELD_SPA ); ?>">

		<?php echo vks_render_dropdown( 'type', __( 'Accommodation', 'viktorin-site' ), __( 'Any type', 'viktorin-site' ), $types, vks_icon( 'home' ) ); // phpcs:ignore ?>

		<?php echo vks_render_dates_field( __( 'Check in – Check out', 'viktorin-site' ) ); // phpcs:ignore ?>

		<?php echo vks_render_dropdown( 'guests', __( 'Guests', 'viktorin-site' ), __( 'Any', 'viktorin-site' ), vks_guest_options( vks_max_guests() ), vks_icon( 'guests' ) ); // phpcs:ignore ?>

		<?php echo vks_render_dropdown( 'spa', __( 'Spa & wellness', 'viktorin-site' ), __( 'Any', 'viktorin-site' ), $spa, vks_icon( 'spa' ) ); // phpcs:ignore ?>

		<div class="vks-field vks-field--submit">
			<button type="submit" class="vks-submit"><?php esc_html_e( 'Search', 'viktorin-site' ); ?></button>
		</div>
	</form>
	<?php
	return ob_get_clean();
}
