<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Register settings ────────────────────────────────────────────────────────

add_action( 'admin_init', 'suc_register_login_logo_settings' );
function suc_register_login_logo_settings() {
    register_setting( 'suc_login_logo_group', 'suc_logo_url', [
        'sanitize_callback' => 'esc_url_raw',
        'default'           => '',
    ] );
    register_setting( 'suc_login_logo_group', 'suc_logo_width', [
        'sanitize_callback' => 'absint',
        'default'           => 200,
    ] );
    register_setting( 'suc_login_logo_group', 'suc_logo_height', [
        'sanitize_callback' => 'absint',
        'default'           => 80,
    ] );
    register_setting( 'suc_login_logo_group', 'suc_logo_link', [
        'sanitize_callback' => 'esc_url_raw',
        'default'           => '',
    ] );
}

// ─── Enqueue media uploader on our settings page ─────────────────────────────

add_action( 'admin_enqueue_scripts', 'suc_enqueue_login_logo_scripts' );
function suc_enqueue_login_logo_scripts( $hook ) {
    if ( $hook !== 'settings_page_superuser-core' ) {
        return;
    }
    wp_enqueue_media();
}

// ─── Settings tab UI ──────────────────────────────────────────────────────────

function suc_render_login_logo_tab() {
    $logo_url    = get_option( 'suc_logo_url', '' );
    $logo_width  = get_option( 'suc_logo_width', 200 );
    $logo_height = get_option( 'suc_logo_height', 80 );
    $logo_link   = get_option( 'suc_logo_link', home_url() );
    ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'suc_login_logo_group' ); ?>
        <table class="form-table" role="presentation">

            <tr>
                <th scope="row"><label for="suc_logo_url">Logo Image</label></th>
                <td>
                    <?php if ( $logo_url ) : ?>
                        <div style="margin-bottom:10px;">
                            <img src="<?php echo esc_url( $logo_url ); ?>"
                                 style="max-width:<?php echo esc_attr( $logo_width ); ?>px;max-height:<?php echo esc_attr( $logo_height ); ?>px;display:block;border:1px solid #ddd;padding:4px;background:#fff;" />
                        </div>
                    <?php endif; ?>
                    <input type="text" id="suc_logo_url" name="suc_logo_url"
                           value="<?php echo esc_url( $logo_url ); ?>"
                           class="regular-text" />
                    <button type="button" id="suc-upload-btn" class="button">Choose Image</button>
                    <script>
                    jQuery(function($){
                        var frame;
                        $('#suc-upload-btn').on('click', function(e){
                            e.preventDefault();
                            if (frame) { frame.open(); return; }
                            frame = wp.media({ title: 'Select Login Logo', button: { text: 'Use this image' }, multiple: false });
                            frame.on('select', function(){
                                var attachment = frame.state().get('selection').first().toJSON();
                                $('#suc_logo_url').val(attachment.url);
                            });
                            frame.open();
                        });
                    });
                    </script>
                    <p class="description">Paste a URL or choose from the media library.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="suc_logo_width">Logo Width (px)</label></th>
                <td>
                    <input type="number" id="suc_logo_width" name="suc_logo_width"
                           value="<?php echo esc_attr( $logo_width ); ?>"
                           min="1" max="800" class="small-text" />
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="suc_logo_height">Logo Height (px)</label></th>
                <td>
                    <input type="number" id="suc_logo_height" name="suc_logo_height"
                           value="<?php echo esc_attr( $logo_height ); ?>"
                           min="1" max="400" class="small-text" />
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="suc_logo_link">Logo Link URL</label></th>
                <td>
                    <input type="url" id="suc_logo_link" name="suc_logo_link"
                           value="<?php echo esc_url( $logo_link ); ?>"
                           class="regular-text" />
                    <p class="description">Where clicking the logo takes the user. Defaults to your homepage.</p>
                </td>
            </tr>

        </table>
        <?php submit_button(); ?>
    </form>
    <?php
}

// ─── Login page output ────────────────────────────────────────────────────────

add_action( 'login_enqueue_scripts', 'suc_login_logo_css' );
function suc_login_logo_css() {
    $logo_url    = get_option( 'suc_logo_url', '' );
    $logo_width  = absint( get_option( 'suc_logo_width', 200 ) );
    $logo_height = absint( get_option( 'suc_logo_height', 80 ) );

    if ( ! $logo_url ) {
        return;
    }
    ?>
    <style>
        #login h1 a,
        .login h1 a {
            background-image: url(<?php echo esc_url( $logo_url ); ?>);
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            width: <?php echo $logo_width; ?>px;
            height: <?php echo $logo_height; ?>px;
        }
    </style>
    <?php
}

add_filter( 'login_headerurl', 'suc_login_logo_url' );
function suc_login_logo_url() {
    $link = get_option( 'suc_logo_link', '' );
    return $link ? $link : home_url();
}

add_filter( 'login_headertext', 'suc_login_logo_title' );
function suc_login_logo_title() {
    return get_bloginfo( 'name' );
}
