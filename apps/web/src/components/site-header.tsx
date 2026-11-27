import Link from "next/link";
import { SiteNavigation } from "@/components/site-navigation";
import { getYearPages } from "@/lib/api";

type SiteHeaderProps = {
  overlay?: boolean;
  currentYearSlug?: string;
};

export async function SiteHeader({ overlay = false, currentYearSlug }: SiteHeaderProps) {
  const [latestYearPage] = await getYearPages();
  return (
    <header className={`site-header ${overlay ? "site-header--overlay" : ""}`}>
      <div className="site-header__inner page-shell">
        <Link className="brand" href="/" aria-label="Fyldte chokolader – forside">
          Fyldte chokolader
        </Link>
        <SiteNavigation
          latestYearPage={latestYearPage && { slug: latestYearPage.slug, year: latestYearPage.year }}
          currentYearSlug={currentYearSlug}
        />
      </div>
    </header>
  );
}
