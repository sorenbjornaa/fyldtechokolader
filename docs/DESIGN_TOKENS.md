# Designtokens på web

De 74 variabler i Figma-filen **Design system** er afstemt med `apps/web/src/app/tokens.css`. Paletten og de semantiske farver følger Figma, inklusive aliaserne. Afstande, radier, touchmål, container, grid og typografi følger Figma-tilstandene Mobile, Tablet og Desktop. Web bruger sit eksisterende skift ved 768 og 1200 px. Skriftfamilierne bruger Adobe Fonts' CSS-navne og webfallbacks.

Brug tokenets semantiske rolle ved valg af farve. Ved identiske afstande bruges den tilsvarende værdi fra afstandsskalaen. Ved nye Figma-variabler bruges deres angivne Web-syntaks.

## Afstemte farvetokens

Figma og CSS bruger samme Web-syntaks for de tidligere navnekollisioner samt fejltekst og diskrete kanter.

| Figma-variabel | Web-syntaks |
| --- | --- |
| `color/border/action` | `var(--ft-color-border-action)` |
| `color/rating/inverse` | `var(--ft-color-rating-inverse)` |
| `color/text/subtle` | `var(--ft-color-text-subtle)` |
| `color/feedback/error-text` | `var(--ft-color-feedback-error-text)` |
| `color/border/line` | `var(--ft-color-line)` |

Brug fejlteksttonen `#f0b1a2` til lille tekst på mørke flader. Den giver ca. 9,51:1 mod sidebaggrunden og 7,85:1 mod den diskrete flade. Den almindelige fejltone `#c85c5c` giver henholdsvis ca. 4,25:1 og 3,51:1.

## Webspecifikke værdier

`--ft-header-height` er højden på webnavigationen og bruges i layoutberegninger for hero og detaljeside.

`--rating-progress` er sliderens aktuelle tilstand og beregnes ud fra brugerens valg.

## Layoutværdier med egen funktion på web

- Forsidens og detaljesidens flydende overskrifter, sidens maksbredde på 1440 px og masonry-gridets 24-spaltede underopdeling bevarer deres egne regler. Figma-tokens for overskrifter, containerbredde og almindeligt grid findes i CSS til de komponenter, hvor de passer til layoutet.
- Heroens billedgradient, navigationens toning, sliderens skygge og animationstider er komponentvalg. Overvej en Figma-stil for gradient eller skygge, hvis effekten skal genbruges. Behold individuelle kantbredder, ikonmål og rent tekniske mål i komponenterne, medmindre der opstår en fælles skala.
