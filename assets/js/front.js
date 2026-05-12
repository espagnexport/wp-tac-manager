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
        var icon = document.querySelector( '#tarteaucitronRoot .tarteaucitronIcon' );
        if ( icon ) { icon.style.display = 'none'; }
    } );

    // ── Body attributes per service + stats tracking ──
    settings.services.forEach( function ( serviceKey ) {
        ( function ( key ) {
            document.addEventListener( key + '_allowed', function () {
                document.body.setAttribute( 'data-wptac-service-' + key, '1' );
                wptacTrackConsent( key, 1 );
            } );
            document.addEventListener( key + '_disallowed', function () {
                document.body.setAttribute( 'data-wptac-service-' + key, '0' );
                wptacTrackConsent( key, 0 );
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

    // ── Stats tracking via AJAX ──
    function wptacTrackConsent( service, allowed ) {
        if ( ! settings.ajaxUrl ) {
            return;
        }

        var data = new FormData();
        data.append( 'action', 'wptac_track_consent' );
        data.append( 'service', service );
        data.append( 'status', allowed ? '1' : '0' );
        data.append( '_ajax_nonce', settings.nonce );

        if ( typeof navigator.sendBeacon === 'function' ) {
            navigator.sendBeacon( settings.ajaxUrl, data );
        } else {
            fetch( settings.ajaxUrl, {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            } );
        }
    }
} )();
