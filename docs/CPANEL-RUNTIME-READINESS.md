# cPanel runtime readiness

## Deployment checklist

### Hosting képességek

- [ ] cPanel **Setup Node.js App** vagy Passenger támogatás.
- [ ] Node.js 24 vagy újabb választható.
- [ ] Hosszú életű Node processz automatikus újraindítással.
- [ ] SSH shell hozzáférés kulcsalapú hitelesítéssel.
- [ ] GitHub elérés SSH 22 vagy SSH 443 porton.
- [ ] Git, npm, PHP 8.1–8.3 és Composer 2.
- [ ] Cron elérhető karbantartási probe-okhoz.
- [ ] HTTPS aldomain és reverse proxy a Node alkalmazáshoz.
- [ ] Környezeti változók kezelése cPanelből.
- [ ] Alkalmazáslogok és logrotáció elérhető.
- [ ] Írható, nem publikus shared, log és backup könyvtárak.

### Node alkalmazás

- [ ] Application root:
      `~/apps/wp-command-center-ai/repository/services/command-center-mcp`.
- [ ] Startup file: `dist/src/index.js`.
- [ ] Application mode: `staging`.
- [ ] A hosting által adott `PORT` vagy explicit `WPCCAI_MCP_PORT`.
- [ ] `WPCCAI_MCP_HOST=127.0.0.1`.
- [ ] `WPCCAI_MCP_TRANSPORT=http`.
- [ ] Pontos HTTPS Origin allowlist.
- [ ] A secret értékek kizárólag cPanelben vagy Giten kívüli fájlban vannak.

### Telepítés

- [ ] A repository munkafája tiszta.
- [ ] A cél commit az `origin/main` része.
- [ ] Pre-deploy commitmentés elkészült.
- [ ] `npm ci` sikeres.
- [ ] `npm run check` sikeres.
- [ ] `npm audit --omit=dev` nem jelez sérülékenységet.
- [ ] A buildazonosító megegyezik a telepített Git committal.
- [ ] A cPanel/Passenger restart sikeres.

### Runtime validáció

- [ ] `GET /health` HTTPS-en `200` választ ad.
- [ ] A health `status` mezője `ok`.
- [ ] A health `build` mezője a telepített commit.
- [ ] A health nem tartalmaz tokent, URL-t vagy konfigurációs titkot.
- [ ] Hibás MCP bearer tokenre `401` érkezik.
- [ ] Nem engedélyezett Origin esetén `403` érkezik.
- [ ] A belső Node port közvetlenül nem publikus.
- [ ] cPanel restart után a processz automatikusan feláll.
- [ ] A logok írhatók és rotálódnak.

## Újrafelhasználható ellenőrző scriptek

A scripteket a service könyvtárból kell futtatni:

```bash
cd ~/apps/wp-command-center-ai/repository/services/command-center-mcp
chmod 750 scripts/cpanel/*.sh
```

### Readiness

```bash
export WPCCAI_SHARED_DIR="$HOME/apps/wp-command-center-ai/shared"
export WPCCAI_LOG_DIR="$HOME/apps/wp-command-center-ai/logs"
export WPCCAI_BACKUP_DIR="$HOME/apps/wp-command-center-ai/backups"
export WPCCAI_HEALTH_URL="https://mcp-staging.example.com"
scripts/cpanel/readiness-check.sh
```

A script ellenőrzi:

- Node.js és npm;
- Git és SSH;
- PHP és Composer;
- cron és Passenger jelenlét;
- kötelező környezeti változók;
- HTTPS Origin;
- írható runtime könyvtárak;
- opcionális élő HTTPS health endpoint;
- helyi buildképesség.

### Deploy

```bash
export WPCCAI_APP_ROOT="$HOME/apps/wp-command-center-ai"
export WPCCAI_REPOSITORY_DIR="$WPCCAI_APP_ROOT/repository"
export WPCCAI_BACKUP_DIR="$WPCCAI_APP_ROOT/backups/pre-deploy"
export WPCCAI_DEPLOY_BRANCH=main
scripts/cpanel/deploy.sh
```

A deploy script:

1. elutasítja a nem tiszta munkafát;
2. menti az előző commitot;
3. fetch és fast-forward merge műveletet végez;
4. a Git commitból buildazonosítót készít;
5. futtatja az install, check, audit és build folyamatot;
6. nem próbál hosting-specifikus restartot kitalálni.

### Health probe

```bash
export WPCCAI_HEALTH_URL="https://mcp-staging.example.com"
scripts/cpanel/health-check.sh
```

A probe ellenőrzi a válasz kötelező mezőit és hibával tér vissza, ha a runtime
nem egészséges. Cronból használható riasztási integráció részeként, de nem
process managerként.

## Health endpoint szerződés

Az autentikációt nem igénylő `GET /health` kizárólag működési metaadatot ad:

```json
{
  "status": "ok",
  "version": "0.1.0",
  "build": "32feee6ca615",
  "uptimeSeconds": 120,
  "runtimeMode": "staging",
  "transport": "http",
  "nodeVersion": "v24.0.0"
}
```

Nem szerepelhet benne:

- token vagy authorization állapot;
- WordPress URL;
- fájlrendszerútvonal;
- hostname, felhasználónév vagy IP-cím;
- környezeti változó értéke;
- fleet vagy ügyféladat.

## Restart procedure

A restart hostingfüggő. Elfogadott módszerek:

1. cPanel **Setup Node.js App → Restart**;
2. a szolgáltató dokumentált Passenger restart művelete;
3. szolgáltató által biztosított process manager.

A repository nem tartalmaz `kill`, `pkill`, `nohup` vagy önálló daemonizáló
scriptet. Restart után kötelező:

```bash
scripts/cpanel/health-check.sh
```

## Rollback procedure

A deploy script az előző commitot ide menti:

```text
~/apps/wp-command-center-ai/backups/pre-deploy/previous-commit.txt
```

Rollback előtt az aktuális hibás deploy commitot fel kell jegyezni. Ezután:

```bash
cd "$HOME/apps/wp-command-center-ai/repository"
previous_commit="$(cat "$HOME/apps/wp-command-center-ai/backups/pre-deploy/previous-commit.txt")"
git fetch origin
git checkout --detach "$previous_commit"
cd services/command-center-mcp
export WPCCAI_BUILD_ID="$previous_commit"
npm ci
npm run check
npm audit --omit=dev
```

Ezután a cPanel/Passenger dokumentált restartja és a health probe következik.
A detached checkout csak ideiglenes runtime rollback; a tartós javításnak új,
tesztelt GitHub commitként kell a `main` ágra kerülnie.

## Tényleges szerverkapcsolathoz szükséges adatok

A lokális előkészítés után a következő információk nélkül nem végezhető valós
staging telepítés:

- staging cPanel hostname vagy belépési URL;
- SSH hostname és port;
- korlátozott cPanel/SSH felhasználónév;
- jóváhagyott SSH hitelesítési mód és a lokálisan elérhető kulcs azonosítója;
- staging MCP aldomain és kívánt HTTPS URL;
- cPanel Node.js App/Passenger elérhetősége és választható Node-verziók;
- a cPanel által elvárt application root és startup file beállítási módja;
- környezeti változók cPanelben történő beállításának lehetősége;
- a staging WordPress bridge URL-je és a titkok biztonságos átadási csatornája.

Titkot nem szabad chatüzenetben vagy repository-fájlban megadni. A tokeneket a
cPanel secret/environment felületén kell létrehozni vagy elhelyezni.
