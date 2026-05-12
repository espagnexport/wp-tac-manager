<?php
/**
 * Vista: Página de ajustes del plugin WP TAC Manager
 *
 * Variables disponibles inyectadas desde WPTAC_Admin::render_settings_page():
 *   @var array<string, mixed>        $settings   Configuración completa actual.
 *   @var array<string, array<mixed>> $services   Catálogo de servicios disponibles.
 *
 * @package WP_TAC_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Helper: obtener valor de config con notación de puntos
$cfg = static function( string $path, mixed $default = '' ) use ( $settings ): mixed {
    $keys    = explode( '.', $path );
    $current = $settings;
    foreach ( $keys as $key ) {
        if ( ! is_array( $current ) || ! array_key_exists( $key, $current ) ) {
            return $default;
        }
        $current = $current[ $key ];
    }
    return $current;
};
?>
<div class="wrap wptac-wrap" id="wptac-settings-page">

    <div class="wptac-header">
        <div class="wptac-header__logo">
            <span class="wptac-header__icon">🍋</span>
            <div>
                <h1><?php esc_html_e( 'WP TAC Manager', 'wp-tac-manager' ); ?></h1>
                <p><?php esc_html_e( 'Cookie management with Tarte au Citron', 'wp-tac-manager' ); ?></p>
            </div>
        </div>
        <div class="wptac-header__meta">
            <span class="wptac-badge">
                <?php
                printf(
                    /* translators: número de versión */
                    esc_html__( 'tarteaucitron.js v%s', 'wp-tac-manager' ),
                    WPTAC_TARTEAUCITRON_VERSION
                );
                ?>
            </span>
        </div>
    </div>

    <?php /* Mensajes de estado (JS los muestra/oculta) */ ?>
    <div id="wptac-notice" class="wptac-notice" aria-live="polite" hidden></div>

    <form id="wptac-form" novalidate>
        <?php /* Nonce de seguridad oculto en el formulario (respaldo, el JS usa el de wp_localize_script) */ ?>
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'wptac_save_settings_nonce' ) ); ?>">

        <div class="wptac-layout">

            <?php /* ═══════════════════════════════════════════════
                   COLUMNA IZQUIERDA: Navegación entre secciones
                   ═══════════════════════════════════════════════ */ ?>
            <nav class="wptac-nav" aria-label="<?php esc_attr_e( 'Configuration sections', 'wp-tac-manager' ); ?>">
                <ul>
                    <li>
                        <a href="#section-general" class="wptac-nav__link is-active" data-section="general">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php esc_html_e( 'General settings', 'wp-tac-manager' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#section-services" class="wptac-nav__link" data-section="services">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <?php esc_html_e( 'Services', 'wp-tac-manager' ); ?>
                            <?php
                            // Contador de servicios activos
                            $active_count = count( WPTAC_Services::get_active_services( $settings ) );
                            if ( $active_count > 0 ) :
                            ?>
                                <span class="wptac-badge wptac-badge--count"><?php echo esc_html( $active_count ); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="#section-updates" class="wptac-nav__link" data-section="updates">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e( 'Updates', 'wp-tac-manager' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#section-colors" class="wptac-nav__link" data-section="colors">
                            <span class="dashicons dashicons-art"></span>
                            <?php esc_html_e( 'Colors', 'wp-tac-manager' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#section-texts" class="wptac-nav__link" data-section="texts">
                            <span class="dashicons dashicons-edit"></span>
                            <?php esc_html_e( 'Labels', 'wp-tac-manager' ); ?>
                        </a>
                    </li>
                </ul>
            </nav>

            <?php /* ═══════════════════════════════════════════════
                   COLUMNA DERECHA: Contenido de cada sección
                   ═══════════════════════════════════════════════ */ ?>
            <div class="wptac-sections">

                <?php /* ─────────────────────────────────────────
                        SECCIÓN: Configuración general
                        ───────────────────────────────────────── */ ?>
                <section id="section-general" class="wptac-section is-active" aria-labelledby="section-general-title">
                    <div class="wptac-section__header">
                        <h2 id="section-general-title"><?php esc_html_e( 'General settings', 'wp-tac-manager' ); ?></h2>
                        <p><?php esc_html_e( 'Global options for the tarteaucitron cookie banner.', 'wp-tac-manager' ); ?></p>
                    </div>

                    <div class="wptac-card">

                        <?php /* Posición del banner */ ?>
                        <div class="wptac-field">
                            <label class="wptac-field__label" for="general_orientation">
                                <?php esc_html_e( 'Banner position', 'wp-tac-manager' ); ?>
                            </label>
                            <select id="general_orientation" name="general[orientation]" class="wptac-field__select">
                                <?php
                                $orientations = [
                                    'bottom' => __( 'Bottom', 'wp-tac-manager' ),
                                    'top'    => __( 'Top', 'wp-tac-manager' ),
                                    'middle' => __( 'Center', 'wp-tac-manager' ),
                                    'popup'  => __( 'Centered modal (popup)', 'wp-tac-manager' ),
                                ];
                                foreach ( $orientations as $value => $label ) :
                                ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $cfg( 'general.orientation' ), $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php /* Posición del icono */ ?>
                        <div class="wptac-field">
                            <label class="wptac-field__label" for="general_icon_position">
                                <?php esc_html_e( 'Cookie icon position', 'wp-tac-manager' ); ?>
                            </label>
                            <select id="general_icon_position" name="general[icon_position]" class="wptac-field__select">
                                <?php
                                $icon_positions = [
                                    'BottomRight' => __( 'Bottom right', 'wp-tac-manager' ),
                                    'BottomLeft'  => __( 'Bottom left', 'wp-tac-manager' ),
                                    'TopRight'    => __( 'Top right', 'wp-tac-manager' ),
                                    'TopLeft'     => __( 'Top left', 'wp-tac-manager' ),
                                ];
                                foreach ( $icon_positions as $value => $label ) :
                                ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $cfg( 'general.icon_position', 'BottomRight' ), $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="wptac-field__desc">
                                <?php esc_html_e( 'Location of the tarteaucitron floating icon.', 'wp-tac-manager' ); ?>
                            </p>
                        </div>

                        <?php /* Icono personalizado */ ?>
                        <div class="wptac-field">
                            <label class="wptac-field__label">
                                <?php esc_html_e( 'Custom icon', 'wp-tac-manager' ); ?>
                            </label>
                            <div class="wptac-media" id="wptac-custom-icon">
                                <div class="wptac-media__preview" id="wptac-icon-preview"<?php echo $cfg( 'general.custom_icon' ) ? '' : ' hidden'; ?>>
                                    <?php if ( $cfg( 'general.custom_icon' ) ) : ?>
                                        <?php echo wp_get_attachment_image( $cfg( 'general.custom_icon' ), 'thumbnail', false, [ 'class' => 'wptac-media__image' ] ); ?>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="general_custom_icon" name="general[custom_icon]" value="<?php echo esc_attr( $cfg( 'general.custom_icon' ) ); ?>">
                                <div class="wptac-media__actions">
                                    <button type="button" class="wptac-btn wptac-btn--ghost wptac-btn--sm" id="wptac-icon-select">
                                        <span class="dashicons dashicons-upload"></span>
                                        <?php esc_html_e( 'Select image', 'wp-tac-manager' ); ?>
                                    </button>
                                    <button type="button" class="wptac-btn wptac-btn--ghost wptac-btn--sm" id="wptac-icon-remove"<?php echo $cfg( 'general.custom_icon' ) ? '' : ' hidden'; ?>>
                                        <span class="dashicons dashicons-no-alt"></span>
                                        <?php esc_html_e( 'Remove', 'wp-tac-manager' ); ?>
                                    </button>
                                </div>
                            </div>
                            <p class="wptac-field__desc">
                                <?php esc_html_e( 'Custom image for the floating cookie icon (recommended: 64×64 px).', 'wp-tac-manager' ); ?>
                            </p>
                        </div>

                        <?php /* Idioma */ ?>
                        <div class="wptac-field">
                            <label class="wptac-field__label" for="general_language">
                                <?php esc_html_e( 'Banner language', 'wp-tac-manager' ); ?>
                            </label>
                            <select id="general_language" name="general[language]" class="wptac-field__select">
                                <?php
                                $languages = [
                                    'auto' => __( 'Automatic (WordPress language)', 'wp-tac-manager' ),
                                    'es'   => 'Español',
                                    'en'   => 'English',
                                    'fr'   => 'Français',
                                    'de'   => 'Deutsch',
                                    'it'   => 'Italiano',
                                    'pt'   => 'Português',
                                    'nl'   => 'Nederlands',
                                ];
                                foreach ( $languages as $value => $label ) :
                                ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $cfg( 'general.language' ), $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php /* Nombre de la cookie */ ?>
                        <div class="wptac-field">
                            <label class="wptac-field__label" for="general_cookie_name">
                                <?php esc_html_e( 'Cookie name', 'wp-tac-manager' ); ?>
                            </label>
                            <input
                                type="text"
                                id="general_cookie_name"
                                name="general[cookie_name]"
                                class="wptac-field__input"
                                value="<?php echo esc_attr( $cfg( 'general.cookie_name', 'tarteaucitron' ) ); ?>"
                                pattern="[a-z0-9_-]+"
                                maxlength="64"
                            >
                            <p class="wptac-field__desc">
                                <?php esc_html_e( 'Name of the cookie that stores user preferences. Only lowercase letters, numbers, hyphens and underscores.', 'wp-tac-manager' ); ?>
                            </p>
                        </div>

                        <?php /* Hashtag para reabrir el panel */ ?>
                        <div class="wptac-field">
                            <label class="wptac-field__label" for="general_hashtag">
                                <?php esc_html_e( 'Hashtag to reopen the panel', 'wp-tac-manager' ); ?>
                            </label>
                            <input
                                type="text"
                                id="general_hashtag"
                                name="general[hashtag]"
                                class="wptac-field__input wptac-field__input--short"
                                value="<?php echo esc_attr( $cfg( 'general.hashtag', '#tarteaucitron' ) ); ?>"
                            >
                            <p class="wptac-field__desc">
                                <?php
                                printf(
                                    /* translators: ejemplo de uso del hashtag */
                                    esc_html__( 'Add %s to any link so users can reopen the cookie panel.', 'wp-tac-manager' ),
                                    '<code>#tarteaucitron</code>'
                                );
                                ?>
                            </p>
                        </div>

                        <?php /* Segundos antes de recargar */ ?>
                        <div class="wptac-field">
                            <label class="wptac-field__label" for="general_reload_thx_seconds">
                                <?php esc_html_e( 'Auto-reload after accepting (seconds)', 'wp-tac-manager' ); ?>
                            </label>
                            <input
                                type="number"
                                id="general_reload_thx_seconds"
                                name="general[reload_thx_seconds]"
                                class="wptac-field__input wptac-field__input--short"
                                value="<?php echo absint( $cfg( 'general.reload_thx_seconds', 0 ) ); ?>"
                                min="0"
                                max="60"
                            >
                            <p class="wptac-field__desc">
                                <?php esc_html_e( '0 = no auto-reload. Useful for loading blocked scripts after consent.', 'wp-tac-manager' ); ?>
                            </p>
                        </div>

                        <?php /* Fecha de expiración forzada */ ?>
                        <div class="wptac-field">
                            <label class="wptac-field__label" for="general_force_expiry_date">
                                <?php esc_html_e( 'Force new consent request from', 'wp-tac-manager' ); ?>
                            </label>
                            <input
                                type="text"
                                id="general_force_expiry_date"
                                name="general[force_expiry_date]"
                                class="wptac-field__input wptac-field__input--short"
                                value="<?php echo esc_attr( $cfg( 'general.force_expiry_date', '' ) ); ?>"
                                placeholder="2024/01/01"
                                pattern="\d{4}/\d{2}/\d{2}"
                            >
                            <p class="wptac-field__desc">
                                <?php esc_html_e( 'Format: YYYY/MM/DD. If the user\'s consent is older than this date, they will be asked again.', 'wp-tac-manager' ); ?>
                            </p>
                        </div>

                        <?php /* ── Toggles de opciones booleanas ── */ ?>
                        <div class="wptac-field wptac-field--toggles">
                            <p class="wptac-field__label"><?php esc_html_e( 'Behavior options', 'wp-tac-manager' ); ?></p>

                            <?php
                            $toggles = [
                                'group_services'       => [
                                    'label' => __( 'Group services by category', 'wp-tac-manager' ),
                                    'desc'  => __( 'Shows services grouped in the cookie panel.', 'wp-tac-manager' ),
                                ],
                                'show_alert_small'     => [
                                    'label' => __( 'Show always-visible small button', 'wp-tac-manager' ),
                                    'desc'  => __( 'Shows a small quick-access button to the cookie panel.', 'wp-tac-manager' ),
                                ],
                                'cookie_accessible_ui' => [
                                    'label' => __( 'Accessible interface (cookie list)', 'wp-tac-manager' ),
                                    'desc'  => __( 'Improves accessibility for screen readers.', 'wp-tac-manager' ),
                                ],
                                'enable_banner'          => [
                                    'label' => __( 'Enable cookie banner', 'wp-tac-manager' ),
                                    'desc'  => __( 'Shows the consent banner to visitors. If disabled, tarteaucitron is not loaded.', 'wp-tac-manager' ),
                                ],
                                'disable_banner_loggedin' => [
                                    'label' => __( 'Disable banner for logged-in users', 'wp-tac-manager' ),
                                    'desc'  => __( 'The banner is only shown to visitors who are not logged in.', 'wp-tac-manager' ),
                                ],
                                'handle_browser_dnt'   => [
                                    'label' => __( 'Respect browser Do Not Track signal', 'wp-tac-manager' ),
                                    'desc'  => __( 'If the user has DNT enabled, all services will be automatically denied.', 'wp-tac-manager' ),
                                ],
                                'accept_all_cta'       => [
                                    'label' => __( 'Show "Accept All" button', 'wp-tac-manager' ),
                                    'desc'  => __( 'Allows users to accept all services with one click.', 'wp-tac-manager' ),
                                ],
                                'deny_all_cta'         => [
                                    'label' => __( 'Show "Deny All" button', 'wp-tac-manager' ),
                                    'desc'  => __( 'Allows users to deny all non-essential services with one click.', 'wp-tac-manager' ),
                                ],
                                'show_details_on_click' => [
                                    'label' => __( 'Show details on click', 'wp-tac-manager' ),
                                    'desc'  => __( 'Click to expand the service description.', 'wp-tac-manager' ),
                                ],
                                'cookieslist_embed'    => [
                                    'label' => __( 'Cookie list in panel', 'wp-tac-manager' ),
                                    'desc'  => __( 'Show the cookie list inside the control panel.', 'wp-tac-manager' ),
                                ],
                                'close_popup'          => [
                                    'label' => __( 'Show close button on banner', 'wp-tac-manager' ),
                                    'desc'  => __( 'Display a close X on the cookie banner.', 'wp-tac-manager' ),
                                ],
                                'always_need_consent'  => [
                                    'label' => __( 'Always need consent', 'wp-tac-manager' ),
                                    'desc'  => __( 'Ask for consent even for privacy-by-design services.', 'wp-tac-manager' ),
                                ],
                                'mandatory_cta'        => [
                                    'label' => __( 'Mandatory cookies button', 'wp-tac-manager' ),
                                    'desc'  => __( 'Show a disabled accept button for mandatory cookies.', 'wp-tac-manager' ),
                                ],
                                'adblocker'            => [
                                    'label' => __( 'Adblocker warning', 'wp-tac-manager' ),
                                    'desc'  => __( 'Show a warning if an adblocker is detected.', 'wp-tac-manager' ),
                                ],
                                'more_info_link'       => [
                                    'label' => __( 'Show "More info" link', 'wp-tac-manager' ),
                                    'desc'  => __( 'Display the more info link on the banner.', 'wp-tac-manager' ),
                                ],
                                'mandatory'            => [
                                    'label' => __( 'Show mandatory cookies message', 'wp-tac-manager' ),
                                    'desc'  => __( 'Display information about mandatory cookies.', 'wp-tac-manager' ),
                                ],
                                'bing_consent_mode'    => [
                                    'label' => __( 'Bing Consent Mode', 'wp-tac-manager' ),
                                    'desc'  => __( 'Enable consent mode for Clarity & Bing Ads.', 'wp-tac-manager' ),
                                ],
                                'piano_consent_mode'   => [
                                    'label' => __( 'Piano Consent Mode', 'wp-tac-manager' ),
                                    'desc'  => __( 'Enable consent mode for Piano Analytics.', 'wp-tac-manager' ),
                                ],
                                'piano_consent_mode_essential' => [
                                    'label' => __( 'Piano Essential Mode', 'wp-tac-manager' ),
                                    'desc'  => __( 'Load Piano Analytics in essential mode by default.', 'wp-tac-manager' ),
                                ],
                                'soft_consent_mode'    => [
                                    'label' => __( 'Soft consent mode', 'wp-tac-manager' ),
                                    'desc'  => __( 'Consent is required before loading services.', 'wp-tac-manager' ),
                                ],
                                'data_layer'           => [
                                    'label' => __( 'DataLayer events', 'wp-tac-manager' ),
                                    'desc'  => __( 'Send events to dataLayer with service status.', 'wp-tac-manager' ),
                                ],
                                'server_side'          => [
                                    'label' => __( 'Server-side only', 'wp-tac-manager' ),
                                    'desc'  => __( 'Tags are not loaded client-side, only server-side.', 'wp-tac-manager' ),
                                ],
                                'partners_list'        => [
                                    'label' => __( 'Show partners list', 'wp-tac-manager' ),
                                    'desc'  => __( 'Display the number of partners on the banner.', 'wp-tac-manager' ),
                                ],
                                'show_icon'            => [
                                    'label' => __( 'Show floating cookie icon', 'wp-tac-manager' ),
                                    'desc'  => __( 'Display the floating cookie icon to reopen the panel.', 'wp-tac-manager' ),
                                ],
                                'disable_google_consent_mode' => [
                                    'label' => __( 'Disable Google Consent Mode', 'wp-tac-manager' ),
                                    'desc'  => __( 'Disables tarteaucitron\'s Google Consent Mode v2. Enable this if you use Google Consent Mode from another plugin.', 'wp-tac-manager' ),
                                ],
                                'remove_credit'        => [
                                    'label' => __( 'Hide "Manage Cookies" credit', 'wp-tac-manager' ),
                                    'desc'  => __( 'Hides the tarteaucitron credit link in the banner.', 'wp-tac-manager' ),
                                ],
                            ];
                            foreach ( $toggles as $toggle_key => $toggle_data ) :
                                $field_id  = 'general_' . $toggle_key;
                                $is_checked = (bool) $cfg( 'general.' . $toggle_key );
                            ?>
                            <label class="wptac-toggle" for="<?php echo esc_attr( $field_id ); ?>">
                                <div class="wptac-toggle__switch">
                                    <input
                                        type="checkbox"
                                        id="<?php echo esc_attr( $field_id ); ?>"
                                        name="general[<?php echo esc_attr( $toggle_key ); ?>]"
                                        value="1"
                                        <?php checked( $is_checked ); ?>
                                        role="switch"
                                        aria-checked="<?php echo $is_checked ? 'true' : 'false'; ?>"
                                    >
                                    <span class="wptac-toggle__slider" aria-hidden="true"></span>
                                </div>
                                <div class="wptac-toggle__text">
                                    <span class="wptac-toggle__label"><?php echo esc_html( $toggle_data['label'] ); ?></span>
                                    <span class="wptac-toggle__desc"><?php echo esc_html( $toggle_data['desc'] ); ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <?php /* CSS personalizado */ ?>
                        <div class="wptac-field">
                            <label class="wptac-field__label" for="general_custom_css">
                                <?php esc_html_e( 'Custom CSS', 'wp-tac-manager' ); ?>
                            </label>
                            <textarea
                                id="general_custom_css"
                                name="general[custom_css]"
                                class="wptac-field__textarea"
                                rows="6"
                                placeholder=".tarteaucitronAlertSmall { background: #333; }"
                            ><?php echo esc_textarea( $cfg( 'general.custom_css', '' ) ); ?></textarea>
                            <p class="wptac-field__desc">
                                <?php esc_html_e( 'Additional CSS to customize the cookie banner appearance. No HTML tags allowed.', 'wp-tac-manager' ); ?>
                            </p>
                        </div>

                    </div><!-- /.wptac-card -->
                </section>

                <?php /* ─────────────────────────────────────────
                        SECCIÓN: Servicios
                        ───────────────────────────────────────── */ ?>
                <section id="section-services" class="wptac-section" aria-labelledby="section-services-title">
                    <div class="wptac-section__header">
                        <h2 id="section-services-title"><?php esc_html_e( 'Cookie services', 'wp-tac-manager' ); ?></h2>
                        <p><?php esc_html_e( 'Enable and configure each service. Only enabled and properly configured services will be loaded.', 'wp-tac-manager' ); ?></p>
                    </div>

                    <?php foreach ( $services as $service_key => $service_def ) :
                        $is_enabled    = (bool) $cfg( 'services.' . $service_key . '.enabled', false );
                        $service_params = (array) $cfg( 'services.' . $service_key . '.params', [] );
                    ?>
                    <div class="wptac-card wptac-service <?php echo $is_enabled ? 'is-active' : ''; ?>" id="service-<?php echo esc_attr( $service_key ); ?>">

                        <div class="wptac-service__header">
                            <div class="wptac-service__info">
                                <span class="dashicons <?php echo esc_attr( $service_def['icon'] ?? 'dashicons-admin-generic' ); ?> wptac-service__icon" aria-hidden="true"></span>
                                <div>
                                    <h3 class="wptac-service__name"><?php echo esc_html( $service_def['label'] ); ?></h3>
                                    <span class="wptac-badge wptac-badge--category"><?php echo esc_html( $service_def['category'] ); ?></span>
                                </div>
                            </div>
                            <div class="wptac-service__controls">
                                <a
                                    href="<?php echo esc_url( $service_def['doc_url'] ?? '#' ); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="wptac-btn wptac-btn--ghost wptac-btn--sm"
                                    title="<?php esc_attr_e( 'View documentation', 'wp-tac-manager' ); ?>"
                                >
                                    <span class="dashicons dashicons-external"></span>
                                    <?php esc_html_e( 'Docs', 'wp-tac-manager' ); ?>
                                </a>
                                <label class="wptac-toggle wptac-toggle--inline" for="service_<?php echo esc_attr( $service_key ); ?>_enabled">
                                    <div class="wptac-toggle__switch">
                                        <input
                                            type="checkbox"
                                            id="service_<?php echo esc_attr( $service_key ); ?>_enabled"
                                            name="services[<?php echo esc_attr( $service_key ); ?>][enabled]"
                                            value="1"
                                            class="wptac-service__toggle"
                                            data-service="<?php echo esc_attr( $service_key ); ?>"
                                            <?php checked( $is_enabled ); ?>
                                            role="switch"
                                            aria-expanded="<?php echo $is_enabled ? 'true' : 'false'; ?>"
                                            aria-controls="service-<?php echo esc_attr( $service_key ); ?>-params"
                                        >
                                        <span class="wptac-toggle__slider" aria-hidden="true"></span>
                                    </div>
                                    <span class="screen-reader-text">
                                        <?php
                                        printf(
                                            /* translators: nombre del servicio */
                                            esc_html__( 'Enable %s', 'wp-tac-manager' ),
                                            esc_html( $service_def['label'] )
                                        );
                                        ?>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <?php if ( ! empty( $service_def['description'] ) ) : ?>
                        <p class="wptac-service__desc"><?php echo esc_html( $service_def['description'] ); ?></p>
                        <?php endif; ?>

                        <?php /* Parámetros del servicio (se muestran/ocultan según el toggle) */ ?>
                        <div
                            id="service-<?php echo esc_attr( $service_key ); ?>-params"
                            class="wptac-service__params <?php echo $is_enabled ? '' : 'is-hidden'; ?>"
                            aria-hidden="<?php echo $is_enabled ? 'false' : 'true'; ?>"
                        >
                            <?php foreach ( $service_def['params'] as $param_key => $param_def ) :
                                $field_id    = 'service_' . $service_key . '_' . $param_key;
                                $field_name  = 'services[' . $service_key . '][params][' . $param_key . ']';
                                $field_value = $service_params[ $param_key ] ?? '';
                            ?>
                            <div class="wptac-field">
                                <label class="wptac-field__label" for="<?php echo esc_attr( $field_id ); ?>">
                                    <?php echo esc_html( $param_def['label'] ); ?>
                                    <?php if ( ! empty( $param_def['required'] ) ) : ?>
                                        <span class="wptac-field__required" aria-label="<?php esc_attr_e( 'required', 'wp-tac-manager' ); ?>">*</span>
                                    <?php endif; ?>
                                </label>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr( $field_id ); ?>"
                                    name="<?php echo esc_attr( $field_name ); ?>"
                                    class="wptac-field__input"
                                    value="<?php echo esc_attr( $field_value ); ?>"
                                    placeholder="<?php echo esc_attr( $param_def['placeholder'] ?? '' ); ?>"
                                    <?php echo ! empty( $param_def['required'] ) && $is_enabled ? 'required' : ''; ?>
                                    <?php echo ! empty( $param_def['pattern'] ) ? 'pattern="' . esc_attr( $param_def['pattern'] ) . '"' : ''; ?>
                                >
                                <?php if ( ! empty( $param_def['description'] ) ) : ?>
                                <p class="wptac-field__desc"><?php echo esc_html( $param_def['description'] ); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div><!-- /.wptac-service__params -->

                    </div><!-- /.wptac-service -->
                    <?php endforeach; ?>

                </section><!-- /#section-services -->

                <?php /* ─────────────────────────────────────────
                        SECCIÓN: Actualizaciones
                        ───────────────────────────────────────── */ ?>
                <section id="section-updates" class="wptac-section" aria-labelledby="section-updates-title">
                    <div class="wptac-section__header">
                        <h2 id="section-updates-title"><?php esc_html_e( 'tarteaucitron.js Updates', 'wp-tac-manager' ); ?></h2>
                        <p><?php esc_html_e( 'Check if a new version of tarteaucitron.js is available and update it with one click.', 'wp-tac-manager' ); ?></p>
                    </div>

                    <div class="wptac-card">
                        <div class="wptac-field">
                            <label class="wptac-field__label"><?php esc_html_e( 'Installed version', 'wp-tac-manager' ); ?></label>
                            <p class="wptac-update__version" id="wptac-bundled-version">
                                <strong><?php echo esc_html( WPTAC_TARTEAUCITRON_VERSION ); ?></strong>
                            </p>
                        </div>

                        <div class="wptac-field">
                            <label class="wptac-field__label"><?php esc_html_e( 'Latest available version', 'wp-tac-manager' ); ?></label>
                            <p class="wptac-update__version" id="wptac-latest-version">
                                <span class="wptac-update__unknown"><?php esc_html_e( 'Click "Check" to find out.', 'wp-tac-manager' ); ?></span>
                            </p>
                        </div>

                        <div id="wptac-update-status" class="wptac-update__status" hidden></div>

                        <div class="wptac-update__actions">
                            <button type="button" class="wptac-btn wptac-btn--ghost" id="wptac-btn-check">
                                <span class="dashicons dashicons-update"></span>
                                <?php esc_html_e( 'Check for updates', 'wp-tac-manager' ); ?>
                            </button>
                            <button type="button" class="wptac-btn wptac-btn--primary" id="wptac-btn-update" hidden>
                                <span class="dashicons dashicons-download"></span>
                                <?php esc_html_e( 'Update now', 'wp-tac-manager' ); ?>
                            </button>
                        </div>
                    </div>
                </section><!-- /#section-updates -->

                <?php /* ─────────────────────────────────────────
                        SECCIÓN: Colores
                        ───────────────────────────────────────── */
                $color_defaults = WPTAC_Settings::get_defaults()['colors'];
                $color_fields = [
                    'alert_big_bg'   => [ 'label' => __( 'Main banner background', 'wp-tac-manager' ), 'desc' => __( 'Background color of the main cookie banner.', 'wp-tac-manager' ) ],
                    'alert_big_text' => [ 'label' => __( 'Main banner text', 'wp-tac-manager' ), 'desc' => __( 'Text color in the main banner.', 'wp-tac-manager' ) ],
                    'btn_allow_bg'   => [ 'label' => __( '"Accept" button background', 'wp-tac-manager' ), 'desc' => __( 'Background color of the accept button.', 'wp-tac-manager' ) ],
                    'btn_allow_border' => [ 'label' => __( '"Accept" button border', 'wp-tac-manager' ), 'desc' => __( 'Border color of the accept button.', 'wp-tac-manager' ) ],
                    'btn_allow_text'   => [ 'label' => __( '"Accept" button text', 'wp-tac-manager' ), 'desc' => __( 'Text color of the accept button.', 'wp-tac-manager' ) ],
                    'btn_deny_bg'    => [ 'label' => __( '"Deny" button background', 'wp-tac-manager' ), 'desc' => __( 'Background color of the deny button.', 'wp-tac-manager' ) ],
                    'btn_deny_border'  => [ 'label' => __( '"Deny" button border', 'wp-tac-manager' ), 'desc' => __( 'Border color of the deny button.', 'wp-tac-manager' ) ],
                    'btn_deny_text'    => [ 'label' => __( '"Deny" button text', 'wp-tac-manager' ), 'desc' => __( 'Text color of the deny button.', 'wp-tac-manager' ) ],
                    'panel_bg'       => [ 'label' => __( 'Panel background', 'wp-tac-manager' ), 'desc' => __( 'Background color of the preferences panel.', 'wp-tac-manager' ) ],
                    'panel_text'     => [ 'label' => __( 'Panel text', 'wp-tac-manager' ), 'desc' => __( 'Text color in the preferences panel.', 'wp-tac-manager' ) ],
                    'icon_bg'        => [ 'label' => __( 'Icon background', 'wp-tac-manager' ), 'desc' => __( 'Background color of the floating cookie icon.', 'wp-tac-manager' ) ],
                ];
                ?>
                <section id="section-colors" class="wptac-section" aria-labelledby="section-colors-title">
                    <div class="wptac-section__header">
                        <h2 id="section-colors-title"><?php esc_html_e( 'tarteaucitron colors', 'wp-tac-manager' ); ?></h2>
                        <p><?php esc_html_e( 'Customize the cookie banner colors. Each field includes a color picker.', 'wp-tac-manager' ); ?></p>
                    </div>
                    <div class="wptac-card">
                        <?php foreach ( $color_fields as $color_key => $color_data ) :
                            $current_value = $cfg( 'colors.' . $color_key, $color_defaults[ $color_key ] );
                        ?>
                        <div class="wptac-field wptac-field--color">
                            <label class="wptac-field__label" for="colors_<?php echo esc_attr( $color_key ); ?>">
                                <?php echo esc_html( $color_data['label'] ); ?>
                            </label>
                            <div class="wptac-color-picker-wrap">
                                <input
                                    type="text"
                                    id="colors_<?php echo esc_attr( $color_key ); ?>"
                                    name="colors[<?php echo esc_attr( $color_key ); ?>]"
                                    class="wptac-color-picker"
                                    value="#<?php echo esc_attr( $current_value ); ?>"
                                    data-default="#<?php echo esc_attr( $color_defaults[ $color_key ] ); ?>"
                                    maxlength="7"
                                >
                                <span class="wptac-color-default">
                                    <?php
                                    printf(
                                        esc_html__( 'Default: %s', 'wp-tac-manager' ),
                                        '<code>#' . esc_html( $color_defaults[ $color_key ] ) . '</code>'
                                    );
                                    ?>
                                </span>
                            </div>
                            <p class="wptac-field__desc"><?php echo esc_html( $color_data['desc'] ); ?></p>
                        </div>
                        <?php endforeach; ?>
                        <div class="wptac-field">
                            <button type="button" class="wptac-btn wptac-btn--ghost" id="wptac-reset-colors">
                                <?php esc_html_e( 'Reset to defaults', 'wp-tac-manager' ); ?>
                            </button>
                        </div>

                    </div>
                </section><!-- /#section-colors -->

                <?php /* ─────────────────────────────────────────
                        SECCIÓN: Textos
                        ───────────────────────────────────────── */ ?>
                <section id="section-texts" class="wptac-section" aria-labelledby="section-texts-title">
                    <div class="wptac-section__header">
                        <h2 id="section-texts-title"><?php esc_html_e( 'Custom texts', 'wp-tac-manager' ); ?></h2>
                        <p><?php esc_html_e( 'Override translation strings per language with JSON. Select a language and enter your custom texts.', 'wp-tac-manager' ); ?></p>
                    </div>

                    <?php
                    $langs = [];
                    $lang_files = glob( WPTAC_PLUGIN_DIR . 'assets/js/tarteaucitron/lang/tarteaucitron.*.js' );
                    foreach ( $lang_files as $file ) {
                        if ( preg_match( '/tarteaucitron\.(\w+)\.js$/', $file, $m )
                            && ! str_ends_with( $m[1], '.min' ) && $m[1] !== 'min' ) {
                            $langs[] = $m[1];
                        }
                    }
                    sort( $langs );
                    $saved_texts = $settings['texts'] ?? [];
                    ?>

                    <div class="wptac-card">
                        <div class="wptac-field">
                            <label class="wptac-field__label" for="wptac-text-lang"><?php esc_html_e( 'Language', 'wp-tac-manager' ); ?></label>
                            <select id="wptac-text-lang" class="wptac-field__select" style="max-width:200px;">
                                <option value="">— <?php esc_html_e( 'Select', 'wp-tac-manager' ); ?> —</option>
                                <?php foreach ( $langs as $code ) : ?>
                                <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $code ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="wptac-text-fields">
                            <?php foreach ( $langs as $code ) : ?>
                            <div class="wptac-lang-fields" data-lang="<?php echo esc_attr( $code ); ?>" hidden>
                                <label class="wptac-field__label" for="texts_<?php echo esc_attr( $code ); ?>">
                                    <?php printf( esc_html__( 'Custom JSON for %s', 'wp-tac-manager' ), esc_html( $code ) ); ?>
                                </label>
                                <textarea
                                    id="texts_<?php echo esc_attr( $code ); ?>"
                                    name="texts[<?php echo esc_attr( $code ); ?>]"
                                    class="wptac-field__textarea"
                                    rows="8"
                                    placeholder='<?php echo esc_attr( '{"acceptAll":"Let\'s go!","close":"Done"}' ); ?>'
                                ><?php echo esc_textarea( $saved_texts[ $code ] ?? '' ); ?></textarea>
                                <p class="wptac-field__desc">
                                    <?php esc_html_e( 'Enter a JSON object. These values merge with the default translation for this language.', 'wp-tac-manager' ); ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section><!-- /#section-texts -->

            </div><!-- /.wptac-sections -->
        </div><!-- /.wptac-layout -->

        <?php /* ── Barra de acciones (sticky) ── */ ?>
        <div class="wptac-actions">
            <div class="wptac-actions__inner">
                <button type="submit" class="wptac-btn wptac-btn--primary" id="wptac-save-btn">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e( 'Save changes', 'wp-tac-manager' ); ?>
                </button>
                <span id="wptac-save-status" class="wptac-save-status" aria-live="polite"></span>
            </div>
        </div>

    </form><!-- /#wptac-form -->

</div><!-- /.wrap.wptac-wrap -->
