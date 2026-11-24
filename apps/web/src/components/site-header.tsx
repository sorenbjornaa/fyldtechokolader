import Link from "next/link";

type SiteHeaderProps = {
  overlay?: boolean;
};

export function SiteHeader({ overlay = false }: SiteHeaderProps) {
  return (
    <header className={`site-header ${overlay ? "site-header--overlay" : ""}`}>
      <div className="site-header__inner page-shell">
        <Link className="brand" href="/" aria-label="Fyldte chokolader – forside">
          <span>Fyldte</span>
          <strong>chokolader</strong>
        </Link>
        <nav aria-label="Primær navigation">
          <Link href="/#chokolader">Alle chokolader</Link>
        </nav>
      </div>
    </header>
  );
}
