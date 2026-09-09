=== WP TAC Manager ===
Contributors: espagnexport
Tags: cookies, gdpr, tarteaucitron, cookie-consent, privacy
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 2.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate Tarte au Citron (tarteaucitron.js) with a complete admin panel for managing cookie services from WordPress.

== Description ==

WP TAC Manager bundles [Tarte au Citron](https://tarteaucitron.io/) locally (no CDN dependency) and adds a modern admin panel to configure the cookie banner, services, colors, and texts.

Features:

* 26 predefined services: Google Tag Manager, GA4, Google Ads, Facebook Pixel, LinkedIn Insight Tag, Hotjar, Matomo Cloud, Plausible, Twitter UWT, HubSpot, YouTube, Vimeo, Google Maps, reCAPTCHA, Instagram, TikTok, Microsoft Clarity, Bing Ads, Crisp Chat, Google Fonts, Disqus, Pinterest, Spotify, SoundCloud, Dailymotion, Twitch
* Modern admin panel with tabs (General, Services, Updates, Colors, Texts)
* Full color customization (background, text, button borders and text, panel, icon)
* Custom banner texts per language via JSON (`tarteaucitronCustomText`)
* Automatic banner translations based on the site language
* Consent statistics with a dashboard widget
* One-click tarteaucitron.js updater from GitHub
* GDPR best practices (highPrivacy, DenyAllCta, AcceptAllCta)
* Consent-based iframe lazy loading
* Cache plugin exclusions (Autoptimize, WP Rocket, LiteSpeed Cache, W3 Total Cache)
* Multilingual compatible (Bogo, Polylang, WPML)
* Compatible with the WP Consent API

== Installation ==

1. Upload the `wp-tac-manager` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen.
3. Go to **TAC Manager → Settings** to configure it.

== Frequently Asked Questions ==

= Does this plugin load tarteaucitron.js from a CDN? =

No. tarteaucitron.js is bundled locally, so there is no external dependency.

= How do I customize the banner texts? =

Go to **TAC Manager → Settings → Texts**, select a language, and enter a JSON object with the keys you want to override. Only the specified keys are overridden.

= How do I update tarteaucitron.js? =

Go to **TAC Manager → Settings → Updates** and click "Check for updates". If a newer version is available, upload the official ZIP file or use the automatic updater.

== Screenshots ==

1. General settings tab.
2. Services tab with the 26-service catalog.
3. Updates tab.
4. Colors and texts customization.

== Changelog ==

= 2.2.0 =
* Made the tarteaucitron.js auto-updater atomic: files are downloaded to a temporary location and only moved into place after every download succeeds, preventing mixed-version bundles on partial failures.
* Added strict version validation and detailed error reporting to the update checker.
* Added ZIP archive path validation (zip-slip defense) to the manual upload.
* Hardened the admin update UI against HTML injection in version strings.
* Added filemtime-based cache busting for admin and front-end assets.
* Added separate nonces for the save, check, update, and manual-upload actions.
* Cleaned up updater options and transient on uninstall.
* Fixed dynamic discovery of tarteaucitron.js language files.

= 2.1.1 =
* Fixed manual update extraction (filesystem init + temporary directory creation).
* Fixed plugin update checker autoload.

= 2.1.0 =
* Fixed manual tarteaucitron.js update (nested form caused the upload button to submit the settings form).

= 2.0.0 =
* Version bump to 2.0.0.

= 1.9.0 =
* Added manual update option for tarteaucitron.js in admin panel.
* Changed automatic update to check-for-new-version only.

= 1.4.0 =
* Added 18 new services.
* Full color customization (button borders and button text).
* Custom texts by language via JSON.
* Automatic plugin updater from GitHub Releases.
* Various security and maintenance fixes.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 2.2.0 =
* Security and reliability release. No breaking changes.

= 2.1.1 =
* Bug fix release. No breaking changes.

= 2.1.0 =
* Bug fix release. No breaking changes.

= 2.0.0 =
* Maintenance release. No breaking changes.
