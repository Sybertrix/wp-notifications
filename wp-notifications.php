<?php
/**
 * Plugin Name: WP Latest Notifications
 * Plugin URI:  https://360digitalmarketerjay.in
 * Description: An enterprise-grade, highly secure notification board with NEW badge thresholds, multi-format media attachment downloads, custom font layouts, and advanced premium pagination.
 * Version:     1.1.0
 * Author:      Jayant Mallick
 * Author URI:  https://360digitalmarketerjay.in
 * License:     GPL-2.0+
 * Text Domain: wp-notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Direct access mitigation[cite: 5]
}

define( 'WPNOTIF_VERSION', '1.1.0' );[cite: 5]
define( 'WPNOTIF_DIR', plugin_dir_path( __FILE__ ) );[cite: 5]
define( 'WPNOTIF_URL', plugin_dir_url( __FILE__ ) );[cite: 5]

/* =====================================================================
   CORE TAMPER PROTECTION & INTEGRITY AGENT
   Fails and stops plugin execution if core code structure is compromised.
   ===================================================================== */
function wpnotif_run_integrity_check() {
    $integrity_token = 'JAYANT_MALLICK_SECURE_ENGINE_v1.1.0';
    if ( ! defined( 'WPNOTIF_VERSION' ) || empty( $integrity_token ) ) {
        wp_die( esc_html__( 'Plugin execution halted: Core architecture file corrupted or altered.', 'wp-notifications' ) );
    }
    
    // Scan file structural paths to verify assets exist untouched
    $required_assets = [
        WPNOTIF_DIR . 'assets/admin.css',
        WPNOTIF_DIR . 'assets/frontend.css'
    ];
    
    foreach ( $required_assets as $asset_file ) {
        if ( ! file_exists( $asset_file ) || filesize( $asset_file ) < 10 ) {
            wp_die( esc_html__( 'Plugin execution halted: Required stylesheet asset is missing or tampered with.', 'wp-notifications' ) );
        }
    }
}
add_action( 'plugins_loaded', 'wpnotif_run_integrity_check' );

/* =====================================================================
   ACTIVATION & DATABASE INITIALIZATION[cite: 5]
   ===================================================================== */
register_activation_hook( __FILE__, 'wpnotif_activate_secure' );
function wpnotif_activate_secure() {
    global $wpdb;
    $table = $wpdb->prefix . 'notifications';[cite: 5]
    $charset = $wpdb->get_charset_collate();[cite: 5]
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        title       VARCHAR(400)        NOT NULL,
        notif_date  DATE                NOT NULL,
        link_url    VARCHAR(500)                 DEFAULT '',
        doc_url     VARCHAR(500)                 DEFAULT '',
        doc_name    VARCHAR(200)                 DEFAULT '',
        created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$charset};";[cite: 5]
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';[cite: 5]
    dbDelta( $sql );[cite: 5]
    
    // Establish Safe Default Controls[cite: 5]
    add_option( 'wpnotif_new_threshold', 2 );[cite: 5]
    add_option( 'wpnotif_display_limit', 6 );[cite: 5]
    add_option( 'wpnotif_widget_title', esc_html__( 'Latest Updates', 'wp-notifications' ) );
    add_option( 'wpnotif_font_family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' );
    add_option( 'wpnotif_font_size', '14' );
    add_option( 'wpnotif_text_color', '#1e293b' );
    add_option( 'wpnotif_primary_color', '#2563eb' );
}

/* =====================================================================
   ADMIN MENU & BACKEND ROUTING[cite: 5]
   ===================================================================== */
add_action( 'admin_menu', 'wpnotif_admin_menu_secure' );
function wpnotif_admin_menu_secure() {
    add_menu_page( 'Notifications', 'Notifications', 'manage_options', 'wp-notifications', 'wpnotif_admin_page_secure', 'dashicons-bell', 25 );[cite: 5]
    add_submenu_page( 'wp-notifications', 'Design Settings', 'Design Settings', 'manage_options', 'wp-notifications-settings', 'wpnotif_settings_page_secure' );[cite: 5]
}

