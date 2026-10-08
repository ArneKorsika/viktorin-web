<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Page template options ────────────────────────────────────────────────────

function suc_page_template_options() {
    return [
        'default'                  => 'Default (theme layout)',
        'elementor_header_footer'  => 'Full Width (keeps header & footer)',
        'elementor_canvas'         => 'Canvas (completely blank)',
    ];
}

// ─── Save handler ─────────────────────────────────────────────────────────────

add_action( 'admin_post_suc_save_single_templates', 'suc_save_single_templates' );
function suc_save_single_templates() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    check_admin_referer( 'suc_single_templates_nonce' );

    $mappings      = [];
    $post_types    = isset( $_POST['suc_st_post_type'] )    ? (array) $_POST['suc_st_post_type']    : [];
    $template_ids  = isset( $_POST['suc_st_template_id'] )  ? (array) $_POST['suc_st_template_id']  : [];
    $page_templates = isset( $_POST['suc_st_page_template'] ) ? (array) $_POST['suc_st_page_template'] : [];
    $allowed_page_templates = array_keys( suc_page_template_options() );

    foreach ( $post_types as $index => $post_type ) {
        $post_type     = sanitize_key( $post_type );
        $template_id   = absint( $template_ids[ $index ] ?? 0 );
        $page_template = sanitize_key( $page_templates[ $index ] ?? 'default' );
        if ( ! in_array( $page_template, $allowed_page_templates, true ) ) {
            $page_template = 'default';
        }
        if ( $post_type && $template_id ) {
            $mappings[] = [
                'post_type'     => $post_type,
                'template_id'   => $template_id,
                'page_template' => $page_template,
            ];
        }
    }

    update_option( 'suc_single_templates', $mappings );

    wp_redirect( admin_url( 'options-general.php?page=superuser-core&tab=single-templates&saved=1' ) );
    exit;
}

// ─── Settings tab UI ──────────────────────────────────────────────────────────

