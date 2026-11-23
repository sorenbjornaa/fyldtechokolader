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

