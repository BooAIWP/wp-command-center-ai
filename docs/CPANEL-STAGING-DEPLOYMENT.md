# cPanel staging telepítési terv

## Szerepkör és alapelvek

A GitHub `main` ág marad a forráskód egyetlen hiteles forrása. A cPanel szerver
staging/preview futtatási környezet, nem fejlesztési munkakönyvtár:

- a szerveren nem készül közvetlen kódmódosítás vagy commit;
- minden változás GitHubon keresztül érkezik;
- a szerver csak ellenőrzött commitot húz le, telepít, buildel és indít;
- titok, token, jelszó és szerverazonosító nem kerül Gitbe;
- a WordPress Master/Client működése ebben a fázisban változatlan.

## Telepítés előtti ellenőrzőlista

SSH-kapcsolat után futtatandó:

```bash
node --version
npm --version
php --version
composer --version
git --version
ssh -V
command -v crontab
crontab -l
command -v passenger
passenger-config --version
ps -u "$USER" -o pid,etime,command
```

Elvárt minimumok:

| Komponens | Elvárás |
| --- | --- |
| Node.js | 24 vagy újabb |
| npm | A telepített Node.js kiadás támogatott npm verziója |
| PHP | A WordPress projekt által támogatott 8.1–8.3 |
| Composer | Composer 2 |
| Git | Clone, fetch és fast-forward pull támogatás |
| SSH | Kulcsalapú hozzáférés a cPanel felhasználóhoz és GitHubhoz |
| Cron | Elérhető karbantartási/health-check feladatokhoz |
| Folyamatos processz | cPanel Node.js App, Passenger vagy felügyelt processz |
| HTTPS | Érvényes TLS a staging MCP végpont előtt |

A cPanel felületen külön ellenőrizendő:

- **Setup Node.js App** vagy hasonló alkalmazáskezelő;
- Passenger/Node alkalmazás támogatása;
- beállítható Node.js 24 runtime;
- alkalmazás környezeti változói;
- HTTPS domain vagy aldomain;
- logok helye és rotációja;
- process restart/deploy lehetőség;
- shell/SSH és cron jogosultság.

Ha csak régebbi Node.js érhető el, a kódot nem szabad szerver-specifikus
visszabutítással módosítani. A hosting runtime frissítése vagy más staging
környezet szükséges.

## Ajánlott szerverstruktúra

```text
~/apps/wp-command-center-ai/
├── repository/                 # GitHub clone, csak deploy műveletek
│   └── services/
│       └── command-center-mcp/
├── shared/
│   └── mcp.env                 # titkok, Git munkafán kívül
├── logs/
│   ├── mcp-access.log
│   └── mcp-error.log
└── backups/
    └── pre-deploy/
```

Jogosultságok:

```bash
chmod 700 "$HOME/apps/wp-command-center-ai/shared"
chmod 600 "$HOME/apps/wp-command-center-ai/shared/mcp.env"
chmod 700 "$HOME/apps/wp-command-center-ai/backups"
```

A szolgáltatást külön, korlátozott cPanel felhasználó futtassa. A felhasználó ne
kapjon root jogosultságot, és csak a szükséges alkalmazás-, log- és
konfigurációs könyvtárakat érhesse el.

## Környezeti változók

A repositoryban található
`services/command-center-mcp/.env.example` kizárólag kulcsneveket és
helykitöltőket tartalmaz. A staging értékeket:

1. elsődlegesen a cPanel Node.js App környezeti változói között;
2. ennek hiányában a Giten kívüli `~/apps/wp-command-center-ai/shared/mcp.env`
   fájlban

kell tárolni.

Staging HTTP módhoz szükséges:

```dotenv
NODE_ENV=staging
WPCCAI_MCP_TRANSPORT=http
WPCCAI_MCP_HOST=127.0.0.1
WPCCAI_MCP_PORT=8787
WPCCAI_MCP_ALLOWED_ORIGINS=https://mcp-staging.example.com
WPCCAI_MCP_BOOTSTRAP_TOKEN=<long-random-secret>
WPCCAI_WORDPRESS_BASE_URL=https://wordpress-staging.example.com
WPCCAI_WORDPRESS_BRIDGE_TOKEN=<dedicated-machine-secret>
```

Passenger vagy cPanel által biztosított `PORT` automatikusan használható, ha
`WPCCAI_MCP_PORT` nincs megadva. Az explicit `WPCCAI_MCP_PORT` elsőbbséget élvez.

A jelenlegi Node.js kód nem tölt be automatikusan `.env` fájlt. Ha a hosting
felület nem injektál környezeti változókat, az indító shell töltheti be a Giten
kívüli fájlt:

```bash
set -a
. "$HOME/apps/wp-command-center-ai/shared/mcp.env"
set +a
exec npm start
```

## Első telepítés

### Normál GitHub SSH

```bash
mkdir -p "$HOME/apps/wp-command-center-ai"
cd "$HOME/apps/wp-command-center-ai"
git clone git@github.com:BooAIWP/wp-command-center-ai.git repository
cd repository
git checkout main
git pull --ff-only origin main
cd services/command-center-mcp
npm ci
npm run check
npm audit --omit=dev
npm run build
```

### GitHub SSH a 443-as porton

Ha a kimenő SSH 22-es port blokkolt:

```bash
mkdir -p "$HOME/.ssh"
chmod 700 "$HOME/.ssh"
ssh-keyscan -p 443 ssh.github.com >> "$HOME/.ssh/known_hosts"
chmod 600 "$HOME/.ssh/known_hosts"
GIT_SSH_COMMAND="ssh -p 443 -o Hostname=ssh.github.com" \
  git clone git@github.com:BooAIWP/wp-command-center-ai.git repository
```

