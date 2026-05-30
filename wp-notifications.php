<?php
/**
 * Plugin Name: WP Latest Notifications
 * Plugin URI:  https://360digitalmarketerjay.in/latest-notificstion-board/
 * Description: Scrollable notification list with NEW badge, hyperlink & document/PDF support. Full-page 3-column shortcode + 2-column sidebar widget.
 * Version:     1.0.1
 * Author:      Jayant Mallick
 * License:     GPL-2.0+
 * Text Domain: wp-notifications
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPNOTIF_VERSION', '1.0.1' );
define( 'WPNOTIF_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPNOTIF_URL', plugin_dir_url( __FILE__ ) );

/* =====================================================================
   1. ACTIVATION — create DB table
   ===================================================================== */
register_activation_hook( __FILE__, 'wpnotif_activate' );
function wpnotif_activate() {
    global $wpdb;
    $table   = $wpdb->prefix . 'notifications';
    $charset = $wpdb->get_charset_collate();
    $sql     = "CREATE TABLE IF NOT EXISTS {$table} (
        id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        title       VARCHAR(400)        NOT NULL,
        notif_date  DATE                NOT NULL,
        link_url    VARCHAR(500)                 DEFAULT '',
        doc_url     VARCHAR(500)                 DEFAULT '',
        doc_name    VARCHAR(200)                 DEFAULT '',
        created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    add_option( 'wpnotif_new_threshold', 2 );
    add_option( 'wpnotif_display_limit', 6 );
    add_option( 'wpnotif_widget_title', 'Latest Updates' );
add_option( 'wpnotif_font_family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' );
add_option( 'wpnotif_font_size', '14' );
add_option( 'wpnotif_text_color', '#1e293b' );
add_option( 'wpnotif_custom_title', 'Latest Notifications' );
}

/* =====================================================================
   2. ENQUEUE ASSETS
   ===================================================================== */

/* Admin assets — only on our admin pages */
add_action( 'admin_enqueue_scripts', 'wpnotif_admin_assets' );
function wpnotif_admin_assets( $hook ) {
    if ( strpos( $hook, 'wp-notifications' ) === false ) return;
    wp_enqueue_media();
    wp_enqueue_style(  'wpnotif-admin', WPNOTIF_URL . 'assets/admin.css',   [], WPNOTIF_VERSION );
    wp_enqueue_script( 'wpnotif-admin', WPNOTIF_URL . 'assets/admin.js', ['jquery'], WPNOTIF_VERSION, true );
    wp_localize_script( 'wpnotif-admin', 'WPNotif', [
        'nonce'   => wp_create_nonce( 'wpnotif_nonce' ),
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
    ]);
}

/*
 * Frontend CSS — enqueue on EVERY frontend request unconditionally.
 * This is the fix for the widget area / sidebar not picking up styles,
 * because wp_enqueue_scripts fires before widget output in many themes.
 */
add_action( 'wp_enqueue_scripts', 'wpnotif_frontend_assets' );
function wpnotif_frontend_assets() {
    wp_enqueue_style( 'wpnotif-front', WPNOTIF_URL . 'assets/frontend.css', [], WPNOTIF_VERSION );

    $font_fam = get_option( 'wpnotif_font_family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' );
    $font_sz = get_option( 'wpnotif_font_size', '14' );
    $tx_color = get_option( 'wpnotif_text_color', '#1e293b' );

    $custom_css = "
        .wpnotif-full-wrap, .wpnotif-widget-wrap {
            font-family: {$font_fam} !important;
            font-size: {$font_sz}px !important;
            color: {$tx_color} !important;
        }
    ";
    wp_add_inline_style( 'wpnotif-front', $custom_css );
}

/* =====================================================================
   3. ADMIN MENU
   ===================================================================== */
add_action( 'admin_menu', 'wpnotif_admin_menu' );
function wpnotif_admin_menu() {
    add_menu_page( 'Notifications', 'Notifications', 'manage_options',
        'wp-notifications', 'wpnotif_admin_page', 'dashicons-bell', 25 );
    add_submenu_page( 'wp-notifications', 'Settings', 'Settings',
        'manage_options', 'wp-notifications-settings', 'wpnotif_settings_page' );
}

/* =====================================================================
   4. ADMIN PAGE
   ===================================================================== */
