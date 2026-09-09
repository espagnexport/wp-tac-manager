# AGENTS.md

WordPress plugin integrating Tarte au Citron (tarteaucitron.js) for cookie consent. No CI, tests, lint, or build tooling.

## Commands

- `composer install` — installs `yahnis-elsts/plugin-update-checker` into `vendor/` (gitignored). Only external dependency; the plugin's own classes use a custom autoloader, not Composer. The `autoload.classmap` entry in `composer.json` is redundant (the SPL autoloader already maps `WPTAC_*`); don't rely on it, and if you remove it, refresh the `composer.lock` content-hash via `composer update --lock`.

## Architecture

- Bootstrap is `wp-tac-manager.php`: defines constants, registers a custom SPL autoloader, hooks `init` (GitHub updater) and `plugins_loaded` (module init).
- **Class → file convention (critical):** `WPTAC_Admin` → `includes/class-tac-admin.php`. The autoloader strips the `WPTAC_` prefix, lowercases, and maps `_` → `-`. A new class `WPTAC_Foo_Bar` must live at `includes/class-tac-foo-bar.php`.
- Modules in `includes/`: `WPTAC_Admin` (menu/AJAX/stats), `WPTAC_Renderer` (front-end enqueue + init JS), `WPTAC_Settings` (defaults/sanitize/DB), `WPTAC_Services` (26-service catalog), `WPTAC_Updater` (tarteaucitron.js update). Instantiated in `plugins_loaded`; `WPTAC_Admin` only when `is_admin()`.

## Settings

- All config lives in one option `wptac_settings` (`WPTAC_OPTION_KEY`), a nested `general`/`colors`/`texts`/`services` array.
- Read with `WPTAC_Settings::get_settings()` (deep-merges defaults + saved). Write ONLY via `WPTAC_Settings::save_settings()`/`sanitize()` — never save raw form data.
- Adding a service requires BOTH a `WPTAC_Services::get_definitions()` entry and a `services` default in `WPTAC_Settings::get_defaults()`. Services can also be extended via the `wptac_services` filter.

## Gotchas

- Requires PHP 8.0+ (`match`, `str_starts_with`/`str_ends_with`, arrow fns).
- No build step: JS is committed by hand, including `*.min.js`. `SCRIPT_DEBUG` switches between minified and unminified.
- Version lives in two places: the `Version:` plugin header and `WPTAC_VERSION`. tarteaucitron version is `WPTAC_TARTEAUCITRON_VERSION`.
- `.gitignore` lists `assets/css/` and `assets/js/tarteaucitron`, but those files are already tracked — new files added there (e.g. a new language file from the updater) will be ignored and need `git add -f`.
- `uninstall.php` deletes the settings option; deactivation intentionally preserves it.
- Optional GitHub auth: define `WP_TAC_MANAGER_GITHUB_TOKEN` for updater rate limits.

## References

- README.md — feature list, service table, installation/update steps. NOTICES.md — bundled tarteaucitron.js license (MIT).
