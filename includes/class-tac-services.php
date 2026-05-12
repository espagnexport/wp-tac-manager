<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTAC_Services {

    public static function get_definitions(): array {
        $services = [

            'googletagmanager' => [
                'label'       => 'Google Tag Manager',
                'category'    => 'analytic',
                'icon'        => 'dashicons-chart-bar',
                'description' => __( 'Manage all your measurement and marketing tags from a single Google interface.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/googletagmanager/',
                'tac_key'     => 'googletagmanager',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'GTM container ID', 'wp-tac-manager' ),
                        'placeholder' => 'GTM-XXXXXXX',
                        'required'    => true,
                        'description' => __( 'Your Google Tag Manager container ID (e.g. GTM-ABCDE12).', 'wp-tac-manager' ),
                        'pattern'     => '^GTM-[A-Z0-9]+$',
                    ],
                ],
            ],

            'gtag' => [
                'label'       => 'Google Analytics 4',
                'category'    => 'analytic',
                'icon'        => 'dashicons-chart-line',
                'description' => __( 'Google web analytics (GA4).', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/gtag/',
                'tac_key'     => 'gtag',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'Measurement ID', 'wp-tac-manager' ),
                        'placeholder' => 'G-XXXXXXXXXX',
                        'required'    => true,
                        'description' => __( 'Your Google Analytics 4 Measurement ID (e.g. G-ABCDE12345).', 'wp-tac-manager' ),
                        'pattern'     => '^G-[A-Z0-9]+$',
                    ],
                ],
            ],

            'googleads' => [
                'label'       => 'Google Ads',
                'category'    => 'analytic',
                'icon'        => 'dashicons-chart-line',
                'description' => __( 'Google Ads conversion tracking.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/googleads/',
                'tac_key'     => 'googleads',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'Conversion ID', 'wp-tac-manager' ),
                        'placeholder' => 'AW-123456789',
                        'required'    => true,
                        'description' => __( 'Your Google Ads conversion ID (e.g. AW-123456789).', 'wp-tac-manager' ),
                        'pattern'     => '^AW-[A-Z0-9]+$',
                    ],
                ],
            ],

            'facebookpixel' => [
                'label'       => 'Facebook Pixel',
                'category'    => 'social',
                'icon'        => 'dashicons-facebook',
                'description' => __( 'Meta (Facebook) tracking pixel for conversions and audiences.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/facebookpixel/',
                'tac_key'     => 'facebookpixel',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'Pixel ID', 'wp-tac-manager' ),
                        'placeholder' => '123456789',
                        'required'    => true,
                        'description' => __( 'Your Facebook Pixel ID (e.g. 123456789).', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'linkedininsighttag' => [
                'label'       => 'LinkedIn Insight Tag',
                'category'    => 'social',
                'icon'        => 'dashicons-share',
                'description' => __( 'LinkedIn conversion tracking and remarketing.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/linkedininsighttag/',
                'tac_key'     => 'linkedininsighttag',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'Partner ID', 'wp-tac-manager' ),
                        'placeholder' => '123456',
                        'required'    => true,
                        'description' => __( 'Your LinkedIn Insight Tag partner ID.', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'hotjar' => [
                'label'       => 'Hotjar',
                'category'    => 'analytic',
                'icon'        => 'dashicons-visibility',
                'description' => __( 'Hotjar heatmaps, recordings and surveys.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/hotjar/',
                'tac_key'     => 'hotjar',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'Site ID', 'wp-tac-manager' ),
                        'placeholder' => '1234567',
                        'required'    => true,
                        'description' => __( 'Your Hotjar site ID (e.g. 1234567).', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'matomocloud' => [
                'label'       => 'Matomo Cloud',
                'category'    => 'analytic',
                'icon'        => 'dashicons-analytics',
                'description' => __( 'Self-hosted web analytics with Matomo.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/matomocloud/',
                'tac_key'     => 'matomocloud',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'Site ID', 'wp-tac-manager' ),
                        'placeholder' => '1',
                        'required'    => true,
                        'description' => __( 'Your Matomo site ID.', 'wp-tac-manager' ),
                    ],
                    'host' => [
                        'type'        => 'url',
                        'label'       => __( 'Matomo server URL', 'wp-tac-manager' ),
                        'placeholder' => 'https://your-domain.matomo.cloud',
                        'required'    => true,
                        'description' => __( 'Full URL of your Matomo Cloud instance.', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'plausible' => [
                'label'       => 'Plausible',
                'category'    => 'analytic',
                'icon'        => 'dashicons-chart-area',
                'description' => __( 'Lightweight and privacy-friendly web analytics.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/plausible/',
                'tac_key'     => 'plausible',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'Domain', 'wp-tac-manager' ),
                        'placeholder' => 'yourdomain.com',
                        'required'    => true,
                        'description' => __( 'The domain you track in Plausible.', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'twitteruwt' => [
                'label'       => 'Twitter Universal Tag',
                'category'    => 'social',
                'icon'        => 'dashicons-twitter',
                'description' => __( 'X (Twitter) conversion tracking and audiences.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/twitteruwt/',
                'tac_key'     => 'twitteruwt',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'Pixel ID', 'wp-tac-manager' ),
                        'placeholder' => 'o2f123',
                        'required'    => true,
                        'description' => __( 'Your X/Twitter pixel ID.', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'hubspot' => [
                'label'       => 'HubSpot',
                'category'    => 'api',
                'icon'        => 'dashicons-email-alt',
                'description' => __( 'HubSpot CRM and marketing automation.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/hubspot/',
                'tac_key'     => 'hubspot',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'HubSpot ID', 'wp-tac-manager' ),
                        'placeholder' => '123456',
                        'required'    => true,
                        'description' => __( 'Your HubSpot account ID.', 'wp-tac-manager' ),
                    ],
                ],
            ],

            // ── Additional popular services ──

            'youtube' => [
                'label'       => 'YouTube',
                'category'    => 'video',
                'icon'        => 'dashicons-video-alt3',
                'description' => __( 'YouTube video embedding.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/youtube/',
                'tac_key'     => 'youtube',
                'params'      => [],
            ],

            'vimeo' => [
                'label'       => 'Vimeo',
                'category'    => 'video',
                'icon'        => 'dashicons-video-alt3',
                'description' => __( 'Vimeo video embedding.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/vimeo/',
                'tac_key'     => 'vimeo',
                'params'      => [],
            ],

            'googlemaps' => [
                'label'       => 'Google Maps',
                'category'    => 'api',
                'icon'        => 'dashicons-location',
                'description' => __( 'Google Maps embedding with API key.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/googlemaps/',
                'tac_key'     => 'googlemaps',
                'params'      => [
                    'key' => [
                        'type'        => 'text',
                        'label'       => __( 'API Key', 'wp-tac-manager' ),
                        'placeholder' => 'AIzaSy...',
                        'required'    => false,
                        'description' => __( 'Your Google Maps API key (optional).', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'recaptcha' => [
                'label'       => 'Google reCAPTCHA',
                'category'    => 'api',
                'icon'        => 'dashicons-shield',
                'description' => __( 'Google reCAPTCHA anti-spam protection.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/recaptcha/',
                'tac_key'     => 'recaptcha',
                'params'      => [
                    'sitekey' => [
                        'type'        => 'text',
                        'label'       => __( 'Site Key', 'wp-tac-manager' ),
                        'placeholder' => '6Lc...',
                        'required'    => true,
                        'description' => __( 'Your reCAPTCHA site key.', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'instagram' => [
                'label'       => 'Instagram',
                'category'    => 'social',
                'icon'        => 'dashicons-camera',
                'description' => __( 'Instagram content embedding.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/instagram/',
                'tac_key'     => 'instagram',
                'params'      => [],
            ],

            'tiktok' => [
                'label'       => 'TikTok',
                'category'    => 'social',
                'icon'        => 'dashicons-video-alt3',
                'description' => __( 'TikTok video embedding and tracking.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/tiktok/',
                'tac_key'     => 'tiktok',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'Pixel ID', 'wp-tac-manager' ),
                        'placeholder' => '',
                        'required'    => false,
                        'description' => __( 'Your TikTok pixel ID (optional).', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'clarity' => [
                'label'       => 'Microsoft Clarity',
                'category'    => 'analytic',
                'icon'        => 'dashicons-chart-bar',
                'description' => __( 'Microsoft Clarity session recordings and heatmaps.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/clarity/',
                'tac_key'     => 'clarity',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'Project ID', 'wp-tac-manager' ),
                        'placeholder' => 'abcdefgh',
                        'required'    => true,
                        'description' => __( 'Your Microsoft Clarity project ID.', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'bingads' => [
                'label'       => 'Bing Ads',
                'category'    => 'analytic',
                'icon'        => 'dashicons-chart-line',
                'description' => __( 'Bing Ads (Microsoft Advertising) conversion tracking.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/bingads/',
                'tac_key'     => 'bingads',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'Tag ID', 'wp-tac-manager' ),
                        'placeholder' => '12345678',
                        'required'    => true,
                        'description' => __( 'Your Bing Ads universal event tracking (UET) tag ID.', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'crisp' => [
                'label'       => 'Crisp Chat',
                'category'    => 'other',
                'icon'        => 'dashicons-format-chat',
                'description' => __( 'Crisp live chat widget.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/crisp/',
                'tac_key'     => 'crisp',
                'params'      => [
                    'id' => [
                        'type'        => 'text',
                        'label'       => __( 'Website ID', 'wp-tac-manager' ),
                        'placeholder' => '',
                        'required'    => true,
                        'description' => __( 'Your Crisp website ID.', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'googlefonts' => [
                'label'       => 'Google Fonts',
                'category'    => 'other',
                'icon'        => 'dashicons-editor-textcolor',
                'description' => __( 'Google Fonts loading.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/googlefonts/',
                'tac_key'     => 'googlefonts',
                'params'      => [
                    'families' => [
                        'type'        => 'text',
                        'label'       => __( 'Font families', 'wp-tac-manager' ),
                        'placeholder' => 'Roboto,Open+Sans',
                        'required'    => false,
                        'description' => __( 'Comma-separated list of font families.', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'disqus' => [
                'label'       => 'Disqus',
                'category'    => 'comment',
                'icon'        => 'dashicons-admin-comments',
                'description' => __( 'Disqus comment system.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/disqus/',
                'tac_key'     => 'disqus',
                'params'      => [
                    'shortname' => [
                        'type'        => 'text',
                        'label'       => __( 'Shortname', 'wp-tac-manager' ),
                        'placeholder' => 'example',
                        'required'    => true,
                        'description' => __( 'Your Disqus forum shortname.', 'wp-tac-manager' ),
                    ],
                ],
            ],

            'pinterest' => [
                'label'       => 'Pinterest',
                'category'    => 'social',
                'icon'        => 'dashicons-share',
                'description' => __( 'Pinterest save button and tracking.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/pinterest/',
                'tac_key'     => 'pinterest',
                'params'      => [],
            ],

            'stripe' => [
                'label'       => 'Stripe',
                'category'    => 'api',
                'icon'        => 'dashicons-cart',
                'description' => __( 'Stripe payment processing.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/stripe/',
                'tac_key'     => 'stripe',
                'params'      => [],
            ],

            'paypal' => [
                'label'       => 'PayPal',
                'category'    => 'api',
                'icon'        => 'dashicons-cart',
                'description' => __( 'PayPal payment and checkout.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/paypal/',
                'tac_key'     => 'paypal',
                'params'      => [],
            ],

            'spotify' => [
                'label'       => 'Spotify',
                'category'    => 'other',
                'icon'        => 'dashicons-format-audio',
                'description' => __( 'Spotify music and podcast embedding.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/spotify/',
                'tac_key'     => 'spotify',
                'params'      => [],
            ],

            'soundcloud' => [
                'label'       => 'SoundCloud',
                'category'    => 'other',
                'icon'        => 'dashicons-format-audio',
                'description' => __( 'SoundCloud audio embedding.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/soundcloud/',
                'tac_key'     => 'soundcloud',
                'params'      => [],
            ],

            'dailymotion' => [
                'label'       => 'Dailymotion',
                'category'    => 'video',
                'icon'        => 'dashicons-video-alt3',
                'description' => __( 'Dailymotion video embedding.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/dailymotion/',
                'tac_key'     => 'dailymotion',
                'params'      => [],
            ],

            'twitch' => [
                'label'       => 'Twitch',
                'category'    => 'video',
                'icon'        => 'dashicons-video-alt3',
                'description' => __( 'Twitch live stream embedding.', 'wp-tac-manager' ),
                'doc_url'     => 'https://tarteaucitron.io/service/twitch/',
                'tac_key'     => 'twitch',
                'params'      => [],
            ],

        ];

        return apply_filters( 'wptac_services', $services );
    }

    public static function get_definition( string $key ): ?array {
        return self::get_definitions()[ $key ] ?? null;
    }

    public static function get_active_services( array $settings ): array {
        $active      = [];
        $definitions = self::get_definitions();

        foreach ( $definitions as $key => $def ) {
            $service_config = $settings['services'][ $key ] ?? [];
            if ( ! empty( $service_config['enabled'] ) ) {
                $active[ $key ] = array_merge( $def, [ 'config' => $service_config ] );
            }
        }

        return $active;
    }
}