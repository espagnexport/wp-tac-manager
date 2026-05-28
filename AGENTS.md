# AGENTS.md

This file provides essential guidance for agents working in the `wp-tac-manager` repository.

## Commands

- **Install PHP dependencies**: `composer install`

## Architecture Notes

- This project is a WordPress plugin designed to integrate Tarte au Citron for cookie management.
- The main plugin logic is located in the `includes/` directory, as indicated by the `composer.json` autoload `classmap`.
- The plugin uses `yahnis-elsts/plugin-update-checker` for updates.
- **Key Features**: The plugin bundles `tarteaucitron.js` locally, provides an automatic updater for it, and offers a modern admin panel with comprehensive customization options for colors and texts. It includes 28 predefined services and supports multilingual setups.
- **Security**: Implements CSRF nonces, `current_user_cap()`, full sanitization, and output escaping.

## Installation

1. Upload the `wp-tac-manager` folder to `/wp-content/plugins/`
2. Activate the plugin from **Plugins → Installed Plugins**
3. Go to **TAC Manager → Settings**

## Updates

- **tarteaucitron.js**: Go to **TAC Manager → Settings → Updates** and click "Check" to search for new versions. "Update now" downloads and installs the files automatically.
- **Plugin**: Updates are delivered through GitHub Releases and appear automatically under **Plugins** when a new version is available.