function suc_render_single_templates_tab() {
    $mappings       = get_option( 'suc_single_templates', [] );
    $saved          = isset( $_GET['saved'] ) && $_GET['saved'] === '1';
    $post_types     = get_post_types( [ 'public' => true ], 'objects' );
    $pt_options     = suc_page_template_options();
    unset( $post_types['attachment'] );
    ?>

    <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
    <?php endif; ?>

    <p>Map a post type to an Elementor saved template. Choose the page layout that controls the header/footer wrapping.</p>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="suc_save_single_templates" />
        <?php wp_nonce_field( 'suc_single_templates_nonce' ); ?>

        <table class="wp-list-table widefat fixed striped" id="suc-st-table" style="max-width:900px;margin-bottom:12px;">
            <thead>
                <tr>
                    <th>Post Type</th>
                    <th>Elementor Template ID</th>
                    <th>Page Layout</th>
                    <th style="width:50px;"></th>
                </tr>
            </thead>
            <tbody id="suc-st-rows">
                <?php
                $rows = ! empty( $mappings ) ? $mappings : [ [ 'post_type' => '', 'template_id' => '', 'page_template' => 'elementor_header_footer' ] ];
                foreach ( $rows as $mapping ) :
                    $current_pt = $mapping['page_template'] ?? 'elementor_header_footer';
                ?>
                    <tr class="suc-st-row">
                        <td>
                            <select name="suc_st_post_type[]" style="width:100%;">
                                <option value="">— Select —</option>
                                <?php foreach ( $post_types as $slug => $obj ) : ?>
                                    <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $mapping['post_type'] ?? '' ); ?>>
                                        <?php echo esc_html( $obj->labels->singular_name . ' (' . $slug . ')' ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="suc_st_template_id[]"
                                   value="<?php echo esc_attr( $mapping['template_id'] ?? '' ); ?>"
                                   min="1" style="width:100%;" placeholder="e.g. 267" />
                        </td>
                        <td>
                            <select name="suc_st_page_template[]" style="width:100%;">
                                <?php foreach ( $pt_options as $val => $label ) : ?>
                                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $val, $current_pt ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <button type="button" class="button suc-remove-row">✕</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="button" class="button" id="suc-add-row" style="margin-bottom:16px;">+ Add Row</button>

        <?php submit_button( 'Save Templates' ); ?>
    </form>

    <script>
    (function(){
        var postTypeOptions = <?php
            $opts = '<option value="">— Select —<\/option>';
            foreach ( $post_types as $slug => $obj ) {
                $opts .= '<option value="' . esc_attr( $slug ) . '">' . esc_html( $obj->labels->singular_name . ' (' . $slug . ')' ) . '<\/option>';
            }
            echo json_encode( $opts );
        ?>;

        var pageTemplateOptions = <?php
            $opts2 = '';
            foreach ( $pt_options as $val => $label ) {
                $selected = $val === 'elementor_header_footer' ? ' selected' : '';
                $opts2 .= '<option value="' . esc_attr( $val ) . '"' . $selected . '>' . esc_html( $label ) . '<\/option>';
            }
            echo json_encode( $opts2 );
        ?>;

        document.getElementById('suc-add-row').addEventListener('click', function(){
            var tbody = document.getElementById('suc-st-rows');
            var tr = document.createElement('tr');
            tr.className = 'suc-st-row';
            tr.innerHTML =
                '<td><select name="suc_st_post_type[]" style="width:100%;">' + postTypeOptions + '<\/select><\/td>' +
                '<td><input type="number" name="suc_st_template_id[]" value="" min="1" style="width:100%;" placeholder="e.g. 267" \/><\/td>' +
                '<td><select name="suc_st_page_template[]" style="width:100%;">' + pageTemplateOptions + '<\/select><\/td>' +
                '<td><button type="button" class="button suc-remove-row">✕<\/button><\/td>';
            tbody.appendChild(tr);
        });

        document.addEventListener('click', function(e){
            if ( e.target && e.target.classList.contains('suc-remove-row') ) {
                var row = e.target.closest('tr');
                if ( document.querySelectorAll('.suc-st-row').length > 1 ) {
                    row.remove();
                } else {
                    row.querySelectorAll('select').forEach(function(s){ s.value = ''; });
                    row.querySelectorAll('input').forEach(function(i){ i.value = ''; });
                }
            }
        });
    })();
    </script>
    <?php
}

// ─── Frontend: apply Elementor template to single post type ──────────────────

function suc_get_matched_mapping() {
    if ( ! is_singular() || is_admin() ) {
        return null;
    }
    $mappings = get_option( 'suc_single_templates', [] );
    foreach ( $mappings as $mapping ) {
        if ( ! empty( $mapping['post_type'] ) && ! empty( $mapping['template_id'] ) ) {
            if ( is_singular( $mapping['post_type'] ) ) {
                return $mapping;
            }
        }
    }
    return null;
}

// Override page template via meta so WordPress/Jupiter X/Elementor all pick it up
add_filter( 'get_post_metadata', 'suc_override_page_template', 10, 4 );
function suc_override_page_template( $value, $object_id, $meta_key, $single ) {
    if ( $meta_key !== '_wp_page_template' ) {
        return $value;
    }
    $mapping = suc_get_matched_mapping();
    if ( ! $mapping ) {
        return $value;
    }
    $page_template = $mapping['page_template'] ?? 'elementor_header_footer';
    if ( $page_template === 'default' ) {
        return $value;
    }
    return $single ? $page_template : [ $page_template ];
}

// Inject the Elementor saved template content
add_filter( 'the_content', 'suc_apply_single_template' );
function suc_apply_single_template( $content ) {
    $mapping = suc_get_matched_mapping();
    if ( ! $mapping || ! class_exists( '\Elementor\Plugin' ) ) {
        return $content;
    }

    return \Elementor\Plugin::instance()->frontend->get_builder_content_for_display(
        (int) $mapping['template_id'],
        true
    );
}
