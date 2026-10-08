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

const VKS_POST_TYPE    = 'accommodation';
const VKS_FIELD_TYPE   = 'accommodation-types';
const VKS_FIELD_GUESTS = 'accommodation-guests';
const VKS_FIELD_SPA    = 'accommodation-spa';

add_action( 'wp_enqueue_scripts', 'vks_register_search_assets' );
function vks_register_search_assets() {
	wp_register_style( 'flatpickr', VKS_URL . 'assets/vendor/flatpickr/flatpickr.min.css', [], '4.6.13' );
	wp_register_script( 'flatpickr', VKS_URL . 'assets/vendor/flatpickr/flatpickr.min.js', [], '4.6.13', true );

	wp_register_style(
		'vks-search',
		VKS_URL . 'assets/css/search.css',
		[ 'flatpickr' ],
		filemtime( VKS_PATH . 'assets/css/search.css' )
	);
	wp_register_script(
		'vks-search',
		VKS_URL . 'assets/js/search.js',
		[ 'flatpickr' ],
		filemtime( VKS_PATH . 'assets/js/search.js' ),
		true
	);
}

/**
 * Options of a JetEngine select field on the accommodation post type,
 * as [ value => label ]. Falls back to $fallback if JetEngine isn't available.
 */
function vks_get_field_options( $field_name, $fallback = [] ) {
	if ( ! function_exists( 'jet_engine' ) || ! isset( jet_engine()->meta_boxes ) ) {
		return $fallback;
	}

	$fields = jet_engine()->meta_boxes->get_meta_fields_for_object( VKS_POST_TYPE );

	foreach ( (array) $fields as $field ) {
		if ( ( $field['name'] ?? '' ) !== $field_name || empty( $field['options'] ) ) {
			continue;
		}

		$options = [];
		foreach ( $field['options'] as $key => $option ) {
			if ( is_array( $option ) ) {           // raw format: [ ['key' => .., 'value' => ..], … ]
				$value = trim( (string) ( $option['key'] ?? '' ) );
				$label = trim( (string) ( $option['value'] ?? $value ) );
			} else {                                // processed format: [ key => label ]
				$value = trim( (string) $key );
				$label = trim( (string) $option );
			}
			if ( '' !== $value ) {
				$options[ $value ] = $label;
			}
		}

		return $options ?: $fallback;
	}

	return $fallback;
}

function vks_default_results_url() {
	$page = get_page_by_path( 'apartments' );
	return $page ? get_permalink( $page ) : home_url( '/' );
}

/**
 * Custom dropdown (button + listbox) backed by a hidden input.
 */
function vks_render_dropdown( $name, $label, $placeholder, array $options, $icon = '' ) {
	$id = 'vks-' . $name . '-' . wp_unique_id();
	ob_start();
	?>
	<div class="vks-field vks-field--<?php echo esc_attr( $name ); ?>">
		<span class="vks-label" id="<?php echo esc_attr( $id ); ?>-label"><?php echo esc_html( $label ); ?></span>
		<div class="vks-select" data-vks-select>
			<button type="button" class="vks-control vks-select__button"
				aria-haspopup="listbox" aria-expanded="false"
				aria-labelledby="<?php echo esc_attr( $id ); ?>-label <?php echo esc_attr( $id ); ?>-value">
				<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG ?>
				<span class="vks-select__value is-placeholder" id="<?php echo esc_attr( $id ); ?>-value"><?php echo esc_html( $placeholder ); ?></span>
				<svg class="vks-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="M5 7.5l5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<ul class="vks-select__list" role="listbox" tabindex="-1" hidden aria-labelledby="<?php echo esc_attr( $id ); ?>-label">
				<li role="option" class="vks-select__option" data-value="" aria-selected="true"><?php echo esc_html( $placeholder ); ?></li>
				<?php foreach ( $options as $value => $text ) : ?>
					<li role="option" class="vks-select__option" data-value="<?php echo esc_attr( $value ); ?>" aria-selected="false"><?php echo esc_html( $text ); ?></li>
				<?php endforeach; ?>
			</ul>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="">
		</div>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'viktorin_search', 'vks_render_search_form' );
