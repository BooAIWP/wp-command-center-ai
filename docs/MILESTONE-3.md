# Milestone 3 – Biztonságos Master–Client kommunikáció

## Cél

A Milestone 3 megszünteti a minden kliens által közösen használt heartbeat titkot, és kliensenkénti nyilvános kulcsú hitelesítést vezet be. A Master és a Client továbbra is kizárólag a közös Core protokollrétegen és HTTP REST végpontokon keresztül kapcsolódik.

## Kriptográfiai alapok

- Algoritmus: Ed25519 detached signature.
- Implementáció: PHP Sodium kiterjesztés.
- Kulcsazonosító: a bináris nyilvános kulcs SHA-256 lenyomatának első 24 hexadecimális karaktere.
- Privátkulcs-tárolás: Sodium secretbox hitelesített titkosítás.
- A secretbox kulcsa a WordPress `AUTH_KEY` értékéből, alkalmazásspecifikus kontextussal származik.
- A privát kulcs nem kerül REST válaszba, naplóba vagy adminisztrációs felületre.

## Kliensregisztráció

1. A Master aktiváláskor létrehoz egy enrollment tokent és egy Ed25519 kulcspárt.
2. A Client helyben létrehozza saját Ed25519 kulcspárját.
3. A Client elküldi az enrollment tokent, webhelyadatait és nyilvános kulcsát a challenge végpontnak.
4. A Master rövid életű, egyszer használható challenge értéket ad vissza.
5. A Client aláírja a challenge kanonikus reprezentációját.
6. A Master ellenőrzi az aláírást, regisztrálja a klienst, majd saját privát kulcsával aláírt regisztrációs proof értéket küld.
7. A Client ellenőrzi a proof értéket, és csak ezután tárolja a Master nyilvános kulcsát és a kiosztott site ID-t.

Végpontok:

- `POST /wp-json/wp-command-center-ai/v1/registration/challenge`
- `POST /wp-json/wp-command-center-ai/v1/registration/complete`

A challenge öt percig érvényes, és kiolvasás után azonnal törlődik.

## Aláírt heartbeat protokoll

A Client minden heartbeat kéréshez létrehoz:

- site ID fejlécet;
- key ID fejlécet;
- Unix időbélyeget;
- 192 bites véletlen nonce értéket;
- Ed25519 aláírást.

Az aláírt kanonikus tartalom:

```text
HTTP_METHOD
/REST/ROUTE
TIMESTAMP
NONCE
SHA256(BODY)
```

A Master csak akkor fogadja el a kérést, ha:

- a site ID és key ID ismert;
- az időbélyeg legfeljebb öt percet tér el;
- a nonce korábban nem szerepelt;
- az aláírás megfelel a regisztrált nyilvános kulcsnak.

A Master a heartbeat válaszhoz aláírt receipt értéket ad. A Client csak érvényes receipt után tekinti sikeresnek a kommunikációt.

## Kliensstátusz

A Master minden elfogadott heartbeat után frissíti a kliens utolsó kommunikációs időpontját és jelentését.

- `online`: az utolsó heartbeat legfeljebb 15 perce érkezett;
- `stale`: 15 percnél régebbi, de 65 percnél frissebb;
- `offline`: nincs heartbeat, vagy legalább 65 perce nem érkezett.

A státusz futásidőben kerül kiszámításra, ezért nem igényel külön cron feladatot.

## Kulcsrotációs alap

Mindkét oldal kulcstára támogatja a jelenlegi, következő és korábbi kulcsot.

- Alapértelmezett rotációs időszak: 90 nap.
- Alapértelmezett türelmi idő: 7 nap.
- A Client a jelenlegi kulccsal aláírt heartbeatben hirdeti meg a következő nyilvános kulcsát.
- A Master a hitelesített kérés után elfogadja az új klienskulcsot.
- A Client csak a Master aláírt receipt válasza után lépteti elő az új kulcsot.
- A Master a következő nyilvános kulcsát a jelenlegi kulccsal aláírt key-update üzenetben hirdeti meg.
- A Client kizárólag érvényes key-update aláírás után helyezi bizalmi tárba az új Master kulcsot.

A Master következő kulcsának végleges előléptetése későbbi operatív rotációs folyamat része lehet; a biztonságos terjesztési és többkulcsos ellenőrzési alap ebben a mérföldkőben elkészült.

## Migráció

A Master első `0.3.0` aktiválásakor a korábbi megosztott titkot enrollment tokenként használja fel, ha már létezik. A Client sikeres regisztráció után törli a korábbi megosztott titkot és az enrollment tokent.

A korábbi, nem regisztrált Client site ID nem biztosít hozzáférést. A heartbeat csak sikeres challenge-response regisztráció után fogadható el.

## Biztonsági korlátok

- A regisztrációt és heartbeat kommunikációt HTTPS kapcsolaton kell futtatni.
- Az enrollment token kezelése jelszóval azonos biztonsági szintet igényel.
- A rendszer nem engedélyez távoli parancsvégrehajtást.
- A nonce-tár WordPress transient alapú; elosztott telepítésnél megosztott objektumcache vagy adatbázis szükséges.
- A szerverórák eltérése legfeljebb öt perc lehet.
