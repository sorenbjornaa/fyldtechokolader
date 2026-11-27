import type { MetadataRoute } from "next";
import { getChocolates, getFrontPage, getPrivacyPage } from "@/lib/api";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const [chocolates, frontPage, privacyPage] = await Promise.all([getChocolates(), getFrontPage(), getPrivacyPage()]);
  const baseUrl = "https://www.fyldtechokolader.dk";

  return [
    ...(!frontPage?.seo?.noindex ? [{
      url: baseUrl,
      lastModified: new Date(),
      changeFrequency: "weekly" as const,
      priority: 1,
    }] : []),
    ...(privacyPage && !privacyPage.seo.noindex ? [{ url: `${baseUrl}/privatlivspolitik` }] : []),
    ...chocolates.filter((chocolate) => !chocolate.seo.noindex).map((chocolate) => ({
      url: `${baseUrl}/chokolade/${chocolate.slug}`,
      lastModified: new Date(chocolate.modifiedAt),
      changeFrequency: "monthly" as const,
      priority: 0.8,
    })),
  ];
}
