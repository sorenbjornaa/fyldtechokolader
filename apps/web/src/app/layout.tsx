import type { Metadata } from "next";
import Script from "next/script";
import { SiteFooter } from "@/components/site-footer";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL("https://www.fyldtechokolader.dk"),
  title: {
    default: "Fyldte chokolader",
    template: "%s · Fyldte chokolader",
  },
  description:
    "Håndlavede fyldte chokolader, smagskombinationer, opskrifter og vurderinger.",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  const cookieBotId = process.env.COOKIEBOT_DOMAIN_GROUP_ID;
  const measurementId = process.env.GA_MEASUREMENT_ID;
  const cookieBotEnabled = !["preview", "development"].includes(process.env.VERCEL_ENV ?? "") &&
    /^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i.test(cookieBotId ?? "");
  return (
    <html lang="da">
      <head>
        <link rel="preconnect" href="https://use.typekit.net" />
        <link rel="preconnect" href="https://p.typekit.net" />
        <link
          rel="stylesheet"
          href="https://use.typekit.net/ojv2mga.css"
        />
      </head>
      <body>
        {children}
        {cookieBotEnabled && (
          <Script id="Cookiebot" src="https://consent.cookiebot.com/uc.js" data-cbid={cookieBotId} strategy="beforeInteractive" />
        )}
        <SiteFooter cookieSettings={cookieBotEnabled} measurementId={/^G-[A-Z0-9]+$/.test(measurementId ?? "") ? measurementId : undefined} />
      </body>
    </html>
  );
}
