# WP Command Center AI MCP Server

Ez a csomag a platform külső vezérlősíkjának alapja. Az MCP-réteg csak adapter:
az üzleti folyamatok az alkalmazási szolgáltatásokban, a WordPress-kapcsolat pedig
egy cserélhető gateway implementációban található.

## Állapot

Az első scaffold szándékosan csak olvasási műveleteket tartalmaz:

- platformállapot;
- fleet lista és site részletek;
- inventory lekérdezés;
- capability lekérdezés;
- architektúra- és fleet-summary erőforrások.

Job dispatch, ütemezés, bulk mutation és AI orchestration még nincs bekötve.

## Fejlesztés

```bash
npm install
npm run check
npm run build
```

Alapértelmezésben a szerver `stdio` transporttal indul:

```bash
npm run build
npm start
```

A központi telepítéshez használható Streamable HTTP mód csak explicit bearer
tokennel és Origin allowlisttel indul. A bootstrap token nem végleges production
hitelesítés; éles bevezetés előtt MCP-kompatibilis OAuth 2.1 authorization
szükséges.

Lásd:

- `docs/MCP-SERVER-ARCHITECTURE.md`
- `docs/MCP-WORDPRESS-BOUNDARY.md`
- `docs/MCP-MIGRATION-STRATEGY.md`
