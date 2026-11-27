import Link from "next/link";
import { CookieSettings } from "@/components/cookie-settings";

export function SiteFooter({ cookieSettings, measurementId }: { cookieSettings?: boolean; measurementId?: string }) {
  return (
    <footer className="site-footer page-shell">
      <div className="brand brand--footer">Fyldte chokolader</div>
      <p>Et privat chokoladeprojekt af Christian Munch og Søren Bjørnaa</p>
      <div className="site-footer__links">
        <Link href="/privatlivspolitik">Privatlivspolitik</Link>
        {cookieSettings ? <CookieSettings measurementId={measurementId} /> : null}
      </div>
    </footer>
  );
}
