"use client";

import Image from "next/image";
import { useEffect, useMemo, useRef, useState } from "react";
import { ChocolateCard } from "@/components/chocolate-card";
import type { ChocolateSummary } from "@/types/chocolate";

type ChocolateBrowserProps = {
  chocolates: ChocolateSummary[];
  fixedYear?: number;
};

const filterList = new Intl.ListFormat("da", { type: "conjunction" });
type FilterId = "search" | "year" | "shell";

export function ChocolateBrowser({ chocolates, fixedYear }: ChocolateBrowserProps) {
  const gridRef = useRef<HTMLDivElement>(null);
  const searchRef = useRef<HTMLInputElement>(null);
  const yearRef = useRef<HTMLSelectElement>(null);
  const shellRef = useRef<HTMLSelectElement>(null);
  const [query, setQuery] = useState("");
  const [year, setYear] = useState("alle");
  const [shell, setShell] = useState("alle");
  const searchTerm = query.trim();

  const years = useMemo(
    () =>
      Array.from(
        new Set(
          chocolates
            .filter((chocolate) => shell === "alle" || chocolate.shellType?.slug === shell)
            .map((chocolate) => chocolate.productionYear)
            .filter(Boolean),
        ),
      ).sort((a, b) => Number(b) - Number(a)),
    [chocolates, shell],
  );

  const shells = useMemo(() => {
    const values = new Map<string, string>();
    chocolates.forEach((chocolate) => {
      if (chocolate.shellType && (year === "alle" || String(chocolate.productionYear) === year)) {
        values.set(chocolate.shellType.slug, chocolate.shellType.name);
      }
    });
    return Array.from(values.entries()).sort((a, b) =>
      a[1].localeCompare(b[1], "da"),
    );
  }, [chocolates, year]);

  const filtered = useMemo(() => {
    const normalizedQuery = searchTerm.toLocaleLowerCase("da-DK");

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
  }, [chocolates, searchTerm, shell, year]);

  const activeFilters: {
    id: FilterId;
    label: string;
    summary: string;
    clearLabel: string;
  }[] = [];

  if (searchTerm) {
    activeFilters.push({
      id: "search",
      label: `Søgning: “${searchTerm}”`,
      summary: `søgning efter “${searchTerm}”`,
      clearLabel: "Ryd søgning",
    });
  }

  if (year !== "alle") {
    activeFilters.push({
      id: "year",
      label: `Årgang ${year}`,
      summary: `årgang ${year}`,
      clearLabel: "Fjern årgangsfilter",
    });
  }

  const shellName = shells.find(([slug]) => slug === shell)?.[1];
  if (shellName) {
    activeFilters.push({
      id: "shell",
      label: shellName,
      summary: `chokoladetype ${shellName}`,
      clearLabel: "Fjern chokoladetypefilter",
    });
  }

  const [singleFilter] = activeFilters;
  const hasMultipleFilters = activeFilters.length > 1;
  const clearFilter = (id: FilterId) => {
    switch (id) {
      case "search":
        setQuery("");
        searchRef.current?.focus();
        break;
      case "year":
        setYear("alle");
        yearRef.current?.focus();
        break;
      case "shell":
        setShell("alle");
        shellRef.current?.focus();
        break;
    }
  };

  const resetFilters = () => {
    setQuery("");
    setYear("alle");
    setShell("alle");
    searchRef.current?.focus();
  };

  useEffect(() => {
    const grid = gridRef.current;
    if (!grid) {
      return;
    }

    const desktop = window.matchMedia("(min-width: 1200px)");
    const links = Array.from(
      grid.querySelectorAll<HTMLElement>(".chocolate-card__link"),
    );

    const resizeCard = (link: HTMLElement) => {
      const card = link.closest<HTMLElement>(".chocolate-card");
      if (!card) {
        return;
      }

      if (!desktop.matches) {
        card.style.removeProperty("grid-row-end");
        return;
      }

      const styles = window.getComputedStyle(grid);
      const rowHeight = Number.parseFloat(styles.gridAutoRows);
      const rowGap = Number.parseFloat(styles.rowGap);
      const height = link.getBoundingClientRect().height;
      const span = Math.ceil((height + rowGap) / (rowHeight + rowGap));

      card.style.gridRowEnd = `span ${span}`;
    };

    const updateGrid = () => links.forEach(resizeCard);
    const observer = new ResizeObserver((entries) => {
      entries.forEach((entry) => resizeCard(entry.target as HTMLElement));
    });

    links.forEach((link) => observer.observe(link));
    desktop.addEventListener("change", updateGrid);
    updateGrid();

    return () => {
      observer.disconnect();
      desktop.removeEventListener("change", updateGrid);
    };
  }, [filtered]);

  return (
    <section
      className="catalogue"
      id="chokolader"
      aria-labelledby={fixedYear ? undefined : "catalogue-title"}
      aria-label={fixedYear ? `Chokolader fra ${fixedYear}` : undefined}
    >
      {!fixedYear && (
        <div className="catalogue__heading page-shell">
          <div>
            <p className="eyebrow">Samlingen</p>
            <h2 id="catalogue-title">Find en chokolade</h2>
          </div>
          <p className="catalogue__intro">Søg efter en smag, eller vælg årgang og chokoladetype.</p>
        </div>
      )}

      <div className={`filters page-shell${fixedYear ? " filters--fixed-year" : ""}`} aria-label="Filtrer chokolader">
        <div className="search-field">
          <label className="sr-only" htmlFor="chocolate-search">Søg i chokolader</label>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="7" />
            <path d="m16.5 16.5 4 4" />
          </svg>
          <input
            ref={searchRef}
            id="chocolate-search"
            name="q"
            type="search"
            placeholder="Søg efter smag eller navn"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            onKeyDown={(event) => {
              if (event.key === "Escape" && query) {
                event.preventDefault();
                clearFilter("search");
              }
            }}
          />
          {query && (
            <button
              type="button"
              className="search-field__clear"
              aria-label="Ryd søgefelt"
              onClick={() => clearFilter("search")}
            />
          )}
        </div>

        <label className="select-field">
          <span id="chocolate-shell-label">Chokolade</span>
          <select
            ref={shellRef}
            id="chocolate-shell"
            name="shell"
            aria-labelledby="chocolate-shell-label"
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

        {!fixedYear && (
          <label className="select-field">
            <span id="chocolate-year-label">Årgang</span>
            <select
              ref={yearRef}
              id="chocolate-year"
              name="year"
              aria-labelledby="chocolate-year-label"
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
        )}
      </div>

      <div className="catalogue__status page-shell" aria-live="polite">
        {filtered.length} {filtered.length === 1 ? "chokolade" : "chokolader"}
      </div>

      {filtered.length > 0 ? (
        <div
          className="chocolate-grid chocolate-grid--masonry page-shell"
          ref={gridRef}
        >
          {filtered.map((chocolate, index) => (
            <ChocolateCard
              chocolate={chocolate}
              key={chocolate.id}
              priority={index < 3}
              showYear={!fixedYear}
            />
          ))}
        </div>
      ) : (
        <div className="empty-state page-shell">
          <h3>Ingen chokolader matcher</h3>
          {singleFilter ? (
            <>
              <p>
                Ingen resultater for {filterList.format(activeFilters.map(({ summary }) => summary))}.
              </p>
              {hasMultipleFilters && (
                <ul className="empty-state__chips" aria-label="Aktive valg">
                  {activeFilters.map((filter) => (
                    <li key={filter.id}>
                      <button
                        type="button"
                        className={filter.id === "search" ? "filter-chip filter-chip--search" : "filter-chip"}
                        aria-label={filter.id === "search" ? `Ryd ${filter.summary}` : `Fjern ${filter.label}`}
                        onClick={() => clearFilter(filter.id)}
                      >
                        <span className="filter-chip__surface">
                          <span className="filter-chip__label">{filter.label}</span>
                          <Image className="filter-chip__remove" src="/filter-chip-remove.svg" alt="" width={12} height={12} />
                        </span>
                      </button>
                    </li>
                  ))}
                </ul>
              )}
              <button
                type="button"
                className="button button--secondary"
                onClick={hasMultipleFilters ? resetFilters : () => clearFilter(singleFilter.id)}
              >
                {hasMultipleFilters ? "Nulstil søgning og filtre" : singleFilter.clearLabel}
              </button>
            </>
          ) : (
            <p>{fixedYear ? `Der er endnu ingen chokolader fra ${fixedYear}.` : "Der er endnu ingen chokolader i samlingen."}</p>
          )}
        </div>
      )}
    </section>
  );
}
