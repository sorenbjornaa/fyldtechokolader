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
  if (average === null || count === 0) {
    return <span className="rating-empty">Ingen vurderinger endnu</span>;
  }

  const rounded = Math.round(average);
  const formatted = average.toLocaleString("da-DK", {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  });
  const label = `${formatted} ud af 7 baseret på ${count} ${
    count === 1 ? "vurdering" : "vurderinger"
  }`;

  return (
    <div className={`rating ${compact ? "rating--compact" : ""}`} aria-label={label}>
      <span className="rating__stars" aria-hidden="true">
        {Array.from({ length: 7 }, (_, index) => (
          <svg
            className={index < rounded ? "is-active" : ""}
            key={index}
            viewBox="0 0 24 24"
          >
            <path d="m12 2.2 2.93 5.94 6.56.95-4.75 4.63 1.12 6.54L12 17.18l-5.86 3.08 1.12-6.54-4.75-4.63 6.56-.95L12 2.2Z" />
          </svg>
        ))}
      </span>
      <span className="rating__number">
        {formatted}<span aria-hidden="true">/7</span>
      </span>
      {!compact ? (
        <span className="rating__count">
          {count} {count === 1 ? "vurdering" : "vurderinger"}
        </span>
      ) : null}
    </div>
  );
}
