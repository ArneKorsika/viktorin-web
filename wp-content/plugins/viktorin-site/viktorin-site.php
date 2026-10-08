<?php
/**
 * Plugin Name: Viktorin Site
 * Description: Site-specific functionality for Viktorin Apartments – search bar, single accommodation components. Keep generic, reusable features in SuperUser Core.
 * Version:     1.1.0
 * Author:      SuperUser
 * Author URI:  https://superuser.si
 * License:     GPL-2.0+
 * Text Domain: viktorin-site
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VKS_VERSION', '1.1.0' );
define( 'VKS_PATH', plugin_dir_path( __FILE__ ) );
define( 'VKS_URL', plugin_dir_url( __FILE__ ) );

require_once VKS_PATH . 'includes/helpers.php';
require_once VKS_PATH . 'includes/search-form.php';
require_once VKS_PATH . 'includes/single-stay.php';
