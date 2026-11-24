# fyldtechokolader-core

Projektspecifikt WordPress-plugin til datamodel, individuelle ratings, kontrolleret legacy-import og REST API til Next.js.

## Status

Version `0.1.2` normaliserer HTML-entiteter i API-tekst og tilføjer en kontrolleret administrationsside til legacy-importen.

Pluginet indeholder:

- Sikker overgang af posttypen `chokolade` fra child theme til plugin.
- Ny taxonomy `chokolade_skaltype`.
- Databasetabellen `{prefix}fyld_ratings` med én aktiv rating pr. anonym browser og chokolade.
- Gennemsnit, antal og fordeling på skalaen 1–7.
- Offentlige læse-endpoints og et HMAC-beskyttet skrive-endpoint.
- Ratingoversigt og kontrolleret legacy-import under **Værktøjer → Chokoladeratings**.
- WP-CLI-import af historiske Elementor-ratings med obligatorisk dry-run som standard.

Pluginet sletter ikke data ved deaktivering eller afinstallation.

## REST API

Namespace: `/wp-json/fyldtechokolader/v1`

```text
GET  /chokolader
GET  /chokolader/{slug}
GET  /chokolader/{slug}/rating
POST /chokolader/{slug}/rating
GET  /skaltype
GET  /aar
```

`POST /rating` kræver JSON med `score` og `voterToken`. Next.js-serveren signerer requesten med disse headers:

```text
X-Fyldtechokolader-Timestamp: <unix timestamp>
X-Fyldtechokolader-Signature: <hex HMAC-SHA256>
```

Den kanoniske streng er:

```text
<timestamp>\n<METHOD>\n<REST route>\n<SHA256(raw body)>
```

Signaturen bruger en hemmelighed på mindst 32 tegn, som senere sættes uden for Git i `wp-config.php`:

```php
define( 'FYLDTECHOKOLADER_API_SECRET', 'en-tilfaeldig-hemmelighed-paa-mindst-32-tegn' );
```

Samme hemmelighed gemmes som en krypteret environment variable i Vercel. Den sendes aldrig til browseren.

## Legacy-import

### WordPress-administration

Under **Værktøjer → Chokoladeratings** kan en administrator:

1. Køre en dry-run, som ikke ændrer data.
2. Kontrollere, at resultatet viser præcis 206 sikre ratings.
3. Bekræfte en frisk backup og skrive `IMPORTER 206`.
4. Starte den idempotente import.

Importen analyserer dataene igen umiddelbart før skrivning, afbrydes ved andet end 206 sikre ratings og bruger en databasetransaktion, så en fejl ikke efterlader en delvis import.

### WP-CLI

Dry-run ændrer ingen data:

```bash
wp fyldtechokolader ratings import-legacy
```

Import kræver både flag og interaktiv bekræftelse:

```bash
wp fyldtechokolader ratings import-legacy --commit
```

En skrivende import afbrydes automatisk, hvis kontrollen ikke finder præcis de forventede 206 sikre ratings. `legacy_submission_id` er unikt, så samme import kan køres igen uden dubletter.

## Lokal validering

Fra pluginmappen:

```bash
composer test
composer lint
~/.composer/vendor/bin/phpcs
```

En rigtig WordPress-installation er nødvendig for integrationstest af aktivering, REST-ruter og databaseimport. Pluginet installeres først på CMS'et efter godkendt lokal kontrol og frisk backup.

