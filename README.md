# WP Command Center AI OS

A WP Command Center AI OS egy Composerrel kezelt WordPress plugin-monorepó. Két önállóan telepíthető komponenst tartalmaz:

- **Master plugin:** központi felügyeleti és vezérlési felület.
- **Client plugin:** a kezelt WordPress webhelyek biztonságos csatlakozója.

## Követelmények

- PHP 8.1 vagy újabb
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
- A megosztott projekt-eszköztár WordPress kódolási szabványokat, PHP-kompatibilitási ellenőrzést és PHPUnit-konfigurációt biztosít.

A Milestone 2 részletes technikai leírása: [`docs/MILESTONE-2.md`](docs/MILESTONE-2.md).

## Biztonsági modell

A Client heartbeat kérések HTTPS-en keresztül küldött webhelyazonosítót és megosztott titkot használnak. A megosztott titkot jelszóként kell kezelni. Távoli műveletek engedélyezése előtt szükséges a titokrotáció, a kérésaláírás, a visszajátszás-védelem, a képességalapú parancskezelés és az auditnapló megvalósítása.

## Licenc

GPL-2.0-or-later.
