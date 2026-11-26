# Arbejdsregler – Fyldtechokolader kodebase

Læs `docs/PROJECT_CONTEXT.md` før arbejde i repositoryet.

- Sørens aktuelle bestilling har forrang.
- Skriv dokumentation på dansk og kode/API-navne på engelsk, medmindre eksisterende WordPress-feltnavne skal bevares under migrering.
- Brug eksisterende Figma-tokens med samme navn og værdi i CSS, når de dækker behovet. Angiv nye webtokens og vurder, om de bør føres tilbage til Figma.
- Bevar eksisterende slugs, WordPress-ID-mapping og historiske ratings, indtil en migration er verificeret.
- Ændringer direkte på produktions- eller CMS-sitet kræver en udtrykkelig bestilling.
- Oprettelse, redigering og publicering af almindeligt WordPress-indhold kræver ingen frisk backup. Det gælder alle posttyper, herunder sider og chokolader, samt indhold i deres eksisterende ACF-felter.
- Andre ændringer direkte på produktions- eller CMS-sitet kræver fortsat frisk backup, herunder pluginopdateringer, migreringer og sletning.
- WordPress-forretningslogik hører til i `fyldtechokolader-core`, ikke i tema eller Elementor.
- Next.js må bruge det projektspecifikke REST API og må ikke bindes direkte til legacy-ACF-navne.
- Hemmeligheder, adgangskoder, tokens, persondata og miljøfiler må ikke committes.
- Brug små, målrettede commits og verificér PHP, TypeScript, tests og API-kontrakter før deployment.

Den udførlige projektviden og audits ligger lokalt i Dropbox-projektet:
`/Users/sorenbjornaa/Dropbox/eget/004 Personlige AI-projekter/007 Fyldtechokolader`.

