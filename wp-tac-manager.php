<?php
/**
 * Plugin Name:       WP TAC Manager
 * Plugin URI:        https://github.com/espagnexport/wp-tac-manager
 * Description:       Integración de Tarte au Citron (tarteaucitron.js) con panel de administración para gestionar servicios de cookies desde el back-end de WordPress.
 * Version:           1.6.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Rafael Verde
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-tac-manager
 * Domain Path:       /lang
 */

// Evitar acceso directo al archivo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─────────────────────────────────────────────
// Constantes del plugin
// ─────────────────────────────────────────────
define( 'WPTAC_VERSION',     '1.6.0' );
define( 'WPTAC_TARTEAUCITRON_VERSION', '1.32.0' );
define( 'WPTAC_PLUGIN_FILE', __FILE__ );
define( 'WPTAC_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WPTAC_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'WPTAC_OPTION_KEY',  'wptac_settings' );

// ─────────────────────────────────────────────
// Autoload de clases del plugin
// ─────────────────────────────────────────────
spl_autoload_register( function ( string $class_name ): void {
    // Solo cargamos clases con prefijo WPTAC_
    if ( strpos( $class_name, 'WPTAC_' ) !== 0 ) {
        return;
    }
    // Convierte WPTAC_Admin → class-tac-admin.php
    $file = 'class-tac-' . strtolower( str_replace( [ 'WPTAC_', '_' ], [ '', '-' ], $class_name ) ) . '.php';
    $path = WPTAC_PLUGIN_DIR . 'includes/' . $file;

    if ( file_exists( $path ) ) {
        require_once $path;
    }
} );

// ─────────────────────────────────────────────
// Cargar autoloader de Composer
// ─────────────────────────────────────────────
$composer_autoload = WPTAC_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
    require_once $composer_autoload;
}

// ─────────────────────────────────────────────
// Inicializar actualizador desde GitHub
// ─────────────────────────────────────────────
add_action( 'init', function (): void {
    if ( ! class_exists( \YahnisElsts\PluginUpdateChecker\v5\PucFactory::class ) ) {
        return;
    }

    $updater = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/espagnexport/wp-tac-manager',
        WPTAC_PLUGIN_FILE,
        'wp-tac-manager'
    );

    if ( defined( 'WP_TAC_MANAGER_GITHUB_TOKEN' ) && WP_TAC_MANAGER_GITHUB_TOKEN ) {
        $updater->setAuthentication( WP_TAC_MANAGER_GITHUB_TOKEN );
    }
} );

// ─────────────────────────────────────────────
// Hooks de activación / desactivación
// ─────────────────────────────────────────────

/**
 * Se ejecuta al activar el plugin.
 * Establece opciones por defecto si no existen.
 */
register_activation_hook( __FILE__, function (): void {
    if ( ! get_option( WPTAC_OPTION_KEY ) ) {
        $defaults = WPTAC_Settings::get_defaults();
        add_option( WPTAC_OPTION_KEY, $defaults, '', false );
    }
    // Flush rewrite rules por precaución
    flush_rewrite_rules();
} );

/**
 * Se ejecuta al desactivar el plugin.
 * NO borramos las opciones para preservar la config del usuario.
 */
register_deactivation_hook( __FILE__, function (): void {
    flush_rewrite_rules();
} );

// ─────────────────────────────────────────────
// Arranque del plugin
// ─────────────────────────────────────────────

/**
 * Inicializa todos los módulos del plugin después de que
 * WordPress haya cargado todos los plugins (hook 'plugins_loaded').
 */
add_action( 'plugins_loaded', function (): void {
    // Cargar traducciones
    load_plugin_textdomain(
        'wp-tac-manager',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/lang'
    );

    // Módulo de administración (solo en el back-end)
    if ( is_admin() ) {
        new WPTAC_Admin();
    }

    // Módulo de renderizado en el front-end
    new WPTAC_Renderer();
}, 10 );
