# Milestone 4 – Capability Engine

## Cél

A Capability Engine a WordPress-verzióktól függetlenül írja le, hogy egy Client telepítés milyen protokollokat és funkciókat képes biztonságosan végrehajtani. Ez lesz a Job Queue, a Bulk Actions és a későbbi AI automatizálás előfeltétele.

## Stabil capability azonosítók

A capability nem plugin- vagy WordPress-verziót jelöl, hanem egy stabil funkcionális szerződést.

- `protocol.registration`
- `protocol.heartbeat`
- `inventory.snapshot`
- `runtime.multisite`

Minden capability saját szemantikus verzióval rendelkezik. Egy WordPress-frissítés önmagában nem módosít capability-verziót.

## Feature discovery

A Core `FeatureSet` és `RequirementEvaluator` deklaratív követelményeket értékel:

- PHP extension;
- függvény;
- osztály;
- konstans;
- futásidejű flag.

A Client capability deklarációi `requires` metaadatot tartalmaznak. A `CapabilityDiscovery` csak azokat helyezi a manifestbe, amelyek követelményei ténylegesen elérhetők.

## Biztonságos negotiation

### Regisztráció

1. A Client a registration challenge kéréshez csatolja a capability manifestet.
2. A manifest a challenge transient rekordjába kerül.
3. A Master csak a kliens privátkulcs-birtoklásának ellenőrzése után tárgyalja és tárolja a capabilityket.
4. A Master az elfogadott capabilityk checksumát belefoglalja az Ed25519-aláírt registration proof üzenetbe.
5. A Client ellenőrzi az aláírást és a checksumot, majd helyben tárolja a negotiation eredményét.

### Heartbeat

1. A capability manifest az aláírt heartbeat body része.
2. A Master minden heartbeatnél frissíti a per-site capability állapotot.
3. A negotiation checksum a Master által aláírt heartbeat receipt része.
4. A Client csak érvényes receipt és capability checksum esetén fogadja el az eredményt.

## Master policy

A jelenlegi kötelező policy:

```text
protocol.registration >= 1.0.0
protocol.heartbeat    >= 1.0.0
inventory.snapshot    >= 1.0.0
```

A policy külön szolgáltatás, ezért később környezet, tenant, csoport vagy jobtípus alapján bővíthető.

## Persistence

A Master `wpccai_capabilities` táblája per site és capability tárolja:

- capability ID;
- capability-verzió;
- negotiated állapot;
- jelentési idő.

Az egyedi kulcs `(site_id, capability_id)`, a capability ID és negotiated mező indexelt. A repository támogatja:

- site manifest lekérdezését;
- minimum capability-verzió ellenőrzését;
- fleet szintű capability összesítést.

A Client az utolsó ellenőrzött negotiation eredményt WordPress optionként tárolja, amelyet a későbbi Job Queue végrehajtási kapuk használhatnak.

## Kiterjesztési szabály

Új távoli művelet csak akkor válhat jobtípussá, ha:

1. stabil capability ID-val rendelkezik;
2. a Client deklarálja a futásidejű követelményeit;
3. a Master policy vagy job-specifikus követelmény tárgyalja;
4. a Job Queue dispatch előtt ellenőrzi a per-site negotiated capabilityt.
