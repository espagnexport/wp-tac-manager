<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTAC_Renderer {

    private array $settings;

    public function __construct() {
        $this->settings = WPTAC_Settings::get_settings();
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function enqueue_assets(): void {
        $g = $this->settings['general'];

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
            WPTAC_VERSION
        );

        $custom_css = $g['custom_css'] ?? '';
        if ( '' !== $custom_css ) {
            wp_add_inline_style( 'tarteaucitron', wp_strip_all_tags( $custom_css ) );
        }

        $color_css = self::build_color_css( $this->settings['colors'] ?? [] );
        if ( '' !== $color_css ) {
            wp_add_inline_style( 'tarteaucitron', $color_css );
        }

        wp_enqueue_script(
            'tarteaucitron',
            WPTAC_PLUGIN_URL . 'assets/js/tarteaucitron/tarteaucitron.min.js',
            [],
            WPTAC_VERSION,
            true
        );

        $services_file = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG
            ? 'tarteaucitron.services.js'
            : 'tarteaucitron.services.min.js';
        wp_enqueue_script(
            'tarteaucitron-services',
            WPTAC_PLUGIN_URL . 'assets/js/tarteaucitron/' . $services_file,
            [ 'tarteaucitron' ],
            WPTAC_VERSION,
            true
        );

        $lang      = $this->resolve_language();
        $lang_path = WPTAC_PLUGIN_DIR . 'assets/js/tarteaucitron/lang/tarteaucitron.' . $lang . '.js';
        $min_lang_path = WPTAC_PLUGIN_DIR . 'assets/js/tarteaucitron/lang/tarteaucitron.' . $lang . '.min.js';

        if ( file_exists( $lang_path ) || file_exists( $min_lang_path ) ) {
            $lang_file = file_exists( $min_lang_path )
                ? 'lang/tarteaucitron.' . $lang . '.min.js'
                : 'lang/tarteaucitron.' . $lang . '.js';
            wp_enqueue_script(
                'tarteaucitron-lang',
                WPTAC_PLUGIN_URL . 'assets/js/tarteaucitron/' . $lang_file,
                [ 'tarteaucitron' ],
                WPTAC_VERSION,
                true
            );
        }

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
            WPTAC_VERSION,
            [ 'strategy' => 'defer', 'in_footer' => true ]
        );

        $service_keys = [];
        foreach ( $active_services as $key => $service ) {
            if ( $this->service_is_ready( $key, $service ) ) {
                $service_keys[] = $key;
            }
        }

        wp_localize_script( 'wptac-front', 'wptacFront', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wptac_track_consent_nonce' ),
            'services' => $service_keys,
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
            'showAlertSmall'      => (bool) $g['show_alert_small'],
            'cookieslist'         => (bool) $g['cookie_accessible_ui'],
            'removeCredit'        => (bool) $g['remove_credit'],
            'showIcon'            => true,
            'iconPosition'        => ! empty( $g['icon_position'] ) ? $g['icon_position'] : 'BottomRight',
            'handleBrowserDNTRequest' => false,
            'reloadThxSeconds'    => (int)  $g['reload_thx_seconds'],
            'DenyAllCta'          => (bool) $g['deny_all_cta'],
            'AcceptAllCta'        => (bool) $g['accept_all_cta'],
            'highPrivacy'         => true,
            'serviceDefaultState' => 'wait',
            'adblocker'           => false,
            'moreInfoLink'        => true,
            'useExternalCss'      => false,
            'useExternalJs'       => false,
            'readmoreLink'        => '',
            'mandatory'           => true,
            'googleConsentMode'   => ! (bool) $g['disable_google_consent_mode'],
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

        $config_json = wp_json_encode( $tac_config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE );

        $custom_texts_lines = $this->build_custom_texts();

        $user_params_lines   = $this->build_user_params( $active_services );
        $add_service_lines   = $this->build_add_services( $active_services );

        $script = <<<JS
        (function() {
            'use strict';
            window.tarteaucitronUseMin = true;
            function wptacInit() {
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
            $params  = $service['config']['params'] ?? [];
            $tac_key = $service['tac_key'];

            foreach ( $params as $param_key => $param_value ) {
                if ( '' === $param_value ) {
                    continue;
                }

                $js_param_name = 'tarteaucitron.user.' . $tac_key . ucfirst( $param_key );
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
                '#tarteaucitronRoot .tarteaucitronIcon',
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

    private function exclude_from_optimization(): void {
        $handles = [ 'tarteaucitron', 'tarteaucitron-services', 'tarteaucitron-lang', 'wptac-front' ];

        add_filter( 'autoptimize_filter_js_exclude', function( string $exclude ) use ( $handles ): string {
            return $exclude . ', ' . implode( ', ', $handles );
        } );

        add_filter( 'rocket_exclude_js', function( array $excluded ) use ( $handles ): array {
            foreach ( $handles as $handle ) {
                if ( 'wptac-front' === $handle ) {
                    $excluded[] = 'front';
                } else {
                    $excluded[] = 'tarteaucitron/' . $handle;
                }
            }
            return $excluded;
        } );
        add_filter( 'rocket_delay_js_exclusions', function( array $excluded ): array {
            $excluded[] = 'tarteaucitron';
            $excluded[] = 'wptac-front';
            return $excluded;
        } );

        add_filter( 'litespeed_optimize_js_excludes', function( array $excluded ): array {
            $excluded[] = 'tarteaucitron';
            $excluded[] = 'wptac-front';
            return $excluded;
        } );

        add_filter( 'w3tc_minify_js_do_tag_minification', function( bool $do, string $tag ): bool {
            if ( str_contains( $tag, 'tarteaucitron' ) || str_contains( $tag, 'wptac-front' ) ) {
                return false;
            }
            return $do;
        }, 10, 2 );
    }
}
