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

// Eliminar la opción principal del plugin
delete_option( 'wptac_settings' );

// Eliminar opciones del actualizador de tarteaucitron.js
delete_option( 'wptac_tarteaucitron_latest_version' );
delete_option( 'wptac_tarteaucitron_manual_version' );
delete_transient( 'wptac_tarteaucitron_version_check' );

// Si en el futuro se añaden más opciones, borrarlas aquí:
// delete_option( 'wptac_otra_opcion' );
