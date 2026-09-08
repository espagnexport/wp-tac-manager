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
                        'sv' => '6',
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

    /**
     * Devuelve la lista de idiomas disponibles para los textos personalizados,
     * descubierta a partir de los archivos de idioma incluidos con tarteaucitron.js.
     *
     * @return string[] Lista ordenada de códigos de idioma (p. ej. 'en', 'es').
     */
    public static function get_available_languages(): array {
        static $cache = null;

        if ( null !== $cache ) {
            return $cache;
        }

        $cache = [];
        $files = glob( WPTAC_PLUGIN_DIR . 'assets/js/tarteaucitron/lang/tarteaucitron.*.js' );

        if ( ! is_array( $files ) ) {
            return $cache;
        }

        foreach ( $files as $file ) {
            if ( preg_match( '~tarteaucitron\.([a-z]+)\.js$~', (string) $file, $m ) && 'min' !== $m[1] ) {
                $cache[] = $m[1];
            }
        }

        $cache = array_values( array_unique( $cache ) );
        sort( $cache );

        return $cache;
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
                ? esc_url_raw( trim( self::str( $g['privacy_url'] ) ) )
                : '';

            $settings['general']['hashtag']              = isset( $g['hashtag'] )
                ? sanitize_text_field( self::str( $g['hashtag'] ) )
                : '#tarteaucitron';

            $settings['general']['cookie_name']          = isset( $g['cookie_name'] )
                ? sanitize_key( self::str( $g['cookie_name'] ) )
                : 'tarteaucitron';

            $allowed_orientations                        = [ 'bottom', 'top', 'middle', 'popup' ];
            $settings['general']['orientation']          = isset( $g['orientation'] )
                                                            && in_array( $g['orientation'], $allowed_orientations, true )
                ? $g['orientation']
                : 'bottom';

            $settings['general']['group_services']       = wp_validate_boolean( $g['group_services'] ?? false );
            $settings['general']['show_alert_small']     = wp_validate_boolean( $g['show_alert_small'] ?? false );
            $settings['general']['cookie_accessible_ui'] = wp_validate_boolean( $g['cookie_accessible_ui'] ?? false );
            $settings['general']['remove_credit']        = wp_validate_boolean( $g['remove_credit'] ?? false );
            $settings['general']['handle_browser_dnt']   = wp_validate_boolean( $g['handle_browser_dnt'] ?? false );
            $settings['general']['accept_all_cta']           = wp_validate_boolean( $g['accept_all_cta'] ?? false );
            $settings['general']['deny_all_cta']             = wp_validate_boolean( $g['deny_all_cta'] ?? false );
            $settings['general']['enable_banner']            = wp_validate_boolean( $g['enable_banner'] ?? false );
            $settings['general']['disable_banner_loggedin']  = wp_validate_boolean( $g['disable_banner_loggedin'] ?? false );
            $settings['general']['disable_google_consent_mode'] = wp_validate_boolean( $g['disable_google_consent_mode'] ?? false );
            $settings['general']['show_details_on_click']       = wp_validate_boolean( $g['show_details_on_click'] ?? false );
            $settings['general']['cookieslist_embed']           = wp_validate_boolean( $g['cookieslist_embed'] ?? false );
            $settings['general']['close_popup']                 = wp_validate_boolean( $g['close_popup'] ?? false );
            $settings['general']['always_need_consent']         = wp_validate_boolean( $g['always_need_consent'] ?? false );
            $settings['general']['mandatory_cta']               = wp_validate_boolean( $g['mandatory_cta'] ?? false );
            $settings['general']['bing_consent_mode']           = wp_validate_boolean( $g['bing_consent_mode'] ?? false );
            $settings['general']['piano_consent_mode']          = wp_validate_boolean( $g['piano_consent_mode'] ?? false );
            $settings['general']['piano_consent_mode_essential'] = wp_validate_boolean( $g['piano_consent_mode_essential'] ?? false );
            $settings['general']['soft_consent_mode']           = wp_validate_boolean( $g['soft_consent_mode'] ?? false );
            $settings['general']['data_layer']                  = wp_validate_boolean( $g['data_layer'] ?? false );
            $settings['general']['server_side']                 = wp_validate_boolean( $g['server_side'] ?? false );
            $settings['general']['partners_list']               = wp_validate_boolean( $g['partners_list'] ?? false );
            $settings['general']['adblocker']                   = wp_validate_boolean( $g['adblocker'] ?? false );
            $settings['general']['more_info_link']              = wp_validate_boolean( $g['more_info_link'] ?? false );
            $settings['general']['mandatory']                   = wp_validate_boolean( $g['mandatory'] ?? false );
            $settings['general']['show_icon']                    = wp_validate_boolean( $g['show_icon'] ?? false );

            $settings['general']['reload_thx_seconds']   = isset( $g['reload_thx_seconds'] )
                ? absint( $g['reload_thx_seconds'] )
                : 0;

            $allowed_langs                               = [ 'auto', 'es', 'en', 'fr', 'de', 'it', 'pt', 'nl' ];
            $settings['general']['language']             = isset( $g['language'] )
                                                            && in_array( $g['language'], $allowed_langs, true )
                ? $g['language']
                : 'auto';

            // Fecha: validar formato YYYY/MM/DD
            $force_date = isset( $g['force_expiry_date'] ) ? sanitize_text_field( self::str( $g['force_expiry_date'] ) ) : '';
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
                ? wp_strip_all_tags( self::str( $g['custom_css'] ) )
                : '';

            $custom_icon = isset( $g['custom_icon'] ) ? absint( $g['custom_icon'] ) : 0;
            if ( $custom_icon > 0 && ! wp_attachment_is_image( $custom_icon ) ) {
                $custom_icon = 0;
            }
            $settings['general']['custom_icon'] = $custom_icon;
        }

        // ── Colores ──
        if ( isset( $raw['colors'] ) && is_array( $raw['colors'] ) ) {
            $settings['colors'] = self::sanitize_colors( $raw['colors'] );
        }

        // ── Textos ──
        if ( isset( $raw['texts'] ) && is_array( $raw['texts'] ) ) {
            $settings['texts'] = self::sanitize_texts( $raw['texts'] );
        }

        // ── Servicios ──
        if ( isset( $raw['services'] ) && is_array( $raw['services'] ) ) {
            $settings['services'] = self::sanitize_services( $raw['services'] );
        }

        return $settings;
    }

    /**
     * Sanitiza los textos personalizados por idioma (JSON).
     *
     * Descarta idiomas desconocidos y JSON inválido, y normaliza cada valor
     * según su tipo (solo se aceptan cadenas y números escalares).
     *
     * @param  array<string, mixed> $raw_texts Datos crudos indexados por código de idioma.
     * @return array<string, string> Textos válidos re-encodificados como JSON.
     */
    private static function sanitize_texts( array $raw_texts ): array {
        $languages = self::get_available_languages();
        $sanitized = [];

        foreach ( $raw_texts as $lang => $json_str ) {
            $lang = sanitize_key( (string) $lang );

            if ( '' === $lang || ! in_array( $lang, $languages, true ) || ! is_string( $json_str ) ) {
                continue;
            }

            $decoded = json_decode( trim( $json_str ), true );
            if ( ! is_array( $decoded ) ) {
                continue; // JSON inválido: se ignora.
            }

            $clean = [];
            foreach ( $decoded as $key => $value ) {
                if ( ! is_string( $key ) || '' === $key ) {
                    continue;
                }

                if ( is_string( $value ) ) {
                    $clean[ $key ] = sanitize_text_field( $value );
                } elseif ( is_int( $value ) || is_float( $value ) ) {
                    $clean[ $key ] = (string) $value;
                }
                // Se omiten arrays, objetos y booleanos: no son textos válidos.
            }

            if ( empty( $clean ) ) {
                continue;
            }

            $sanitized[ $lang ] = wp_json_encode( $clean, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        }

        return $sanitized;
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
            if ( ! is_array( $raw_service ) ) {
                $raw_service = [];
            }

            $sanitized[ $service_key ] = [
                'enabled' => wp_validate_boolean( $raw_service['enabled'] ?? false ),
                'params'  => [],
            ];

            $raw_params = $raw_service['params'] ?? [];
            if ( ! is_array( $raw_params ) ) {
                $raw_params = [];
            }

            // Sanitizar cada parámetro según su tipo, usando el esquema confiable del plugin.
            foreach ( $service_def['params'] as $param_key => $param_def ) {
                $value = self::str( $raw_params[ $param_key ] ?? '' );

                $sanitized[ $service_key ]['params'][ $param_key ] = match ( $param_def['type'] ) {
                    'url'    => esc_url_raw( trim( $value ) ),
                    'key'    => sanitize_key( $value ),
                    default  => sanitize_text_field( $value ),
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
            $value = isset( $raw_colors[ $color_key ] ) && is_string( $raw_colors[ $color_key ] )
                ? sanitize_hex_color_no_hash( $raw_colors[ $color_key ] )
                : null;

            $sanitized[ $color_key ] = ( null !== $value && '' !== $value )
                ? $value
                : $defaults['colors'][ $color_key ];
        }

        return $sanitized;
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    /**
     * Convierte de forma segura un valor a string. Los valores no escalares
     * (arrays, objetos) devuelven cadena vacía para evitar errores de tipo.
     *
     * @param mixed $value Valor a convertir.
     * @return string
     */
    private static function str( $value ): string {
        return is_scalar( $value ) ? (string) $value : '';
    }

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
