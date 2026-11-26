import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { ChocolateCard } from "@/components/chocolate-card";
import { RatingForm } from "@/components/rating-form";
import { SiteHeader } from "@/components/site-header";
import { getChocolate, getChocolates } from "@/lib/api";

type ChocolatePageProps = {
  params: Promise<{ slug: string }>;
};

export async function generateStaticParams() {
  const chocolates = await getChocolates();
  return chocolates.map((chocolate) => ({ slug: chocolate.slug }));
}

export async function generateMetadata({
  params,
}: ChocolatePageProps): Promise<Metadata> {
  const { slug } = await params;
  const chocolate = await getChocolate(slug);

  if (!chocolate) {
    return { title: "Chokoladen blev ikke fundet" };
  }

  return {
    title: chocolate.seo.title || chocolate.title,
    description:
      chocolate.seo.description ||
      chocolate.description ||
      `${chocolate.title} – en fyldt chokolade fra ${chocolate.productionYear ?? "samlingen"}.`,
    openGraph: {
      title: chocolate.seo.title || chocolate.title,
      description: chocolate.seo.description || chocolate.description,
      type: "article",
      images: chocolate.seo.image ? [chocolate.seo.image] : [],
    },
  };
}

function formatDate(date: string | null): string | null {
  if (!date) {
    return null;
  }

  return new Intl.DateTimeFormat("da-DK", {
    day: "numeric",
    month: "long",
    year: "numeric",
    timeZone: "UTC",
  }).format(new Date(`${date}T12:00:00Z`));
}

export default function ChocolatePage({ params }: ChocolatePageProps) {
  return (
    <Suspense fallback={<DetailFallback />}>
      <ChocolatePageContent params={params} />
    </Suspense>
  );
}

function DetailFallback() {
  return (
    <main className="loading-page" aria-label="Henter chokoladen">
      <div className="loading-page__hero" />
      <div className="loading-page__lines page-shell">
        <span />
        <span />
        <span />
      </div>
    </main>
  );
}

async function ChocolatePageContent({ params }: ChocolatePageProps) {
  const { slug } = await params;
  const [chocolate, chocolates] = await Promise.all([
    getChocolate(slug),
    getChocolates(),
  ]);

  if (!chocolate) {
    notFound();
  }

  const related = chocolates
    .filter(
      (item) =>
        item.id !== chocolate.id &&
        item.shellType?.slug === chocolate.shellType?.slug,
    )
    .slice(0, 3);
  const hasRecipe = chocolate.recipeHtml
    .replace(/<[^>]+>/g, "")
    .trim().length > 0;

  const jsonLd = JSON.stringify({
    "@context": "https://schema.org",
    "@type": "Product",
    name: chocolate.title,
    description: chocolate.description,
    image: chocolate.image?.src,
    category: chocolate.shellType?.name,
    aggregateRating:
      chocolate.rating.average !== null && chocolate.rating.count > 0
        ? {
            "@type": "AggregateRating",
            ratingValue: chocolate.rating.average,
            ratingCount: chocolate.rating.count,
            bestRating: 7,
            worstRating: 1,
          }
        : undefined,
  }).replace(/</g, "\\u003c");

  return (
    <>
      <SiteHeader />
      <main>
        <article className="detail">
          <div className="detail__media-shell">
            <div className="detail__media">
              {chocolate.image ? (
                <Image
                  src={chocolate.image.src}
                  alt={chocolate.image.alt || chocolate.title}
                  fill
                  priority
                  sizes="(max-width: 899px) 100vw, 58vw"
                  className="detail__image"
                />
              ) : (
                <div className="chocolate-card__placeholder" aria-hidden="true" />
              )}
            </div>
          </div>

          <div className="detail__content">
            <Link className="back-link" href="/#chokolader">
              <span aria-hidden="true">←</span> Alle chokolader
            </Link>
            <p className="eyebrow">
              {[chocolate.shellType?.name, chocolate.productionYear]
                .filter(Boolean)
                .join(" · ")}
            </p>
            <h1>{chocolate.title}</h1>
            {chocolate.description && chocolate.description !== chocolate.title ? (
              <p className="detail__description">{chocolate.description}</p>
            ) : null}
            <RatingForm
              initialAverage={chocolate.rating.average}
              initialCount={chocolate.rating.count}
              slug={chocolate.slug}
              title={chocolate.title}
            />

            <dl className="detail__facts">
              <div>
                <dt>Chokolade</dt>
                <dd>{chocolate.shellType?.name ?? "Ikke angivet"}</dd>
              </div>
              <div>
                <dt>Årgang</dt>
                <dd>{chocolate.productionYear ?? "Ikke angivet"}</dd>
              </div>
              {chocolate.productionDate ? (
                <div>
                  <dt>Lavet</dt>
                  <dd>{formatDate(chocolate.productionDate)}</dd>
                </div>
              ) : null}
            </dl>
          </div>
        </article>

        {hasRecipe ? (
          <section className="recipe page-shell" aria-labelledby="recipe-title">
            <div className="recipe__heading">
              <p className="eyebrow">Fra køkkenet</p>
              <h2 id="recipe-title">Opskrift og noter</h2>
            </div>
            <div
              className="recipe__body"
              dangerouslySetInnerHTML={{ __html: chocolate.recipeHtml }}
            />
          </section>
        ) : null}

        {related.length > 0 ? (
          <section className="related" aria-labelledby="related-title">
            <div className="related__heading page-shell">
              <p className="eyebrow">Mere at smage</p>
              <h2 id="related-title">Samme chokoladetype</h2>
            </div>
            <div className="chocolate-grid page-shell">
              {related.map((item) => (
                <ChocolateCard chocolate={item} key={item.id} />
              ))}
            </div>
          </section>
        ) : null}
      </main>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: jsonLd }} />
    </>
  );
}
