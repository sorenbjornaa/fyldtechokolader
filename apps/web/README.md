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

## Cookiebot og Google Analytics

Sæt `COOKIEBOT_DOMAIN_GROUP_ID` til den eksisterende domænegruppes CBID og `GA_MEASUREMENT_ID` til `G-C563M1LSHN` i Vercels **Production**-miljø, når privatlivssiden og gruppens domæner er gennemgået. Preview indlæser hverken Cookiebot eller GA4. Lokalt kan variablerne angives ved opstart til test med simulerede kald. Google-tagget indlæses ved Cookiebots samtykke til statistik.

Efter udrulning kontrolleres afvisning, accept og tilbagekaldelse i en ny browsersession. Kontrollér derefter en testsidevisning i GA4 Realtime på det ønskede webdatastrøm-id, også ved navigation mellem forsiden og en chokolade.

## Staging og privatlivspolitik

Push til `staging` opretter en Vercel Preview. Åbn den under projektets **Deployments**. Preview kan læse publiceret CMS-indhold, mens rating-API'et afviser skrivning, og `robots.txt` afviser crawl.

`/privatlivspolitik` viser den publicerede side valgt under **Indstillinger → Privatliv** i WordPress via Core-pluginets `/privatlivspolitik`-endpoint fra version 0.1.6. Forsiden, privatlivspolitikken og chokoladesiderne bruger samme SEO-kontrakt med metadata fra Yoast. CMS'ets globale noindex holdes adskilt fra de offentlige siders indeksvalg.

## Kontrol

```bash
npm run lint
npx tsc --noEmit
npm run build
```

## Årgangssider

Fra Core 0.1.7 kan du oprette en WordPress-side på øverste niveau eller genbruge en eksisterende. Udfyld **Produktionsår** under **Årgangsside**, og redigér overlinje, H1, introduktion og topbillede. Hvis H1 eller topbillede er tomt, bruger websitet sidens titel eller designets samlingsfoto. Lad **Produktionsår** stå tomt på almindelige sider.

Ved publicering bruger websitet sidens WordPress-slug som adresse og viser de chokolader, der har samme produktionsår. Menuen linker til den publicerede årgangsside med det højeste årstal. De offentlige sider kan være cached i op til ét minut, før ændringerne begynder at slå igennem.

## Ruter

- `/` – fotohero, søgning, årgangsfilter og skaltypefilter.
- `/chokolade/[slug]` – chokoladedetaljer, rating og eventuel opskrift.
- `/sitemap.xml` – genereres fra WordPress-indholdet.

Brandfontene Bely Display og BD Supper bruges som førstevalg i CSS. Webfontfiler eller et godkendt Adobe Fonts Web Project tilføjes først, når weblicensen er bekræftet.
