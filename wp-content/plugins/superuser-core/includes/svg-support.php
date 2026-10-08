<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Register settings ────────────────────────────────────────────────────────

add_action( 'admin_init', 'suc_register_svg_settings' );
function suc_register_svg_settings() {
    register_setting( 'suc_svg_group', 'suc_svg_enabled', [
        'sanitize_callback' => 'absint',
        'default'           => 0,
    ] );
}

// ─── Enable SVG uploads when toggled on ──────────────────────────────────────

add_filter( 'upload_mimes', 'suc_allow_svg' );
function suc_allow_svg( $mimes ) {
    if ( get_option( 'suc_svg_enabled', 0 ) ) {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
    }
    return $mimes;
}

// Fix SVG preview in media library
add_filter( 'wp_check_filetype_and_ext', 'suc_fix_svg_filetype', 10, 4 );
function suc_fix_svg_filetype( $data, $file, $filename, $mimes ) {
    if ( ! get_option( 'suc_svg_enabled', 0 ) ) {
        return $data;
    }
    $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    if ( $ext === 'svg' || $ext === 'svgz' ) {
        $data['ext']  = $ext;
        $data['type'] = 'image/svg+xml';
    }
    return $data;
}

// Show SVG thumbnails in media library
add_action( 'admin_head', 'suc_svg_media_library_thumbnails' );
function suc_svg_media_library_thumbnails() {
    if ( ! get_option( 'suc_svg_enabled', 0 ) ) {
        return;
    }
    echo '<style>
        .attachment-266x266, .thumbnail img[src$=".svg"] {
            width: 100% !important;
            height: auto !important;
        }
        img[src$=".svg"] { width: 100%; }
    </style>';
}

// ─── Settings tab UI ──────────────────────────────────────────────────────────

function suc_render_svg_support_tab() {
    $enabled = get_option( 'suc_svg_enabled', 0 );
    ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'suc_svg_group' ); ?>
        <table class="form-table" role="presentation">

            <tr>
                <th scope="row">Enable SVG Uploads</th>
                <td>
                    <label>
                        <input type="checkbox" name="suc_svg_enabled" value="1" <?php checked( 1, $enabled ); ?> />
                        Allow SVG files to be uploaded to the Media Library
                    </label>
                    <p class="description">
                        Only enable this if you trust everyone who has upload access to this site.
                        SVG files can contain code — only upload files from trusted sources.
                    </p>
                </td>
            </tr>

        </table>
        <?php submit_button(); ?>
    </form>
    <?php
}
