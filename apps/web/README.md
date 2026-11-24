# Fyldtechokolader frontend

Next.js App Router-frontend til `www.fyldtechokolader.dk`. Indhold hentes server-side fra det projektspecifikke WordPress REST API på `cms.fyldtechokolader.dk`.

## Lokal opsætning

Projektet forventer Node-versionen i `.nvmrc`.

```bash
nvm use
npm install
cp .env.example .env.local
npm run dev
```

Miljøvariablen `WORDPRESS_API_URL` er server-side og må ikke have `NEXT_PUBLIC_`-prefix.

## Kontrol

```bash
npm run lint
npx tsc --noEmit
npm run build
```

## Ruter

- `/` – fotohero, søgning, årgangsfilter og skaltypefilter.
- `/chokolade/[slug]` – chokoladedetaljer, rating og eventuel opskrift.
- `/sitemap.xml` – genereres fra WordPress-indholdet.

Brandfontene Bely Display og BD Supper bruges som førstevalg i CSS. Webfontfiler eller et godkendt Adobe Fonts Web Project tilføjes først, når weblicensen er bekræftet.
