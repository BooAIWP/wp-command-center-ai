# MCP–WordPress API-határ

## Alapelv

Az MCP szerver nem hív Master vagy Client PHP osztályokat. A kapcsolat külön,
verziózott HTTP-szerződésen és a `FleetGateway` porton keresztül történik.

A jelen dokumentum bridge-szerződés. A végpontok ebben a fázisban még nem
kerülnek be a pluginbe, ezért a jelenlegi WordPress-rendszer változatlan marad.

## Javasolt v2 read API

Alapútvonal:

```text
/wp-json/wp-command-center-ai/v2
```

| Metódus | Útvonal | Leírás |
| --- | --- | --- |
| GET | `/platform/status` | Bridge és szerződés verzió |
| GET | `/fleet/sites` | Szűrhető, cursoros site lista |
| GET | `/fleet/sites/{siteId}` | Site metaadat és státusz |
| GET | `/fleet/sites/{siteId}/inventory` | Normalizált inventory |
| GET | `/fleet/sites/{siteId}/capabilities` | Negotiated capabilities |

Lista paraméterek:

- `status`: `online`, `offline`, `unknown`;
- `group`, `tag`, `search`;
- `cursor`: átlátszatlan lapozási token;
- `limit`: 1–200.

## Gép–gép hitelesítés

Az adapter külön bridge credentialt használ:

```http
Authorization: Bearer <dedicated-machine-token>
Accept: application/json
```

Ez nem azonos a Client regisztrációs, heartbeat vagy kulcsrotációs
identitásával. Production előtt a bridge hitelesítést rövid életű, scope-olt
gépi tokenre vagy mTLS-re kell cserélni.

Tervezett scope-ok:

- `fleet:read`;
- `inventory:read`;
- `capabilities:read`;
- később külön `jobs:dispatch` és `jobs:read`.

## Válaszszerződés

Az MCP oldali domainmodell canonical camelCase JSON-t vár. A bridge felelőssége
a WordPress tárolási formátum normalizálása.

Példa:

```json
{
  "id": "site_01H...",
  "name": "Production Store",
  "url": "https://store.example.com",
  "status": "online",
  "groups": ["production"],
  "tags": ["woocommerce", "eu"],
  "wordpressVersion": "6.x",
  "phpVersion": "8.x",
  "lastSeenAt": "2026-06-18T10:00:00Z"
}
```

Hibák:

- `401`: hiányzó vagy érvénytelen gépi identitás;
- `403`: hiányzó scope;
- `404`: ismeretlen site vagy még nem létező snapshot;
- `409`: szerződés- vagy állapotütközés;
- `429`: rate limit;
- `5xx`: bridge vagy belső szolgáltatáshiba.

Minden későbbi írási végpontnál kötelező lesz az idempotency key, request ID,
audit actor és aláírt eredmény. Ebben a read-only scaffoldban ilyen végpont nincs.

## Kompatibilitás

- A `/v1` biztonságos Master/Client protokoll változatlan.
- A `/v2` kizárólag platform bridge API.
- Breaking változás új API-verziót igényel.
- Additív mezők megengedettek; a fogyasztónak az ismeretlen mezőket figyelmen
  kívül kell hagynia.
