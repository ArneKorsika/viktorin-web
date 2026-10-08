<?php
// Only run when WordPress is uninstalling this plugin
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Login Logo options
delete_option( 'suc_logo_url' );
delete_option( 'suc_logo_width' );
delete_option( 'suc_logo_height' );
delete_option( 'suc_logo_link' );

// SVG Support options
delete_option( 'suc_svg_enabled' );

// Single Templates options
delete_option( 'suc_single_templates' );
