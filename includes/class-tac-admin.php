<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTAC_Admin {

    const PAGE_SLUG = 'wp-tac-manager';
    const OPTION_GROUP = 'wptac_option_group';
    const STAT_PREFIX = 'wptac_stat_';
    const RATE_LIMIT_KEY = 'wptac_rate_limit_';
    const RATE_LIMIT_MAX = 10; // Max requests per minute
    const RATE_LIMIT_WINDOW = 60; // 60 seconds

    const NONCE_SAVE          = 'wptac_save_settings_nonce';
    const NONCE_CHECK         = 'wptac_check_update_nonce';
    const NONCE_UPDATE        = 'wptac_update_nonce';
    const NONCE_MANUAL_UPDATE = 'wptac_manual_update_nonce';

    public function __construct() {
        add_action( 'admin_menu',             [ $this, 'register_menu' ] );
        add_action( 'admin_init',             [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts',  [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_ajax_wptac_save_settings', [ $this, 'ajax_save_settings' ] );
        add_action( 'admin_notices',          [ $this, 'admin_notices' ] );
        add_action( 'admin_init',             [ $this, 'stats_reset_action' ] );

        add_action( 'wp_ajax_wptac_track_consent',    [ $this, 'ajax_track_consent' ] );
        add_action( 'wp_ajax_nopriv_wptac_track_consent', [ $this, 'ajax_track_consent' ] );

        add_action( 'wp_ajax_wptac_check_update', [ $this, 'ajax_check_update' ] );
        add_action( 'wp_ajax_wptac_update',       [ $this, 'ajax_update' ] );
        add_action( 'wp_ajax_wptac_manual_update', [ $this, 'ajax_manual_update' ] );


        add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widget' ] );
    }

    public function register_menu(): void {
        add_menu_page(
            __( 'WP TAC Manager — Cookies', 'wp-tac-manager' ),
            __( 'TAC Manager', 'wp-tac-manager' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_settings_page' ],
            'dashicons-chart-line',
            80
        );

        add_submenu_page(
            self::PAGE_SLUG,
            __( 'Cookie Settings', 'wp-tac-manager' ),
            __( 'Settings', 'wp-tac-manager' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings(): void {
        register_setting(
            self::OPTION_GROUP,
            WPTAC_OPTION_KEY,
            [
                'type'              => 'array',
                'sanitize_callback' => [ 'WPTAC_Settings', 'sanitize' ],
                'default'           => WPTAC_Settings::get_defaults(),
            ]
        );
    }

    public function enqueue_admin_assets( string $hook ): void {
        if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
            return;
        }

        wp_enqueue_style( 'wp-color-picker' );

        wp_enqueue_style(
            'wptac-admin',
            WPTAC_PLUGIN_URL . 'admin/css/admin.css',
            [],
            $this->asset_version( 'admin/css/admin.css' )
        );

        wp_enqueue_script(
            'wptac-admin',
            WPTAC_PLUGIN_URL . 'admin/js/admin.js',
            [ 'wp-color-picker' ],
            $this->asset_version( 'admin/js/admin.js' ),
            [ 'strategy' => 'defer', 'in_footer' => true ]
        );

        wp_enqueue_media();

        wp_localize_script( 'wptac-admin', 'wptacAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonces'  => [
                'save'         => wp_create_nonce( self::NONCE_SAVE ),
                'check'        => wp_create_nonce( self::NONCE_CHECK ),
                'update'       => wp_create_nonce( self::NONCE_UPDATE ),
                'manualUpdate' => wp_create_nonce( self::NONCE_MANUAL_UPDATE ),
            ],
            'i18n'    => [
                'saving'       => __( 'Saving…', 'wp-tac-manager' ),
                'saved'        => __( '✓ Settings saved', 'wp-tac-manager' ),
                'error'        => __( '✗ Error saving. Please try again.', 'wp-tac-manager' ),
                'selectIcon'   => __( 'Select cookie icon', 'wp-tac-manager' ),
                'useAsIcon'    => __( 'Use as icon', 'wp-tac-manager' ),
                'checking'     => __( 'Checking…', 'wp-tac-manager' ),
                'updating'     => __( 'Updating…', 'wp-tac-manager' ),
                'updateDone'   => __( 'Update completed', 'wp-tac-manager' ),
                'updateError'  => __( 'Update error', 'wp-tac-manager' ),
                'uptodate'     => __( 'You already have the latest version', 'wp-tac-manager' ),
                'updateAvailable' => __( 'A new version is available.', 'wp-tac-manager' ),
                'checkFailed'        => __( 'Could not check. Try again.', 'wp-tac-manager' ),
                'uploading'          => __( 'Uploading...', 'wp-tac-manager' ),
                'manualUpdateSuccess' => __( '✓ tarteaucitron.js updated successfully.', 'wp-tac-manager' ),
                'manualUpdateError'  => __( '✗ Error updating tarteaucitron.js. Please check the file or try again.', 'wp-tac-manager' ),
            ],
        ] );
    }

    /**
     * Devuelve el timestamp de modificación de un asset del plugin para usarlo
     * como versión de cache-busting. Si el archivo no existe, usa WPTAC_VERSION.
     *
     * @param string $relative_path Ruta relativa al directorio del plugin.
     * @return string
     */
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

    /**
     * Extrae y sanitiza el nonce de la petición AJAX actual.
     * Busca primero en la cabecera X-WP-Nonce y luego en $_REQUEST.
     *
     * @return string
     */
    private function get_request_nonce(): string {
        $nonce = sanitize_text_field( $_SERVER['HTTP_X_WP_NONCE'] ?? '' );

        if ( '' !== $nonce ) {
            return $nonce;
        }

        return sanitize_text_field( $_REQUEST['nonce'] ?? '' );
    }

    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-tac-manager' ) );
        }

        $settings  = WPTAC_Settings::get_settings();
        $services  = WPTAC_Services::get_definitions();
        $languages = WPTAC_Settings::get_available_languages();

        include WPTAC_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    public function ajax_save_settings(): void {
        $raw_json = file_get_contents( 'php://input' );
        $raw_data = json_decode( $raw_json, true );

        $nonce =
            sanitize_text_field( $_SERVER['HTTP_X_WP_NONCE'] ?? '' )
            ?: ( is_array( $raw_data ) ? sanitize_text_field( $raw_data['nonce'] ?? '' ) : '' )
            ?: sanitize_text_field( $_POST['nonce'] ?? '' );

        if ( ! wp_verify_nonce( $nonce, self::NONCE_SAVE ) ) {
            wp_send_json_error(
                [ 'message' => __( 'Invalid or expired nonce. Reload the page and try again.', 'wp-tac-manager' ) ],
                403
            );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                [ 'message' => __( 'Insufficient permissions.', 'wp-tac-manager' ) ],
                403
            );
        }

        if ( ! is_array( $raw_data ) ) {
            wp_send_json_error(
                [ 'message' => __( 'Invalid payload: JSON expected.', 'wp-tac-manager' ) ],
                400
            );
        }

        unset( $raw_data['action'], $raw_data['nonce'] );

        $saved = WPTAC_Settings::save_settings( $raw_data );

        wp_send_json_success( [
            'message' => $saved
                ? __( 'Settings saved successfully.', 'wp-tac-manager' )
                : __( 'No changes to save.', 'wp-tac-manager' ),
        ] );
    }

    public function admin_notices(): void {
        $screen = get_current_screen();
        if ( ! $screen || 'toplevel_page_' . self::PAGE_SLUG !== $screen->id ) {
            return;
        }

        $settings = WPTAC_Settings::get_settings();
        $services = WPTAC_Services::get_active_services( $settings );

        foreach ( $services as $key => $service ) {
            $definition = WPTAC_Services::get_definition( $key );
            if ( ! $definition ) {
                continue;
            }
            foreach ( $definition['params'] as $param_key => $param_def ) {
                if ( ! empty( $param_def['required'] ) && empty( $service['config']['params'][ $param_key ] ) ) {
                    printf(
                        '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                        esc_html(
                            sprintf(
                                __( 'WP TAC Manager: The service "%1$s" is active but is missing the required parameter "%2$s".', 'wp-tac-manager' ),
                                $service['label'],
                                $param_def['label']
                            )
                        )
                    );
                }
            }
        }
    }

    // ─────────────────────────────────────────────
    // Update: AJAX handlers
    // ─────────────────────────────────────────────

    public function ajax_check_update(): void {
        $nonce = $this->get_request_nonce();

        if ( ! wp_verify_nonce( $nonce, self::NONCE_CHECK ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid or expired nonce. Reload the page and try again.', 'wp-tac-manager' ) ], 403 );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'wp-tac-manager' ) ], 403 );
        }

        $force   = ! empty( $_GET['force'] ) || ! empty( $_POST['force'] );
        $bundled = WPTAC_Updater::get_active_version();
        $latest  = WPTAC_Updater::get_latest_version( $force );

        if ( is_wp_error( $latest ) ) {
            wp_send_json_error( [
                'message' => $latest->get_error_message(),
                'code'    => $latest->get_error_code(),
            ], 400 );
        }

        wp_send_json_success( [
            'bundled' => $bundled,
            'latest'  => $latest,
            'needs_update' => version_compare( $latest, $bundled, '>' ),
        ] );
    }

    public function ajax_update(): void {
        $nonce = $this->get_request_nonce();

        if ( ! wp_verify_nonce( $nonce, self::NONCE_UPDATE ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid or expired nonce. Reload the page and try again.', 'wp-tac-manager' ) ], 403 );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'wp-tac-manager' ) ], 403 );
        }

        $result = WPTAC_Updater::do_update();

        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        }

        wp_send_json_error( $result, 400 );
    }

    public function ajax_manual_update(): void {
        check_ajax_referer( self::NONCE_MANUAL_UPDATE, 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'wp-tac-manager' ) ], 403 );
        }

        if ( empty( $_FILES['tarteaucitron_zip_upload'] ) || ! is_array( $_FILES['tarteaucitron_zip_upload'] ) ) {
            wp_send_json_error( [ 'message' => __( 'No file uploaded.', 'wp-tac-manager' ) ], 400 );
        }

        $result = WPTAC_Updater::process_zip_upload( $_FILES['tarteaucitron_zip_upload'] );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ], 400 );
        }

        if ( ! empty( $result['version'] ) ) {
            $message = sprintf( __( 'tarteaucitron.js updated to v%s successfully.', 'wp-tac-manager' ), $result['version'] );
        } else {
            $message = __( 'tarteaucitron.js updated successfully.', 'wp-tac-manager' );
        }

        $response = [ 'message' => $message ];

        if ( ! empty( $result['version'] ) ) {
            $response['new_version'] = $result['version'];
        }

        if ( ! empty( $result['warnings'] ) && is_array( $result['warnings'] ) ) {
            $response['warnings'] = $result['warnings'];
        }

        wp_send_json_success( $response );
    }



    // ─────────────────────────────────────────────
    // Stats: AJAX handler
    // ─────────────────────────────────────────────

    public function ajax_track_consent(): void {
        check_ajax_referer( 'wptac_track_consent_nonce', '_ajax_nonce' );

        $this->check_rate_limit();

        $service = sanitize_key( $_POST['service'] ?? '' );
        $status  = ! empty( $_POST['status'] );

        if ( ! $service ) {
            wp_send_json_error();
        }

        $definitions = WPTAC_Services::get_definitions();
        if ( ! isset( $definitions[ $service ] ) ) {
            wp_send_json_error();
        }

        $option_name = self::STAT_PREFIX . 'service_' . $service . '_' . ( $status ? 'allowed' : 'disallowed' );
        $count       = (int) get_option( $option_name, 0 );
        update_option( $option_name, $count + 1, false );

        $since_opt = self::STAT_PREFIX . 'since';
        if ( ! get_option( $since_opt ) ) {
            update_option( $since_opt, time(), false );
        }

        wp_send_json_success();
    }

    private function check_rate_limit(): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ( empty( $ip ) ) {
            wp_send_json_error( [ 'message' => 'Invalid request.' ], 400 );
        }

        $key = self::RATE_LIMIT_KEY . md5( $ip );
        $timestamps = get_transient( $key );

        if ( ! is_array( $timestamps ) ) {
            $timestamps = [];
        }

        $cutoff = time() - self::RATE_LIMIT_WINDOW;
        $timestamps = array_values(
            array_filter( $timestamps, fn( $ts ) => $ts > $cutoff )
        );

        if ( count( $timestamps ) >= self::RATE_LIMIT_MAX ) {
            wp_send_json_error( [ 'message' => 'Too many requests.' ], 429 );
        }

        $timestamps[] = time();
        set_transient( $key, $timestamps, self::RATE_LIMIT_WINDOW * 2 );
    }

    // ─────────────────────────────────────────────
    // Stats: Dashboard widget
    // ─────────────────────────────────────────────

    public function add_dashboard_widget(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'wptac_dashboard_widget',
            __( 'WP TAC Manager — Statistics', 'wp-tac-manager' ),
            [ $this, 'render_dashboard_widget' ]
        );
    }

    public function render_dashboard_widget(): void {
        $this->render_stats_table( 'widget' );
    }

    public function render_stats_table( string $mode = 'page' ): void {
        $services = WPTAC_Services::get_definitions();
        $rows     = [];

        foreach ( $services as $key => $def ) {
            $allowed    = (int) get_option( self::STAT_PREFIX . 'service_' . $key . '_allowed', 0 );
            $disallowed = (int) get_option( self::STAT_PREFIX . 'service_' . $key . '_disallowed', 0 );
            $total      = $allowed + $disallowed;

            if ( ! $total ) {
                continue;
            }

            $rows[ $key ] = [
                'label'      => $def['label'],
                'total'      => $total,
                'allowed'    => $allowed,
                'disallowed' => $disallowed,
                'pct_allowed'    => number_format( $allowed / $total * 100, 1 ),
                'pct_disallowed' => number_format( $disallowed / $total * 100, 1 ),
            ];
        }

        if ( empty( $rows ) ) {
            echo '<p>' . esc_html__( 'No consent data yet.', 'wp-tac-manager' ) . '</p>';
            return;
        }

        echo '<table class="widefat striped" style="border:0">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Service', 'wp-tac-manager' ) . '</th>';
        echo '<th>' . esc_html__( 'Total', 'wp-tac-manager' ) . '</th>';
        echo '<th>' . esc_html__( 'Accepted', 'wp-tac-manager' ) . '</th>';
        echo '<th>' . esc_html__( 'Rejected', 'wp-tac-manager' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $rows as $row ) {
            echo '<tr>';
            echo '<td><strong>' . esc_html( $row['label'] ) . '</strong></td>';
            echo '<td>' . esc_html( $row['total'] ) . '</td>';
            echo '<td>' . esc_html( $row['allowed'] ) . ' <small>(' . esc_html( $row['pct_allowed'] ) . '%)</small></td>';
            echo '<td>' . esc_html( $row['disallowed'] ) . ' <small>(' . esc_html( $row['pct_disallowed'] ) . '%)</small></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        $since = get_option( self::STAT_PREFIX . 'since' );
        if ( $since ) {
            echo '<p><small>' .
                sprintf(
                    esc_html__( 'Since %s', 'wp-tac-manager' ),
                    wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $since )
                ) .
                '</small></p>';
        }

        if ( 'page' === $mode ) {
            $reset_url = wp_nonce_url(
                add_query_arg( 'wptac_reset_stats', '1' ),
                'wptac_reset_stats_action'
            );
            echo '<p><a href="' . esc_url( $reset_url ) . '" class="button" style="color:#d63638">' .
                esc_html__( 'Reset statistics', 'wp-tac-manager' ) .
                '</a></p>';
        }
    }

    public function stats_reset_action(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( empty( $_GET['wptac_reset_stats'] ) ) {
            return;
        }

        check_admin_referer( 'wptac_reset_stats_action' );

        $services = WPTAC_Services::get_definitions();
        foreach ( $services as $key => $def ) {
            delete_option( self::STAT_PREFIX . 'service_' . $key . '_allowed' );
            delete_option( self::STAT_PREFIX . 'service_' . $key . '_disallowed' );
        }
        delete_option( self::STAT_PREFIX . 'since' );

        wp_safe_redirect( remove_query_arg( 'wptac_reset_stats' ) );
        exit;
    }
}