function wpnotif_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'notifications';

    if ( isset( $_POST['wpnotif_delete'], $_POST['notif_id'] ) ) {
        check_admin_referer( 'wpnotif_delete_' . absint( $_POST['notif_id'] ) );
        $wpdb->delete( $table, [ 'id' => absint( $_POST['notif_id'] ) ] );
        echo '<div class="notice notice-success"><p>Notification deleted.</p></div>';
    }

    if ( isset( $_POST['wpnotif_save'] ) ) {
        check_admin_referer( 'wpnotif_save' );
        $data = [
            'title'      => sanitize_text_field( $_POST['notif_title'] ),
            'notif_date' => sanitize_text_field( $_POST['notif_date'] ),
            'link_url'   => esc_url_raw( $_POST['notif_link'] ),
            'doc_url'    => esc_url_raw( $_POST['notif_doc_url'] ),
            'doc_name'   => sanitize_text_field( $_POST['notif_doc_name'] ),
        ];
        $id = absint( $_POST['edit_id'] ?? 0 );
        if ( $id ) {
            $wpdb->update( $table, $data, [ 'id' => $id ] );
            echo '<div class="notice notice-success"><p>Notification updated.</p></div>';
        } else {
            $wpdb->insert( $table, $data );
            echo '<div class="notice notice-success"><p>Notification added.</p></div>';
        }
    }

    $edit_item = null;
    if ( isset( $_GET['edit'] ) ) {
        $edit_item = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id=%d", absint( $_GET['edit'] )
        ));
    }

    $items     = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY notif_date DESC" );
    $threshold = (int) get_option( 'wpnotif_new_threshold', 2 );
    ?>
    <div class="wrap wpnotif-wrap">
    <h1 class="wp-heading-inline">Latest Notifications</h1>
        <hr class="wp-header-end">
        <div class="wpnotif-grid">

            <!-- ADD / EDIT FORM -->
            <div class="wpnotif-card">
                <h2><?php echo $edit_item ? 'Edit Notification' : 'Add Notification'; ?></h2>
                <form method="post">
                    <?php wp_nonce_field( 'wpnotif_save' ); ?>
                    <input type="hidden" name="wpnotif_save" value="1">
                    <input type="hidden" name="edit_id" value="<?php echo esc_attr( $edit_item->id ?? 0 ); ?>">

                    <div class="wpnotif-field">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="notif_title" required
                               value="<?php echo esc_attr( $edit_item->title ?? '' ); ?>"
                               placeholder="e.g. Annual Report 2025 Released">
                    </div>
                    <div class="wpnotif-field">
                        <label>Date <span class="required">*</span></label>
                        <input type="date" name="notif_date" required
                               value="<?php echo esc_attr( $edit_item->notif_date ?? date('Y-m-d') ); ?>">
                    </div>
                    <div class="wpnotif-field">
                        <label>Link URL <span style="font-weight:400;color:#8c8f94">(embeds in title)</span></label>
                        <div class="wpnotif-link-row">
                            <input type="url" id="notif_link" name="notif_link"
                                   value="<?php echo esc_attr( $edit_item->link_url ?? '' ); ?>"
                                   placeholder="https://example.com/page">
                            <button type="button" class="button" id="clear-link">✕</button>
                        </div>
                    </div>
                    <div class="wpnotif-field">
                        <label>Document / PDF <span style="font-weight:400;color:#8c8f94">(optional)</span></label>
                        <div class="wpnotif-doc-row">
                            <input type="hidden" id="notif_doc_url"  name="notif_doc_url"
                                   value="<?php echo esc_attr( $edit_item->doc_url ?? '' ); ?>">
                            <input type="hidden" id="notif_doc_name" name="notif_doc_name"
                                   value="<?php echo esc_attr( $edit_item->doc_name ?? '' ); ?>">
                            <button type="button" class="button" id="open-media-uploader">📎 Choose File</button>
                            <button type="button" class="button" id="clear-doc">✕ Remove</button>
                        </div>
                        <div id="doc-preview" class="wpnotif-doc-preview"
                             style="<?php echo ( $edit_item && $edit_item->doc_url ) ? '' : 'display:none'; ?>">
                            <span class="dashicons dashicons-media-document"></span>
                            <span id="doc-preview-name"><?php echo esc_html( $edit_item->doc_name ?? '' ); ?></span>
                        </div>
                    </div>
                    <div class="wpnotif-submit-row">
                        <button type="submit" class="button button-primary">
                            <?php echo $edit_item ? '💾 Update' : '➕ Add Notification'; ?>
                        </button>
                        <?php if ( $edit_item ) : ?>
                            <a href="<?php echo admin_url('admin.php?page=wp-notifications'); ?>" class="button">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- NOTIFICATION LIST -->
            <div class="wpnotif-card">
                <h2>All Notifications
                    <span class="wpnotif-count">(<?php echo count($items); ?>)</span>
                </h2>
                <?php if ( empty($items) ) : ?>
                    <p class="wpnotif-empty">No notifications yet.</p>
                <?php else : ?>
                <div class="wpnotif-admin-list">
                    <?php foreach ( $items as $i => $item ) :
                        if ( $i === $threshold ) : ?>
                            <div class="wpnotif-new-sep"><span>▲ NEW ABOVE</span></div>
                        <?php endif; ?>
                        <div class="wpnotif-admin-row <?php echo $i < $threshold ? 'is-new' : ''; ?>">
                            <div class="wpnotif-admin-row-meta">
                                <span class="wpnotif-date"><?php echo esc_html( date('d M Y', strtotime($item->notif_date)) ); ?></span>
                                <?php if ( $item->doc_url ) echo '<span class="wpnotif-pill pdf">📄 PDF</span>'; ?>
                                <?php if ( $item->link_url ) echo '<span class="wpnotif-pill link">🔗 Link</span>'; ?>
                            </div>
                            <div class="wpnotif-admin-row-title"><?php echo esc_html($item->title); ?></div>
                            <div class="wpnotif-admin-row-actions">
                                <a href="<?php echo admin_url('admin.php?page=wp-notifications&edit='.$item->id); ?>"
                                   class="button button-small">Edit</a>
                                <form method="post" style="display:inline">
                                    <?php wp_nonce_field('wpnotif_delete_'.$item->id); ?>
                                    <input type="hidden" name="wpnotif_delete" value="1">
                                    <input type="hidden" name="notif_id" value="<?php echo $item->id; ?>">
                                    <button type="submit" class="button button-small button-link-delete"
                                            onclick="return confirm('Delete?')">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /grid -->
    </div>
    <?php
}

