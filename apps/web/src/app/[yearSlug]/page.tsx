import Image from "next/image";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { ChocolateBrowser } from "@/components/chocolate-browser";
import { SiteHeader } from "@/components/site-header";
import { chocolateSummaries, getChocolates, getYearPages } from "@/lib/api";
import { pageMetadata } from "@/lib/metadata";

type YearPageProps = PageProps<"/[yearSlug]">;
const fallbackImage = { src: "/year-collection.png", alt: "" };

async function findYearPage(params: YearPageProps["params"]) {
  const [{ yearSlug }, pages] = await Promise.all([params, getYearPages()]);
  return pages.find(({ slug }) => decodeURIComponent(slug) === yearSlug);
}

export async function generateMetadata({ params }: YearPageProps) {
  const page = await findYearPage(params);
  if (!page) notFound();

  return pageMetadata(`/${page.slug}`, {
    title: page.heading,
    description: page.description || `Se de håndlavede fyldte chokolader fra ${page.year}.`,
    image: (page.image ?? fallbackImage).src,
  }, page.seo);
}

async function YearCollection({ params }: YearPageProps) {
  const page = await findYearPage(params);
  if (!page) notFound();

  const chocolates = (await getChocolates()).filter(({ productionYear }) => productionYear === page.year);
  const image = page.image ?? fallbackImage;

  return (
    <>
      <SiteHeader overlay currentYearSlug={page.slug} />
      <main>
        <section className="hero hero--year" aria-labelledby="year-heading">
          <a className="hero__media" href="#chokolader" aria-label={`Se chokoladerne fra ${page.year}`}>
            <Image src={image.src} alt={image.alt} fill fetchPriority="high" loading="eager" sizes="(min-width: 1200px) 115vw, 100vw" className="hero__image" />
          </a>
          <div className="hero__veil" aria-hidden="true" />
          <div className="hero__content page-shell">
            <p className="eyebrow hero__eyebrow">{page.eyebrow || `Chokolader – ${page.year}`}</p>
            <h1 id="year-heading">{page.heading}</h1>
            {page.description && <p className="hero__description">{page.description}</p>}
          </div>
        </section>
        <ChocolateBrowser key={page.slug} chocolates={chocolateSummaries(chocolates)} fixedYear={page.year} />
      </main>
    </>
  );
}

export default function YearPage(props: YearPageProps) {
  return (
    <Suspense fallback={<div className="loading-page__hero" aria-hidden="true" />}>
      <YearCollection {...props} />
    </Suspense>
  );
}
