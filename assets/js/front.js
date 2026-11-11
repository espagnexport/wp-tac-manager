( function () {
    'use strict';

    var settings = typeof wptacFront !== 'undefined' ? wptacFront : {};

    if ( ! settings || ! settings.services ) {
        return;
    }

    // ── Body attribute: banner visibility ──
    document.body.setAttribute( 'data-wptac-banner-visible', '1' );

    window.addEventListener( 'tac.close_alert', function () {
        document.body.setAttribute( 'data-wptac-banner-visible', '0' );
    } );

    // ── Body attributes per service ──
    settings.services.forEach( function ( serviceKey ) {
        ( function ( key ) {
            document.addEventListener( key + '_allowed', function () {
                document.body.setAttribute( 'data-wptac-service-' + key, '1' );
            } );
            document.addEventListener( key + '_disallowed', function () {
                document.body.setAttribute( 'data-wptac-service-' + key, '0' );
            } );
        } )( serviceKey );
    } );

    // ── Data attribute interactions ──
    document.body.addEventListener( 'click', function ( e ) {
        var target = e.target;

        while ( target && target !== document.body ) {
            var allowService = target.getAttribute( 'data-wptac-allow-service' );
            if ( allowService ) {
                e.preventDefault();
                tarteaucitron.userInterface.respond(
                    document.getElementById( allowService + 'Allowed' ),
                    true
                );
                return;
            }

            var openPanel = target.getAttribute( 'data-wptac-open-panel' );
            if ( openPanel !== null ) {
                e.preventDefault();
                tarteaucitron.userInterface.openPanel();
                return;
            }

            var closePanel = target.getAttribute( 'data-wptac-close-panel' );
            if ( closePanel !== null ) {
                e.preventDefault();
                tarteaucitron.userInterface.closePanel();
                return;
            }

            target = target.parentNode;
        }
    } );

    // ── Iframe lazy-load based on consent ──
    settings.services.forEach( function ( serviceKey ) {
        ( function ( key ) {
            document.addEventListener( key + '_allowed', function () {
                var iframes = document.querySelectorAll(
                    'iframe[data-src][data-wptac-service="' + key + '"]'
                );
                Array.prototype.forEach.call( iframes, function ( el ) {
                    el.setAttribute( 'src', el.getAttribute( 'data-src' ) );
                } );
            } );
            document.addEventListener( key + '_disallowed', function () {
                var iframes = document.querySelectorAll(
                    'iframe[src][data-wptac-service="' + key + '"]'
                );
                Array.prototype.forEach.call( iframes, function ( el ) {
                    el.setAttribute( 'data-src', el.getAttribute( 'src' ) );
                    el.removeAttribute( 'src' );
                } );
            } );
        } )( serviceKey );
    } );

    // Google Consent Mode v2 lo gestiona tarteaucitron de forma nativa y
    // granular (opción googleConsentMode), por lo que no se duplica aquí.

    // ── Stats tracking via AJAX ──
    // Las decisiones se agrupan y se envían en una sola petición (p. ej.
    // "Aceptar todo" genera una decisión por servicio), para no agotar el
    // límite de peticiones por IP del servidor.
    var pendingConsents = {};
    var flushTimer      = null;

    function wptacFlushConsents() {
        var keys = Object.keys( pendingConsents );

        if ( flushTimer ) {
            clearTimeout( flushTimer );
            flushTimer = null;
        }
        if ( ! settings.ajaxUrl || ! keys.length ) {
            return;
        }

        var data = new FormData();
        data.append( 'action', 'wptac_track_consent' );
        keys.forEach( function ( key ) {
            data.append( 'consents[' + key + ']', pendingConsents[ key ] ? '1' : '0' );
        } );
        pendingConsents = {};

        if ( typeof navigator.sendBeacon === 'function' ) {
            navigator.sendBeacon( settings.ajaxUrl, data );
        } else {
            fetch( settings.ajaxUrl, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                keepalive: true
            } );
        }
    }

    function wptacTrackConsent( service, allowed ) {
        pendingConsents[ service ] = allowed;
        if ( ! flushTimer ) {
            flushTimer = setTimeout( wptacFlushConsents, 500 );
        }
    }

    // Si el usuario abandona la página antes del envío, enviar lo pendiente.
    window.addEventListener( 'pagehide', wptacFlushConsents );

    // Decorate tarteaucitron.cookie.create to capture explicit user responses only
    if ( typeof tarteaucitron !== 'undefined' && tarteaucitron.cookie && typeof tarteaucitron.cookie.create === 'function' ) {
        var originalCreate = tarteaucitron.cookie.create;
        tarteaucitron.cookie.create = function ( key, status ) {
            originalCreate.call( this, key, status );
            if ( status === 'true' || status === true || status === 'false' || status === false ) {
                var allowed = ( status === 'true' || status === true );
                wptacTrackConsent( key, allowed );
            }
        };
    }
} )();