/* =====================================================================
   5. SETTINGS PAGE
   ===================================================================== */
function wpnotif_settings_page() {
    if ( isset( $_POST['wpnotif_settings_save'] ) ) {
        check_admin_referer( 'wpnotif_settings' );
        update_option( 'wpnotif_new_threshold', absint( $_POST['wpnotif_new_threshold'] ) );
        update_option( 'wpnotif_display_limit', absint( $_POST['wpnotif_display_limit'] ) );
        update_option( 'wpnotif_widget_title', sanitize_text_field( $_POST['wpnotif_widget_title'] ) );
        update_option( 'wpnotif_font_family', sanitize_text_field( $_POST['wpnotif_font_family'] ) );
        update_option( 'wpnotif_font_size', absint( $_POST['wpnotif_font_size'] ) );
        update_option( 'wpnotif_text_color', sanitize_hex_color( $_POST['wpnotif_text_color'] ) );
        echo '<div class="notice notice-success"><p>Design settings updated.</p></div>';
    }

    $title = get_option( 'wpnotif_widget_title', 'Latest Updates' );
    $font_fam = get_option( 'wpnotif_font_family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' );
    $font_sz = get_option( 'wpnotif_font_size', '14' );
    $tx_color = get_option( 'wpnotif_text_color', '#1e293b' );
    ?>
    <div class="wrap wpnotif-wrap">
        <h1>Design & Configuration</h1>
        <form method="post">
            <?php wp_nonce_field('wpnotif_settings'); ?>
            <input type="hidden" name="wpnotif_settings_save" value="1">
            <table class="form-table">
                <tr>
                    <th><label>Widget Title</label></th>
                    <td><input type="text" name="wpnotif_widget_title" value="<?php echo esc_attr($title); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label>Font Family</label></th>
                    <td>
                        <select name="wpnotif_font_family">
                            <option value="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" <?php selected($font_fam, "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"); ?>>Modern Sans</option>
                            <option value="'Helvetica Neue', Helvetica, Arial, sans-serif" <?php selected($font_fam, "'Helvetica Neue', Helvetica, Arial, sans-serif"); ?>>Classic Helvetica</option>
                            <option value="Georgia, serif" <?php selected($font_fam, "Georgia, serif"); ?>>Elegant Serif</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Font Size (px)</label></th>
                    <td><input type="number" name="wpnotif_font_size" value="<?php echo $font_sz; ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th><label>Text Color</label></th>
                    <td><input type="color" name="wpnotif_text_color" value="<?php echo esc_attr($tx_color); ?>"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}


