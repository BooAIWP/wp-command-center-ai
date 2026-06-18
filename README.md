# WP Command Center AI

WP Command Center AI is a Composer-managed WordPress plugin monorepo. It contains a central **Master** plugin and a lightweight **Client** plugin for connecting managed WordPress sites.

## Requirements

- PHP 8.1 or newer
- Composer 2
- WordPress 6.5 or newer

## Repository layout

```text
plugins/
├── wp-command-center-ai-master/  Central command-center plugin
└── wp-command-center-ai-client/  Managed-site connector plugin
tests/                            Shared automated tests
```

Each plugin is independently installable and contains its own Composer package metadata, source code, assets, language directory, uninstall handler, and readme.

## Development

```bash
composer install
composer check
```

Run `composer lint:fix` to automatically fix supported coding-standard violations.

For local WordPress development, symlink or copy either plugin directory into `wp-content/plugins/`, then activate it in WordPress.

## Initial capabilities

- The Master plugin exposes an authenticated REST heartbeat endpoint and an administration dashboard.
- The Client plugin stores connection settings and sends scheduled heartbeats to a configured Master site.
- Shared project tooling enforces WordPress coding standards, PHP compatibility, and PHPUnit configuration.

## Security

Client heartbeats use a site identifier and shared secret sent over HTTPS. Treat the shared secret like a password. Production deployments should add secret rotation, request signing, replay protection, capability-scoped commands, and an audit trail before enabling remote actions.

## License

GPL-2.0-or-later.
