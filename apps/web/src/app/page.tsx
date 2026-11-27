import Image from "next/image";
import { ChocolateBrowser } from "@/components/chocolate-browser";
import { SiteHeader } from "@/components/site-header";
import { getChocolates, getFrontPage } from "@/lib/api";
import { pageMetadata } from "@/lib/metadata";

export async function generateMetadata() {
  const page = await getFrontPage();
  return pageMetadata("/", {
    title: "Fyldte chokolader",
    description: page?.hero.description || "Håndlavede fyldte chokolader, smagskombinationer, opskrifter og vurderinger.",
    image: page?.hero.image?.src,
  }, page?.seo);
}

export default async function Home() {
  const [chocolates, frontPage] = await Promise.all([
    getChocolates(),
    getFrontPage(),
  ]);
  const latest = chocolates[0];
  const hero = frontPage?.hero;
  const introduction = frontPage?.introduction;
  const image = hero?.image ?? latest?.image;
  const buttonLabel = hero?.buttonLabel || "Se alle chokolader";
  const latestDescription = latest?.description !== latest?.title
    ? latest?.description
    : undefined;

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
          {image ? (
            <a className="hero__media" href="#chokolader" aria-label={buttonLabel}>
              <Image
                src={image.src}
                alt={image.alt}
                fill
                fetchPriority="high"
                loading="eager"
                sizes="100vw"
                className="hero__image"
              />
            </a>
          ) : null}
          <div className="hero__veil" aria-hidden="true" />
          <div className="hero__content page-shell">
            <p className="eyebrow hero__eyebrow">
              {hero?.eyebrow || `Seneste fra ${latest?.productionYear ?? "samlingen"}`}
            </p>
            <h1>{hero?.heading || latest?.title || "Fyldte chokolader"}</h1>
            <p className="hero__description">
              {hero?.description || latestDescription || "En samling af håndlavede fyldte chokolader, smage og opskrifter."}
            </p>
            <a className="button button--primary" href="#chokolader">
              {buttonLabel}
              <svg
                className="button__icon"
                aria-hidden="true"
                viewBox="0 0 24 24"
              >
                <path d="M12 5v14m-6-6 6 6 6-6" />
              </svg>
            </a>
          </div>
        </section>

        <section className="introduction page-shell reveal-on-scroll">
          <p className="eyebrow">{introduction?.eyebrow || "Smagen først"}</p>
          <div className="introduction__grid">
            <h2>
              {introduction?.heading || <>Små øjeblikke,<br />gemt i chokolade.</>}
            </h2>
            <p>
              {introduction?.description || "Her samler jeg årets fyldte chokolader – fra den første idé til den færdige smag. Se opskrifterne, find dine favoritter, og følg med, når nye kombinationer kommer til."}
            </p>
          </div>
        </section>

        <ChocolateBrowser chocolates={summaries} />
      </main>
    </>
  );
}
