"use client";

import { useMemo, useState } from "react";
import { ChocolateCard } from "@/components/chocolate-card";
import type { ChocolateSummary } from "@/types/chocolate";

type ChocolateBrowserProps = {
  chocolates: ChocolateSummary[];
};

export function ChocolateBrowser({ chocolates }: ChocolateBrowserProps) {
  const [query, setQuery] = useState("");
  const [year, setYear] = useState("alle");
  const [shell, setShell] = useState("alle");

  const years = useMemo(
    () =>
      Array.from(
        new Set(chocolates.map((chocolate) => chocolate.productionYear).filter(Boolean)),
      ).sort((a, b) => Number(b) - Number(a)),
    [chocolates],
  );

  const shells = useMemo(() => {
    const values = new Map<string, string>();
    chocolates.forEach((chocolate) => {
      if (chocolate.shellType) {
        values.set(chocolate.shellType.slug, chocolate.shellType.name);
      }
    });
    return Array.from(values.entries()).sort((a, b) =>
      a[1].localeCompare(b[1], "da"),
    );
  }, [chocolates]);

  const filtered = useMemo(() => {
    const normalizedQuery = query.trim().toLocaleLowerCase("da-DK");

    return chocolates.filter((chocolate) => {
      const searchable = [
        chocolate.title,
        chocolate.description,
        chocolate.shellType?.name,
        chocolate.productionYear,
      ]
        .filter(Boolean)
        .join(" ")
        .toLocaleLowerCase("da-DK");

      return (
        (!normalizedQuery || searchable.includes(normalizedQuery)) &&
        (year === "alle" || String(chocolate.productionYear) === year) &&
        (shell === "alle" || chocolate.shellType?.slug === shell)
      );
    });
  }, [chocolates, query, shell, year]);

  return (
    <section className="catalogue" id="chokolader" aria-labelledby="catalogue-title">
      <div className="catalogue__heading page-shell">
        <div>
          <p className="eyebrow">Samlingen</p>
          <h2 id="catalogue-title">Find en chokolade</h2>
        </div>
        <p className="catalogue__intro">
          Søg efter en smag, eller vælg årgang og chokoladetype.
        </p>
      </div>

      <div className="filters page-shell" aria-label="Filtrer chokolader">
        <label className="search-field">
          <span className="sr-only">Søg i chokolader</span>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="7" />
            <path d="m16.5 16.5 4 4" />
          </svg>
          <input
            id="chocolate-search"
            name="q"
            type="search"
            placeholder="Søg efter smag eller navn"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
          />
        </label>

        <label className="select-field">
          <span>Årgang</span>
          <select
            id="chocolate-year"
            name="year"
            value={year}
            onChange={(event) => setYear(event.target.value)}
          >
            <option value="alle">Alle år</option>
            {years.map((value) => (
              <option value={String(value)} key={value}>
                {value}
              </option>
            ))}
          </select>
        </label>

        <label className="select-field">
          <span>Chokolade</span>
          <select
            id="chocolate-shell"
            name="shell"
            value={shell}
            onChange={(event) => setShell(event.target.value)}
          >
            <option value="alle">Alle typer</option>
            {shells.map(([slug, name]) => (
              <option value={slug} key={slug}>
                {name}
              </option>
            ))}
          </select>
        </label>
      </div>

      <div className="catalogue__status page-shell" aria-live="polite">
        {filtered.length} {filtered.length === 1 ? "chokolade" : "chokolader"}
      </div>

      {filtered.length > 0 ? (
        <div className="chocolate-grid page-shell">
          {filtered.map((chocolate, index) => (
            <ChocolateCard
              chocolate={chocolate}
              key={chocolate.id}
              priority={index < 3}
            />
          ))}
        </div>
      ) : (
        <div className="empty-state page-shell">
          <h3>Ingen chokolader matcher</h3>
          <p>Prøv et andet søgeord eller nulstil et af filtrene.</p>
        </div>
      )}
    </section>
  );
}
