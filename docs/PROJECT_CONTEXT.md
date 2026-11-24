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

**Version 0.1.2 verificeret på CMS 24.11.2026:** Pluginet er aktivt. API'et returnerer fortsat alle 28 chokolader, og titler, beskrivelser og SEO-titler returneres nu som ren tekst uden HTML-entiteter. Ratingtabellen indeholder fortsat 0 rækker, så installationen har ikke startet importen. Næste trin er at køre den skrivebeskyttede dry-run under Værktøjer → Chokoladeratings.

**Administrativ dry-run godkendt 24.11.2026:** Pluginets egen dry-run viser 259 indsendelser, 250 gyldige scores, præcis 206 sikre kandidater og 44 gyldige scores uden chokolade-ID. Rapporten indeholder alle 28 chokolader, og summen af deres ratingantal er 206. Resultatet matcher både den tekniske audit og den uafhængige SQL-kontrol. Ingen data er skrevet; faktisk import afventer en ny backupbekræftelse.

**Backup bekræftet 24.11.2026:** Søren har lavet en ny backup efter installation og godkendt dry-run. Forudsætningen for at starte den kontrollerede import af de 206 ratings er dermed opfyldt.

**Legacy-import gennemført og verificeret 24.11.2026:** Ratingtabellen indeholder præcis 206 rækker med 206 unikke `legacy_submission_id`-værdier. Alle rækker har kilden `legacy`, ingen mangler legacy-ID, scoreintervallet er 1–7, og ratings er fordelt på alle 28 chokolader. REST API'ets antal og gennemsnit matcher dry-run-rapporten for samtlige chokolader. De 44 historiske scores uden chokolade-ID er ikke importeret.

**Indholdsmigrering auditeret 24.11.2026:** De to chokolader uden `arstal` kan fastlægges uden gæt: Miso til 2023 og Saba & Balsamico til 2025, understøttet af både legacy-årstaxonomi og oprettelsesår. De øvrige 26 har en konkret legacy-dato, som kan opdeles i `produktionsaar` og `produktionsdato`. Skaltypeværdierne kan hovedsageligt normaliseres direkte, men tre beslutninger mangler: Pistacie-postens ACF-værdi siger hvid, mens titel og taxonomy siger mørk; `Karamel` skal enten have egen type eller mappes til Dulcey/andet; `Kokusmælke chokolade` skal enten have egen type eller mappes til lys/andet.

**Indholdsmapping besluttet og version 0.1.3 bygget 24.11.2026:** Pistacie mappes til mørk, Appelsins kokosmælkebaserede chokolade mappes til lys, og Karamel – citron & lakrids mappes til den nye type `uden-skal` (“Uden chokoladeskal”). Pluginet registrerer ACF-gruppen “Chokolade – data” og tilføjer Værktøjer → Chokoladeindhold med skrivebeskyttet dry-run samt en kontrolleret migrering af alle 28 poster. Skrivning kræver frisk backup og kontrolteksten `MIGRER 28`; legacy-felterne bevares. Miso og Saba & Balsamico får kun verificerede år, ikke opdigtede datoer. Domænetests, PHP-syntaks og WordPress Coding Standards består.

**Indholdsmigrering dry-run godkendt 24.11.2026:** Version 0.1.3 er aktiv på CMS'et. Pluginets administrationsflow fandt og validerede præcis 28 chokolader. Alle produktionsår og de 26 kendte produktionsdatoer er korrekte; Miso og Saba & Balsamico har kun år. Skaltypemappingen matcher de godkendte valg, inklusive Pistacie som mørk, Appelsin som lys og Karamel – citron & lakrids som `uden-skal`. Ingen data er ændret. Faktisk migrering afventer en frisk backup.

**Indholdsmigrering gennemført og verificeret 24.11.2026:** Alle 28 publicerede chokolader har nu `produktionsaar` og præcis én term i `chokolade_skaltype`; 26 har en kendt `produktionsdato`, mens Miso og Saba & Balsamico korrekt ikke har en præcis dato. Fordelingen er 12 hvid, 9 mørk, 4 lys, 2 Dulcey og 1 uden skal. REST API'et returnerer de nye værdier for alle 28 poster, og de fem særkontrollerede poster matcher beslutningerne. Migreringsversion 1 er registreret. Commit-rapporten viser fortsat “Skal ændres”, fordi status beregnes før skrivning; dette er en kosmetisk UI-fejl og ikke en datamigreringsfejl.