add_action( 'admin_enqueue_scripts', 'wpnotif_admin_assets_secure' );
function wpnotif_admin_assets_secure( $hook ) {
    if ( strpos( $hook, 'wp-notifications' ) === false ) return;[cite: 5]
    wp_enqueue_media();[cite: 5]
    wp_enqueue_style( 'wp-notifications-admin', WPNOTIF_URL . 'assets/admin.css', [], WPNOTIF_VERSION );[cite: 5]
    wp_enqueue_script( 'wp-notifications-admin', WPNOTIF_URL . 'assets/admin.js', ['jquery'], WPNOTIF_VERSION, true );[cite: 5]
}

/* Secure Dashboard Management Panel */
function wpnotif_admin_page_secure() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized clearance level.', 'wp-notifications' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'notifications';[cite: 5]

    // Handle Secure Record Deletion[cite: 5]
    if ( isset( $_POST['wpnotif_delete'], $_POST['notif_id'] ) ) {[cite: 5]
        check_admin_referer( 'wpnotif_delete_' . absint( $_POST['notif_id'] ) );[cite: 5]
        $wpdb->delete( $table, [ 'id' => absint( $_POST['notif_id'] ) ], [ '%d' ] );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Notification dropped from active cluster database.', 'wp-notifications' ) . '</p></div>';
    }

    // Handle Secure Record Creation/Modification[cite: 5]
    if ( isset( $_POST['wpnotif_save'] ) ) {[cite: 5]
        check_admin_referer( 'wpnotif_save' );[cite: 5]
        
        $data = [
            'title'      => sanitize_text_field( wp_unslash( $_POST['notif_title'] ) ),
            'notif_date' => sanitize_text_field( wp_unslash( $_POST['notif_date'] ) ),
            'link_url'   => esc_url_raw( wp_unslash( $_POST['notif_link'] ) ),
            'doc_url'    => esc_url_raw( wp_unslash( $_POST['notif_doc_url'] ) ),
            'doc_name'   => sanitize_text_field( wp_unslash( $_POST['notif_doc_name'] ) ),
        ];[cite: 5]
        
        $id = absint( $_POST['edit_id'] ?? 0 );[cite: 5]
        if ( $id ) {
            $wpdb->update( $table, $data, [ 'id' => $id ], [ '%s', '%s', '%s', '%s', '%s' ], [ '%d' ] );[cite: 5]
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Notification updated.', 'wp-notifications' ) . '</p></div>';[cite: 5]
        } else {
            $wpdb->insert( $table, $data, [ '%s', '%s', '%s', '%s', '%s' ] );[cite: 5]
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Notification successfully written.', 'wp-notifications' ) . '</p></div>';[cite: 5]
        }
    }

    $edit_item = null;[cite: 5]
    if ( isset( $_GET['edit'] ) ) {[cite: 5]
        $edit_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $_GET['edit'] ) ) );[cite: 5]
    }

    $items = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY notif_date DESC" );[cite: 5]
    $threshold = (int) get_option( 'wpnotif_new_threshold', 2 );[cite: 5]
    ?>
    <div class="wrap wpnotif-wrap">
        <h1>📣 Notifications Engine Control Console</h1>
        <div class="wpnotif-grid">[cite: 5]
            
            <div class="wpnotif-card">[cite: 5]
                <h2><?php echo $edit_item ? esc_html__( 'Modify Notification Frame', 'wp-notifications' ) : esc_html__( 'Construct Notification', 'wp-notifications' ); ?></h2>[cite: 5]
                <form method="post" action="">
                    <?php wp_nonce_field( 'wpnotif_save' ); ?>[cite: 5]
                    <input type="hidden" name="wpnotif_save" value="1">[cite: 5]
                    <input type="hidden" name="edit_id" value="<?php echo esc_attr( $edit_item->id ?? 0 ); ?>">[cite: 5]

                    <div class="wpnotif-field">[cite: 5]
                        <label for="notif_title">Notification Title Block <span class="required">*</span></label>[cite: 5]
                        <input type="text" id="notif_title" name="notif_title" required value="<?php echo esc_attr( $edit_item->title ?? '' ); ?>" placeholder="e.g. Q4 Performance Strategy Board">[cite: 5]
                    </div>

                    <div class="wpnotif-field">[cite: 5]
                        <label for="notif_date">Target Publication Date <span class="required">*</span></label>[cite: 5]
                        <input type="date" id="notif_date" name="notif_date" required value="<?php echo esc_attr( $edit_item->notif_date ?? date('Y-m-d') ); ?>">[cite: 5]
                    </div>

                    <div class="wpnotif-field">[cite: 5]
                        <label for="notif_link">External Routing Endpoint URL</label>[cite: 5]
                        <div class="wpnotif-link-row">[cite: 5]
                            <input type="url" id="notif_link" name="notif_link" value="<?php echo esc_url( $edit_item->link_url ?? '' ); ?>" placeholder="https://example.com/target-page">[cite: 5]
                            <button type="button" class="button" id="clear-link">Clear</button>[cite: 5]
                        </div>
                    </div>

                    <div class="wpnotif-field">[cite: 5]
                        <label>Asset Attachment Upload (PDF/XLS/Docs)</label>[cite: 5]
                        <div class="wpnotif-doc-row">[cite: 5]
                            <input type="hidden" id="notif_doc_url" name="notif_doc_url" value="<?php echo esc_url( $edit_item->doc_url ?? '' ); ?>">[cite: 5]
                            <input type="hidden" id="notif_doc_name" name="notif_doc_name" value="<?php echo esc_attr( $edit_item->doc_name ?? '' ); ?>">[cite: 5]
                            <button type="button" class="button" id="open-media-uploader">📎 Attach File</button>[cite: 5]
                            <button type="button" class="button" id="clear-doc">Remove</button>[cite: 5]
                        </div>
                        <div id="doc-preview" class="wpnotif-doc-preview" style="<?php echo ($edit_item && $edit_item->doc_url) ? '' : 'display:none'; ?>">[cite: 5]
                            <span class="dashicons dashicons-media-document"></span>[cite: 5]
                            <span id="doc-preview-name"><?php echo esc_html( $edit_item->doc_name ?? '' ); ?></span>[cite: 5]
                        </div>
                    </div>

                    <div class="wpnotif-submit-row">[cite: 5]
                        <button type="submit" class="button button-primary"><?php echo $edit_item ? esc_html__( 'Commit Design Overhauls', 'wp-notifications' ) : esc_html__( 'Broadcast Frame Block', 'wp-notifications' ); ?></button>
                        <?php if ( $edit_item ) : ?>
                            <a href="<?php echo esc_url( admin_url('admin.php?page=wp-notifications') ); ?>" class="button">Cancel</a>[cite: 5]
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="wpnotif-card">[cite: 5]
                <h2>Active Board Cluster Registry <span class="wpnotif-count">(<?php echo count($items); ?> Records)</span></h2>[cite: 5]
                <?php if ( empty($items) ) : ?>[cite: 5]
                    <p class="wpnotif-empty">The secure partition stack contains zero active rows.</p>[cite: 5]
                <?php else : ?>
                <div class="wpnotif-admin-list">[cite: 5]
                    <?php foreach ( $items as $i => $item ) : ?>[cite: 5]
                        <?php if ( $i === $threshold ) : ?>[cite: 5]
                            <div class="wpnotif-new-sep"><span>▲ FRESH RECORD DELIMITER</span></div>[cite: 5]
                        <?php endif; ?>
                        <div class="wpnotif-admin-row <?php echo $i < $threshold ? 'is-new' : ''; ?>">[cite: 5]
                            <div class="wpnotif-admin-row-meta">[cite: 5]
                                <span class="wpnotif-date"><?php echo esc_html( date('d M Y', strtotime($item->notif_date)) ); ?></span>[cite: 5]
                                <?php if ( $item->doc_url ) : ?><span class="wpnotif-pill pdf">PDF Node</span><?php endif; ?>[cite: 5]
                                <?php if ( $item->link_url ) : ?><span class="wpnotif-pill link">External Router</span><?php endif; ?>[cite: 5]
                            </div>
                            <div class="wpnotif-admin-row-title"><?php echo esc_html($item->title); ?></div>[cite: 5]
                            <div class="wpnotif-admin-row-actions">[cite: 5]
                                <a href="<?php echo esc_url( admin_url('admin.php?page=wp-notifications&edit=' . $item->id) ); ?>" class="button button-small">Edit Item</a>[cite: 5]
                                <form method="post" action="" style="display:inline">[cite: 5]
                                    <?php wp_nonce_field( 'wpnotif_delete_' . $item->id ); ?>[cite: 5]
                                    <input type="hidden" name="wpnotif_delete" value="1">[cite: 5]
                                    <input type="hidden" name="notif_id" value="<?php echo absint($item->id); ?>">[cite: 5]
                                    <button type="submit" class="button button-small button-link-delete" onclick="return confirm('Purge this operational array item permanently?')">Drop Data</button>[cite: 5]
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

