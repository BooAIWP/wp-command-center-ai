# MCP Server architektúra

## Cél

A WP Command Center AI hosszú távú központja külön futó szolgáltatás lesz. A
WordPress pluginek adatgyűjtési és végrehajtási adapterek maradnak; az
ütemezés, workflow, AI orchestration, queue koordináció és központi dashboard
fokozatosan az MCP szerver mögé kerülhet.

Ez a fázis nem migrál meglévő kódot és nem módosítja a működő Master/Client
protokollt.

## Rétegek

```text
MCP clients / future dashboard / automation
                    |
             MCP transport layer
          (stdio / Streamable HTTP)
                    |
              MCP tool adapters
                    |
          Application services / ports
                    |
        FleetGateway infrastructure port
                    |
     WordPress adapter now | external data services later
```

### Domain

Normalizált, WordPress-független fleet, inventory és capability modellek.
Ebben a rétegben nincs MCP SDK, HTTP vagy WordPress típus.

### Application

Use-case szolgáltatások és portok. A `FleetQueryService` a lekérdezési
szabályokat biztosítja, a `FleetGateway` pedig elrejti az aktuális adattárolási
helyet.

### Infrastructure

Az első adapter a Master plugin jövőbeli, verziózott REST bridge-éhez kapcsolódik.
Az adapter cserélhető külső adatbázisra vagy külön fleet szolgáltatásra anélkül,
hogy az MCP toolszerződések megváltoznának.

### MCP

Az MCP tools és resources vékony adapterek. Nem tartalmaznak perzisztenciát vagy
WordPress-specifikus üzleti logikát.

## Transport és biztonság

- Lokális fejlesztésnél a `stdio` az alapértelmezett.
- Központi telepítésnél Streamable HTTP használható.
- HTTP módban kötelező a bearer token és az Origin allowlist.
- A HTTP szerver alapértelmezett bind címe `127.0.0.1`.
- A bootstrap bearer token kizárólag kezdeti infrastruktúra. Production előtt
  MCP authorization kompatibilis OAuth 2.1, rövid életű tokenek, auditált
  kliensazonosítás és reverse-proxy TLS szükséges.
- Az MCP-szerver gépi identitása nem használhatja újra a Client enrollment vagy
  Ed25519 site kulcsokat.
- Titkok nem kerülnek tool outputba, resource tartalomba vagy logba.

## MCP katalógus

### Tools

| Név | Hozzáférés | Cél |
| --- | --- | --- |
| `platform_get_status` | read | Szolgáltatás- és bridge-állapot |
| `fleet_list_sites` | read | Szűrhető, lapozható fleet lista |
| `fleet_get_site` | read | Egy site normalizált metaadatai |
| `inventory_get_site` | read | Legutóbbi inventory snapshot |
| `capabilities_get_site` | read | Legutóbbi capability negotiation |

### Resources

| URI | Cél |
| --- | --- |
| `wpccai://platform/architecture` | A vezérlősík határainak gépi leírása |
| `wpccai://fleet/summary` | Kompakt online/offline fleet összesítés |

Job-, mutation- és adminisztratív tool ebben a fázisban nincs. Ezek csak külön
authorization, idempotencia, jóváhagyás, audit és queue lifecycle után
vezethetők be.

## Könyvtárstruktúra

```text
services/command-center-mcp/
├── src/
│   ├── application/
│   │   ├── ports/
│   │   └── services/
│   ├── domain/
│   ├── infrastructure/wordpress/
│   ├── mcp/
│   └── transports/
├── tests/
├── package.json
└── tsconfig.json
```

## Skálázási irány

A jelenlegi HTTP session map egyetlen processz alap. Több példányos deployment
előtt külső session/state store, stateless auth, rate limiting, telemetry és
horizontal routing szükséges. A domain/application határ ezt nem befolyásolja.
