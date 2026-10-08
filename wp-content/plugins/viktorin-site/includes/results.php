<?php
/**
 * Accommodation results page helpers.
 *
 * [viktorin_results_count query="filter-item"] – live "5 stays" counter for a
 * JetEngine listing grid (the element with that CSS ID). Updated in JS whenever
 * JetSmartFilters re-renders the grid.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'vks_register_results_assets' );
function vks_register_results_assets() {
	wp_register_script( 'vks-results', VKS_URL . 'assets/js/results.js', [], filemtime( VKS_PATH . 'assets/js/results.js' ), true );
}

add_shortcode( 'viktorin_results_count', 'vks_results_count' );
function vks_results_count( $atts ) {
	$atts = shortcode_atts( [ 'query' => 'filter-item' ], $atts, 'viktorin_results_count' );

	$total = (int) ( wp_count_posts( VKS_POST_TYPE )->publish ?? 0 );

	wp_enqueue_style( 'vks-search' );
	wp_enqueue_script( 'vks-results' );
	wp_localize_script( 'vks-results', 'vksResults', [
		'one'   => __( '%d stay', 'viktorin-site' ),
		'many'  => __( '%d stays', 'viktorin-site' ),
		'none'  => __( 'No stays match your filters', 'viktorin-site' ),
		'sortPrefix'  => __( 'Sort by:', 'viktorin-site' ),
		'sortDefault' => __( 'Recommended', 'viktorin-site' ),
	] );

	return sprintf(
		'<p class="vks-count" data-vks-count-for="%1$s" aria-live="polite">%2$s</p>',
		esc_attr( $atts['query'] ),
		esc_html( sprintf( _n( '%d stay', '%d stays', $total, 'viktorin-site' ), $total ) )
	);
}