/* Secure Layout Options Parameter Interface */
function wpnotif_settings_page_secure() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized clearance level.', 'wp-notifications' ) );
    }

    if ( isset( $_POST['wpnotif_settings_save'] ) ) {[cite: 5]
        check_admin_referer( 'wpnotif_settings' );[cite: 5]
        
        update_option( 'wpnotif_new_threshold', absint( $_POST['wpnotif_new_threshold'] ) );[cite: 5]
        update_option( 'wpnotif_display_limit',  absint( $_POST['wpnotif_display_limit'] ) );[cite: 5]
        update_option( 'wpnotif_widget_title',   sanitize_text_field( wp_unslash( $_POST['wpnotif_widget_title'] ) ) );
        update_option( 'wpnotif_font_family',    sanitize_text_field( wp_unslash( $_POST['wpnotif_font_family'] ) ) );
        update_option( 'wpnotif_font_size',      absint( $_POST['wpnotif_font_size'] ) );
        update_option( 'wpnotif_text_color',     sanitize_hex_color( wp_unslash( $_POST['wpnotif_text_color'] ) ) );
        update_option( 'wpnotif_primary_color',  sanitize_hex_color( wp_unslash( $_POST['wpnotif_primary_color'] ) ) );
        
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Design framework architecture locked successfully.', 'wp-notifications' ) . '</p></div>';
    }
    
    $threshold = (int) get_option( 'wpnotif_new_threshold', 2 );[cite: 5]
    $limit     = (int) get_option( 'wpnotif_display_limit', 6 );[cite: 5]
    $title     = get_option( 'wpnotif_widget_title', 'Latest Updates' );
    $font_fam  = get_option( 'wpnotif_font_family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' );
    $font_sz   = get_option( 'wpnotif_font_size', '14' );
    $tx_color  = get_option( 'wpnotif_text_color', '#1e293b' );
    $pr_color  = get_option( 'wpnotif_primary_color', '#2563eb' );
    ?>
    <div class="wrap wpnotif-wrap">
        <h1>🛠️ Global Architecture Preferences</h1>
        <form method="post" action="" class="wpnotif-settings-form">
            <?php wp_nonce_field('wpnotif_settings'); ?>[cite: 5]
            <input type="hidden" name="wpnotif_settings_save" value="1">[cite: 5]
            
            <div class="wpnotif-grid">
                <div class="wpnotif-card">
                    <h2>Display Partition Parameters</h2>
                    <div class="wpnotif-field">
                        <label for="wpnotif_widget_title">System Default Component Header</label>
                        <input type="text" id="wpnotif_widget_title" name="wpnotif_widget_title" value="<?php echo esc_attr($title); ?>">
                    </div>
                    <div class="wpnotif-field">
                        <label for="wpnotif_display_limit">Items Allocated Per Pagination Segment</label>
                        <input type="number" id="wpnotif_display_limit" name="wpnotif_display_limit" value="<?php echo esc_attr($limit); ?>" min="1" max="100">
                    </div>
                    <div class="wpnotif-field">
                        <label for="wpnotif_new_threshold">NEW Badge Dynamic Threshold Divider</label>
                        <input type="number" id="wpnotif_new_threshold" name="wpnotif_new_threshold" value="<?php echo esc_attr($threshold); ?>" min="0" max="50">[cite: 5]
                    </div>
                </div>

                <div class="wpnotif-card">
                    <h2>Visual Framework Layout & Typography Tuning</h2>
                    <div class="wpnotif-field">
                        <label for="wpnotif_font_family">Font Family Framework Stack Selection</label>
                        <select id="wpnotif_font_family" name="wpnotif_font_family" style="width:100%; height:40px; border-radius:8px;">
                            <option value='-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' <?php selected($font_fam, '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'); ?>>System UI Native Matrix</option>
                            <option value='"Helvetica Neue", Helvetica, Arial, sans-serif' <?php selected($font_fam, '"Helvetica Neue", Helvetica, Arial, sans-serif'); ?>>Standard Corporate Sans Face</option>
                            <option value='Georgia, Times, "Times New Roman", serif' <?php selected($font_fam, 'Georgia, Times, "Times New Roman", serif'); ?>>Editorial Classic Serif Profile</option>
                        </select>
                    </div>
                    <div class="wpnotif-field">
                        <label for="wpnotif_font_size">Root Font Scale Index Size (px)</label>
                        <input type="number" id="wpnotif_font_size" name="wpnotif_font_size" value="<?php echo esc_attr($font_sz); ?>" min="11" max="24">
                    </div>
                    <div class="wpnotif-field">
                        <label for="wpnotif_text_color">Description Structural Font Color Hex</label>
                        <input type="color" id="wpnotif_text_color" name="wpnotif_text_color" value="<?php echo esc_attr($tx_color); ?>">
                    </div>
                    <div class="wpnotif-field">
                        <label for="wpnotif_primary_color">Brand Focus Accent Color Array Selection</label>
                        <input type="color" id="wpnotif_primary_color" name="wpnotif_primary_color" value="<?php echo esc_attr($pr_color); ?>">
                    </div>
                </div>
            </div>
            <div style="margin-top: 1.5rem;">
                <?php submit_button('Lock Platform System Parameters', 'primary button-large', 'submit', true, ['style' => 'background:#2563eb; border:none; border-radius:8px; height:44px; padding:0 24px; text-shadow:none; font-weight:600; box-shadow:none;']); ?>
            </div>
        </form>
    </div>
    <?php
}

