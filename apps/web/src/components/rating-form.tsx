"use client";

import { useState } from "react";
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

const DEFAULT_SCORE = 4;

function RatingStar() {
  return (
    <svg aria-hidden="true" viewBox="0 0 24 24">
      <path d="m12 2.2 2.93 5.94 6.56.95-4.75 4.63 1.12 6.54L12 17.18l-5.86 3.08 1.12-6.54-4.75-4.63 6.56-.95L12 2.2Z" />
    </svg>
  );
}

export function RatingForm({
  slug,
  title,
  initialAverage,
  initialCount,
}: RatingFormProps) {
  const [isActive, setIsActive] = useState(false);
  const [score, setScore] = useState(DEFAULT_SCORE);
  const [average, setAverage] = useState(initialAverage);
  const [count, setCount] = useState(initialCount);
  const [status, setStatus] = useState<"idle" | "saving" | "saved" | "error">(
    "idle",
  );
  const [message, setMessage] = useState("");

  async function saveRating() {
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
            <output htmlFor={`rating-${slug}`}>{score}/7</output>
          </div>
          <div
            className="rating-slider"
            style={{ "--rating-progress": `${(score / 7) * 100}%` } as React.CSSProperties}
          >
            <span className="rating-slider__fill" aria-hidden="true" />
            <span className="rating-slider__marks" aria-hidden="true">
              {Array.from({ length: 7 }, (_, index) => {
                const value = index + 1;
                return (
                  <span
                    className={
                      value <= score
                        ? "rating-slider__mark is-active"
                        : "rating-slider__mark"
                    }
                    key={value}
                  >
                    {value <= score ? <RatingStar /> : <span>•</span>}
                  </span>
                );
              })}
            </span>
            <input
              id={`rating-${slug}`}
              aria-label={`Bedøm ${title} fra 1 til 7`}
              max="7"
              min="1"
              onChange={(event) => {
                setScore(Number(event.currentTarget.value));
                setStatus("idle");
                setMessage("");
              }}
              step="1"
              type="range"
              value={score}
            />
          </div>
          <div className="rating-form__actions">
            <button
              className="button button--primary"
              disabled={status === "saving"}
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
