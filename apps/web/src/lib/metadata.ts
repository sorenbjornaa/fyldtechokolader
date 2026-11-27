import type { Metadata } from "next";
import type { PageSeo } from "@/types/chocolate";

export const publicOrigin = "https://www.fyldtechokolader.dk";

export function pageMetadata(
  path: string,
  fallback: { title: string; description: string; image?: string | null },
  seo?: PageSeo,
  type: "article" | "website" = "website",
): Metadata {
  const title = seo?.title || fallback.title;
  const description = seo?.description || fallback.description;
  const image = seo?.image || fallback.image;
  const canonical = new URL(path, publicOrigin);
  if (seo?.canonical) {
    try {
      const custom = new URL(seo.canonical);
      if (custom.hostname === "fyldtechokolader.dk" || custom.hostname === canonical.hostname) {
        canonical.pathname = custom.pathname;
        canonical.search = custom.search;
      }
    } catch {
      // A malformed manual canonical falls back to the public route.
    }
  }

  return {
    title: seo?.source === "yoast" ? { absolute: title } : title,
    description,
    alternates: { canonical },
    robots: { index: process.env.VERCEL_ENV !== "preview" && !seo?.noindex, follow: !seo?.nofollow },
    openGraph: { type, title, description, url: canonical, ...(image ? { images: [image] } : {}) },
  };
}
