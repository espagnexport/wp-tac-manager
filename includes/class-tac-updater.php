<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTAC_Updater {

    const CDN_PACKAGE  = 'https://cdn.jsdelivr.net/npm/tarteaucitronjs/package.json';
    const CDN_FILES    = 'https://cdn.jsdelivr.net/npm/tarteaucitronjs';
    const GITHUB_API   = 'https://api.github.com/repos/AmauriC/tarteaucitron.js/releases/latest';
    const GITHUB_RAW   = 'https://raw.githubusercontent.com/AmauriC/tarteaucitron.js';
    const VERSION_OPTION = 'wptac_tarteaucitron_latest_version';

    const VERSION_MANUAL_OPTION = 'wptac_tarteaucitron_manual_version';

    const VERSION_CHECK_TRANSIENT    = 'wptac_tarteaucitron_version_check';
    const VERSION_CHECK_TTL          = DAY_IN_SECONDS;
    const VERSION_CHECK_NEGATIVE_TTL = MINUTE_IN_SECONDS;

    public static function get_active_version(): string {
        $downloaded = get_option( self::VERSION_OPTION, '' );
        if ( ! empty( $downloaded ) ) {
            return (string) $downloaded;
        }

        $manual_version = get_option( self::VERSION_MANUAL_OPTION, '' );
        if ( ! empty( $manual_version ) ) {
            return (string) $manual_version;
        }

        return WPTAC_TARTEAUCITRON_VERSION;
    }

    public static function get_bundled_version(): string {
        return self::get_active_version();
    }

    /**
     * Obtiene la última versión disponible de tarteaucitron.js.
     *
     * @param bool $force Si es true, ignora la caché y vuelve a consultar las fuentes.
     * @return string|WP_Error Versión en caso de éxito o WP_Error con el detalle del fallo.
     */
    public static function get_latest_version( bool $force = false ) {
        if ( $force ) {
            self::clear_version_cache();
        } else {
            $cached = get_transient( self::VERSION_CHECK_TRANSIENT );

            if ( false !== $cached ) {
                if ( is_wp_error( $cached ) ) {
                    return $cached;
                }
                if ( '' !== $cached ) {
                    return (string) $cached;
                }
            }
        }

        $version = self::fetch_cdn_version();

        if ( is_wp_error( $version ) ) {
            $cdn_error = $version;
            $github    = self::fetch_github_version();

            if ( is_wp_error( $github ) ) {
                $error = new WP_Error(
                    'version_check_failed',
                    __( 'Could not retrieve the latest tarteaucitron.js version from any update source.', 'wp-tac-manager' ),
                    [
                        'cdn'    => $cdn_error,
                        'github' => $github,
                    ]
                );
                set_transient( self::VERSION_CHECK_TRANSIENT, $error, self::VERSION_CHECK_NEGATIVE_TTL );
                return $error;
            }

            $version = $github;
        }

        set_transient( self::VERSION_CHECK_TRANSIENT, $version, self::VERSION_CHECK_TTL );

        return (string) $version;
    }

    private static function fetch_github_version() {
        $headers = [
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WP-TAC-Manager/' . WPTAC_VERSION,
        ];

        if ( defined( 'WP_TAC_MANAGER_GITHUB_TOKEN' ) && WP_TAC_MANAGER_GITHUB_TOKEN ) {
            $headers['Authorization'] = 'Bearer ' . WP_TAC_MANAGER_GITHUB_TOKEN;
        }

        $response = wp_remote_get( self::GITHUB_API, [
            'timeout'   => 10,
            'headers'   => $headers,
            'sslverify' => true,
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'github_request_failed', $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return new WP_Error(
                'github_http_error',
                sprintf( __( 'GitHub returned HTTP %d.', 'wp-tac-manager' ), $code )
            );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
            return new WP_Error( 'github_invalid_response', __( 'GitHub returned an invalid release payload.', 'wp-tac-manager' ) );
        }

        return ltrim( (string) $data['tag_name'], 'v' );
    }

    private static function fetch_cdn_version() {
        $response = wp_remote_get( self::CDN_PACKAGE, [
            'timeout'   => 10,
            'sslverify' => true,
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'cdn_request_failed', $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return new WP_Error(
                'cdn_http_error',
                sprintf( __( 'The CDN returned HTTP %1$d while fetching %2$s.', 'wp-tac-manager' ), $code, self::CDN_PACKAGE )
            );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) || empty( $data['version'] ) ) {
            return new WP_Error( 'cdn_invalid_response', __( 'The CDN returned an invalid package.json without a version.', 'wp-tac-manager' ) );
        }

        return (string) $data['version'];
    }

    public static function clear_version_cache(): void {
        delete_transient( self::VERSION_CHECK_TRANSIENT );
    }

    private static function save_downloaded_version( string $version ): void {
        update_option( self::VERSION_OPTION, $version, false );
    }

    public static function do_update(): array {
        $latest = self::get_latest_version();
        if ( is_wp_error( $latest ) ) {
            return [
                'success' => false,
                'message' => $latest->get_error_message(),
                'code'    => $latest->get_error_code(),
            ];
        }

        if ( ! preg_match( '/^\d+\.\d+(\.\d+)?$/', (string) $latest ) ) {
            return [
                'success' => false,
                'message' => sprintf(
                    /* translators: versión recibida de la fuente de actualizaciones */
                    __( 'Refusing to update: unexpected version format "%s".', 'wp-tac-manager' ),
                    (string) $latest
                ),
            ];
        }

        if ( ! version_compare( $latest, self::get_bundled_version(), '>' ) ) {
            return [ 'success' => false, 'message' => __( 'You already have the latest version installed.', 'wp-tac-manager' ) ];
        }

        $tag = 'v' . $latest;
        $cdn_base   = self::CDN_FILES . '@' . $tag;
        $github_raw = self::GITHUB_RAW . '/' . $tag;

        $file_map = [
            'assets/js/tarteaucitron/tarteaucitron.js'            => '/tarteaucitron.js',
            'assets/js/tarteaucitron/tarteaucitron.min.js'        => '/tarteaucitron.min.js',
            'assets/js/tarteaucitron/tarteaucitron.services.js'   => '/tarteaucitron.services.js',
            'assets/js/tarteaucitron/tarteaucitron.services.min.js' => '/tarteaucitron.services.min.js',
            'assets/css/tarteaucitron.css'                         => '/tarteaucitron.css',
            'assets/css/tarteaucitron.min.css'                     => '/tarteaucitron.min.css',
        ];

        $lang_files = self::get_lang_file_list();
        foreach ( $lang_files as $lang_file ) {
            $file_map[ 'assets/js/tarteaucitron/lang/' . $lang_file ] = '/lang/' . $lang_file;
        }

        $files = [];
        foreach ( $file_map as $relative_path => $file_suffix ) {
            $files[ $relative_path ] = [
                $cdn_base . $file_suffix,
                $github_raw . $file_suffix,
            ];
        }

        global $wp_filesystem;
        if ( ! $wp_filesystem ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if ( ! $wp_filesystem ) {
            return [ 'success' => false, 'message' => __( 'Could not initialize the filesystem.', 'wp-tac-manager' ) ];
        }

        $plugin_dir = WPTAC_PLUGIN_DIR;
        $errors     = [];
        $temp_files = [];

        // Fase 1: descargar todos los archivos a una ubicación temporal,
        // sin tocar los archivos en producción.
        foreach ( $files as $relative_path => $urls ) {
            $local_file = $plugin_dir . $relative_path;
            $local_dir  = dirname( $local_file );

            if ( ! $wp_filesystem->is_dir( $local_dir ) ) {
                $wp_filesystem->mkdir( $local_dir, FS_CHMOD_DIR );
            }

            $temp_file = $local_file . '.wptac-download';

            $ok = false;
            foreach ( $urls as $remote_url ) {
                $response = wp_remote_get( $remote_url, [
                    'timeout'  => 30,
                    'headers'  => [ 'User-Agent' => 'WP-TAC-Manager/' . WPTAC_VERSION ],
                    'stream'   => true,
                    'filename' => $temp_file,
                ] );

                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                    $ok = true;
                    break;
                }
            }

            if ( ! $ok ) {
                if ( $wp_filesystem->exists( $temp_file ) ) {
                    $wp_filesystem->delete( $temp_file );
                }
                $errors[] = sprintf(
                    __( 'Error downloading %s', 'wp-tac-manager' ),
                    basename( $relative_path )
                );
                continue;
            }

            $temp_files[] = [ $temp_file, $local_file ];
        }

        // Fase 2: si algo falló, limpiar temporales y abortar SIN modificar
        // los archivos en producción (evita bundles con versiones mezcladas).
        if ( ! empty( $errors ) ) {
            foreach ( $temp_files as $pair ) {
                if ( $wp_filesystem->exists( $pair[0] ) ) {
                    $wp_filesystem->delete( $pair[0] );
                }
            }

            $message = sprintf(
                __( 'Update aborted: %1$d files downloaded, %2$d errors. No files were modified.', 'wp-tac-manager' ),
                count( $temp_files ),
                count( $errors )
            );
            return [ 'success' => false, 'message' => $message, 'errors' => $errors ];
        }

        // Fase 3: mover todos los temporales a su destino final.
        $move_errors = [];
        foreach ( $temp_files as $pair ) {
            if ( ! $wp_filesystem->move( $pair[0], $pair[1], true ) ) {
                $move_errors[] = basename( $pair[1] );
            }
        }

        if ( ! empty( $move_errors ) ) {
            foreach ( $temp_files as $pair ) {
                if ( $wp_filesystem->exists( $pair[0] ) ) {
                    $wp_filesystem->delete( $pair[0] );
                }
            }

            return [
                'success' => false,
                'message' => __( 'Update aborted: could not move downloaded files into place.', 'wp-tac-manager' ),
                'errors'  => $move_errors,
            ];
        }

        self::save_downloaded_version( $latest );
        self::clear_version_cache();

        return [
            'success' => true,
            'latest'  => $latest,
            'message' => sprintf(
                __( 'tarteaucitron.js updated to v%s successfully.', 'wp-tac-manager' ),
                $latest
            ),
        ];
    }

    /**
     * Procesa un archivo ZIP subido manualmente e instala tarteaucitron.js.
     *
     * @param array $file Elemento de $_FILES (name, type, tmp_name, error, size).
     * @return array|WP_Error `[ 'version' => string, 'copied' => int, 'warnings' => string[] ]` o error.
     */
    public static function process_zip_upload( array $file ) {
        // 1. Validar errores de subida.
        if ( ! isset( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
            return new WP_Error( 'upload_error', __( 'There was an error uploading the file.', 'wp-tac-manager' ) );
        }

        // 2. Validar extensión .zip.
        $filename = isset( $file['name'] ) ? (string) $file['name'] : '';
        if ( '.zip' !== strtolower( substr( $filename, -4 ) ) ) {
            return new WP_Error( 'invalid_type', __( 'Invalid file type. Please upload a ZIP file.', 'wp-tac-manager' ) );
        }

        // 3. Guard contra archivos irrazonablemente grandes (zip bombs).
        $size = isset( $file['size'] ) ? (int) $file['size'] : 0;
        if ( empty( $size ) || $size > 20 * MB_IN_BYTES ) {
            return new WP_Error( 'too_large', __( 'The uploaded ZIP file is too large.', 'wp-tac-manager' ) );
        }

        // 4. Inicializar el sistema de archivos de WordPress.
        if ( ! function_exists( 'wp_handle_upload' ) || ! function_exists( 'unzip_file' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if ( ! WP_Filesystem() ) {
            return new WP_Error( 'fs_init', __( 'Could not initialize the filesystem.', 'wp-tac-manager' ) );
        }

        global $wp_filesystem;

        if ( ! $wp_filesystem ) {
            return new WP_Error( 'fs_init', __( 'Could not initialize the filesystem.', 'wp-tac-manager' ) );
        }

        // 5. Mover el archivo subido a uploads (valida tipo/extensión con WP).
        $move = wp_handle_upload( $file, [ 'test_form' => false, 'mimes' => [ 'zip' => 'application/zip' ] ] );

        if ( isset( $move['error'] ) ) {
            return new WP_Error( 'upload', $move['error'] );
        }

        $zip_path = $move['file'];

        // 6. Directorio temporal de extracción dentro de wp_upload_dir().
        $upload_dir = wp_upload_dir();
        $extract    = trailingslashit( $upload_dir['basedir'] ) . 'wptac-' . wp_generate_password( 12, false, false ) . '/';

        if ( ! $wp_filesystem->mkdir( $extract, FS_CHMOD_DIR, true ) ) {
            wp_delete_file( $zip_path );
            return new WP_Error( 'mkdir', __( 'Could not create temporary directory for extraction.', 'wp-tac-manager' ) );
        }

        // 7. Rechazar archivos con rutas inseguras (zip-slip) antes de extraer.
        if ( self::zip_has_unsafe_paths( $zip_path ) ) {
            wp_delete_file( $zip_path );
            return new WP_Error( 'unsafe_zip', __( 'The ZIP file contains unsafe file paths.', 'wp-tac-manager' ) );
        }

        // 8. Descomprimir.
        $unzipped = unzip_file( $zip_path, $extract );
        wp_delete_file( $zip_path );

        if ( is_wp_error( $unzipped ) || true !== $unzipped ) {
            $wp_filesystem->delete( $extract, true );
            return new WP_Error( 'unzip', __( 'Could not unzip the file.', 'wp-tac-manager' ) );
        }

        // 9. Localizar la raíz del paquete (soporta carpetas raíz de GitHub).
        $root = self::find_package_root( $extract );

        if ( null === $root ) {
            $wp_filesystem->delete( $extract, true );
            return new WP_Error( 'no_package', __( 'Could not find the tarteaucitron.js package in the ZIP.', 'wp-tac-manager' ) );
        }

        // 9. Leer la versión desde package.json (si existe).
        $version = '';
        $package_json = trailingslashit( $root ) . 'package.json';

        if ( $wp_filesystem->exists( $package_json ) ) {
            $package = json_decode( (string) $wp_filesystem->get_contents( $package_json ), true );
            $version = isset( $package['version'] ) ? sanitize_text_field( (string) $package['version'] ) : '';
        }

        // 10. Copiar archivos principales.
        $file_map = [
            'assets/js/tarteaucitron/tarteaucitron.js'             => 'tarteaucitron.js',
            'assets/js/tarteaucitron/tarteaucitron.min.js'         => 'tarteaucitron.min.js',
            'assets/js/tarteaucitron/tarteaucitron.services.js'    => 'tarteaucitron.services.js',
            'assets/js/tarteaucitron/tarteaucitron.services.min.js' => 'tarteaucitron.services.min.js',
            'assets/css/tarteaucitron.css'                          => 'tarteaucitron.css',
            'assets/css/tarteaucitron.min.css'                      => 'tarteaucitron.min.css',
        ];

        $copied   = 0;
        $warnings = [];

        foreach ( $file_map as $relative => $basename ) {
            $source = trailingslashit( $root ) . $basename;
            $dest   = WPTAC_PLUGIN_DIR . $relative;

            if ( ! $wp_filesystem->is_dir( dirname( $dest ) ) ) {
                $wp_filesystem->mkdir( dirname( $dest ), FS_CHMOD_DIR, true );
            }

            if ( $wp_filesystem->exists( $source ) && $wp_filesystem->copy( $source, $dest, true, FS_CHMOD_FILE ) ) {
                $copied++;
            } else {
                $warnings[] = sprintf( __( 'Failed to copy %s', 'wp-tac-manager' ), $basename );
            }
        }

        // 11. Copiar el directorio /lang/ completo.
        $lang_src = trailingslashit( $root ) . 'lang';
        $lang_dst = WPTAC_PLUGIN_DIR . 'assets/js/tarteaucitron/lang';

        if ( $wp_filesystem->is_dir( $lang_src ) ) {
            if ( ! $wp_filesystem->is_dir( $lang_dst ) ) {
                $wp_filesystem->mkdir( $lang_dst, FS_CHMOD_DIR, true );
            }

            $entries = $wp_filesystem->dirlist( $lang_src );

            if ( is_array( $entries ) ) {
                foreach ( $entries as $name => $info ) {
                    if ( ! isset( $info['type'] ) || 'f' !== $info['type'] ) {
                        continue;
                    }

                    // Solo archivos de idioma .js / .min.js.
                    if ( ! preg_match( '~^tarteaucitron\.[a-z]+(\.min)?\.js$~i', $name ) ) {
                        continue;
                    }

                    if ( $wp_filesystem->copy( trailingslashit( $lang_src ) . $name, trailingslashit( $lang_dst ) . $name, true, FS_CHMOD_FILE ) ) {
                        $copied++;
                    } else {
                        $warnings[] = sprintf( __( 'Failed to copy %s', 'wp-tac-manager' ), $name );
                    }
                }
            }
        }

        // 12. Eliminar el directorio temporal.
        $wp_filesystem->delete( $extract, true );

        if ( $copied <= 0 ) {
            return new WP_Error( 'copy_failed', __( 'No files could be copied from the ZIP.', 'wp-tac-manager' ) );
        }

        // 13. Guardar versión y limpiar cachés.
        if ( '' !== $version ) {
            update_option( self::VERSION_MANUAL_OPTION, $version, false );
            update_option( self::VERSION_OPTION, $version, false );
        }

        self::clear_version_cache();

        return [
            'version'  => $version,
            'copied'   => $copied,
            'warnings' => $warnings,
        ];
    }

    /**
     * Comprueba si un archivo ZIP contiene rutas inseguras (zip-slip).
     * Usa ZipArchive cuando está disponible; en caso contrario asume que no es
     * posible inspeccionarlo y delega la protección en unzip_file() + el copiado
     * con lista blanca de nombres de archivo.
     *
     * @param string $zip_path Ruta al archivo ZIP.
     * @return bool True si contiene rutas inseguras.
     */
    private static function zip_has_unsafe_paths( string $zip_path ): bool {
        if ( ! class_exists( 'ZipArchive' ) ) {
            return false;
        }

        $zip = new ZipArchive();
        if ( true !== $zip->open( $zip_path ) ) {
            return false;
        }

        $unsafe = false;
        for ( $i = 0; $i < $zip->numFiles; $i++ ) {
            $name = (string) $zip->getNameIndex( $i );
            if ( '' === $name ) {
                continue;
            }

            if ( str_contains( $name, '..' ) || str_starts_with( $name, '/' ) || str_contains( $name, '\\' ) ) {
                $unsafe = true;
                break;
            }
        }
        $zip->close();

        return $unsafe;
    }

    /**
     * Localiza la raíz del paquete dentro del directorio extraído.
     * Soporta tanto archivos en la raíz como anidados en una carpeta (p. ej. tarteaucitron.js-master/).
     *
     * @param string $extract Directorio de extracción.
     * @return string|null Ruta a la raíz del paquete o null.
     */
    private static function find_package_root( string $extract ): ?string {
        global $wp_filesystem;

        if ( $wp_filesystem->exists( trailingslashit( $extract ) . 'package.json' )
            || $wp_filesystem->exists( trailingslashit( $extract ) . 'tarteaucitron.js' ) ) {
            return $extract;
        }

        $entries = $wp_filesystem->dirlist( $extract );

        if ( ! is_array( $entries ) ) {
            return null;
        }

        foreach ( $entries as $name => $info ) {
            if ( ! isset( $info['type'] ) || 'd' !== $info['type'] ) {
                continue;
            }

            $candidate = trailingslashit( $extract ) . $name;

            if ( $wp_filesystem->exists( trailingslashit( $candidate ) . 'package.json' )
                || $wp_filesystem->exists( trailingslashit( $candidate ) . 'tarteaucitron.js' ) ) {
                return $candidate;
            }
        }

        return null;
    }

    public static function get_lang_file_list(): array {
        $files = [];
        $found = glob( WPTAC_PLUGIN_DIR . 'assets/js/tarteaucitron/lang/tarteaucitron.*.js' );

        if ( is_array( $found ) ) {
            foreach ( $found as $file ) {
                $files[] = basename( (string) $file );
            }
        }

        sort( $files );

        return $files;
    }
}
