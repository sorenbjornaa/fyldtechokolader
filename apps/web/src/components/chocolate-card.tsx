import Image from "next/image";
import Link from "next/link";
import { RatingDisplay } from "@/components/rating-display";
import type { ChocolateSummary } from "@/types/chocolate";

type ChocolateCardProps = {
  chocolate: ChocolateSummary;
  priority?: boolean;
};

export function ChocolateCard({ chocolate, priority = false }: ChocolateCardProps) {
  return (
    <article className="chocolate-card reveal-on-scroll">
      <Link
        className="chocolate-card__link"
        href={`/chokolade/${chocolate.slug}`}
        aria-label={`Se ${chocolate.title}`}
      >
        <div className="chocolate-card__media">
          {chocolate.image ? (
            <Image
              src={chocolate.image.src}
              alt={chocolate.image.alt || chocolate.title}
              fill
              sizes="(max-width: 767px) 100vw, (max-width: 1199px) 50vw, 42vw"
              className="chocolate-card__image"
              priority={priority}
            />
          ) : (
            <div className="chocolate-card__placeholder" aria-hidden="true" />
          )}
          {chocolate.badge ? (
            <span className="chocolate-card__badge">{chocolate.badge}</span>
          ) : null}
        </div>
        <div className="chocolate-card__content">
          <p className="eyebrow chocolate-card__meta">
            {[chocolate.shellType?.name, chocolate.productionYear]
              .filter(Boolean)
              .join(" · ")}
          </p>
          <h3>{chocolate.title}</h3>
          {chocolate.description && chocolate.description !== chocolate.title ? (
            <p className="chocolate-card__description">{chocolate.description}</p>
          ) : null}
          <RatingDisplay
            average={chocolate.rating.average}
            count={chocolate.rating.count}
            compact
          />
        </div>
      </Link>
    </article>
  );
}