**Next.js læsefrontend og Vercel-projekt oprettet 24.11.2026:** `apps/web` er bygget med Next.js 16.4.6, TypeScript, App Router, Cache Components og Tailwind 4. Frontenden har fotohero, søgning, årgangs- og skaltypefiltre, alle 28 chokoladekort, dynamiske `/chokolade/[slug]`-sider, ratingvisning, opskriftsindhold, metadata, JSON-LD, sitemap, robots, loading-, fejl- og 404-visninger. WordPress-data hentes én gang pr. cacheperiode for at undgå One.com-rate limiting; 28 detaljesider udledes fra den komplette liste-respons. Lokal lint, TypeScript og produktionsbuild består med 35 genererede sider. UI er browserkontrolleret på desktop samt 390 og 320 px; søgning og navigation virker uden konsolfejl ud over forventede Next.js-devbeskeder. Vercel-projektet `b-design/fyldtechokolader` er GitHub-forbundet med root `apps/web`, og `WORDPRESS_API_URL` findes i development, preview og production. Interaktiv ratinginput og endeligt domæneskift er ikke udført endnu. Brandfontene bruger lokale fontnavne og fallback, indtil webfontlicensen er bekræftet.

**Ny rating-slider implementeret 24.11.2026:** Figma-varianten `35:1079` er omsat til en tilgængelig React-range-slider på chokoladedetaljen. Den bruger skala 1–7, starter ved midterværdien 4, følger drag og tastaturinput med dynamisk guld/brun gradient samt hvide stjerner og inaktive prikker og skriver først ved “Gem bedømmelse”. Next.js-routehandleren opretter en anonym HttpOnly-browseridentitet, validerer same-origin og score, HMAC-signerer requesten til WordPress og invaliderer chokoladecachen efter succes. Lokal UI-, fejltilstands-, lint-, TypeScript- og buildkontrol består. Den delte `FYLDTECHOKOLADER_API_SECRET` mangler fortsat i WordPress og Vercel; den skal konfigureres før gemmeflowet kan aktiveres og end-to-end-testes mod CMS'et.

**Masonry-inspireret kortgrid 24.11.2026:** Desktop-forskydningen på hvert andet kort er fjernet. Samlingsgridet bruger ved mindst 1200 px et 24-kolonners mønster med afdæmpede variationer på 9, 8 og 7 kolonner, enkelte beslægtede billedformater og dynamisk beregnede række-spans, så kortene pakker tættere under kortere kolonner. De første tre kort starter på samme linje. Mobil og tablet beholder de enkle én- og tokolonnelayouts, og filtrering genberegner layoutet uden inline-rækker på mindre skærme.

**Version 0.1.4 og ratingkonfiguration 24.11.2026:** En frisk backup er bekræftet. Pluginversion `0.1.4` kan hente HMAC-hemmeligheden fra `FYLDTECHOKOLADER_API_SECRET` i `wp-config.php` eller som fallback fra den ikke-autoloadede WordPress-option `fyldtechokolader_api_secret`. Den fælles hemmelighed er oprettet og gemt uden eksponering i både WordPress og Vercel for development, preview og production. Testpakken, PHP-syntaks og WordPress Coding Standards består, og installations-ZIP'en er bygget. CMS'et kører fortsat `0.1.3`; `0.1.4` skal installeres, før første end-to-end-rating kan gennemføres.

**Version 0.1.4 verificeret og signeringsflow testet 24.11.2026:** Pluginet er installeret, aktivt og rapporterer version `0.1.4`. En usigneret kontrolrequest direkte til WordPress afvises med HTTP 401, mens den tilsvarende request gennem Vercels Next.js-route bliver HMAC-signeret, godkendt af WordPress og når frem til den forventede 404 for en ikke-eksisterende test-slug. Dermed er den fælles hemmelighed og hele server-til-server-flowet verificeret uden at oprette en rating. Ratingtabellen indeholder fortsat præcis 206 rækker. Live-UI'en åbner slideren, reagerer på tastaturinput og kan annulleres uden konsolfejl. En virkelig rating afventer et subjektivt valg af chokolade og score.

**Første live-rating gennemført 24.11.2026:** Søren valgte `Citron lakrids` (`citron-lakrids-2`) og scoren 5/7. Ratingen blev indsendt gennem den deployede slider, gemt af det signerede WordPress-endpoint og bekræftet af både UI og API. Chokoladen gik fra 3 ratings med gennemsnit 7,0 til 4 ratings med gennemsnit 6,5; fordelingen er tre 7-taller og ét 5-tal. Den samlede ratingtabel steg fra 206 til 207 rækker. Browseren viste succesbeskeden uden konsolfejl.

Detaljerede audits og datamodel findes i det lokale Dropbox-projekt under `002 Website`.

