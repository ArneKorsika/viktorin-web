<?php
/**
 * Shared helpers: field access, icons, form controls.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VKS_POST_TYPE       = 'accommodation';
const VKS_FIELD_TYPE      = 'accommodation-types';
const VKS_FIELD_GUESTS    = 'accommodation-guests';
const VKS_FIELD_SPA       = 'accommodation-spa';
const VKS_FIELD_PRICE     = 'accommodation-price';
const VKS_FIELD_CITY      = 'accommodation-city';
const VKS_FIELD_BEDS      = 'accommodation-beds';
const VKS_FIELD_BATHROOMS = 'accommodation-bathroom';
const VKS_FIELD_AMENITIES = 'accommodation-amenities';
const VKS_FIELD_DESC      = 'accommodation-description';
const VKS_FIELD_GALLERY   = 'yacht-slider'; // JetEngine gallery field (labelled "Gallery")

/**
 * Options of a JetEngine select/checkbox field on the accommodation post type,
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

function vks_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' );
}

function vks_format_price( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value || ! is_numeric( str_replace( ',', '.', $value ) ) ) {
		return $value;
	}
	// Same format as the listing cards: "1500€".
	return round( (float) str_replace( ',', '.', $value ) ) . '€';
}

/**
 * Inline SVG icons (stroke = currentColor).
 */