Már létező clone esetén:

```bash
cd "$HOME/apps/wp-command-center-ai/repository"
GIT_SSH_COMMAND="ssh -p 443 -o Hostname=ssh.github.com" \
  git fetch origin
GIT_SSH_COMMAND="ssh -p 443 -o Hostname=ssh.github.com" \
  git pull --ff-only origin main
```

A GitHub host key ujjlenyomatát az első kapcsolat előtt hivatalos GitHub
dokumentáció alapján ellenőrizni kell. A `StrictHostKeyChecking=no` használata
tilos.

## Frissítési eljárás

Telepítés előtt készüljön visszaállítási pont:

```bash
cd "$HOME/apps/wp-command-center-ai/repository"
git rev-parse HEAD > "$HOME/apps/wp-command-center-ai/backups/pre-deploy/commit.txt"
cp "$HOME/apps/wp-command-center-ai/shared/mcp.env" \
  "$HOME/apps/wp-command-center-ai/backups/pre-deploy/mcp.env.backup"
```

Ezután:

```bash
cd "$HOME/apps/wp-command-center-ai/repository"
git fetch origin
git checkout main
git pull --ff-only origin main
cd services/command-center-mcp
npm ci
npm run check
npm audit --omit=dev
npm run build
```

Csak sikeres ellenőrzés után indítható újra a cPanel/Passenger alkalmazás. A
restart pontos módja hostingfüggő; a cPanel által biztosított restart műveletet
kell használni.

## Runtime módok

### Lokális stdio MCP

Fejlesztői gépen, közvetlen MCP klienshez:

```bash
cd services/command-center-mcp
npm ci
npm run build
WPCCAI_MCP_TRANSPORT=stdio npm start
```

Ebben a módban a stdout az MCP protokollé; diagnosztikai üzenet csak stderrre
írható.

### Staging HTTP MCP

cPanel/Passenger vagy más felügyelt Node processz mögött:

```bash
export NODE_ENV=staging
export WPCCAI_MCP_TRANSPORT=http
export WPCCAI_MCP_HOST=127.0.0.1
export WPCCAI_MCP_PORT="${PORT:-8787}"
export WPCCAI_MCP_ALLOWED_ORIGINS=https://mcp-staging.example.com
export WPCCAI_MCP_BOOTSTRAP_TOKEN=<secret>
npm start
```

A publikus HTTPS végpont reverse proxyn keresztül érje el a lokális Node
processzt. A belső portot nem szabad közvetlenül az internetre nyitni.

### Jövőbeli production

A production mód még nem kész. Bevezetési feltételei:

- MCP-kompatibilis OAuth 2.1 authorization;
- rövid életű, scope-olt tokenek;
- központi secret manager;
- többpéldányos session/state store;
- rate limiting és request size policy;
- strukturált audit és telemetry;
- health/readiness végpont;
- felügyelt rolling deployment és visszaállítás;
- külső perzisztencia és mentési stratégia.

A staging bootstrap bearer tokent nem szabad production hitelesítésként kezelni.

## Ha nincs tartós Node processz

A cron nem alkalmas folyamatos MCP HTTP szerver megbízható futtatására. Tilos
percenkénti cronból háttérprocesszt indító, PID-fájlos vagy `nohup` alapú
ál-process manager megoldást építeni.

Javasolt fallback sorrend:

1. cPanel Node.js App/Passenger engedélyezése a szolgáltatónál;
2. külön staging VPS vagy menedzselt Node/PaaS runtime használata;
3. konténeres runtime, ha a hosting támogatja;
4. csak lokális `stdio` mód fejlesztési validációhoz.

A WordPress plugin nem veheti át az MCP szerver vagy queue szerepét csak azért,
mert a hosting nem támogat hosszú életű Node processzt.

## Üzemeltetési és biztonsági követelmények

- Titok nem kerül Gitbe, build artifactba, logba vagy MCP outputba.
- A publikus MCP végpont kizárólag HTTPS-en érhető el.
- A cPanel felhasználó és az alkalmazás jogosultsága minimális.
- A logokhoz méret- és időalapú rotáció, megőrzési limit és hozzáférés-védelem
  szükséges.
- Minden deploy előtt commit- és konfigurációmentés készül.
- Minden deploy során `npm ci`, `npm run check` és `npm audit --omit=dev` fut.
- A `package-lock.json` kötelező és a telepítés reprodukálható.
- Dependency audit hiba esetén nincs automatikus frissítés; külön, tesztelt
  GitHub commit szükséges.
- A bridge token elkülönül a WordPress Client kulcsoktól.
- Az Origin allowlist pontos HTTPS originokat tartalmaz, wildcard nélkül.

## Staging elfogadási feltételek

- a telepített commit megegyezik az `origin/main` kívánt commitjával;
- `npm run check` és `npm audit --omit=dev` sikeres;
- a Node processzt cPanel/Passenger felügyeli;
- a publikus végpont HTTPS-es és a belső port nem publikus;
- hibás tokennel `401`, hibás Originnel `403` érkezik;
- restart után a szolgáltatás automatikusan visszaáll;
- logrotáció és visszaállítási eljárás dokumentált és kipróbált.

## Végrehajtható runtime segédletek

A részletes readiness checklist, deploy, rollback, restart és health-check
eljárás itt található:

[`docs/CPANEL-RUNTIME-READINESS.md`](CPANEL-RUNTIME-READINESS.md)

A szerveren használható scriptek:

```text
services/command-center-mcp/scripts/cpanel/readiness-check.sh
services/command-center-mcp/scripts/cpanel/deploy.sh
services/command-center-mcp/scripts/cpanel/health-check.sh
```
