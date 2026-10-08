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
