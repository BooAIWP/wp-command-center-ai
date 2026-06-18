# Milestone 2 – WP Command Center AI OS alap-infrastruktúra

## Cél

A mérföldkő létrehozza azt a közös futtatási réteget, amelyre a Master és Client plugin további moduljai épülhetnek. A megvalósítás nem vezet be új üzleti funkciót.

## Komponensek

### Kernel

A `Kernel` az alkalmazás belépési pontja. Feladata:

- a közös szolgáltatások regisztrálása;
- a modulok fogadása;
- a modulok regisztrációjának és indításának koordinálása;
- az aktiválási és deaktiválási folyamat delegálása;
- a REST API bootstrap indítása;
- kernel lifecycle események kibocsátása.

A Kernel idempotens: ugyanazon kérésen belül többször meghívott `boot()` nem indítja újra a modulokat.

### Service Container

A szolgáltatáskonténer támogatja:

- szolgáltatásdefiníciók regisztrálását;
- singleton példányok létrehozását;
- kész objektumpéldányok tárolását;
- aliasok használatát;
- körkörös függőségek felismerését;
- egyértelmű kivételeket hiányzó vagy hibás szolgáltatás esetén.

### Event Bus

Az eseménybusz szinkron eseménykezelést biztosít:

- string és objektum események;
- prioritásos listenerek;
- determinisztikus végrehajtási sorrend;
- kernel-, modul-, REST- és lifecycle-események.

### Module Loader

A modulbetöltő kezeli a modulok teljes életciklusát:

1. regisztráció;
2. boot;
3. aktiválás;
4. fordított sorrendű deaktiválás.

A modulazonosítók egyediek, és a modulok a regisztráció megkezdése után már nem módosíthatók.

### Capability Registry

A capability registry a rendszer futásidejű képességeinek deklaratív katalógusa. Nem helyettesíti a WordPress felhasználói jogosultságait. A registry a modulok által támogatott műveletek felderítésére szolgál.

### REST API bootstrap

A közös REST bootstrap route provider objektumokat fogad, és egyetlen `rest_api_init` kapcsolódási ponton regisztrálja őket. A meglévő Master heartbeat végpont már ezt az infrastruktúrát használja.

### Logging subsystem

A naplózási réteg egységes interfészt és szabványos naplószinteket biztosít. A WordPress logger:

- csatornaazonosítót és UTC időbélyeget ad minden rekordhoz;
- kontextushelyettesítést támogat;
- rekurzívan maszkolja a jelszó-, secret-, token- és authorization-mezőket;
- `wpccai_log` eseményt bocsát ki;
- kizárólag engedélyezett `WP_DEBUG_LOG` esetén ír a WordPress hibánaplóba.

### Aktiválás és deaktiválás

Az `Activator` és `Deactivator` központilag futtatja a lifecycle modulokat és eseményeket bocsát ki. A Client cron ütemezése és a Master titok inicializálása modul-lifecycle műveletként fut.

## Csomagolás

A közös infrastruktúra a `wp-command-center-ai/core` Composer-csomagban található. A Master és Client plugin path repository használatával hivatkozik rá fejlesztés közben. Kiadási csomag készítésekor a Composer autoload és a Core függőség a plugin `vendor` könyvtárába kerül.

## Kiterjesztési szabályok

- Új üzleti terület külön modult kap.
- Modul csak a `register()` metódusban regisztrál szolgáltatást.
- WordPress hook csak a `boot()` metódusban kapcsolható.
- Telepítési állapot változtatása kizárólag lifecycle metódusban történhet.
- REST végpont `RestRouteProviderInterface` implementációként adható hozzá.
- Modul által biztosított funkciót a capability registryben deklarálni kell.
- Naplózás a `LoggerInterface` szolgáltatáson keresztül történik.
