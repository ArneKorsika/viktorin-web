<?php
/**
 * Viktorin – Jupiter X child theme.
 *
 * Child functions.php runs before the parent's. Loading Jupiter X's init first
 * (the pattern from Artbees' official child theme) makes the jupiterx_* API
 * available below. The parent's own require_once then becomes a no-op.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/lib/init.php';

define( 'VIKTORIN_CHILD_VERSION', wp_get_theme()->get( 'Version' ) );

add_action( 'wp_enqueue_scripts', 'viktorin_child_enqueue_assets', 20 );
function viktorin_child_enqueue_assets() {
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();

	if ( file_exists( $dir . '/assets/css/site.css' ) ) {
		wp_enqueue_style( 'viktorin-child', $uri . '/assets/css/site.css', [], filemtime( $dir . '/assets/css/site.css' ) );
	}

	if ( file_exists( $dir . '/assets/js/site.js' ) ) {
		wp_enqueue_script( 'viktorin-child', $uri . '/assets/js/site.js', [], filemtime( $dir . '/assets/js/site.js' ), true );
	}
}

/**
 * Always load styles for widgets used in the Jupiter X header/footer templates.
 *
 * Elementor only enqueues a widget's CSS when the page content itself uses that
 * widget. Jupiter X renders the header/footer outside Elementor's theme builder,
 * so pages without e.g. an Image Box (About, Contact) rendered the header's
 * "Call us" block unstyled.
 */
add_action( 'elementor/frontend/after_enqueue_styles', 'viktorin_enqueue_header_widget_styles' );
function viktorin_enqueue_header_widget_styles() {
	foreach ( [ 'widget-image-box', 'widget-icon-box' ] as $handle ) {
		if ( wp_style_is( $handle, 'registered' ) ) {
			wp_enqueue_style( $handle );
		}
	}
}
