# WP Latest Notifications — WordPress Plugin

A clean, self-contained WordPress plugin that powers a scrollable notification board with:

- ✅ Scrollable list showing the latest 5–6 (configurable) notifications
- ✅ Red **NEW** badge line after the top N date-sorted entries
- ✅ Hyperlink embedded in title (opens in new tab)
- ✅ Document / PDF upload via WordPress media library, with a Download button
- ✅ **Full-page shortcode** — 3-column table (S.No | Title | Download)
- ✅ **Sidebar widget** — 2-column compact list (S.No | Title + date)
- ✅ Classic Widgets support + Gutenberg-compatible via shortcode block
- ✅ REST API endpoint for headless use


## File Structure

```
wp-notifications/
├── wp-notifications.php   ← main plugin file
└── assets/
    ├── admin.css
    ├── admin.js
    └── frontend.css
```


## Installation

1. Upload the `wp-notifications/` folder to `/wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**
3. On first activation the plugin creates a `wp_notifications` table in your database automatically


## Admin Usage

Navigate to **Notifications** in the WordPress admin sidebar.

### Adding a notification
| Field | Description |
|-------|-------------|
| Title | The notification text (required) |
| Date  | Publication date (required, used for sorting & NEW badge) |
| Link URL | Optional. If set, the title becomes a hyperlink |
| Document / PDF | Optional. Opens the WP Media Library — pick any uploaded file |

### NEW badge threshold
The admin list shows a dividing line after the top N entries (default: 2). Change this under **Notifications → Settings**.


## Shortcodes

### Full-page 3-column table

```
[wp_notifications]
```

| Attribute | Default | Description |
|-----------|---------|-------------|
| `limit`   | 6       | Max rows displayed |
| `new`     | 2       | Rows before the NEW badge separator |

Example:
```
[wp_notifications limit="8" new="3"]
```

Wrap in a div with class `wpnotif-scrollable` to make the table scroll vertically:
```html
<div class="wpnotif-scrollable">
  [wp_notifications limit="10"]
</div>
```

### Sidebar / compact 2-column widget

```
[wp_notifications_widget]
```

| Attribute | Default | Description |
|-----------|---------|-------------|
| `limit`   | 5       | Max rows |
| `new`     | 2       | Rows before the NEW separator |
| `title`   | Notifications | Header text |

Example:
```
[wp_notifications_widget limit="5" title="Latest Updates"]
```


## Classic Widget

Go to **Appearance → Widgets** and drag **Latest Notifications** into any sidebar. Configure title and item count there.


## REST API

Fetch notifications as JSON (useful for AJAX or headless setups):

```
GET /wp-json/wp-notifications/v1/list?limit=6
```

Response:
```json
[
  {
    "id": 1,
    "title": "Annual Report 2025 Released",
    "notif_date": "2025-05-28",
    "link_url": "https://example.com/report",
    "doc_url": "https://example.com/wp-content/uploads/report.pdf",
    "doc_name": "Annual_Report_2025.pdf"
  }
]
```


## Customisation

All frontend colours and spacing are in `assets/frontend.css`. Key variables to tweak:

- `.wpnotif-is-new` — left border colour on NEW rows (`#e24b4a`)
- `.wpnotif-new-badge` — the red NEW pill
- `.wpnotif-dl-btn` — the download button (blue by default)
- `.wpnotif-table thead tr` — table header background


## Requirements

- WordPress 5.5+
- PHP 7.4+
- MySQL 5.6+ / MariaDB 10+

# Terms & Conditions
--------------------------
## WP Latest Notifications

*   **Contributors:** Jayant Mallick[cite: 5]
*   **Plugin URI:** https://360digitalmarketerjay.in[cite: 5]
*   **Tags:** notifications, bulletin, board, secure feed, asset manager
*   **Requires at least:** 5.8
*   **Tested up to:** 6.9
*   **Stable tag:** 1.1.0
*   **License:** GPLv2 or later[cite: 5]
*   **License URI:** https://www.gnu.org/licenses/gpl-2.0.html

An enterprise notification delivery system optimized for global security compliance.

## Terms & Operational Distribution Policy

By deploying this plugin on a live environment, you agree to the following operational parameters:

1. **Security Integrity Policy:** This plugin features a continuous runtime asset integrity validator. Unauthorized modifications to the core distribution file trees (`wp-notifications.php` or asset style files) will invoke a safe validation halt, freezing execution to prevent exploit injections[cite: 4, 5].
2. **Data Processing and Privacy Compliance:** This plugin processes records entirely within local databases on your server infrastructure[cite: 4, 5]. It does not execute remote telemetry callbacks or track external user behavior data, making it natively compliant with international privacy frameworks (GDPR and CCPA)[cite: 4, 5].
3. **Distribution Rules:** Redistribution or custom modifications of this code structure must preserve developer attribute markers pointing to Jayant Mallick as the initial framework architect[cite: 5].

## Installation Setup

1. Extract the asset package directory block into your `/wp-content/plugins/` environment folder[cite: 4].
2. Access the administrative controls menu area and trigger activation[cite: 4].
3. Embed your notification board anywhere on your pages using the primary tracking shortcode: `[wp_notifications]`[cite: 3, 4].