/* =====================================================================
   6. SHORTCODE — full-page 3-column table  [wp_notifications]
   ===================================================================== */
add_shortcode( 'wp_notifications', 'wpnotif_shortcode_full' );
function wpnotif_shortcode_full( $atts ) {
    global $wpdb;
    $atts = shortcode_atts([
        'limit' => (int) get_option( 'wpnotif_display_limit', 6 ),
        'new'   => (int) get_option( 'wpnotif_new_threshold', 2 ),
    ], $atts );

    $limit     = max(1, (int)$atts['limit']);
    $threshold = max(0, (int)$atts['new']);
    $table     = $wpdb->prefix . 'notifications';
    $items     = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY notif_date DESC LIMIT %d", $limit
    ));

    ob_start(); ?>
    <div class="wpnotif-full-wrap">
        <table class="wpnotif-table" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th class="wpnotif-col-sno">S.No</th>
                    <th class="wpnotif-col-title">Title</th>
                    <th class="wpnotif-col-doc">Document</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $items as $i => $item ) :
                if ( $i === $threshold ) : ?>
                    <tr class="wpnotif-new-divider-row">
                        <td colspan="3">
                            <div class="wpnotif-new-divider">
                                <span class="wpnotif-new-badge">NEW</span>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr class="wpnotif-row <?php echo $i < $threshold ? 'wpnotif-is-new' : ''; ?>">
                    <td class="wpnotif-col-sno"><?php echo $i + 1; ?></td>
                    <td class="wpnotif-col-title">
                        <?php if ( $item->link_url ) : ?>
                            <a href="<?php echo esc_url($item->link_url); ?>" class="wpnotif-title-link"
                               target="_blank" rel="noopener">
                                <?php echo esc_html($item->title); ?>
                                <svg class="wpnotif-ext-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            </a>
                        <?php else : ?>
                            <span><?php echo esc_html($item->title); ?></span>
                        <?php endif; ?>
                        <span class="wpnotif-date-chip"><?php echo date('d M Y', strtotime($item->notif_date)); ?></span>
                    </td>
                    <td class="wpnotif-col-doc">
                        <?php if ( $item->doc_url ) : ?>
                            <a href="<?php echo esc_url($item->doc_url); ?>" class="wpnotif-dl-btn"
                               download="<?php echo esc_attr($item->doc_name ?: 'document'); ?>" target="_blank">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
                                Download
                            </a>
                        <?php else : ?>
                            <span class="wpnotif-no-doc">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php return ob_get_clean();
}

/* =====================================================================
   7. SHORTCODE — sidebar 2-column widget  [wp_notifications_widget]
   ===================================================================== */
