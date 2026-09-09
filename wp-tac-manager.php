<?php
/**
 * Plugin Name:       WP TAC Manager
 * Plugin URI:        https://github.com/espagnexport/wp-tac-manager
 * Description:       Integración de Tarte au Citron (tarteaucitron.js) con panel de administración para gestionar servicios de cookies desde el back-end de WordPress.
 * Version:           2.2.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Rafael Verde
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-tac-manager
 * Domain Path:       /lang
 * Consent API:       true
 * 
 * @package WpTacManager
 */

// Evitar acceso directo al archivo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─────────────────────────────────────────────
// Constantes del plugin
// ─────────────────────────────────────────────
define( 'WPTAC_VERSION',     '2.2.0' );
define( 'WPTAC_TARTEAUCITRON_VERSION', '1.32.0' );
define( 'WPTAC_PLUGIN_FILE', __FILE__ );
define( 'WPTAC_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WPTAC_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'WPTAC_OPTION_KEY',  'wptac_settings' );

// ─────────────────────────────────────────────
// Cargar autoloader de Composer
// ─────────────────────────────────────────────
$composer_autoload = WPTAC_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
    require_once $composer_autoload;
}

// ─────────────────────────────────────────────
// Autoload de clases internas del plugin
// ─────────────────────────────────────────────
spl_autoload_register(
	function ( string $class_name ): void {
		if ( strpos( $class_name, 'WPTAC_' ) !== 0 ) {
			return;
		}

		$file = 'class-tac-' . strtolower( str_replace( [ 'WPTAC_', '_' ], [ '', '-' ], $class_name ) ) . '.php';
		$path = WPTAC_PLUGIN_DIR . 'includes/' . $file;

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

// ─────────────────────────────────────────────
// Inicialización del Actualizador de GitHub (Fuera de hooks tardíos)
// ─────────────────────────────────────────────
if ( class_exists( \YahnisElsts\PluginUpdateChecker\v5\PucFactory::class ) ) {
	$wptac_updater = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/espagnexport/wp-tac-manager',
		WPTAC_PLUGIN_FILE,
		'wp-tac-manager'
	);

	// Activar soporte para Releases/Tags de GitHub en lugar de forzar la rama main
	$wptac_updater->getVcsApi()->enableReleaseAssets();

	if ( defined( 'WP_TAC_MANAGER_GITHUB_TOKEN' ) && WP_TAC_MANAGER_GITHUB_TOKEN ) {
		$wptac_updater->setAuthentication( WP_TAC_MANAGER_GITHUB_TOKEN );
	}
}

// ─────────────────────────────────────────────
// Hooks de activación y desactivación
// ─────────────────────────────────────────────
register_activation_hook(
	__FILE__,
	function (): void {
		if ( ! get_option( WPTAC_OPTION_KEY ) ) {
			$defaults = WPTAC_Settings::get_defaults();
			add_option( WPTAC_OPTION_KEY, $defaults, '', false );
		}
	}
);

register_deactivation_hook(
	__FILE__,
	function (): void {
		// Intencionalmente vacío para preservar la configuración del usuario.
	}
);

// ─────────────────────────────────────────────
// Arranque del plugin
// ─────────────────────────────────────────────
add_action(
	'init',
	function (): void {
		// Cargar i18n / traducciones
		load_plugin_textdomain(
			'wp-tac-manager',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/lang'
		);
	}
);

add_action(
	'plugins_loaded',
	function (): void {
		// Módulo de administración
		if ( is_admin() ) {
			new WPTAC_Admin();
		}

		// Módulo de renderizado en el front-end
		new WPTAC_Renderer();

		// Declarar compatibilidad con WP Consent API
		if ( function_exists( 'wp_consent_api_registered' ) ) {
			wp_consent_api_registered( 'wp-tac-manager' );
		}
	},
	10
);