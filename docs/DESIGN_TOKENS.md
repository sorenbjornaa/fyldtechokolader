# Designtokens på web

De 70 variabler i Figma-filen **Design system** er afstemt med `apps/web/src/app/tokens.css`. Paletten og de semantiske farver følger Figma, inklusive aliaserne. Afstande, radier, touchmål, container, grid og typografi følger Figma-tilstandene Mobile, Tablet og Desktop. Web bruger sit eksisterende skift ved 768 og 1200 px. Skriftfamilierne bruger Adobe Fonts' CSS-navne og webfallbacks.

Brug tokenets semantiske rolle ved valg af farve. Ved identiske afstande bruges den tilsvarende værdi fra afstandsskalaen. Ved nye Figma-variabler bruges deres angivne Web-syntaks.

## Anbefalede rettelser i Figma

Tre Figma-variabler deler Web-syntaks med en anden variabel. CSS bruger derfor entydige navne. Ret Web-syntaksen i Figma, så kommende eksporter får samme navne.

| Figma-variabel | Deler Web-syntaks med | Anbefalet Web-syntaks |
| --- | --- | --- |
| `color/border/action` | `color/border/subtle` | `var(--ft-color-border-action)` |
| `color/rating/inverse` | `color/rating/inactive` | `var(--ft-color-rating-inverse)` |
| `color/text/subtile` | `color/text/inverse` | `var(--ft-color-text-subtle)` efter navnerettelse i Figma. CSS bruger foreløbig `--ft-color-text-subtile` for at kunne genfindes ved det nuværende Figma-navn. |

## Webspecifikke tokens og anbefalinger

| Token | Anvendelse og vurdering |
| --- | --- |
| `--ft-color-feedback-error-text` | Lys fejltekst til mørke flader. Løft gerne tilbage i Figma som `color/feedback/error-text`. Figma-fejltonen `#c85c5c` giver ca. 4,25:1 mod sidebaggrunden og 3,51:1 mod den diskrete flade. Webværdien `#f0b1a2` giver ca. 9,51:1 og 7,85:1. |
| `--ft-color-line` | Diskrete skillelinjer og kanten på søgemærket. Overvej `color/border/line` i Figma, hvis den samme transparente behandling bruges på tværs af komponenter. |
| `--ft-header-height` | Højden på webnavigationen bruges i beregninger for hero og detaljeside. Behold i web, medmindre højden skal fastlægges som en delt komponentvariabel i Figma. |

`--rating-progress` er sliderens aktuelle tilstand og beregnes ud fra brugerens valg.

## Layoutværdier med egen funktion på web

- Forsidens og detaljesidens flydende overskrifter, sidens maksbredde på 1440 px og masonry-gridets 24-spaltede underopdeling bevarer deres egne regler. Figma-tokens for overskrifter, containerbredde og almindeligt grid findes i CSS til de komponenter, hvor de passer til layoutet.
- Heroens billedgradient, navigationens toning, sliderens skygge og animationstider er komponentvalg. Overvej en Figma-stil for gradient eller skygge, hvis effekten skal genbruges. Behold individuelle kantbredder, ikonmål og rent tekniske mål i komponenterne, medmindre der opstår en fælles skala.
