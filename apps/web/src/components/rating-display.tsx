import Image from "next/image";

type RatingDisplayProps = {
  average: number | null;
  count: number;
  compact?: boolean;
};

export function RatingDisplay({
  average,
  count,
  compact = false,
}: RatingDisplayProps) {
  const hasRatings = average !== null && count > 0;
  const rounded = hasRatings ? Math.round(average) : 0;
  const formatted = hasRatings ? average.toLocaleString("da-DK", {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  }) : null;
  const countLabel = `${count} ${count === 1 ? "vurdering" : "vurderinger"}`;
  const emptyLabel = "Ingen vurderinger endnu";
  const label = hasRatings ? `${formatted} ud af 7 baseret på ${countLabel}` : emptyLabel;

  return (
    <div className={`rating ${compact ? "rating--compact" : ""}`} role="img" aria-label={label}>
      <span className="rating__stars" aria-hidden="true">
        {Array.from({ length: 7 }, (_, index) => (
          <Image
            className="rating__star"
            key={index}
            src={index < rounded ? "/rating-star-active.svg" : "/rating-star-inactive.svg"}
            alt=""
            width={24}
            height={24}
          />
        ))}
      </span>
      {hasRatings ? (
        <>
          <span className="rating__number">
            {formatted}<span>/7</span>
          </span>
          {!compact && <span className="rating__count">{countLabel}</span>}
        </>
      ) : (
        <span className="rating-empty">{emptyLabel}</span>
      )}
    </div>
  );
}
