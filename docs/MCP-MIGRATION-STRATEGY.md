# MCP migrációs és minimál-adapter stratégia

## Kiindulási állapot

A Master plugin jelenleg a fleet, inventory és capability adatok működő
rendszere. Ezt a fázist nem szabad adat- vagy kódmigrációval összekeverni.

## Fázisok

### 1. Külső vezérlősík scaffold

- külön MCP service package;
- WordPress-független domain és application réteg;
- read-only `FleetGateway`;
- MCP tool/resource katalógus;
- nincs pluginmódosítás.

### 2. Minimális Master bridge

A Master plugin csak a dokumentált v2 read végpontokat és a dedikált gépi
authorizationt kapja meg. A meglévő repositoryk maradnak az adatforrások.

### 3. Külső perzisztencia

Új adapter vezeti be a külső fleet/inventory/capability store-t. Átmenetileg
dual-read vagy ellenőrzött backfill használható; az MCP toolok nem változnak.

### 4. Queue és végrehajtás

Csak a read path stabilizálása után kerülhet a központi queue az MCP platformra.
A WordPress oldal végrehajtó adapter marad. A dispatch szerződéshez kötelező:

- idempotencia;
- explicit capability gate;
- aláírt job és eredmény;
- retry/dead-letter lifecycle;
- immutable audit trail;
- actor és approval policy.

### 5. Dashboard, scheduler és AI orchestration

Ezek az application service-eket használják, nem a WordPress REST végpontokat
közvetlenül. Így a WordPress későbbi leválasztása nem igényel frontend- vagy
MCP-tool migrációt.

## Adattulajdonlás

| Adat | Most | Célállapot |
| --- | --- | --- |
| Site enrollment és klienskulcs | WordPress Master | WordPress biztonsági adapter |
| Fleet metadata | WordPress Master | Külső platform store |
| Inventory és capabilities | WordPress Master | Külső platform store |
| Job queue és workflow | Nincs ebben a fázisban | Külső MCP platform |
| Audit és automation policy | Jövőbeli WordPress modul | Külső MCP platform |

## Visszagörgethetőség

Az első két fázis additív. Az MCP service leállítása vagy a bridge kikapcsolása
nem módosítja a jelenlegi Master/Client kommunikációt. Ez a feltétel minden
későbbi migrációs lépésnél kötelező.
