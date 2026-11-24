# Projektkontekst

## Besluttet løsning

- CMS: `https://cms.fyldtechokolader.dk`
- Backend: WordPress + ACF på One.com
- Frontend: Next.js på Vercel
- Integration: REST API under `/wp-json/fyldtechokolader/v1`
- Elementor bruges kun som reference under migreringen og udfases fra frontend.

## Indhold

- 28 publicerede chokolader.
- Custom post type: `chokolade`.
- Eksisterende taxonomy: `chokolade_typer`, som blander år og skaltype.
- Eksisterende slugs og QR-destinationer skal bevares.
- WordPress Media Library er billedkilde.

## Ratings

- Brugeren afgiver en heltalsrating fra 1 til 7 med en slider.
- Gennemsnittet beregnes separat pr. chokolade og vises med én decimal.
- Nye ratings gemmes individuelt i en separat WordPress-tabel.
- En anonym browser har én aktiv, ændringsbar stemme pr. chokolade.
- Ingen navn, e-mail eller permanent IP-adresse gemmes i v1.
- 206 historiske Elementor-ratings har gyldig score og sikkert chokolade-ID og skal migreres.
- 44 tidlige scores stammer fra før rating blev knyttet til en chokolade; de arkiveres, men migreres ikke.
- Legacy-ACF-feltet indeholder den seneste stemme, ikke et gennemsnit.

## Implementeringsrækkefølge

1. Byg og test `fyldtechokolader-core` lokalt.
2. Opret ratingtabel, REST API og idempotent dry-run-import.
3. Verificér de 206 legacy-ratings pr. chokolade.
4. Installér pluginet kontrolleret på CMS efter backup.
5. Byg Next.js-frontenden mod det nye API.
6. Forbind GitHub-repositoryet til Vercel.

## Pluginarkitektur

- WordPress-forretningslogikken samles i ét plugin: `fyldtechokolader-core`.
- Pluginet opdeles internt i moduler til indholdsmodel, ratings, REST API, legacy-import, administration og infrastruktur.
- Rating og REST API udgives ikke som separate plugins, fordi de deler datamodel, validering, versionsstyring og deployment.
- Pluginet bygges og testes lokalt, før det installeres på CMS'et.

## Implementeringsstatus

**24.11.2026:** Lokal version `0.1.0` af `fyldtechokolader-core` er bygget. Den indeholder pluginregistrering, ratingtabel, ratingservice, HMAC-beskyttet skrive-endpoint, offentlige læse-endpoints, skrivebeskyttet adminoversigt og en idempotent WP-CLI-import med dry-run. Import med skrivning afbrydes, hvis analysen ikke finder præcis 206 sikre legacy-ratings. Composer-validering, domænetests, PHP-syntaks, WordPress Coding Standards og Git whitespace-kontrol består. Pluginet er endnu ikke installeret eller integrationstestet på CMS'et.

**Integrationstest 24.11.2026:** Version `0.1.0` er installeret og aktiv på CMS'et, og databaseskema version 1 er oprettet. Endpoints til år og skaltyper virker. Liste-endpointet afslørede en fejl, hvor WordPress REST API sendte requestobjektet som ekstra argument til `sanitize_title` for et tomt standardfilter. Rettelsen er implementeret lokalt i version `0.1.1` med en regressionstest. CMS'et skal opdateres til `0.1.1`, før API-kontrollen fortsætter. WPVibes WP-CLI-emulator tillader ikke projektspecifikke kommandoer, så importerens dry-run kan ikke køres gennem denne forbindelse.

**Verificeret 24.11.2026:** Version `0.1.1` er aktiv på CMS'et. Liste- og detalje-endpoints, ratingoversigt, søgning samt årsfilter virker, og API'et returnerer alle 28 chokolader. En uafhængig skrivebeskyttet SQL-dry-run mod Elementor-tabellerne fandt 259 indsendelser, 250 gyldige scores, præcis 206 sikre ratings fordelt på alle 28 chokolader samt 44 gyldige scores uden chokolade-ID. Den nye ratingtabel indeholder 0 rækker; ingen ratings er importeret endnu. API'et returnerer fortsat HTML-entiteter i visse titler, og skaltypeværdierne er endnu legacy-data, indtil indholdsmigreringen udføres.

**Version 0.1.2 bygget 24.11.2026:** API-tekst normaliseres, så HTML-entiteter ikke sendes til Next.js. Adminsiden er udvidet med en nonce- og rettighedsbeskyttet dry-run/import samt rapport pr. chokolade. Skrivning kræver frisk backup-bekræftelse og kontrolteksten `IMPORTER 206`; importen genkontrollerer kandidaterne og kører i en databasetransaktion. Installationen udfører ikke import automatisk. Domænetests, PHP-syntaks og WordPress Coding Standards består.

Detaljerede audits og datamodel findes i det lokale Dropbox-projekt under `002 Website`.