function vks_icon( $name, $class = 'vks-icon' ) {
	$p = [
		'home'      => '<path d="M3 10.5 12 3l9 7.5M5.5 9v11h13V9"/>',
		'calendar'  => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
		'guests'    => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5c1.2-4 4.1-6 7.5-6s6.3 2 7.5 6"/>',
		'spa'       => '<path d="M12 20c-4.5 0-8-2.6-8-6.2 2.8 0 5.6 1.2 8 3.6 2.4-2.4 5.2-3.6 8-3.6 0 3.6-3.5 6.2-8 6.2Z"/><path d="M12 17.4c-2-2.2-2.9-4.6-2.6-7.4L12 7.5l2.6 2.5c.3 2.8-.6 5.2-2.6 7.4Z"/>',
		'bed'       => '<path d="M3 18.5V7M21 18.5V13a3 3 0 0 0-3-3h-7.5v6H3M3 16h18"/><circle cx="7" cy="12" r="2"/>',
		'bath'      => '<path d="M4 12h16v2.5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V12ZM6 12V5.5A2.5 2.5 0 0 1 8.5 3c1.2 0 2.2.8 2.4 2M7 19.5 6 21M17 19.5l1 1.5"/>',
		'pin'       => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0C18.5 15.4 12 21 12 21Z"/><circle cx="12" cy="10" r="2.4"/>',
		'photos'    => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5"/>',
		'check'     => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'wifi'      => '<path d="M2.5 9a14 14 0 0 1 19 0M5.5 12.3a9.5 9.5 0 0 1 13 0M8.6 15.5a5 5 0 0 1 6.8 0"/><circle cx="12" cy="19" r="1"/>',
		'parking'   => '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><path d="M9.5 17V7.5h3.2a2.8 2.8 0 0 1 0 5.6H9.5"/>',
		'snow'      => '<path d="M12 2.5v19M3.8 7.2l16.4 9.6M3.8 16.8l16.4-9.6M9.5 4 12 6.5 14.5 4M9.5 20l2.5-2.5 2.5 2.5"/>',
		'kitchen'   => '<path d="M7 3v8M5 3v5a2 2 0 0 0 4 0V3M7 11v10M17 21V3c-2.2 1.2-3.5 3.6-3.5 7v3H17"/>',
		'tv'        => '<rect x="2.5" y="5" width="19" height="12.5" rx="2"/><path d="M8 21h8"/>',
		'washer'    => '<rect x="4" y="2.5" width="16" height="19" rx="2.5"/><circle cx="12" cy="13" r="4.5"/><path d="M7.5 6h1M11 6h1"/>',
		'terrace'   => '<path d="M4 21V11h16v10M4 15h16M8 11v10M12 11v10M16 11v10M2.5 11 12 4l9.5 7"/>',
		'sauna'     => '<path d="M3 20.5h18M5 20.5V12h14v8.5M8.5 9c-1-1.4 1-2.6 0-4M12 9c-1-1.4 1-2.6 0-4M15.5 9c-1-1.4 1-2.6 0-4"/>',
		'jacuzzi'   => '<path d="M3 13h18v2a6 6 0 0 1-6 6H9a6 6 0 0 1-6-6v-2Z"/><circle cx="8" cy="8" r="1.4"/><circle cx="12.5" cy="5.5" r="1.4"/><circle cx="16" cy="9" r="1.4"/>',
		'pool'      => '<path d="M2.5 17c1.6 0 1.6 1.2 3.2 1.2S7.3 17 8.9 17s1.6 1.2 3.2 1.2 1.6-1.2 3.2-1.2 1.6 1.2 3.2 1.2 1.6-1.2 3-1.2M8 14.5V5a2 2 0 0 1 4 0M16 14.5V5a2 2 0 0 0-4 0M8 9h8"/>',
		'pets'      => '<circle cx="5.5" cy="10" r="1.8"/><circle cx="9" cy="5.8" r="1.8"/><circle cx="15" cy="5.8" r="1.8"/><circle cx="18.5" cy="10" r="1.8"/><path d="M12 11.5c-3 0-6 4.5-4.6 7 1 1.8 3 .8 4.6.8s3.6 1 4.6-.8c1.4-2.5-1.6-7-4.6-7Z"/>',
		'nosmoke'   => '<path d="M3 15.5h12.5v3H3zM18 15.5v3M20.5 15.5v3M18 12.5c0-2 2.5-2 2.5-4.5M4.5 4.5l15 15"/>',
		'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
	];
	$path = $p[ $name ] ?? $p['check'];
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

/**
 * Custom dropdown (button + listbox) backed by a hidden input.
 */
function vks_render_dropdown( $name, $label, $placeholder, array $options, $icon = '', $selected = '' ) {
	$id         = 'vks-' . $name . '-' . wp_unique_id();
	$selected   = (string) $selected;
	$has_value  = '' !== $selected && isset( $options[ $selected ] );
	$shown_text = $has_value ? $options[ $selected ] : $placeholder;
	ob_start();
	?>
	<div class="vks-field vks-field--<?php echo esc_attr( $name ); ?>">
		<span class="vks-label" id="<?php echo esc_attr( $id ); ?>-label"><?php echo esc_html( $label ); ?></span>
		<div class="vks-select" data-vks-select>
			<button type="button" class="vks-control vks-select__button"
				aria-haspopup="listbox" aria-expanded="false"
				aria-labelledby="<?php echo esc_attr( $id ); ?>-label <?php echo esc_attr( $id ); ?>-value">
				<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG ?>
				<span class="vks-select__value<?php echo $has_value ? '' : ' is-placeholder'; ?>" id="<?php echo esc_attr( $id ); ?>-value"><?php echo esc_html( $shown_text ); ?></span>
				<svg class="vks-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="M5 7.5l5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<ul class="vks-select__list" role="listbox" tabindex="-1" hidden aria-labelledby="<?php echo esc_attr( $id ); ?>-label">
				<?php if ( '' !== $placeholder ) : ?>
					<li role="option" class="vks-select__option" data-value="" aria-selected="<?php echo $has_value ? 'false' : 'true'; ?>"><?php echo esc_html( $placeholder ); ?></li>
				<?php endif; ?>
				<?php foreach ( $options as $value => $text ) : ?>
					<li role="option" class="vks-select__option" data-value="<?php echo esc_attr( $value ); ?>" aria-selected="<?php echo ( $has_value && (string) $value === $selected ) ? 'true' : 'false'; ?>"><?php echo esc_html( $text ); ?></li>
				<?php endforeach; ?>
			</ul>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $has_value ? $selected : '' ); ?>">
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Date range field (Flatpickr is attached in JS).
 */
function vks_render_dates_field( $label ) {
	$id = 'vks-dates-' . wp_unique_id();
	ob_start();
	?>
	<div class="vks-field vks-field--dates">
		<label class="vks-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<div class="vks-control vks-dates">
			<?php echo vks_icon( 'calendar' ); // phpcs:ignore ?>
			<input type="text" id="<?php echo esc_attr( $id ); ?>" class="vks-dates__input"
				placeholder="<?php esc_attr_e( 'Select dates', 'viktorin-site' ); ?>" autocomplete="off" readonly>
			<button type="button" class="vks-dates__clear" aria-label="<?php esc_attr_e( 'Clear dates', 'viktorin-site' ); ?>" hidden>&times;</button>
		</div>
		<input type="hidden" name="check_in" value="">
		<input type="hidden" name="check_out" value="">
	</div>
	<?php
	return ob_get_clean();
}

function vks_guest_options( $max ) {
	$guests = [];
	for ( $i = 1; $i <= max( 1, (int) $max ); $i++ ) {
		/* translators: %d: number of guests */
		$guests[ $i ] = sprintf( _n( '%d guest', '%d guests', $i, 'viktorin-site' ), $i );
	}
	return $guests;
}
