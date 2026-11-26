import type { Metadata } from "next";
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
        <SiteFooter />
      </body>
    </html>
  );
}