add_shortcode( 'wp_notifications_widget', 'wpnotif_shortcode_widget' );
function wpnotif_shortcode_widget( $atts ) {
    global $wpdb;
    $atts = shortcode_atts([
        'limit' => 5,
        'new'   => (int) get_option( 'wpnotif_new_threshold', 2 ),
        'title' => 'Notifications',
    ], $atts );

    $limit     = max(1, (int)$atts['limit']);
    $threshold = max(0, (int)$atts['new']);
    $table     = $wpdb->prefix . 'notifications';
    $items     = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY notif_date DESC LIMIT %d", $limit
    ));

    ob_start(); ?>
    <div class="wpnotif-widget-wrap">

        <?php if ( ! empty( trim($atts['title']) ) ) : ?>
        <div class="wpnotif-widget-header">
            <span class="wpnotif-widget-title"><?php echo esc_html($atts['title']); ?></span>
        </div>
        <?php endif; ?>

        <div class="wpnotif-widget-list">
        <?php foreach ( $items as $i => $item ) :

            /* ---- NEW separator ---- */
            if ( $i === $threshold ) : ?>
                <div class="wpnotif-widget-sep">
                    <span>NEW</span>
                </div>
            <?php endif; ?>

            <?php
            /*
             * Each row uses BOTH a CSS class AND inline grid styles.
             * The inline styles are the critical fallback that ensures
             * the 2-column layout renders even when the external CSS
             * file hasn't loaded yet (e.g. widget areas, page builders).
             */
            $is_new    = $i < $threshold;
            $row_style = 'display:grid;grid-template-columns:28px 1fr;gap:8px;align-items:start;'
                       . 'padding:9px 14px;border-bottom:1px solid #f3f4f6;';
            if ( $is_new ) {
                $row_style .= 'background:#fff5f5;border-left:3px solid #e24b4a;padding-left:11px;';
            }
            ?>
            <div class="wpnotif-widget-row <?php echo $is_new ? 'wpnotif-is-new' : ''; ?>"
                 style="<?php echo $row_style; ?>">

                <!-- Column 1: serial number -->
                <span class="wpnotif-widget-sno"
                      style="font-size:11px;font-weight:600;color:#c0c4cc;padding-top:2px;text-align:right;">
                    <?php echo $i + 1; ?>
                </span>

                <!-- Column 2: title + date -->
                <div class="wpnotif-widget-body" style="display:flex;flex-direction:column;gap:2px;">
                    <?php if ( $item->link_url ) : ?>
                        <a href="<?php echo esc_url($item->link_url); ?>"
                           class="wpnotif-widget-link"
                           style="font-size:13px;color:#1565c0;text-decoration:none;line-height:1.4;"
                           target="_blank" rel="noopener">
                            <?php echo esc_html($item->title); ?>
                        </a>
                    <?php else : ?>
                        <span class="wpnotif-widget-item-title"
                              style="font-size:13px;color:<?php echo $is_new ? '#b91c1c' : '#1f2937'; ?>;line-height:1.4;font-weight:<?php echo $is_new ? '500' : '400'; ?>;">
                            <?php echo esc_html($item->title); ?>
                        </span>
                    <?php endif; ?>
                    <span class="wpnotif-widget-date"
                          style="font-size:11px;color:#9ca3af;">
                        <?php echo date('d M Y', strtotime($item->notif_date)); ?>
                    </span>
                </div>

            </div>
        <?php endforeach; ?>
        </div><!-- /list -->
    </div>
    <?php return ob_get_clean();
}

/* =====================================================================
   8. CLASSIC WORDPRESS WIDGET
   ===================================================================== */
add_action( 'widgets_init', function() {
    register_widget( 'WPNotif_Sidebar_Widget' );
});

class WPNotif_Sidebar_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct( 'wp_notifications_widget', 'Latest Notifications',
            [ 'description' => 'Compact 2-column notification list with NEW badge.' ] );
    }
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] ?? 'Notifications' );
        $limit = absint( $instance['limit'] ?? 5 );
        echo $args['before_widget'];
        echo do_shortcode( "[wp_notifications_widget limit='{$limit}' title='']" );
        echo $args['after_widget'];
    }
    public function form( $instance ) {
        $title = esc_attr( $instance['title'] ?? 'Notifications' );
        $limit = absint( $instance['limit'] ?? 5 );
        echo '<p><label>Title<br><input class="widefat" name="'.$this->get_field_name('title').'" type="text" value="'.$title.'"></label></p>';
        echo '<p><label>Number of items<br><input class="tiny-text" name="'.$this->get_field_name('limit').'" type="number" value="'.$limit.'" min="1" max="20"></label></p>';
    }
    public function update( $new, $old ) {
        return [ 'title' => sanitize_text_field($new['title']), 'limit' => absint($new['limit']) ];
    }
}

/* =====================================================================
   9. REST API
   ===================================================================== */
add_action( 'rest_api_init', function() {
    register_rest_route( 'wp-notifications/v1', '/list', [
        'methods'             => 'GET',
        'callback'            => 'wpnotif_rest_list',
        'permission_callback' => '__return_true',
        'args'                => [ 'limit' => [ 'default' => 6, 'sanitize_callback' => 'absint' ] ],
    ]);
});
function wpnotif_rest_list( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'notifications';
    return rest_ensure_response( $wpdb->get_results( $wpdb->prepare(
        "SELECT id,title,notif_date,link_url,doc_url,doc_name FROM {$table} ORDER BY notif_date DESC LIMIT %d",
        $req->get_param('limit')
    )));
}
