/**
 * WP TAC Manager — Admin JS
 *
 * Gestiona:
 *  - Navegación entre secciones con tabs
 *  - Toggle de servicios (mostrar/ocultar parámetros)
 *  - Selector de icono personalizado (Media Uploader)
 *  - Guardado vía fetch (AJAX) con feedback visual
 *  - Validación básica de formulario antes de enviar
 *
 * Vanilla JS, sin dependencias externas.
 * Compatible con los navegadores soportados por WordPress (ES2020+).
 */

/* global wptacAdmin, wp */

( function () {
    'use strict';

    // ─────────────────────────────────────────────
    // Referencias al DOM
    // ─────────────────────────────────────────────
    const form       = document.getElementById( 'wptac-form' );
    const saveBtn    = document.getElementById( 'wptac-save-btn' );
    const saveStatus = document.getElementById( 'wptac-save-status' );
    const notice     = document.getElementById( 'wptac-notice' );
    const navLinks   = document.querySelectorAll( '.wptac-nav__link' );
    const sections   = document.querySelectorAll( '.wptac-section' );

    if ( ! form ) return; // Salir si no estamos en nuestra página

    // ─────────────────────────────────────────────
    // Navegación entre secciones (tabs)
    // ─────────────────────────────────────────────

    /**
     * Activa la sección indicada y actualiza el estado de los links del nav.
     *
     * @param {string} sectionId - ID de la sección a mostrar (sin '#').
     */
    function activateSection( sectionId ) {
        sections.forEach( ( section ) => {
            section.classList.toggle( 'is-active', section.id === sectionId );
        } );
        navLinks.forEach( ( link ) => {
            const isActive = link.dataset.section === sectionId.replace( 'section-', '' );
            link.classList.toggle( 'is-active', isActive );
            link.setAttribute( 'aria-current', isActive ? 'page' : 'false' );
        } );
        // Sincronizar hash de la URL sin scroll (UX limpia)
        history.replaceState( null, '', '#' + sectionId );
    }

    // Listener de navegación
    navLinks.forEach( ( link ) => {
        link.addEventListener( 'click', ( e ) => {
            e.preventDefault();
            const sectionId = 'section-' + link.dataset.section;
            activateSection( sectionId );
        } );
    } );

    // Restaurar sección desde hash de URL al cargar
    const hashSection = window.location.hash.replace( '#', '' );
    if ( hashSection && document.getElementById( hashSection ) ) {
        activateSection( hashSection );
    }

    // ─────────────────────────────────────────────
    // Toggle de parámetros de servicios
    // ─────────────────────────────────────────────

    /**
     * Muestra u oculta los parámetros de un servicio y
     * actualiza los atributos ARIA correctamente.
     *
     * @param {HTMLInputElement} toggleInput - Checkbox del servicio.
     */
    function updateServiceState( toggleInput ) {
        const serviceKey   = toggleInput.dataset.service;
        const paramsPanel  = document.getElementById( 'service-' + serviceKey + '-params' );
        const serviceCard  = document.getElementById( 'service-' + serviceKey );
        const isEnabled    = toggleInput.checked;

        if ( paramsPanel ) {
            paramsPanel.classList.toggle( 'is-hidden', ! isEnabled );
            paramsPanel.setAttribute( 'aria-hidden', String( ! isEnabled ) );

            // Habilitar/deshabilitar los inputs según el estado
            // para que los campos required no bloqueen si el servicio está desactivado
            paramsPanel.querySelectorAll( 'input, select, textarea' ).forEach( ( input ) => {
                input.disabled = ! isEnabled;
            } );
        }

        if ( serviceCard ) {
            serviceCard.classList.toggle( 'is-active', isEnabled );
        }

        toggleInput.setAttribute( 'aria-expanded', String( isEnabled ) );
    }

    // Inicializar estado de todos los toggles al cargar
    document.querySelectorAll( '.wptac-service__toggle' ).forEach( ( toggle ) => {
        updateServiceState( toggle );
        toggle.addEventListener( 'change', () => updateServiceState( toggle ) );
    } );

    // Sincronizar aria-checked en todos los checkboxes tipo switch
    document.querySelectorAll( '[role="switch"]' ).forEach( ( sw ) => {
        sw.addEventListener( 'change', () => {
            sw.setAttribute( 'aria-checked', String( sw.checked ) );
        } );
    } );

    // ─────────────────────────────────────────────
    // Serialización del formulario
    // ─────────────────────────────────────────────

    /**
     * Convierte los datos del formulario en un objeto JSON anidado
     * respetando la notación array de PHP: name="general[privacy_url]"
     *
     * @returns {Object} Objeto con la configuración lista para enviar.
     */
    function serializeFormToObject() {
        const formData = new FormData( form );
        const result   = {};

        // Primero establecemos false para todos los checkboxes conocidos
        // (los checkboxes desmarcados no aparecen en FormData)
        form.querySelectorAll( 'input[type="checkbox"]' ).forEach( ( cb ) => {
            const match = cb.name.match( /^(general|services\[(\w+)\])\[(\w+)\]$/ );
            if ( ! match ) return;

            if ( cb.name.startsWith( 'general[' ) ) {
                const key = cb.name.slice( 8, -1 );
                if ( ! result.general ) result.general = {};
                result.general[ key ] = false;
            } else if ( cb.name.startsWith( 'services[' ) ) {
                const svcMatch = cb.name.match( /^services\[(\w+)\]\[(\w+)\]$/ );
                if ( svcMatch ) {
                    const [ , svcKey, paramKey ] = svcMatch;
                    if ( ! result.services ) result.services = {};
                    if ( ! result.services[ svcKey ] ) result.services[ svcKey ] = {};
                    result.services[ svcKey ][ paramKey ] = false;
                }
            }
        } );

        // Sobrescribir con los valores reales del FormData
        for ( const [ name, value ] of formData.entries() ) {
            if ( name === '_wpnonce' ) continue;

            // colors[alert_big_bg]
            const colorsMatch  = name.match( /^colors\[(\w+)\]$/ );
            const textsMatch   = name.match( /^texts\[(\w+)\]$/ );
            const generalMatch = name.match( /^general\[(\w+)\]$/ );
            const serviceMatch = name.match( /^services\[(\w+)\]\[(\w+)\]$/ );
            const serviceParam = name.match( /^services\[(\w+)\]\[params\]\[(\w+)\]$/ );

            if ( colorsMatch ) {
                if ( ! result.colors ) result.colors = {};
                result.colors[ colorsMatch[1] ] = value;

            } else if ( textsMatch ) {
                if ( ! result.texts ) result.texts = {};
                result.texts[ textsMatch[1] ] = value;

            } else if ( generalMatch ) {
                if ( ! result.general ) result.general = {};
                result.general[ generalMatch[1] ] = value;

            } else if ( serviceMatch ) {
                const [ , svcKey, subKey ] = serviceMatch;
                if ( ! result.services ) result.services = {};
                if ( ! result.services[ svcKey ] ) result.services[ svcKey ] = {};
                result.services[ svcKey ][ subKey ] = value;

            } else if ( serviceParam ) {
                const [ , svcKey, paramKey ] = serviceParam;
                if ( ! result.services ) result.services = {};
                if ( ! result.services[ svcKey ] ) result.services[ svcKey ] = {};
                if ( ! result.services[ svcKey ].params ) result.services[ svcKey ].params = {};
                result.services[ svcKey ].params[ paramKey ] = value;
            }
        }

        return result;
    }

    // ─────────────────────────────────────────────
    // Feedback visual del guardado
    // ─────────────────────────────────────────────

    function showSaving() {
        saveBtn.disabled = true;
        saveStatus.textContent = wptacAdmin.i18n.saving;
        saveStatus.className   = 'wptac-save-status is-saving';
        hideNotice();
    }

    function showSaved( message ) {
        saveBtn.disabled = false;
        saveStatus.textContent = message || wptacAdmin.i18n.saved;
        saveStatus.className   = 'wptac-save-status is-saved';
        // Limpiar el mensaje después de 4 segundos
        setTimeout( () => {
            saveStatus.textContent = '';
            saveStatus.className   = 'wptac-save-status';
        }, 4000 );
    }

    function showError( message ) {
        saveBtn.disabled = false;
        saveStatus.textContent = message || wptacAdmin.i18n.error;
        saveStatus.className   = 'wptac-save-status is-error';
        showNotice( message || wptacAdmin.i18n.error, 'error' );
    }

    function showNotice( message, type = 'info' ) {
        notice.textContent = message;
        notice.className   = 'wptac-notice wptac-notice--' + type;
        notice.hidden      = false;
    }

    function hideNotice() {
        notice.hidden = true;
    }

    // ─────────────────────────────────────────────
    // Guardado vía AJAX
    // ─────────────────────────────────────────────

    form.addEventListener( 'submit', async ( e ) => {
        e.preventDefault();

        // Validación HTML5 nativa
        if ( ! form.checkValidity() ) {
            form.reportValidity();
            return;
        }

        showSaving();

        const payload = serializeFormToObject();

        try {
            // Enviamos action y nonce como parámetros de URL:
            // admin-ajax.php los lee de $_GET antes de parsear el body,
            // así wp_verify_nonce() los encuentra independientemente del Content-Type.
            const url = new URL( wptacAdmin.ajaxUrl );
            url.searchParams.set( 'action', 'wptac_save_settings' );
            url.searchParams.set( 'nonce',  wptacAdmin.nonce );

            const response = await fetch( url.toString(), {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce':   wptacAdmin.nonce, // Cabecera adicional (leída por el handler PHP)
                },
                body: JSON.stringify( payload ), // Solo datos de configuración, sin action/nonce
                credentials: 'same-origin',      // Incluir cookies de sesión de WordPress
            } );

            if ( ! response.ok ) {
                throw new Error( `HTTP ${ response.status }` );
            }

            const data = await response.json();

            if ( data.success ) {
                showSaved( data.data?.message );
            } else {
                showError( data.data?.message );
            }

        } catch ( error ) {
            console.error( '[WP TAC Manager]', error );
            showError( wptacAdmin.i18n.error );
        }
    } );

    // ─────────────────────────────────────────────
    // Comprobación / actualización de tarteaucitron.js
    // ─────────────────────────────────────────────

    const btnCheck  = document.getElementById( 'wptac-btn-check' );
    const btnUpdate = document.getElementById( 'wptac-btn-update' );
    const elLatest  = document.getElementById( 'wptac-latest-version' );
    const elStatus  = document.getElementById( 'wptac-update-status' );

    if ( btnCheck ) {
        btnCheck.addEventListener( 'click', async () => {
            btnCheck.disabled = true;
            btnCheck.innerHTML = '<span class="dashicons dashicons-update wptac-spin"></span> ' + wptacAdmin.i18n.checking;
            elStatus.hidden = true;

            try {
                const url = new URL( wptacAdmin.ajaxUrl );
                url.searchParams.set( 'action', 'wptac_check_update' );
                url.searchParams.set( 'nonce', wptacAdmin.nonce );

                const response = await fetch( url.toString(), {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': wptacAdmin.nonce,
                    },
                    credentials: 'same-origin',
                } );

                const data = await response.json();

                if ( data.success && data.data ) {
                    const d = data.data;
                    if ( d.latest ) {
                        elLatest.innerHTML = '<strong>' + d.latest + '</strong>';

                        if ( d.needs_update ) {
                            elStatus.className = 'wptac-update__status is-info';
                            elStatus.textContent = wptacAdmin.i18n.updateAvailable;
                            elStatus.hidden = false;
                            btnUpdate.hidden = false;
                        } else {
                            elStatus.className = 'wptac-update__status is-success';
                            elStatus.textContent = wptacAdmin.i18n.uptodate;
                            elStatus.hidden = false;
                            btnUpdate.hidden = true;
                        }
                    } else {
                        elLatest.innerHTML = '<span class="wptac-update__unknown">' + wptacAdmin.i18n.checkFailed + '</span>';
                    }
                } else {
                    elLatest.innerHTML = '<span class="wptac-update__unknown">' + wptacAdmin.i18n.checkFailed + '</span>';
                }
            } catch ( err ) {
                elLatest.innerHTML = '<span class="wptac-update__unknown">' + wptacAdmin.i18n.checkFailed + '</span>';
            }

            btnCheck.disabled = false;
            btnCheck.innerHTML = '<span class="dashicons dashicons-update"></span> ' + ( btnCheck.dataset.label || wptacAdmin.i18n.checking );
        } );

        // Save original label
        btnCheck.dataset.label = btnCheck.textContent.trim();
    }

    if ( btnUpdate ) {
        btnUpdate.addEventListener( 'click', async () => {
            btnUpdate.disabled = true;
            btnUpdate.innerHTML = '<span class="dashicons dashicons-download wptac-spin"></span> ' + wptacAdmin.i18n.updating;

            try {
                const url = new URL( wptacAdmin.ajaxUrl );
                url.searchParams.set( 'action', 'wptac_do_update' );
                url.searchParams.set( 'nonce', wptacAdmin.nonce );

                const response = await fetch( url.toString(), {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': wptacAdmin.nonce,
                    },
                    credentials: 'same-origin',
                } );

                const data = await response.json();

                if ( data.success ) {
                    elStatus.className = 'wptac-update__status is-success';
                    elStatus.textContent = data.data?.message || wptacAdmin.i18n.updateDone;
                    elStatus.hidden = false;
                    btnUpdate.hidden = true;
                    // Update bundled version display
                    const bundledEl = document.getElementById( 'wptac-bundled-version' );
                    if ( bundledEl && data.data?.latest ) {
                        bundledEl.innerHTML = '<strong>' + data.data.latest + '</strong>';
                    }
                } else {
                    elStatus.className = 'wptac-update__status is-error';
                    elStatus.textContent = data.data?.message || wptacAdmin.i18n.updateError;
                    elStatus.hidden = false;
                }
            } catch ( err ) {
                elStatus.className = 'wptac-update__status is-error';
                elStatus.textContent = wptacAdmin.i18n.updateError;
                elStatus.hidden = false;
            }

            btnUpdate.disabled = false;
            btnUpdate.innerHTML = '<span class="dashicons dashicons-download"></span> ' + wptacAdmin.i18n.updating;
        } );
    }

    // ─────────────────────────────────────────────
    // Indicador de cambios sin guardar
    // ─────────────────────────────────────────────

    let hasUnsavedChanges = false;

    form.addEventListener( 'change', () => {
        hasUnsavedChanges = true;
    } );

    form.addEventListener( 'submit', () => {
        hasUnsavedChanges = false;
    } );

    window.addEventListener( 'beforeunload', ( e ) => {
        if ( hasUnsavedChanges ) {
            // El mensaje estándar del navegador (no personalizable en navegadores modernos)
            e.preventDefault();
            e.returnValue = '';
        }
    } );

    // ─────────────────────────────────────────────
    // Selector de icono personalizado (Media Uploader)
    // ─────────────────────────────────────────────

    const iconSelectBtn = document.getElementById( 'wptac-icon-select' );
    const iconRemoveBtn = document.getElementById( 'wptac-icon-remove' );
    const iconInput     = document.getElementById( 'general_custom_icon' );
    const iconPreview   = document.getElementById( 'wptac-icon-preview' );

    if ( iconSelectBtn && iconInput ) {
        let mediaFrame = null;

        iconSelectBtn.addEventListener( 'click', ( e ) => {
            e.preventDefault();

            if ( mediaFrame ) {
                mediaFrame.open();
                return;
            }

            mediaFrame = wp.media( {
                title:    wptacAdmin.i18n.selectIcon,
                button:   { text: wptacAdmin.i18n.useAsIcon },
                multiple: false,
                library:  { type: 'image' },
            } );

            mediaFrame.on( 'select', () => {
                const attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
                iconInput.value = attachment.id;
                iconPreview.innerHTML = '<img src="' + attachment.url + '" class="wptac-media__image" alt="">';
                iconPreview.hidden = false;
                iconRemoveBtn.hidden = false;
                hasUnsavedChanges = true;
            } );

            mediaFrame.open();
        } );

        if ( iconRemoveBtn ) {
            iconRemoveBtn.addEventListener( 'click', ( e ) => {
                e.preventDefault();
                iconInput.value = '0';
                iconPreview.innerHTML = '';
                iconPreview.hidden = true;
                iconRemoveBtn.hidden = true;
                hasUnsavedChanges = true;
            } );
        }
    }

} )();

// ── Color picker initialization (requires jQuery via wp-color-picker) ──
( function ( $ ) {
    'use strict';

    var $colorPickers = $( '.wptac-color-picker' );
    if ( ! $colorPickers.length ) {
        return;
    }

    $colorPickers.wpColorPicker();

    $( '#wptac-reset-colors' ).on( 'click', function () {
        $colorPickers.each( function () {
            var $this   = $( this );
            var defaultVal = $this.data( 'default' );
            if ( defaultVal ) {
                $this.wpColorPicker( 'color', defaultVal );
            }
        } );
    } );

    // ── Language switcher on Texts tab ──
    var langSelect = document.getElementById( 'wptac-text-lang' );
    var langFields = document.querySelectorAll( '.wptac-lang-fields' );

    if ( langSelect && langFields.length ) {
        langSelect.addEventListener( 'change', function () {
            var selected = this.value;
            langFields.forEach( function ( el ) {
                el.hidden = el.dataset.lang !== selected;
            } );
        } );
    }

} )( jQuery );