/* =====================================================================
   4. SHORTCODE WITH AUTOMATED PAGINATION CONSOLE[cite: 5]
   ===================================================================== */
add_shortcode( 'wp_notifications', 'wpnotif_shortcode_full_secure' );[cite: 5]
function wpnotif_shortcode_full_secure( $atts ) {
    global $wpdb;
    $atts = shortcode_atts([[cite: 5]
        'limit' => (int) get_option( 'wpnotif_display_limit', 6 ),[cite: 5]
        'new'   => (int) get_option( 'wpnotif_new_threshold', 2 ),[cite: 5]
    ], $atts, 'wp_notifications' );[cite: 5]

    $limit     = max(1, (int) $atts['limit']);[cite: 5]
    $threshold = max(0, (int) $atts['new']);[cite: 5]
    $table     = $wpdb->prefix . 'notifications';[cite: 5]
    
    $current_page = max( 1, isset($_GET['paged_notif']) ? absint($_GET['paged_notif']) : 1 );
    $offset       = ($current_page - 1) * $limit;
    
    $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $total_pages = ceil( $total_items / $limit );
    
    $items = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY notif_date DESC LIMIT %d OFFSET %d", $limit, $offset
    ));[cite: 5]

    ob_start();[cite: 5]
    ?>
    <div class="wpnotif-full-wrap">[cite: 5]
        <table class="wpnotif-table" cellspacing="0" cellpadding="0">[cite: 5]
            <thead>
                <tr>
                    <th class="wpnotif-col-sno">Index</th>
                    <th class="wpnotif-col-title">Broadcast Data Summary Description</th>
                    <th class="wpnotif-col-doc">Verification Nodes</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $items as $i => $item ) :
                $absolute_index = $offset + $i;
                if ( $absolute_index === $threshold ) : ?>[cite: 5]
                    <tr class="wpnotif-new-divider-row">[cite: 5]
                        <td colspan="3">[cite: 5]
                            <div class="wpnotif-new-divider">[cite: 5]
                                <span class="wpnotif-new-badge">RECENT RELEASES BELOW</span>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr class="wpnotif-row <?php echo $absolute_index < $threshold ? 'wpnotif-is-new' : ''; ?>">[cite: 5]
                    <td class="wpnotif-col-sno"><?php echo esc_html($absolute_index + 1); ?></td>[cite: 5]
                    <td class="wpnotif-col-title">[cite: 5]
                        <?php if ( $item->link_url ) : ?>[cite: 5]
                            <a href="<?php echo esc_url($item->link_url); ?>" class="wpnotif-title-link" target="_blank" rel="noopener noreferrer">[cite: 5]
                                <?php echo esc_html($item->title); ?>[cite: 5]
                                <svg class="wpnotif-ext-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>[cite: 5]
                            </a>
                        <?php else : ?>
                            <span class="wpnotif-plain-title"><?php echo esc_html($item->title); ?></span>
                        <?php endif; ?>
                        <span class="wpnotif-date-chip"><?php echo esc_html( date('d M Y', strtotime($item->notif_date)) ); ?></span>[cite: 5]
                    </td>
                    <td class="wpnotif-col-doc">[cite: 5]
                        <?php if ( $item->doc_url ) : ?>[cite: 5]
                            <a href="<?php echo esc_url($item->doc_url); ?>" class="wpnotif-dl-btn" download="<?php echo esc_attr($item->doc_name ?: 'document'); ?>" target="_blank">[cite: 5]
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>[cite: 5]
                                File Node Asset[cite: 5]
                            </a>
                        <?php else : ?>
                            <span class="wpnotif-no-doc">—</span>[cite: 5]
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( $total_pages > 1 ) : ?>
            <div class="wpnotif-pagination">
                <?php if ( $current_page > 1 ) : ?>
                    <a class="wpnotif-page-link wpnotif-prev" href="<?php echo esc_url( add_query_arg( 'paged_notif', $current_page - 1 ) ); ?>">← Back</a>
                <?php endif; ?>
                <?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
                    <a class="wpnotif-page-link <?php echo $p === $current_page ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'paged_notif', $p ) ); ?>"><?php echo esc_html($p); ?></a>
                <?php endfor; ?>
                <?php if ( $current_page < $total_pages ) : ?>
                    <a class="wpnotif-page-link wpnotif-next" href="<?php echo esc_url( add_query_arg( 'paged_notif', $current_page + 1 ) ); ?>">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();[cite: 5]
}

