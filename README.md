# WP TAC Manager

Plugin WordPress para integrar [Tarte au Citron](https://tarteaucitron.io/) con panel de administración completo para gestionar servicios de cookies desde el back-end de WordPress.

## Características

- ✅ **tarteaucitron.js bundleado localmente** (v1.32.0) — sin dependencias de CDN externas
- ✅ **Actualizador automático** de tarteaucitron.js desde GitHub con un clic
- ✅ **Actualizador del plugin** desde GitHub Releases mediante plugin-update-checker
- ✅ **28 servicios predefinidos**: Google Tag Manager, GA4, Google Ads, Facebook Pixel, LinkedIn Insight Tag, Hotjar, Matomo Cloud, Plausible, Twitter UWT, HubSpot, YouTube, Vimeo, Google Maps, reCAPTCHA, Instagram, TikTok, Microsoft Clarity, Bing Ads, Crisp Chat, Google Fonts, Disqus, Pinterest, Stripe, PayPal, Spotify, SoundCloud, Dailymotion, Twitch
- ✅ **Panel de administración** moderno con navegación por tabs (General, Servicios, Actualizaciones, Colores, Textos)
- ✅ **Personalización completa de colores**: fondo, texto, bordes de botones (Aceptar/Denegar), panel, icono
- ✅ **Textos personalizados por idioma** mediante `tarteaucitronCustomText` (formato JSON) — se fusionan con la traducción activa
- ✅ **Traducciones automáticas** del banner según el idioma del sitio
- ✅ **Estadísticas de consentimiento** con widget en el escritorio
- ✅ **Limitación de tasa** (rate limiting) en el endpoint de consentimiento
- ✅ **Seguridad**: nonces CSRF, `current_user_cap()`, sanitización completa, escapado de salida
- ✅ **AJAX sin recarga** — guardado con feedback visual inmediato
- ✅ **Generación dinámica del init JS** — sin archivos cacheados problemáticos
- ✅ **Idioma automático** desde la configuración de WordPress
- ✅ **GDPR best practices** — highPrivacy=true, DenyAllCta=true, AcceptAllCta=true
- ✅ **Integración con cachés**: exclusiones para Autoptimize, WP Rocket, LiteSpeed Cache, W3 Total Cache
- ✅ **Iframes lazy-load** basado en consentimiento
- ✅ **Soporte multilingüe** compatible con Bogo, Polylang, WPML (la URL de política de privacidad se obtiene automáticamente de WordPress)
- ✅ **Personalización por JSON**: cualquier opción de tarteaucitron.js se puede sobrescribir (privacyUrl, textos, colores, etc.)

## Instalación

1. Sube la carpeta `wp-tac-manager` a `/wp-content/plugins/`
2. Activa el plugin desde **Plugins → Plugins instalados**
3. Ve a **TAC Manager → Ajustes**

## Servicios incluidos

| Servicio | Categoría | Parámetros |
|----------|-----------|------------|
| Google Tag Manager | Analítica | ID del contenedor |
| Google Analytics 4 | Analítica | Measurement ID |
| Google Ads | Analítica | ID de conversión |
| Microsoft Clarity | Analítica | ID del proyecto |
| Bing Ads | Analítica | UET Tag ID |
| Hotjar | Analítica | Site ID |
| Matomo Cloud | Analítica | Site ID + URL del servidor |
| Plausible | Analítica | Dominio |
| Facebook Pixel | Social | Pixel ID |
| LinkedIn Insight Tag | Social | Partner ID |
| Twitter UWT | Social | Pixel ID |
| Instagram | Social | — |
| TikTok | Social | Pixel ID |
| Pinterest | Social | — |
| YouTube | Video | — |
| Vimeo | Video | — |
| Dailymotion | Video | — |
| Twitch | Video | — |
| Google Maps | API | API Key |
| reCAPTCHA | API | Site Key |
| Stripe | API | — |
| PayPal | API | — |
| HubSpot | API | HubSpot ID |
| Crisp Chat | Otros | Website ID |
| Google Fonts | Otros | Familias |
| Disqus | Comentarios | Shortname |
| Spotify | Otros | — |
| SoundCloud | Otros | — |

## Personalización de textos

Ve a **TAC Manager → Ajustes → Textos** y selecciona un idioma. Introduce un JSON con las claves que quieras sobrescribir:

```json
{"acceptAll": "¡Vamos!", "denyAll": "No, gracias"}
```

Los valores se fusionan con la traducción activa del banner mediante `tarteaucitronCustomText`. Solo las claves especificadas se sobrescriben.

## Personalización de colores

Ve a **TAC Manager → Ajustes → Colores** para personalizar:
- Fondo y texto del banner principal
- Fondo, borde y texto del botón **Aceptar**
- Fondo, borde y texto del botón **Denegar**
- Fondo y texto del panel de preferencias
- Fondo del icono flotante

## Actualizaciones

- **tarteaucitron.js**: Ve a **TAC Manager → Ajustes → Actualizaciones** y haz clic en "Comprobar" para buscar nuevas versiones. "Actualizar ahora" descarga e instala los archivos automáticamente.
- **Plugin**: Las actualizaciones se entregan mediante GitHub Releases. Aparecen automáticamente en **Plugins** cuando hay una nueva versión.

## Seguridad implementada

| Medida | Dónde |
|---|---|
| `check_ajax_referer()` en todos los endpoints AJAX | `WPTAC_Admin` |
| `current_user_can('manage_options')` | Menú admin + AJAX + render |
| `sanitize_text_field()` / `esc_url_raw()` / `sanitize_key()` / `absint()` | `WPTAC_Settings::sanitize()` |
| `wp_json_encode()` con `JSON_HEX_TAG \| JSON_HEX_APOS \| JSON_HEX_AMP` | `WPTAC_Renderer` |
| Lista blanca de orientaciones, idiomas y posiciones | `WPTAC_Settings::sanitize()` |
| Lista blanca de servicios conocidos | `WPTAC_Settings::sanitize_services()` |
| Validación de colores hex | `WPTAC_Settings::sanitize_colors()` |
| `esc_html()` / `esc_attr()` / `esc_url()` / `esc_textarea()` en todas las vistas | `settings-page.php` |
| Rate limiting (10 req/min por IP) | `WPTAC_Admin::check_rate_limit()` |
| `WP_UNINSTALL_PLUGIN` check | `uninstall.php` |

## Estructura del plugin

```
wp-tac-manager/
├── wp-tac-manager.php              # Bootstrap, constantes, autoloader, actualizador GitHub
├── uninstall.php                   # Limpieza de BD al desinstalar
├── composer.json                   # Dependencias (plugin-update-checker)
├── includes/
│   ├── class-tac-admin.php         # Menú, assets admin, AJAX, estadísticas
│   ├── class-tac-renderer.php      # Encolado front-end + init JS
│   ├── class-tac-services.php      # Catálogo de 28 servicios
│   ├── class-tac-settings.php      # Defaults, sanitización, acceso a BD
│   └── class-tac-updater.php       # Actualizador de tarteaucitron.js
├── admin/
│   ├── views/settings-page.php     # Template HTML con 5 tabs
│   ├── js/admin.js                 # UX (tabs, toggles, AJAX, selector de idioma)
│   └── css/admin.css               # Estilos del panel
├── lang/                           # Archivos de traducción (.pot, .po, .mo)
└── assets/
    ├── js/tarteaucitron/           # Librería tarteaucitron.js
    │   ├── tarteaucitron.js
    │   ├── tarteaucitron.min.js
    │   ├── tarteaucitron.services.js
    │   ├── tarteaucitron.services.min.js
    │   └── lang/                   # Traducciones del banner (38 idiomas)
    └── css/
        ├── tarteaucitron.css
        └── tarteaucitron.min.css
```

## Changelog

### 1.4.0
- Añadidos 18 nuevos servicios: YouTube, Vimeo, Google Maps, reCAPTCHA, Instagram, TikTok, Microsoft Clarity, Bing Ads, Crisp Chat, Google Fonts, Disqus, Pinterest, Stripe, PayPal, Spotify, SoundCloud, Dailymotion, Twitch
- Personalización completa de colores: borde de botones y texto de botones
- Textos personalizados por idioma vía JSON (tarteaucitronCustomText)
- Actualizador automático del plugin desde GitHub Releases
- Mejora en la detección de versiones de tarteaucitron.js (con fallback a CDN)
- Eliminación del campo obligatorio de URL de privacidad (se obtiene automáticamente de WordPress)
- Interfaz de administración en inglés (traducible vía Loco Translate)
- Varias correcciones de seguridad y mantenimiento

### 1.0.0
- Release inicial con soporte para Google Tag Manager, GA4, Google Ads, Facebook Pixel, LinkedIn, Hotjar, Matomo Cloud, Plausible, Twitter UWT, HubSpot
- Panel de administración con tabs
- Personalización de colores básica
- Estadísticas de consentimiento
- Actualizador de tarteaucitron.js
- Exclusiones para plugins de caché
