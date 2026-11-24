import Link from "next/link";
import { SiteHeader } from "@/components/site-header";

export default function NotFound() {
  return (
    <>
      <SiteHeader />
      <main className="message-page page-shell">
        <p className="eyebrow">404</p>
        <h1>Den chokolade kan vi ikke finde.</h1>
        <p>Den kan være blevet omdøbt, eller linket kan være gammelt.</p>
        <Link className="button button--primary" href="/#chokolader">
          Se alle chokolader
        </Link>
      </main>
    </>
  );
}