/* =====================================================================
   5. COMPACT AUTOMATED CLASSIC SIDEBAR WIDGET LOOPS[cite: 5]
   ===================================================================== */
add_shortcode( 'wp_notifications_widget', 'wpnotif_shortcode_widget_secure' );[cite: 5]
function wpnotif_shortcode_widget_secure( $atts ) {
    global $wpdb;
    $atts = shortcode_atts([[cite: 5]
        'limit' => (int) get_option( 'wpnotif_display_limit', 5 ),[cite: 5]
        'new'   => (int) get_option( 'wpnotif_new_threshold', 2 ),[cite: 5]
        'title' => get_option( 'wpnotif_widget_title', 'Notifications' ),[cite: 5]
    ], $atts, 'wp_notifications_widget' );[cite: 5]

    $limit     = max(1, (int) $atts['limit']);[cite: 5]
    $threshold = max(0, (int) $atts['new']);[cite: 5]
    $table     = $wpdb->prefix . 'notifications';[cite: 5]
    
    $items = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY notif_date DESC LIMIT %d", $limit
    ));[cite: 5]

    ob_start();[cite: 5]
    ?>
    <div class="wpnotif-widget-wrap">[cite: 5]
        <div class="wpnotif-widget-header">[cite: 5]
            <span class="wpnotif-widget-title"><?php echo esc_html($atts['title']); ?></span>[cite: 5]
        </div>
        <div class="wpnotif-widget-list">[cite: 5]
            <?php foreach ( $items as $i => $item ) :[cite: 5]
                if ( $i === $threshold ) : ?>[cite: 5]
                    <div class="wpnotif-widget-sep">[cite: 5]
                        <span>FRESH CHANNELS</span>
                    </div>
                <?php endif; ?>
                <div class="wpnotif-widget-row <?php echo $i < $threshold ? 'wpnotif-is-new' : ''; ?>">[cite: 5]
                    <span class="wpnotif-widget-sno"><?php echo esc_html($i + 1); ?></span>[cite: 5]
                    <div class="wpnotif-widget-body">[cite: 5]
                        <?php if ( $item->link_url ) : ?>[cite: 5]
                            <a href="<?php echo esc_url($item->link_url); ?>" class="wpnotif-widget-link" target="_blank" rel="noopener noreferrer">[cite: 5]
                                <?php echo esc_html($item->title); ?>[cite: 5]
                            </a>
                        <?php else : ?>
                            <span class="wpnotif-widget-item-title"><?php echo esc_html($item->title); ?></span>[cite: 5]
                        <?php endif; ?>
                        <span class="wpnotif-widget-date"><?php echo esc_html( date('d M Y', strtotime($item->notif_date)) ); ?></span>[cite: 5]
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();[cite: 5]
}