function vks_render_search_form( $atts ) {
	$atts = shortcode_atts(
		[
			'action'   => vks_default_results_url(),
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

	// Guests: offer 1 … highest capacity, searched as "at least N".
	$capacities = array_filter( array_map( 'intval', array_keys( vks_get_field_options( VKS_FIELD_GUESTS, [ 2 => 2, 3 => 3, 4 => 4 ] ) ) ) );
	$max_guests = $capacities ? max( $capacities ) : 4;
	$guests     = [];
	for ( $i = 1; $i <= $max_guests; $i++ ) {
		/* translators: %d: number of guests */
		$guests[ $i ] = sprintf( _n( '%d guest', '%d guests', $i, 'viktorin-site' ), $i );
	}

	$icon_home  = '<svg class="vks-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5M5.5 9v11h13V9" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	$icon_cal   = '<svg class="vks-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M3.5 10h17M8 3v4M16 3v4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
	$icon_spa   = '<svg class="vks-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20c-4.5 0-8-2.6-8-6.2 2.8 0 5.6 1.2 8 3.6 2.4-2.4 5.2-3.6 8-3.6 0 3.6-3.5 6.2-8 6.2Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 17.4c-2-2.2-2.9-4.6-2.6-7.4L12 7.5l2.6 2.5c.3 2.8-.6 5.2-2.6 7.4Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>';
	$icon_guest = '<svg class="vks-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M4.5 20.5c1.2-4 4.1-6 7.5-6s6.3 2 7.5 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';

	$dates_id = 'vks-dates-' . wp_unique_id();

	ob_start();
	?>
	<form class="vks-search" role="search" method="get"
		action="<?php echo esc_url( $atts['action'] ); ?>"
		data-provider="<?php echo esc_attr( $atts['provider'] ); ?>"
		data-query-id="<?php echo esc_attr( $atts['query_id'] ); ?>"
		data-field-type="<?php echo esc_attr( VKS_FIELD_TYPE ); ?>"
		data-field-guests="<?php echo esc_attr( VKS_FIELD_GUESTS ); ?>"
		data-field-spa="<?php echo esc_attr( VKS_FIELD_SPA ); ?>">

		<?php echo vks_render_dropdown( 'type', __( 'Apartment', 'viktorin-site' ), __( 'Any type', 'viktorin-site' ), $types, $icon_home ); // phpcs:ignore ?>

		<div class="vks-field vks-field--dates">
			<label class="vks-label" for="<?php echo esc_attr( $dates_id ); ?>"><?php esc_html_e( 'Check in – Check out', 'viktorin-site' ); ?></label>
			<div class="vks-control vks-dates">
				<?php echo $icon_cal; // phpcs:ignore ?>
				<input type="text" id="<?php echo esc_attr( $dates_id ); ?>" class="vks-dates__input"
					placeholder="<?php esc_attr_e( 'Select dates', 'viktorin-site' ); ?>" autocomplete="off" readonly>
				<button type="button" class="vks-dates__clear" aria-label="<?php esc_attr_e( 'Clear dates', 'viktorin-site' ); ?>" hidden>&times;</button>
			</div>
			<input type="hidden" name="check_in" value="">
			<input type="hidden" name="check_out" value="">
		</div>

		<?php echo vks_render_dropdown( 'guests', __( 'Guests', 'viktorin-site' ), __( 'Any', 'viktorin-site' ), $guests, $icon_guest ); // phpcs:ignore ?>

		<?php echo vks_render_dropdown( 'spa', __( 'Spa & wellness', 'viktorin-site' ), __( 'Any', 'viktorin-site' ), $spa, $icon_spa ); // phpcs:ignore ?>

		<div class="vks-field vks-field--submit">
			<button type="submit" class="vks-submit"><?php esc_html_e( 'Search', 'viktorin-site' ); ?></button>
		</div>
	</form>
	<?php
	return ob_get_clean();
}
