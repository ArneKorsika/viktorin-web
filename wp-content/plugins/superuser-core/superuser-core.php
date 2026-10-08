<?php
/**
 * Plugin Name: SuperUser Core
 * Plugin URI:  https://superuser.si
 * Description: Core customisations for SuperUser client sites — Login Logo, SVG Support, and more.
 * Version:     1.0.0
 * Author:      Arne Korsika
 * Author URI:  https://superuser.si
 * License:     GPL-2.0+
 * Text Domain: superuser-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SUC_VERSION', '1.1.0' );
define( 'SUC_PATH', plugin_dir_path( __FILE__ ) );
define( 'SUC_URL', plugin_dir_url( __FILE__ ) );

require_once SUC_PATH . 'includes/login-logo.php';
require_once SUC_PATH . 'includes/svg-support.php';
require_once SUC_PATH . 'includes/single-templates.php';

// ─── Settings page (tabbed) ───────────────────────────────────────────────────

add_action( 'admin_menu', 'suc_add_settings_page' );
function suc_add_settings_page() {
    add_options_page(
        'SuperUser Core',
        'SuperUser Core',
        'manage_options',
        'superuser-core',
        'suc_render_settings_page'
    );
}

function suc_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'login-logo';
    $tabs = [
        'login-logo'       => 'Login Logo',
        'svg-support'      => 'SVG Support',
        'single-templates' => 'Single Templates',
    ];
    ?>
    <div class="wrap">
        <h1>SuperUser Core</h1>
        <nav class="nav-tab-wrapper">
            <?php foreach ( $tabs as $slug => $label ) :
                $active = $active_tab === $slug ? ' nav-tab-active' : '';
                $url    = admin_url( 'options-general.php?page=superuser-core&tab=' . $slug );
            ?>
                <a href="<?php echo esc_url( $url ); ?>" class="nav-tab<?php echo $active; ?>">
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="tab-content" style="margin-top:20px;">
            <?php
            if ( $active_tab === 'login-logo' ) {
                suc_render_login_logo_tab();
            } elseif ( $active_tab === 'svg-support' ) {
                suc_render_svg_support_tab();
            } elseif ( $active_tab === 'single-templates' ) {
                suc_render_single_templates_tab();
            }
            ?>
        </div>
    </div>
    <?php
}
