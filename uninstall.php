<?php
/**
 * Uninstall script de WP TAC Manager.
 *
 * Se ejecuta automáticamente cuando el usuario elimina el plugin
 * desde el panel de WordPress.
 *
 * ⚠️  Este archivo BORRA los datos del plugin de la base de datos.
 *     Está diseñado así intencionalmente para dejar la BD limpia.
 */

// Bloquear acceso directo (WordPress define WP_UNINSTALL_PLUGIN al llamar este archivo)
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Eliminar la opción principal del plugin
delete_option( 'wptac_settings' );

// Eliminar opciones heredadas del actualizador de tarteaucitron.js
delete_option( 'wptac_tarteaucitron_latest_version' );
delete_option( 'wptac_tarteaucitron_manual_version' );
delete_transient( 'wptac_tarteaucitron_version_check' );

// Eliminar los contadores de estadísticas (wptac_stat_*)
$wptac_stat_options = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
        $wpdb->esc_like( 'wptac_stat_' ) . '%'
    )
);
foreach ( $wptac_stat_options as $wptac_option ) {
    delete_option( $wptac_option );
}

// Eliminar los transients del límite de peticiones (wptac_rate_limit_*)
$wptac_rate_transients = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
        $wpdb->esc_like( '_transient_wptac_rate_limit_' ) . '%'
    )
);
foreach ( $wptac_rate_transients as $wptac_transient ) {
    delete_transient( substr( $wptac_transient, strlen( '_transient_' ) ) );
}
