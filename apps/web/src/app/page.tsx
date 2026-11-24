import Image from "next/image";
import Link from "next/link";
import { ChocolateBrowser } from "@/components/chocolate-browser";
import { RatingDisplay } from "@/components/rating-display";
import { SiteHeader } from "@/components/site-header";
import { getChocolates } from "@/lib/api";

export default async function Home() {
  const chocolates = await getChocolates();
  const hero = chocolates[0];

  if (!hero) {
    throw new Error("Der blev ikke fundet nogen chokolader.");
  }

  const summaries = chocolates.map((chocolate) => ({
    id: chocolate.id,
    slug: chocolate.slug,
    title: chocolate.title,
    description: chocolate.description,
    productionYear: chocolate.productionYear,
    productionDate: chocolate.productionDate,
    shellType: chocolate.shellType,
    badge: chocolate.badge,
    image: chocolate.image,
    rating: chocolate.rating,
  }));

  return (
    <>
      <SiteHeader overlay />
      <main>
        <section className="hero">
          {hero.image ? (
            <Image
              src={hero.image.src}
              alt={hero.image.alt || hero.title}
              fill
              priority
              sizes="100vw"
              className="hero__image"
            />
          ) : null}
          <div className="hero__veil" aria-hidden="true" />
          <div className="hero__content page-shell">
            <p className="eyebrow hero__eyebrow">
              Seneste fra {hero.productionYear ?? "samlingen"}
            </p>
            <h1>{hero.title}</h1>
            <p className="hero__description">
              {hero.description && hero.description !== hero.title
                ? hero.description
                : "En samling af håndlavede fyldte chokolader, smage og opskrifter."}
            </p>
            <RatingDisplay
              average={hero.rating.average}
              count={hero.rating.count}
            />
            <Link className="button button--primary" href={`/chokolade/${hero.slug}`}>
              Se chokoladen
              <span aria-hidden="true">↗</span>
            </Link>
          </div>
          <a className="hero__scroll" href="#chokolader">
            Udforsk samlingen
            <span aria-hidden="true">↓</span>
          </a>
        </section>

        <section className="introduction page-shell reveal-on-scroll">
          <p className="eyebrow">Smagen først</p>
          <div className="introduction__grid">
            <h2>
              Små øjeblikke,
              <br />
              gemt i chokolade.
            </h2>
            <p>
              Her samler jeg årets fyldte chokolader – fra den første idé til
              den færdige smag. Se opskrifterne, find dine favoritter, og følg
              med, når nye kombinationer kommer til.
            </p>
          </div>
        </section>

        <ChocolateBrowser chocolates={summaries} />
      </main>
      <footer className="site-footer page-shell">
        <div className="brand brand--footer">
          <span>Fyldte</span>
          <strong>chokolader</strong>
        </div>
        <p>Et privat chokoladeprojekt af Søren Bjørnå.</p>
      </footer>
    </>
  );
}
