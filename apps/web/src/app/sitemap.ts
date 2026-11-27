import type { MetadataRoute } from "next";
import { getChocolates, getFrontPage, getPrivacyPage, getYearPages } from "@/lib/api";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const [chocolates, frontPage, privacyPage, yearPages] = await Promise.all([getChocolates(), getFrontPage(), getPrivacyPage(), getYearPages()]);
  const baseUrl = "https://www.fyldtechokolader.dk";

  return [
    ...(!frontPage?.seo?.noindex ? [{
      url: baseUrl,
      lastModified: new Date(),
      changeFrequency: "weekly" as const,
      priority: 1,
    }] : []),
    ...(privacyPage && !privacyPage.seo.noindex ? [{ url: `${baseUrl}/privatlivspolitik` }] : []),
    ...yearPages.filter((page) => !page.seo.noindex).map((page) => ({ url: `${baseUrl}/${page.slug}` })),
    ...chocolates.filter((chocolate) => !chocolate.seo.noindex).map((chocolate) => ({
      url: `${baseUrl}/chokolade/${chocolate.slug}`,
      lastModified: new Date(chocolate.modifiedAt),
      changeFrequency: "monthly" as const,
      priority: 0.8,
    })),
  ];
}
