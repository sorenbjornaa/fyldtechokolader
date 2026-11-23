# Arbejdsregler – Fyldtechokolader kodebase

Læs `docs/PROJECT_CONTEXT.md` før arbejde i repositoryet.

- Sørens aktuelle bestilling har forrang.
- Skriv dokumentation på dansk og kode/API-navne på engelsk, medmindre eksisterende WordPress-feltnavne skal bevares under migrering.
- Bevar eksisterende slugs, WordPress-ID-mapping og historiske ratings, indtil en migration er verificeret.
- Foretag ikke ændringer direkte på produktions- eller CMS-sitet uden frisk backup og udtrykkelig bestilling.
- WordPress-forretningslogik hører til i `fyldtechokolader-core`, ikke i tema eller Elementor.
- Next.js må bruge det projektspecifikke REST API og må ikke bindes direkte til legacy-ACF-navne.
- Hemmeligheder, adgangskoder, tokens, persondata og miljøfiler må ikke committes.
- Brug små, målrettede commits og verificér PHP, TypeScript, tests og API-kontrakter før deployment.

Den udførlige projektviden og audits ligger lokalt i Dropbox-projektet:
`/Users/sorenbjornaa/Dropbox/eget/004 Personlige AI-projekter/007 Fyldtechokolader`.

