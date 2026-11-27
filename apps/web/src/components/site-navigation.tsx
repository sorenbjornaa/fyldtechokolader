"use client";

import Link from "next/link";
import { useId, useRef, useState } from "react";

type SiteNavigationProps = {
  latestYearPage?: { slug: string; year: number };
  currentYearSlug?: string;
};

export function SiteNavigation({ latestYearPage, currentYearSlug }: SiteNavigationProps) {
  const menuId = useId();
  const toggleRef = useRef<HTMLButtonElement>(null);
  const [open, setOpen] = useState(false);

  return (
    <div
      className="site-navigation"
      onKeyDown={(event) => {
        if (event.key === "Escape" && open) {
          setOpen(false);
          toggleRef.current?.focus();
        }
      }}
    >
      <button
        ref={toggleRef}
        className="site-navigation__toggle"
        type="button"
        aria-expanded={open}
        aria-controls={menuId}
        onClick={() => setOpen(!open)}
      >
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d={open ? "m6 6 12 12M18 6 6 18" : "M3 6h18M3 12h18M3 18h18"} stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
        </svg>
        Menu
      </button>
      <nav id={menuId} className="site-navigation__links" data-open={open} aria-label="Primær navigation">
        <Link href="/#chokolader" onClick={() => setOpen(false)}>Alle chokolader</Link>
        {latestYearPage && (
          <Link
            href={`/${latestYearPage.slug}`}
            aria-current={currentYearSlug === latestYearPage.slug ? "page" : undefined}
            onClick={() => setOpen(false)}
          >
            {latestYearPage.year}
          </Link>
        )}
      </nav>
    </div>
  );
}
