# fyldtechokolader-core

Projektspecifikt WordPress-plugin til datamodel, individuelle ratings, kontrolleret legacy-import og REST API til Next.js.

## Status

Version `0.1.4` gør ratingintegrationen klar til drift på hosts, hvor `wp-config.php` ikke kan ændres automatisk. En `wp-config.php`-konstant har fortsat førsteprioritet, men pluginet kan som fallback læse hemmeligheden fra en ikke-autoloadet WordPress-option.

Pluginet indeholder:

- Sikker overgang af posttypen `chokolade` fra child theme til plugin.
- Ny taxonomy `chokolade_skaltype`.
- Databasetabellen `{prefix}fyld_ratings` med én aktiv rating pr. anonym browser og chokolade.
- Gennemsnit, antal og fordeling på skalaen 1–7.
- Offentlige læse-endpoints og et HMAC-beskyttet skrive-endpoint.
- Ratingoversigt og kontrolleret legacy-import under **Værktøjer → Chokoladeratings**.
- Dry-run og kontrolleret indholdsmigrering under **Værktøjer → Chokoladeindhold**.
- ACF-gruppen **Chokolade – data** med smagsbeskrivelse, produktionsår, produktionsdato og badge.
- WP-CLI-import af historiske Elementor-ratings med obligatorisk dry-run som standard.

Pluginet sletter ikke data ved deaktivering eller afinstallation.

## Forside

Fra version `0.1.5` ligger ACF-gruppen **Forside – indhold** på siden valgt under **Indstillinger → Læsning**. Her kan redaktøren styre heroens overlinje, H1, beskrivelse, knaptekst og samlingsbillede samt introduktionens tekst. Samlingsbilledet vælges i mediebiblioteket, hvor alt-teksten også redigeres. Både billedet og knappen linker til `#chokolader`.

Frontendens standardindhold vises, hvis et felt står tomt. Gem ændringer med **Opdater**. Next.js bruger minutcache til forsideindholdet, så ændringer kan vises med en kort forsinkelse.

## Indholdsmigrering

Under **Værktøjer → Chokoladeindhold** kan en administrator først køre en skrivebeskyttet dry-run for alle 28 chokolader. Den viser legacy-dato, nyt produktionsår og -dato samt gammel og ny skaltype pr. post.

Den faktiske migrering kræver en frisk backup og kontrolteksten `MIGRER 28`. Den opretter de nye meta-værdier og tildeler præcis én term i `chokolade_skaltype`, men sletter ikke legacy-felterne eller den gamle taxonomy. Migreringen er idempotent og kan kontrolleres med en ny dry-run efter gennemførelsen.

## REST API

Namespace: `/wp-json/fyldtechokolader/v1`

```text
GET  /chokolader
GET  /chokolader/{slug}
GET  /chokolader/{slug}/rating
POST /chokolader/{slug}/rating
GET  /skaltype
GET  /aar
GET  /forside
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

Hvis hostens `wp-config.php` ikke er tilgængelig, kan den samme værdi gemmes i databasen uden autoload:

```bash
wp option update fyldtechokolader_api_secret '<hemmelighed>' --autoload=no
```

Konstanten i `wp-config.php` har altid forrang over optionen.

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

