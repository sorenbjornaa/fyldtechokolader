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

Detaljerede audits og datamodel findes i det lokale Dropbox-projekt under `002 Website`.

