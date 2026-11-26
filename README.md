# Fyldtechokolader

Kodebase til den nye headless version af Fyldtechokolader.

## Arkitektur

- WordPress og ACF på One.com som headless CMS.
- Projektspecifikt WordPress-plugin til datamodel, ratings og REST API.
- Next.js-frontend hostet på Vercel.
- Rating fra 1–7 via slider; gennemsnit beregnes pr. chokolade.

## Struktur

```text
apps/web/                                  Next.js-frontend
wordpress/plugins/fyldtechokolader-core/  WordPress-plugin
docs/                                      Teknisk projektkontekst
```

Se [docs/PROJECT_CONTEXT.md](docs/PROJECT_CONTEXT.md), før udviklingen begynder.

## Lav et lokalt commit

Når alle dine lokale ændringer hører til samme opgave, kan du bruge én kommando:

```sh
bash scripts/commit.sh "Beskriv ændringen" --all
```

Scriptet viser, hvilke filer der kommer med. Svar `j` for at fortsætte eller `v` for at se hele diffen. Det kører lint og produktionsbuild ved ændringer i frontend og PHP-tests og syntakskontrol ved ændringer i pluginet. Kontrollerne bruger de valgte filversioner. Et frontendbuild kræver adgang til projektets WordPress-API.

Hvis arbejdsområdet indeholder flere opgaver, kan du stage det ønskede i Git eller VS Code og køre kommandoen uden `--all`. Du kan også vælge hele filer efter `--` eller vælge enkelte ændringer med `--patch FIL`.

```sh
bash scripts/commit.sh "Opdatér filtre" --patch apps/web/src/app/globals.css -- apps/web/src/components/chocolate-browser.tsx
```

Efter det lokale commit spørger scriptet, om du vil pushe grenen til GitHub. Svar `j` for at sende committet og eventuelle tidligere lokale commits på grenen. Tryk Enter for at beholde dem lokalt. Push til `main` kan starte en produktionsudrulning på Vercel.

