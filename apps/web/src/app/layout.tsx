import type { Metadata } from "next";
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
      <body>{children}</body>
    </html>
  );
}
