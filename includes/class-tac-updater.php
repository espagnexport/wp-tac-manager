<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTAC_Updater {

    const GITHUB_API    = 'https://api.github.com/repos/AmauriC/tarteaucitron.js/releases/latest';
    const GITHUB_RAW    = 'https://raw.githubusercontent.com/AmauriC/tarteaucitron.js';
    const VERSION_OPTION = 'wptac_tarteaucitron_latest_version';

    public static function get_bundled_version(): string {
        return WPTAC_TARTEAUCITRON_VERSION;
    }

    public static function get_latest_version(): ?string {
        $cached = get_transient( 'wptac_tarteaucitron_version_check' );
        if ( false !== $cached ) {
            return $cached ?: null;
        }

        $version = self::fetch_github_version();
        if ( null === $version ) {
            $version = self::fetch_cdn_version();
        }

        if ( null !== $version ) {
            set_transient( 'wptac_tarteaucitron_version_check', $version, DAY_IN_SECONDS );
        } else {
            set_transient( 'wptac_tarteaucitron_version_check', '', HOUR_IN_SECONDS );
        }

        return $version;
    }

    private static function fetch_github_version(): ?string {
        $response = wp_remote_get( self::GITHUB_API, [
            'timeout'   => 10,
            'headers'   => [
                'Accept'       => 'application/vnd.github.v3+json',
                'User-Agent'   => 'WP-TAC-Manager/' . WPTAC_VERSION,
            ],
            'sslverify' => true,
        ] );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return null;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
            return null;
        }

        return ltrim( $data['tag_name'], 'v' );
    }

    private static function fetch_cdn_version(): ?string {
        $response = wp_remote_get( 'https://cdn.jsdelivr.net/npm/tarteaucitronjs/package.json', [
            'timeout'   => 10,
            'sslverify' => true,
        ] );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return null;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return $data['version'] ?? null;
    }

    public static function clear_version_cache(): void {
        delete_transient( 'wptac_tarteaucitron_version_check' );
    }

    private static function save_downloaded_version( string $version ): void {
        update_option( self::VERSION_OPTION, $version, false );
    }

    public static function do_update(): array {
        $latest = self::get_latest_version();
        if ( null === $latest ) {
            return [ 'success' => false, 'message' => __( 'No se pudo obtener la última versión.', 'wp-tac-manager' ) ];
        }

        if ( ! version_compare( $latest, self::get_bundled_version(), '>' ) ) {
            return [ 'success' => false, 'message' => __( 'Ya tienes la última versión instalada.', 'wp-tac-manager' ) ];
        }

        $tag = 'v' . $latest;
        $base_raw_url = self::GITHUB_RAW . '/' . $tag;

        $files = [
            'assets/js/tarteaucitron/tarteaucitron.js'            => $base_raw_url . '/tarteaucitron.js',
            'assets/js/tarteaucitron/tarteaucitron.min.js'        => $base_raw_url . '/tarteaucitron.min.js',
            'assets/js/tarteaucitron/tarteaucitron.services.js'   => $base_raw_url . '/tarteaucitron.services.js',
            'assets/js/tarteaucitron/tarteaucitron.services.min.js' => $base_raw_url . '/tarteaucitron.services.min.js',
            'assets/css/tarteaucitron.css'                         => $base_raw_url . '/tarteaucitron.css',
            'assets/css/tarteaucitron.min.css'                     => $base_raw_url . '/tarteaucitron.min.css',
        ];

        $lang_files = self::get_lang_file_list();
        foreach ( $lang_files as $lang_file ) {
            $files[ 'assets/js/tarteaucitron/lang/' . $lang_file ] = $base_raw_url . '/lang/' . $lang_file;
        }

        global $wp_filesystem;
        if ( ! $wp_filesystem ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if ( ! $wp_filesystem ) {
            return [ 'success' => false, 'message' => __( 'No se pudo inicializar el sistema de archivos.', 'wp-tac-manager' ) ];
        }

        $plugin_dir = WPTAC_PLUGIN_DIR;
        $errors     = [];
        $downloaded = 0;

        foreach ( $files as $relative_path => $remote_url ) {
            $local_file = $plugin_dir . $relative_path;
            $local_dir  = dirname( $local_file );

            if ( ! $wp_filesystem->is_dir( $local_dir ) ) {
                $wp_filesystem->mkdir( $local_dir, FS_CHMOD_DIR );
            }

            $response = wp_remote_get( $remote_url, [
                'timeout'  => 30,
                'headers'  => [ 'User-Agent' => 'WP-TAC-Manager/' . WPTAC_VERSION ],
                'stream'   => true,
                'filename' => $local_file,
            ] );

            if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                $errors[] = sprintf(
                    __( 'Error al descargar %s', 'wp-tac-manager' ),
                    basename( $relative_path )
                );
                continue;
            }

            ++$downloaded;
        }

        if ( $downloaded > 0 ) {
            self::save_downloaded_version( $latest );
            self::clear_version_cache();
        }

        if ( ! empty( $errors ) ) {
            $message = sprintf(
                __( 'Actualización parcial: %1$d archivos actualizados, %2$d errores.', 'wp-tac-manager' ),
                $downloaded,
                count( $errors )
            );
            return [ 'success' => false, 'message' => $message, 'errors' => $errors ];
        }

        return [
            'success' => true,
            'latest'  => $latest,
            'message' => sprintf(
                __( 'tarteaucitron.js actualizado a v%s correctamente.', 'wp-tac-manager' ),
                $latest
            ),
        ];
    }

    private static function get_lang_file_list(): array {
        $langs = [ 'ar', 'bg', 'ca', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'fi', 'fr', 'he', 'hr', 'hu', 'id', 'is', 'it', 'ja', 'ko', 'lt', 'lv', 'nb', 'nl', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sr', 'sv', 'th', 'tr', 'uk', 'vi', 'zh', 'cn' ];
        $files = [];
        foreach ( $langs as $lang ) {
            $files[] = 'tarteaucitron.' . $lang . '.js';
            $files[] = 'tarteaucitron.' . $lang . '.min.js';
        }
        return $files;
    }
}
