# WP TAC Manager

WordPress plugin to integrate [Tarte au Citron](https://tarteaucitron.io/) with a complete administration panel for managing cookie services from the WordPress back office.

## Features

- ✅ Locally bundled tarteaucitron.js (v1.33.0) — no external CDN dependencies
- ✅ Automatic tarteaucitron.js updater from GitHub with one click
- ✅ Plugin updater from GitHub Releases using plugin-update-checker
- ✅ 28 predefined services: Google Tag Manager, GA4, Google Ads, Facebook Pixel, LinkedIn Insight Tag, Hotjar, Matomo Cloud, Plausible, Twitter UWT, HubSpot, YouTube, Vimeo, Google Maps, reCAPTCHA, Instagram, TikTok, Microsoft Clarity, Bing Ads, Crisp Chat, Google Fonts, Disqus, Pinterest, Stripe, PayPal, Spotify, SoundCloud, Dailymotion, Twitch
-✅ Modern admin panel with tab navigation (General, Services, Updates, Colors, Texts)
- ✅ Full color customization: background, text, button borders (Accept/Deny), panel, icon
- ✅ Custom texts by language using tarteaucitronCustomText (JSON format) — merged with the active translation
- ✅ Automatic banner translations based on the site language
- ✅ Consent statistics with dashboard widget
- ✅ Rate limiting on the consent endpoint
- ✅ Security: CSRF nonces, current_user_cap(), full sanitization, output escaping
- ✅ AJAX without page reload — saving with instant visual feedback
- ✅ Dynamic init JS generation — no problematic cached files
- ✅ Automatic language detection from WordPress settings
- ✅ GDPR best practices — highPrivacy=true, DenyAllCta=true, AcceptAllCta=true
- ✅ Cache integrations: exclusions for Autoptimize, WP Rocket, LiteSpeed Cache, W3 Total Cache
- ✅ Consent-based iframe lazy loading
- ✅ Multilingual support compatible with Bogo, Polylang, WPML (privacy policy URL is automatically retrieved from WordPress)
- ✅ JSON customization: any tarteaucitron.js option can be overridden (privacyUrl, texts, colors, etc.)

## Installation

1. Upload the `wp-tac-manager` folder to `/wp-content/plugins/`
2. Activate the plugin from **Plugins → Installed Plugins**
3. Go to **TAC Manager → Settings**

## Included services

| Service              | Category  | Parameters           |
| -------------------- | --------- | -------------------- |
| Google Tag Manager   | Analytics | Container ID         |
| Google Analytics 4   | Analytics | Measurement ID       |
| Google Ads           | Analytics | Conversion ID        |
| Microsoft Clarity    | Analytics | Project ID           |
| Bing Ads             | Analytics | UET Tag ID           |
| Hotjar               | Analytics | Site ID              |
| Matomo Cloud         | Analytics | Site ID + Server URL |
| Plausible            | Analytics | Domain               |
| Facebook Pixel       | Social    | Pixel ID             |
| LinkedIn Insight Tag | Social    | Partner ID           |
| Twitter UWT          | Social    | Pixel ID             |
| Instagram            | Social    | —                    |
| TikTok               | Social    | Pixel ID             |
| Pinterest            | Social    | —                    |
| YouTube              | Video     | —                    |
| Vimeo                | Video     | —                    |
| Dailymotion          | Video     | —                    |
| Twitch               | Video     | —                    |
| Google Maps          | API       | API Key              |
| reCAPTCHA            | API       | Site Key             |
| Stripe               | API       | —                    |
| PayPal               | API       | —                    |
| HubSpot              | API       | HubSpot ID           |
| Crisp Chat           | Other     | Website ID           |
| Google Fonts         | Other     | Font Families        |
| Disqus               | Comments  | Shortname            |
| Spotify              | Other     | —                    |
| SoundCloud           | Other     | —                    |


## Text Customization

Go to **TAC Manager → Settings → Texts** and select a language. Enter a JSON object with the keys you want to override:

{"acceptAll": "¡Let's go!", "denyAll": "No thanks"}

Values are merged with the active banner translation using `tarteaucitronCustomText`. Only the specified keys are overridden.

## Color customization

Go to **TAC Manager → Settings → Colors** to customize:
- Main banner background and text
- **Accept** button background, border, and text
- **Deny** button background, border, and text
- Preferences panel background and text
- Floating icon background

## Updates

- **tarteaucitron.js**: Go to **TAC Manager → Settings → Updates** and click "Check" to search for new versions. "Update now" downloads and installs the files automatically.
- **Plugin**: Updates are delivered through GitHub Releases. They appear automatically under **Plugins** when a new version is available.

## Implemented Security

| Measure                                                                   | Where                                 |
| ------------------------------------------------------------------------- | ------------------------------------- |
| `check_ajax_referer()` on all AJAX endpoints                              | `WPTAC_Admin`                         |
| `current_user_can('manage_options')`                                      | Admin menu + AJAX + render            |
| `sanitize_text_field()` / `esc_url_raw()` / `sanitize_key()` / `absint()` | `WPTAC_Settings::sanitize()`          |
| `wp_json_encode()` with `JSON_HEX_TAG \| JSON_HEX_APOS \| JSON_HEX_AMP`   | `WPTAC_Renderer`                      |
| Whitelist for orientations, languages, and positions                      | `WPTAC_Settings::sanitize()`          |
| Whitelist for known services                                              | `WPTAC_Settings::sanitize_services()` |
| Hex color validation                                                      | `WPTAC_Settings::sanitize_colors()`   |
| `esc_html()` / `esc_attr()` / `esc_url()` / `esc_textarea()` in all views | `settings-page.php`                   |
| Rate limiting (10 req/min per IP)                                         | `WPTAC_Admin::check_rate_limit()`     |
| `WP_UNINSTALL_PLUGIN` check                                               | `uninstall.php`                       |

## Plugin structure

wp-tac-manager/
├── wp-tac-manager.php              # Bootstrap, constants, autoloader, GitHub updater
├── uninstall.php                   # Database cleanup on uninstall
├── composer.json                   # Dependencies (plugin-update-checker)
├── includes/
│   ├── class-tac-admin.php         # Menu, admin assets, AJAX, statistics
│   ├── class-tac-renderer.php      # Front-end enqueue + init JS
│   ├── class-tac-services.php      # Catalog of 28 services
│   ├── class-tac-settings.php      # Defaults, sanitization, DB access
│   └── class-tac-updater.php       # tarteaucitron.js updater
├── admin/
│   ├── views/settings-page.php     # HTML template with 5 tabs
│   ├── js/admin.js                 # UX (tabs, toggles, AJAX, language selector)
│   └── css/admin.css               # Admin panel styles
├── lang/                           # Translation files (.pot, .po, .mo)
└── assets/
    ├── js/tarteaucitron/           # tarteaucitron.js library
    │   ├── tarteaucitron.js
    │   ├── tarteaucitron.min.js
    │   ├── tarteaucitron.services.js
    │   ├── tarteaucitron.services.min.js
    │   └── lang/                   # Banner translations (38 languages)
    └── css/
        ├── tarteaucitron.css
        └── tarteaucitron.min.css

## Changelog
### 2.0.0
- Version bump to 2.0.0.

### 1.9.0
- Added manual update option for tarteaucitron.js in admin panel.
- Changed automatic update to 'check for new version' only.

### 1.4.0
- Added 18 new services: YouTube, Vimeo, Google Maps, reCAPTCHA, Instagram, TikTok, Microsoft Clarity, Bing Ads, Crisp Chat, Google Fonts, Disqus, Pinterest, Stripe, PayPal, Spotify, SoundCloud, Dailymotion, Twitch
- Full color customization: button borders and button text
- Custom texts by language via JSON (tarteaucitronCustomText)
- Automatic plugin updater from GitHub Releases
- Improved tarteaucitron.js version detection (with CDN fallback)
- Removed mandatory privacy URL field (automatically retrieved from WordPress)
- English admin interface (translatable via Loco Translate)
- Various security and maintenance fixes

### 1.0.0

- Initial release with support for Google Tag Manager, GA4, Google Ads, Facebook Pixel, LinkedIn, Hotjar, Matomo Cloud, Plausible, Twitter UWT, HubSpot
- Admin panel with tabs
- Basic color customization
- Consent statistics
- tarteaucitron.js updater
- Exclusions for cache plugins
