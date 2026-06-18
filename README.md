# WP Command Center AI OS

A WP Command Center AI OS egy Composerrel kezelt WordPress plugin-monorepó. Két önállóan telepíthető komponenst tartalmaz:

- **Master plugin:** központi felügyeleti és vezérlési felület.
- **Client plugin:** a kezelt WordPress webhelyek biztonságos csatlakozója.

## Követelmények

- PHP 8.1 vagy újabb
- PHP Sodium kiterjesztés
- Composer 2
- WordPress 6.5 vagy újabb
- HTTPS a Master és a Client webhelyek között

## Könyvtárstruktúra

```text
plugins/
├── wp-command-center-ai-master/  Központi felügyeleti plugin
└── wp-command-center-ai-client/  Kezelt webhely csatlakozó
tests/                            Megosztott automatizált tesztek
```

Mindkét plugin saját Composer-csomagleírással, forráskóddal, nyelvi könyvtárral, eltávolítási kezelővel és WordPress readme fájllal rendelkezik.

## Fejlesztés

```bash
composer install
composer check
```

A támogatott kódstílus-hibák automatikus javítása:

```bash
composer lint:fix
```

Helyi WordPress-fejlesztéshez másold vagy symlinkeld a kívánt plugin könyvtárát a `wp-content/plugins/` könyvtárba.

## Jelenlegi képességek

- A Master plugin hitelesített REST heartbeat végpontot és adminisztrációs felületet biztosít.
- A Client plugin tárolja a kapcsolati beállításokat, és ütemezett heartbeat kéréseket küld a Master webhelynek.
- A közös OS Core Kernel, szolgáltatáskonténer, eseménybusz, modulbetöltő, capability registry, REST bootstrap, naplózás és lifecycle infrastruktúrát biztosít.
- A Master és Client challenge-response regisztrációt, Ed25519-aláírt heartbeat protokollt, replay-védelmet és kulcsrotációs alapot használ.
- A megosztott projekt-eszköztár WordPress kódolási szabványokat, PHP-kompatibilitási ellenőrzést és PHPUnit-konfigurációt biztosít.

A Milestone 2 részletes technikai leírása: [`docs/MILESTONE-2.md`](docs/MILESTONE-2.md).

A Milestone 3 biztonsági protokollja: [`docs/MILESTONE-3.md`](docs/MILESTONE-3.md).

A Milestone 4 Capability Engine architektúrája: [`docs/MILESTONE-4-CAPABILITY-ENGINE.md`](docs/MILESTONE-4-CAPABILITY-ENGINE.md).

## Biztonsági modell

A Client heartbeat kérések kliensenkénti Ed25519 kulccsal aláírtak. Az időbélyeg és az egyszer használható nonce visszajátszás-védelmet biztosít. A privát kulcsok hitelesítetten titkosítva maradnak az adott WordPress telepítésen. Távoli műveletek engedélyezése előtt továbbra is szükséges a képességalapú parancskezelés és az auditnapló megvalósítása.

## MCP vezérlősík

Az MCP vezérlősík dokumentációja:

- [`docs/MCP-SERVER-ARCHITECTURE.md`](docs/MCP-SERVER-ARCHITECTURE.md)
- [`docs/MCP-WORDPRESS-BOUNDARY.md`](docs/MCP-WORDPRESS-BOUNDARY.md)
- [`docs/MCP-MIGRATION-STRATEGY.md`](docs/MCP-MIGRATION-STRATEGY.md)
- [`docs/CPANEL-STAGING-DEPLOYMENT.md`](docs/CPANEL-STAGING-DEPLOYMENT.md)

## Licenc

GPL-2.0-or-later.
