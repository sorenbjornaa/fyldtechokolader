"use client";

import { useRef, useState } from "react";
import { RatingDisplay } from "@/components/rating-display";

type RatingFormProps = {
  slug: string;
  title: string;
  initialAverage: number | null;
  initialCount: number;
};

type RatingResponse = {
  average: number | null;
  count: number;
  score: number;
};

function RatingStar() {
  return (
    <svg aria-hidden="true" viewBox="0 0 24 24">
      <path d="m12 2.2 2.93 5.94 6.56.95-4.75 4.63 1.12 6.54L12 17.18l-5.86 3.08 1.12-6.54-4.75-4.63 6.56-.95L12 2.2Z" />
    </svg>
  );
}

function DragHint() {
  return (
    <span className="rating-slider__hint" aria-hidden="true">
      <svg viewBox="0 0 24 24">
        <path d="M18 11V6a2 2 0 0 0-4 0v5" />
        <path d="M14 10V4a2 2 0 0 0-4 0v8" />
        <path d="M10 10.5V6a2 2 0 0 0-4 0v8" />
        <path d="M18 8a2 2 0 0 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.9-6-2.4L2.5 16a2 2 0 0 1 2.8-2.8L8 15.9" />
      </svg>
    </span>
  );
}

export function RatingForm({
  slug,
  title,
  initialAverage,
  initialCount,
}: RatingFormProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [isActive, setIsActive] = useState(false);
  const [score, setScore] = useState<number | null>(null);
  const [hasInteracted, setHasInteracted] = useState(false);
  const [average, setAverage] = useState(initialAverage);
  const [count, setCount] = useState(initialCount);
  const [status, setStatus] = useState<"idle" | "saving" | "saved" | "error">(
    "idle",
  );
  const [message, setMessage] = useState("");

  async function saveRating() {
    if (score === null) {
      return;
    }

    setStatus("saving");
    setMessage("");

    try {
      const response = await fetch(`/api/ratings/${encodeURIComponent(slug)}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ score }),
      });
      const result = (await response.json()) as RatingResponse & {
        message?: string;
      };

      if (!response.ok) {
        throw new Error(result.message || "Bedømmelsen kunne ikke gemmes.");
      }

      setAverage(result.average);
      setCount(result.count);
      setScore(result.score);
      setStatus("saved");
      setMessage("Tak – din bedømmelse er gemt.");
      setIsActive(false);
    } catch (error) {
      setStatus("error");
      setMessage(
        error instanceof Error
          ? error.message
          : "Bedømmelsen kunne ikke gemmes. Prøv igen.",
      );
    }
  }

  function updateScoreFromPointer(event: React.PointerEvent<HTMLDivElement>) {
    const bounds = event.currentTarget.getBoundingClientRect();
    const position = Math.min(
      1,
      Math.max(0, (event.clientX - bounds.left) / bounds.width),
    );
    const nextScore = Math.min(7, Math.floor(position * 7) + 1);

    setScore(nextScore);
    setHasInteracted(true);
    setStatus("idle");
    setMessage("");
  }

  function startPointerRating(event: React.PointerEvent<HTMLDivElement>) {
    event.currentTarget.setPointerCapture(event.pointerId);
    inputRef.current?.focus({ preventScroll: true });
    updateScoreFromPointer(event);
  }

  function continuePointerRating(event: React.PointerEvent<HTMLDivElement>) {
    if (event.currentTarget.hasPointerCapture(event.pointerId)) {
      updateScoreFromPointer(event);
    }
  }

  function stopPointerRating(event: React.PointerEvent<HTMLDivElement>) {
    if (event.currentTarget.hasPointerCapture(event.pointerId)) {
      event.currentTarget.releasePointerCapture(event.pointerId);
    }
  }

  return (
    <section className="rating-form" aria-labelledby="rating-form-title">
      <div className="rating-form__summary">
        <p className="rating-form__label" id="rating-form-title">
          Samlet bedømmelse
        </p>
        <RatingDisplay average={average} count={count} />
      </div>

      {isActive ? (
        <div className="rating-form__editor">
          <div className="rating-form__value-row">
            <label htmlFor={`rating-${slug}`}>Din bedømmelse</label>
            <output htmlFor={`rating-${slug}`}>
              {score === null ? "–" : score}/7
            </output>
          </div>
          <div
            className={`rating-slider${score === null ? " is-neutral" : ""}`}
            onPointerCancel={stopPointerRating}
            onPointerDown={startPointerRating}
            onPointerMove={continuePointerRating}
            onPointerUp={stopPointerRating}
            style={{
              "--rating-progress": `${score === null ? 0 : (score / 7) * 100}%`,
            } as React.CSSProperties}
          >
            <span className="rating-slider__fill" aria-hidden="true" />
            <span className="rating-slider__marks" aria-hidden="true">
              {Array.from({ length: 7 }, (_, index) => {
                const value = index + 1;
                return (
                  <span
                    className={
                      score !== null && value <= score
                        ? "rating-slider__mark is-active"
                        : "rating-slider__mark"
                    }
                    key={value}
                  >
                    {score !== null && value <= score ? (
                      <RatingStar />
                    ) : (
                      <span>•</span>
                    )}
                  </span>
                );
              })}
            </span>
            {!hasInteracted ? <DragHint /> : null}
            <input
              ref={inputRef}
              id={`rating-${slug}`}
              aria-label={`Bedøm ${title} fra 1 til 7`}
              aria-valuetext={
                score === null ? "Ingen bedømmelse valgt" : `${score} ud af 7`
              }
              max="7"
              min="0"
              onChange={(event) => {
                const nextScore = Number(event.currentTarget.value);
                setScore(nextScore === 0 ? null : nextScore);
                setHasInteracted(true);
                setStatus("idle");
                setMessage("");
              }}
              onKeyDown={() => setHasInteracted(true)}
              step="1"
              type="range"
              value={score ?? 0}
            />
          </div>
          <div className="rating-form__actions">
            <button
              className="button button--primary"
              disabled={score === null || status === "saving"}
              onClick={saveRating}
              type="button"
            >
              {status === "saving" ? "Gemmer…" : "Gem bedømmelse"}
            </button>
            <button
              className="rating-form__cancel"
              disabled={status === "saving"}
              onClick={() => {
                setIsActive(false);
                setScore(null);
                setHasInteracted(false);
                setStatus("idle");
                setMessage("");
              }}
              type="button"
            >
              Annuller
            </button>
          </div>
        </div>
      ) : (
        <button
          className="rating-form__activate"
          onClick={() => {
            setIsActive(true);
            setScore(null);
            setHasInteracted(false);
            setStatus("idle");
            setMessage("");
          }}
          type="button"
        >
          Bedøm chokoladen
        </button>
      )}

      {message ? (
        <p
          className={`rating-form__message rating-form__message--${status}`}
          role={status === "error" ? "alert" : "status"}
        >
          {message}
        </p>
      ) : null}
    </section>
  );
}
