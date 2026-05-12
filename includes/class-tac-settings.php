<?php
/**
 * WPTAC_Settings
 *
 * Centraliza los valores por defecto, la sanitización y el acceso
 * a la configuración almacenada en la base de datos.
 *
 * @package WP_TAC_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTAC_Settings {

    // ─────────────────────────────────────────────
    // Valores por defecto
    // ─────────────────────────────────────────────

    /**
     * Devuelve la configuración por defecto del plugin.
     * Se usa al activar el plugin y como fallback en get_settings().
     *
     * @return array<string, mixed>
     */
    public static function get_defaults(): array {
        return [
            // ── Configuración general de tarteaucitron ──
            'general' => [
                'privacy_url'              => '',       // URL a la política de privacidad
                'hashtag'                  => '#tarteaucitron', // Hashtag para reabrir el panel
                'cookie_name'              => 'tarteaucitron', // Nombre de la cookie
                'orientation'              => 'bottom', // Posición del banner: top | bottom | middle | popup
                'group_services'           => false,    // Agrupar servicios por categoría
                'show_alert_small'         => false,    // Mostrar botón pequeño siempre visible
                'cookie_accessible_ui'     => true,     // UI accesible para lectores de pantalla
                'remove_credit'            => false,    // Ocultar crédito "Manage Cookies"
                'handle_browser_dnt'       => false,    // Respetar Do Not Track del navegador
                'accept_all_cta'           => true,     // Mostrar botón "Aceptar todo"
                'deny_all_cta'             => true,     // Mostrar botón "Denegar todo"
                'enable_banner'            => true,     // Activar banner de cookies
                'disable_banner_loggedin'  => false,    // Desactivar banner para usuarios logueados
                'disable_google_consent_mode' => false, // Desactivar Google Consent Mode
                'show_details_on_click'    => true,
                'cookieslist_embed'        => false,
                'close_popup'              => true,
                'always_need_consent'      => false,
                'mandatory_cta'            => false,
                'bing_consent_mode'        => true,
                'piano_consent_mode'       => true,
                'piano_consent_mode_essential' => false,
                'soft_consent_mode'        => false,
                'data_layer'               => false,
                'server_side'              => false,
                'partners_list'            => true,
                'adblocker'                => false,
                'more_info_link'           => true,
                'mandatory'                => true,
                'show_icon'                => true,
                'reload_thx_seconds'       => 0,        // Segundos antes de recargar (0 = sin recarga)
                'language'                 => 'auto',   // Idioma: auto | es | en | fr
                'force_expiry_date'        => '',        // Fecha de expiración forzada (YYYY/MM/DD)
                'icon_position'            => 'BottomRight', // Posición del icono: BottomRight | BottomLeft | TopRight | TopLeft
                'custom_icon'              => 0,         // ID de attachment del icono personalizado
                'custom_css'               => '',        // CSS personalizado para tarteaucitron
            ],
            // ── Colores personalizados ──
            'colors' => [
                'alert_big_bg'     => 'ffffff',
                'alert_big_text'   => '000000',
                'btn_allow_bg'     => '1B870B',
                'btn_allow_border' => '1B870B',
                'btn_allow_text'   => 'ffffff',
                'btn_deny_bg'      => '9C1A1A',
                'btn_deny_border'  => '9C1A1A',
                'btn_deny_text'    => 'ffffff',
                'panel_bg'         => 'ffffff',
                'panel_text'       => '333333',
                'icon_bg'          => 'fbd600',
            ],
            // ── Textos personalizados (JSON por idioma) ──
            'texts' => [],
            // ── Servicios ──
            'services' => [
                'googletagmanager' => [
                    'enabled' => false,
                    'params'  => [
                        'id' => '',
                    ],
                ],
                'gtag' => [
                    'enabled' => false,
                    'params'  => [
                        'id' => '',
                    ],
                ],
                'googleads' => [
                    'enabled' => false,
                    'params'  => [
                        'id' => '',
                    ],
                ],
                'facebookpixel' => [
                    'enabled' => false,
                    'params'  => [
                        'id' => '',
                    ],
                ],
                'linkedininsighttag' => [
                    'enabled' => false,
                    'params'  => [
                        'id' => '',
                    ],
                ],
                'hotjar' => [
                    'enabled' => false,
                    'params'  => [
                        'id' => '',
                    ],
                ],
                'matomocloud' => [
                    'enabled' => false,
                    'params'  => [
                        'id' => '',
                        'host' => '',
                    ],
                ],
                'plausible' => [
                    'enabled' => false,
                    'params'  => [
                        'id' => '',
                    ],
                ],
                'twitteruwt' => [
                    'enabled' => false,
                    'params'  => [
                        'id' => '',
                    ],
                ],
                'hubspot' => [
                    'enabled' => false,
                    'params'  => [
                        'id' => '',
                    ],
                ],
            ],
        ];
    }

    // ─────────────────────────────────────────────
    // Acceso a la configuración
    // ─────────────────────────────────────────────

    /**
     * Devuelve la configuración completa fusionando los defaults
     * con lo que hay en la base de datos.
     *
     * @return array<string, mixed>
     */
    public static function get_settings(): array {
        $saved    = get_option( WPTAC_OPTION_KEY, [] );
        $defaults = self::get_defaults();

        // Fusión profunda para no perder nuevas claves de defaults
        return self::deep_merge( $defaults, is_array( $saved ) ? $saved : [] );
    }

    /**
     * Guarda la configuración sanitizada en la base de datos.
     *
     * @param  array<string, mixed> $raw_data Datos crudos del formulario.
     * @return bool                           True si se guardó correctamente.
     */
    public static function save_settings( array $raw_data ): bool {
        $sanitized = self::sanitize( $raw_data );
        return update_option( WPTAC_OPTION_KEY, $sanitized, false );
    }

    // ─────────────────────────────────────────────
    // Sanitización
    // ─────────────────────────────────────────────

    /**
     * Sanitiza recursivamente todos los datos del formulario.
     * NUNCA guardar $raw_data directamente.
     *
     * @param  array<string, mixed> $raw
     * @return array<string, mixed>
     */
    public static function sanitize( array $raw ): array {
        $existing = get_option( WPTAC_OPTION_KEY, [] );
        $settings = self::deep_merge( self::get_defaults(), is_array( $existing ) ? $existing : [] );

        // ── General ──
        if ( isset( $raw['general'] ) && is_array( $raw['general'] ) ) {
            $g = $raw['general'];

            $settings['general']['privacy_url']          = isset( $g['privacy_url'] )
                ? esc_url_raw( trim( $g['privacy_url'] ) )
                : '';

            $settings['general']['hashtag']              = isset( $g['hashtag'] )
                ? sanitize_text_field( $g['hashtag'] )
                : '#tarteaucitron';

            $settings['general']['cookie_name']          = isset( $g['cookie_name'] )
                ? sanitize_key( $g['cookie_name'] )
                : 'tarteaucitron';

            $allowed_orientations                        = [ 'bottom', 'top', 'middle', 'popup' ];
            $settings['general']['orientation']          = isset( $g['orientation'] )
                                                            && in_array( $g['orientation'], $allowed_orientations, true )
                ? $g['orientation']
                : 'bottom';

            $settings['general']['group_services']       = ! empty( $g['group_services'] );
            $settings['general']['show_alert_small']     = ! empty( $g['show_alert_small'] );
            $settings['general']['cookie_accessible_ui'] = ! empty( $g['cookie_accessible_ui'] );
            $settings['general']['remove_credit']        = ! empty( $g['remove_credit'] );
            $settings['general']['handle_browser_dnt']   = ! empty( $g['handle_browser_dnt'] );
            $settings['general']['accept_all_cta']           = ! empty( $g['accept_all_cta'] );
            $settings['general']['deny_all_cta']             = ! empty( $g['deny_all_cta'] );
            $settings['general']['enable_banner']            = ! empty( $g['enable_banner'] );
            $settings['general']['disable_banner_loggedin']  = ! empty( $g['disable_banner_loggedin'] );
            $settings['general']['disable_google_consent_mode'] = ! empty( $g['disable_google_consent_mode'] );
            $settings['general']['show_details_on_click']       = ! empty( $g['show_details_on_click'] );
            $settings['general']['cookieslist_embed']           = ! empty( $g['cookieslist_embed'] );
            $settings['general']['close_popup']                 = ! empty( $g['close_popup'] );
            $settings['general']['always_need_consent']         = ! empty( $g['always_need_consent'] );
            $settings['general']['mandatory_cta']               = ! empty( $g['mandatory_cta'] );
            $settings['general']['bing_consent_mode']           = ! empty( $g['bing_consent_mode'] );
            $settings['general']['piano_consent_mode']          = ! empty( $g['piano_consent_mode'] );
            $settings['general']['piano_consent_mode_essential'] = ! empty( $g['piano_consent_mode_essential'] );
            $settings['general']['soft_consent_mode']           = ! empty( $g['soft_consent_mode'] );
            $settings['general']['data_layer']                  = ! empty( $g['data_layer'] );
            $settings['general']['server_side']                 = ! empty( $g['server_side'] );
            $settings['general']['partners_list']               = ! empty( $g['partners_list'] );
            $settings['general']['adblocker']                   = ! empty( $g['adblocker'] );
            $settings['general']['more_info_link']              = ! empty( $g['more_info_link'] );
            $settings['general']['mandatory']                   = ! empty( $g['mandatory'] );
            $settings['general']['show_icon']                    = ! empty( $g['show_icon'] );

            $settings['general']['reload_thx_seconds']   = isset( $g['reload_thx_seconds'] )
                ? absint( $g['reload_thx_seconds'] )
                : 0;

            $allowed_langs                               = [ 'auto', 'es', 'en', 'fr', 'de', 'it', 'pt', 'nl' ];
            $settings['general']['language']             = isset( $g['language'] )
                                                            && in_array( $g['language'], $allowed_langs, true )
                ? $g['language']
                : 'auto';

            // Fecha: validar formato YYYY/MM/DD
            $force_date = isset( $g['force_expiry_date'] ) ? sanitize_text_field( $g['force_expiry_date'] ) : '';
            if ( $force_date && ! preg_match( '/^\d{4}\/\d{2}\/\d{2}$/', $force_date ) ) {
                $force_date = '';
            }
            $settings['general']['force_expiry_date'] = $force_date;

            $allowed_icon_positions                   = [ 'BottomRight', 'BottomLeft', 'TopRight', 'TopLeft' ];
            $settings['general']['icon_position']     = isset( $g['icon_position'] )
                                                        && in_array( $g['icon_position'], $allowed_icon_positions, true )
                ? $g['icon_position']
                : 'BottomRight';

            $settings['general']['custom_css'] = isset( $g['custom_css'] )
                ? wp_strip_all_tags( $g['custom_css'] )
                : '';

            $settings['general']['custom_icon'] = isset( $g['custom_icon'] )
                ? absint( $g['custom_icon'] )
                : 0;
        }

        // ── Colores ──
        if ( isset( $raw['colors'] ) && is_array( $raw['colors'] ) ) {
            $settings['colors'] = self::sanitize_colors( $raw['colors'] );
        }

        // ── Textos ──
        if ( isset( $raw['texts'] ) && is_array( $raw['texts'] ) ) {
            foreach ( $raw['texts'] as $lang => $json_str ) {
                $settings['texts'][ sanitize_key( $lang ) ] = sanitize_textarea_field( $json_str );
            }
        }

        // ── Servicios ──
        if ( isset( $raw['services'] ) && is_array( $raw['services'] ) ) {
            $settings['services'] = self::sanitize_services( $raw['services'] );
        }

        return $settings;
    }

    /**
     * Sanitiza la sección de servicios.
     * Solo se aceptan servicios conocidos (definidos en WPTAC_Services).
     *
     * @param  array<string, mixed> $raw_services
     * @return array<string, mixed>
     */
    private static function sanitize_services( array $raw_services ): array {
        $known_services = WPTAC_Services::get_definitions();
        $sanitized      = [];

        foreach ( $known_services as $service_key => $service_def ) {
            $raw_service = $raw_services[ $service_key ] ?? [];

            $sanitized[ $service_key ] = [
                'enabled' => ! empty( $raw_service['enabled'] ),
                'params'  => [],
            ];

            // Sanitizar cada parámetro del servicio según su tipo
            foreach ( $service_def['params'] as $param_key => $param_def ) {
                $raw_value = $raw_service['params'][ $param_key ] ?? '';

                $sanitized[ $service_key ]['params'][ $param_key ] = match ( $param_def['type'] ) {
                    'url'    => esc_url_raw( trim( $raw_value ) ),
                    'key'    => sanitize_key( $raw_value ),
                    'text'   => sanitize_text_field( $raw_value ),
                    default  => sanitize_text_field( $raw_value ),
                };
            }
        }

        return $sanitized;
    }

    /**
     * Sanitiza los valores de colores. Solo acepta colores predefinidos con valores hex válidos.
     *
     * @param  array<string, mixed> $raw_colors
     * @return array<string, mixed>
     */
    private static function sanitize_colors( array $raw_colors ): array {
        $defaults  = self::get_defaults();
        $known     = array_keys( $defaults['colors'] );
        $sanitized = [];

        foreach ( $known as $color_key ) {
            $raw = isset( $raw_colors[ $color_key ] ) ? sanitize_text_field( $raw_colors[ $color_key ] ) : '';
            $raw = ltrim( $raw, '#' );
            $raw = strtoupper( $raw );

            if ( preg_match( '/^[0-9A-F]{6}$/', $raw ) ) {
                $sanitized[ $color_key ] = $raw;
            } else {
                $sanitized[ $color_key ] = $defaults['colors'][ $color_key ];
            }
        }

        return $sanitized;
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    /**
     * Fusión profunda de arrays. Los valores de $override tienen prioridad.
     *
     * @param  array<string, mixed> $base
     * @param  array<string, mixed> $override
     * @return array<string, mixed>
     */
    public static function deep_merge( array $base, array $override ): array {
        foreach ( $override as $key => $value ) {
            if ( isset( $base[ $key ] ) && is_array( $base[ $key ] ) && is_array( $value ) ) {
                $base[ $key ] = self::deep_merge( $base[ $key ], $value );
            } else {
                $base[ $key ] = $value;
            }
        }
        return $base;
    }
}
