<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTAC_Renderer {

    private array $settings = [];

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    private function asset_version( string $relative_path ): string {
        $file = WPTAC_PLUGIN_DIR . ltrim( $relative_path, '/' );

        if ( file_exists( $file ) ) {
            $mtime = filemtime( $file );
            if ( false !== $mtime ) {
                return (string) $mtime;
            }
        }

        return WPTAC_VERSION;
    }

    public function enqueue_assets(): void {
        // Los ajustes se leen solo cuando se renderiza el front-end.
        $this->settings = WPTAC_Settings::get_settings();
        $g              = $this->settings['general'];

        if ( empty( $g['enable_banner'] ) ) {
            return;
        }

        if ( ! empty( $g['disable_banner_loggedin'] ) && is_user_logged_in() ) {
            return;
        }

        $active_services = WPTAC_Services::get_active_services( $this->settings );

        $css_file = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG
            ? 'tarteaucitron.css'
            : 'tarteaucitron.min.css';
        wp_enqueue_style(
            'tarteaucitron',
            WPTAC_PLUGIN_URL . 'assets/css/' . $css_file,
            [],
            $this->asset_version( 'assets/css/' . $css_file )
        );

        $custom_css = $g['custom_css'] ?? '';
        if ( '' !== $custom_css ) {
            wp_add_inline_style( 'tarteaucitron', wp_strip_all_tags( $custom_css ) );
        }

        $color_css = self::build_color_css( $this->settings['colors'] ?? [] );
        if ( '' !== $color_css ) {
            wp_add_inline_style( 'tarteaucitron', $color_css );
        }

        $js_file = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG
            ? 'tarteaucitron.js'
            : 'tarteaucitron.min.js';
        wp_enqueue_script(
            'tarteaucitron',
            WPTAC_PLUGIN_URL . 'assets/js/tarteaucitron/' . $js_file,
            [],
            $this->asset_version( 'assets/js/tarteaucitron/' . $js_file ),
            true
        );

        // Archivo de idioma: se encola antes que los servicios y el init, porque
        // con useExternalJs = true tarteaucitron no lo descarga por su cuenta.
        $lang          = $this->resolve_language();
        $lang_path     = WPTAC_PLUGIN_DIR . 'assets/js/tarteaucitron/lang/tarteaucitron.' . $lang . '.js';
        $min_lang_path = WPTAC_PLUGIN_DIR . 'assets/js/tarteaucitron/lang/tarteaucitron.' . $lang . '.min.js';
        $services_deps = [ 'tarteaucitron' ];

        if ( file_exists( $lang_path ) || file_exists( $min_lang_path ) ) {
            $use_min_lang = file_exists( $min_lang_path ) && ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $lang_path ) );
            $lang_file    = $use_min_lang
                ? 'lang/tarteaucitron.' . $lang . '.min.js'
                : 'lang/tarteaucitron.' . $lang . '.js';
            wp_enqueue_script(
                'tarteaucitron-lang',
                WPTAC_PLUGIN_URL . 'assets/js/tarteaucitron/' . $lang_file,
                [ 'tarteaucitron' ],
                $this->asset_version( 'assets/js/tarteaucitron/' . $lang_file ),
                true
            );
            $services_deps[] = 'tarteaucitron-lang';
        }

        $services_file = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG
            ? 'tarteaucitron.services.js'
            : 'tarteaucitron.services.min.js';
        wp_enqueue_script(
            'tarteaucitron-services',
            WPTAC_PLUGIN_URL . 'assets/js/tarteaucitron/' . $services_file,
            $services_deps,
            $this->asset_version( 'assets/js/tarteaucitron/' . $services_file ),
            true
        );

        $init_script = $this->build_init_script( $active_services );
        wp_add_inline_script( 'tarteaucitron-services', $init_script, 'after' );

        $this->enqueue_front_script( $active_services );

        $this->exclude_from_optimization();
    }

    private function enqueue_front_script( array $active_services ): void {
        $script_name = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG
            ? 'assets/js/front.js'
            : 'assets/js/front.min.js';

        if ( ! file_exists( WPTAC_PLUGIN_DIR . $script_name ) ) {
            $script_name = 'assets/js/front.js';
        }

        wp_enqueue_script(
            'wptac-front',
            WPTAC_PLUGIN_URL . $script_name,
            [ 'tarteaucitron-services' ],
            $this->asset_version( $script_name ),
            [ 'strategy' => 'defer', 'in_footer' => true ]
        );

        // Se usan las claves de tarteaucitron (tac_key), que son las que llevan
        // los eventos "<clave>_allowed" / "<clave>_disallowed".
        $service_keys = [];
        foreach ( $active_services as $key => $service ) {
            if ( $this->service_is_ready( $key, $service ) ) {
                $service_keys[] = $service['tac_key'];
            }
        }

        // Sin nonce: el endpoint de estadísticas es anónimo y un nonce
        // incrustado en páginas cacheadas caduca y hace fallar el registro.
        wp_localize_script( 'wptac-front', 'wptacFront', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'services' => array_values( array_unique( $service_keys ) ),
        ] );
    }

    private function build_init_script( array $active_services ): string {
        $g = $this->settings['general'];

        $tac_config = [
            'privacyUrl'          => ! empty( $g['privacy_url'] ) ? $g['privacy_url'] : ( get_privacy_policy_url() ?: home_url( '/' ) ),
            'hashtag'             => $g['hashtag'],
            'cookieName'          => $g['cookie_name'],
            'orientation'         => $g['orientation'],
            'bodyPosition'        => $g['orientation'] === 'popup' ? 'bottom' : $g['orientation'],
            'groupServices'       => (bool) $g['group_services'],
            'showDetailsOnClick'  => (bool) $g['show_details_on_click'],
            'showAlertSmall'      => (bool) $g['show_alert_small'],
            'cookieslist'         => (bool) $g['cookie_accessible_ui'],
            'cookieslistEmbed'    => (bool) $g['cookieslist_embed'],
            'removeCredit'        => (bool) $g['remove_credit'],
            'closePopup'          => (bool) $g['close_popup'],
            'showIcon'            => (bool) $g['show_icon'],
            'iconPosition'        => ! empty( $g['icon_position'] ) ? $g['icon_position'] : 'BottomRight',
            'DenyAllCta'          => (bool) $g['deny_all_cta'],
            'AcceptAllCta'        => (bool) $g['accept_all_cta'],
            'highPrivacy'         => true,
            'alwaysNeedConsent'   => (bool) $g['always_need_consent'],
            'handleBrowserDNTRequest' => (bool) $g['handle_browser_dnt'],
            'moreInfoLink'        => (bool) $g['more_info_link'],
            'useExternalCss'      => true,
            // El plugin ya encola tarteaucitron.services.js y el archivo de idioma:
            // evita que tarteaucitron los vuelva a descargar por su cuenta.
            'useExternalJs'       => true,
            'readmoreLink'        => '',
            'mandatory'           => (bool) $g['mandatory'],
            'mandatoryCta'        => (bool) $g['mandatory_cta'],
            'googleConsentMode'   => ! (bool) $g['disable_google_consent_mode'],
            'bingConsentMode'     => (bool) $g['bing_consent_mode'],
            'pianoConsentMode'    => (bool) $g['piano_consent_mode'],
            'pianoConsentModeEssential' => (bool) $g['piano_consent_mode_essential'],
            'softConsentMode'     => (bool) $g['soft_consent_mode'],
            'dataLayer'           => (bool) $g['data_layer'],
            'serverSide'          => (bool) $g['server_side'],
            'partnersList'        => (bool) $g['partners_list'],
        ];

        if ( ! empty( $g['force_expiry_date'] ) ) {
            $tac_config['forceExpireDate'] = $g['force_expiry_date'];
        }

        $custom_icon_id = absint( $g['custom_icon'] ?? 0 );
        if ( $custom_icon_id > 0 ) {
            $icon_url = wp_get_attachment_image_url( $custom_icon_id, 'thumbnail' );
            if ( $icon_url ) {
                $tac_config['iconSrc'] = $icon_url;
            }
        }

        $google_services = [ 'googletagmanager', 'gtag', 'googleads' ];
        foreach ( $google_services as $gs ) {
            if ( array_key_exists( $gs, $active_services ) ) {
                $tac_config['dataLayer'] = true;
                break;
            }
        }

        $config_json = wp_json_encode( $tac_config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE );

        $custom_texts_lines = $this->build_custom_texts();

        // Fuerza el idioma resuelto por el plugin; si no, tarteaucitron lo
        // deduce del atributo lang del <html> o del navegador.
        $force_language = wp_json_encode( $this->resolve_language() );

        $user_params_lines   = $this->build_user_params( $active_services );
        $add_service_lines   = $this->build_add_services( $active_services );

        $script = <<<JS
        (function() {
            'use strict';
            function wptacInit() {
                window.tarteaucitronForceLanguage = {$force_language};
                {$custom_texts_lines}
                tarteaucitron.init({$config_json});
        {$user_params_lines}
        {$add_service_lines}
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wptacInit);
            } else {
                wptacInit();
            }
        })();
        JS;

        return $script;
    }

    private function build_user_params( array $active_services ): string {
        $lines = [];

        foreach ( $active_services as $key => $service ) {
            if ( ! $this->service_is_ready( $key, $service ) ) {
                continue;
            }

            $params_config = $service['config']['params'] ?? [];
            $tac_key       = $service['tac_key'];

            // Se recorren los parámetros definidos en el catálogo (no los
            // guardados), para no emitir claves obsoletas.
            foreach ( $service['params'] ?? [] as $param_key => $param_def ) {
                $param_value = (string) ( $params_config[ $param_key ] ?? '' );
                if ( '' === $param_value ) {
                    continue;
                }

                $js_key    = $param_def['js_key'] ?? null;

                if ( $js_key ) {
                    $js_param_name = 'tarteaucitron.user.' . $js_key;
                } else {
                    $js_param_name = 'tarteaucitron.user.' . $tac_key . ucfirst( $param_key );
                }

                if ( ! empty( $param_def['trailing_slash'] ) ) {
                    $param_value = trailingslashit( $param_value );
                }

                if ( ! empty( $param_def['list_separator'] ) ) {
                    $param_value = array_values( array_filter( array_map(
                        'trim',
                        explode( $param_def['list_separator'], $param_value )
                    ), 'strlen' ) );
                }

                $escaped_value = wp_json_encode( $param_value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP );

                $lines[] = "    {$js_param_name} = {$escaped_value};";
            }
        }

        return implode( "\n", $lines );
    }

    private function build_add_services( array $active_services ): string {
        $lines = [
            '    tarteaucitron.job = tarteaucitron.job || [];',
        ];

        foreach ( $active_services as $key => $service ) {
            if ( ! $this->service_is_ready( $key, $service ) ) {
                continue;
            }

            $tac_key = wp_json_encode( $service['tac_key'] );
            $lines[] = "    tarteaucitron.job.push({$tac_key});";
        }

        return implode( "\n", $lines );
    }

    private function service_is_ready( string $key, array $service ): bool {
        $definition = WPTAC_Services::get_definition( $key );
        if ( ! $definition ) {
            return false;
        }

        foreach ( $definition['params'] as $param_key => $param_def ) {
            if ( ! empty( $param_def['required'] ) ) {
                $value = $service['config']['params'][ $param_key ] ?? '';
                if ( '' === $value ) {
                    return false;
                }
            }
        }

        return true;
    }

    private function build_custom_texts(): string {
        $texts = $this->settings['texts'] ?? [];
        if ( empty( $texts ) ) {
            return '';
        }

        $active_lang = $this->resolve_language();
        $json_str    = trim( $texts[ $active_lang ] ?? '' );
        if ( '' === $json_str ) {
            return '';
        }

        $decoded = json_decode( $json_str, true );
        if ( ! is_array( $decoded ) || empty( $decoded ) ) {
            return '';
        }

        return 'tarteaucitronCustomText = ' . wp_json_encode( $decoded, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . ';';
    }

    private function resolve_language(): string {
        $lang_setting = $this->settings['general']['language'] ?? 'auto';

        if ( 'auto' === $lang_setting ) {
            $lang = substr( get_locale(), 0, 2 );
        } else {
            $lang = $lang_setting;
        }

        $base = WPTAC_PLUGIN_DIR . 'assets/js/tarteaucitron/lang/tarteaucitron.' . $lang;
        if ( ! file_exists( $base . '.js' ) && ! file_exists( $base . '.min.js' ) ) {
            $lang = 'en';
        }

        return $lang;
    }

    public static function build_color_css( array $colors ): string {
        $defaults = WPTAC_Settings::get_defaults()['colors'];
        $rules    = [];

        $colors_map = [
            'alert_big_bg'   => [
                '#tarteaucitronRoot #tarteaucitronAlertBig',
            ],
            'alert_big_text' => [
                '#tarteaucitronRoot #tarteaucitronAlertBig',
                '#tarteaucitronAlertBig #tarteaucitronDisclaimerAlert',
            ],
            'btn_allow_bg'   => [
                '#tarteaucitronRoot .tarteaucitronAllow',
                '#tarteaucitronRoot .tarteaucitronAllow:before',
            ],
            'btn_allow_border'   => [
                '#tarteaucitronRoot .tarteaucitronAllow',
            ],
            'btn_allow_text'     => [
                '#tarteaucitronRoot .tarteaucitronAllow',
            ],
            'btn_deny_bg'    => [
                '#tarteaucitronRoot .tarteaucitronDeny',
                '#tarteaucitronRoot .tarteaucitronDeny:before',
            ],
            'btn_deny_border'    => [
                '#tarteaucitronRoot .tarteaucitronDeny',
            ],
            'btn_deny_text'      => [
                '#tarteaucitronRoot .tarteaucitronDeny',
            ],
            'panel_bg'       => [
                '#tarteaucitronRoot .tarteaucitronBorder',
                '#tarteaucitronRoot #tarteaucitronServices_mandatory .tarteaucitronMain',
                '#tarteaucitronRoot .tarteaucitronMain',
            ],
            'panel_text'     => [
                '#tarteaucitronRoot .tarteaucitronName .tarteaucitronH2',
                '#tarteaucitronRoot .tarteaucitronName .tarteaucitronH3',
                '#tarteaucitronRoot .tarteaucitronMain .tarteaucitronName',
            ],
            'icon_bg'        => [
                '#tarteaucitronRoot #tarteaucitronIcon #tarteaucitronManager',
            ],
        ];

        foreach ( $colors_map as $key => $selectors ) {
            $value = $colors[ $key ] ?? $defaults[ $key ] ?? '';
            if ( '' === $value ) {
                continue;
            }

            $color_val   = '#' . ltrim( $value, '#' );
            $css_prop    = match ( true ) {
                str_ends_with( $key, '_border' ) => 'border-color',
                str_ends_with( $key, '_bg' )     => 'background',
                default                           => 'color',
            };

            $selector_str = implode( ', ', $selectors );
            $rules[]      = "{$selector_str} {{$css_prop}:{$color_val}!important}";
        }

        return implode( "\n", $rules );
    }

    /**
     * Excluye los scripts del banner (archivos y script de inicio en línea) de
     * la minificación, combinación y carga diferida de los plugins de caché.
     * "tarteaucitron" cubre todos los archivos (con o sin .min, idiomas) y el
     * script en línea, que lo menciona.
     */
    private function exclude_from_optimization(): void {
        $exclude_keywords = [
            'tarteaucitron',
            'wptacFront',
            'wptac-front',
            'wp-tac-manager/assets/js/front',
        ];

        $merge = static function ( $excludes ) use ( $exclude_keywords ) {
            if ( ! is_array( $excludes ) ) {
                $excludes = [];
            }
            return array_merge( $excludes, $exclude_keywords );
        };

        // LiteSpeed Cache.
        add_filter( 'litespeed_optimize_js_excludes', $merge );
        add_filter( 'litespeed_optm_js_defer_exc', $merge );
        add_filter( 'litespeed_delay_js_excludes', $merge );

        // WP Rocket.
        add_filter( 'rocket_exclude_js', $merge );
        add_filter( 'rocket_exclude_inline_js', $merge );
        add_filter( 'rocket_exclude_defer_js', $merge );
        add_filter( 'rocket_delay_js_exclusions', $merge );

        // Autoptimize (lista separada por comas).
        add_filter( 'autoptimize_filter_js_exclude', static function ( $exclude ) use ( $exclude_keywords ) {
            $exclude = is_string( $exclude ) ? $exclude : '';
            return ltrim( $exclude . ', ' . implode( ', ', $exclude_keywords ), ', ' );
        } );

        // W3 Total Cache.
        add_filter( 'w3tc_minify_js_do_tag_minification', static function ( $do, $tag = '' ) use ( $exclude_keywords ) {
            foreach ( $exclude_keywords as $keyword ) {
                if ( is_string( $tag ) && str_contains( $tag, $keyword ) ) {
                    return false;
                }
            }
            return $do;
        }, 10, 2 );
    }
}