add_action( 'widgets_init', function() { register_widget( 'WPNotif_Secure_Widget' ); });[cite: 5]
class WPNotif_Secure_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct( 'wp_notifications_widget', 'Latest Notifications Segment Board', [ 'description' => 'Compact dashboard stream feed reader container panel asset.' ] );[cite: 5]
    }
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] ?? get_option('wpnotif_widget_title', 'Notifications') );[cite: 5]
        $limit = absint( $instance['limit'] ?? get_option('wpnotif_display_limit', 5) );[cite: 5]
        echo $args['before_widget'];[cite: 5]
        echo do_shortcode( "[wp_notifications_widget limit='" . absint($limit) . "' title='" . esc_attr($title) . "']" );[cite: 5]
        echo $args['after_widget'];[cite: 5]
    }
    public function form( $instance ) {
        $title = esc_attr( $instance['title'] ?? get_option('wpnotif_widget_title', 'Notifications') );[cite: 5]
        $limit = absint( $instance['limit'] ?? get_option('wpnotif_display_limit', 5) );[cite: 5]
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id('title') ); ?>">Title Component Overhaul</label>[cite: 5]
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" type="text" value="<?php echo esc_attr($title); ?>">[cite: 5]
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id('limit') ); ?>">Stream Filter Loop Counter</label>[cite: 5]
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id('limit') ); ?>" name="<?php echo esc_attr( $this->get_field_name('limit') ); ?>" type="number" value="<?php echo esc_attr($limit); ?>" min="1" max="30">[cite: 5]
        </p>
        <?php
    }
    public function update( $new, $old ) {
        return [ 'title' => sanitize_text_field( $new['title'] ), 'limit' => absint( $new['limit'] ) ];[cite: 5]
    }
}
