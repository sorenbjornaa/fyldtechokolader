import type { MetadataRoute } from "next";
import { getChocolates } from "@/lib/api";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const chocolates = await getChocolates();
  const baseUrl = "https://www.fyldtechokolader.dk";

  return [
    {
      url: baseUrl,
      lastModified: new Date(),
      changeFrequency: "weekly",
      priority: 1,
    },
    ...chocolates.map((chocolate) => ({
      url: `${baseUrl}/chokolade/${chocolate.slug}`,
      lastModified: new Date(chocolate.modifiedAt),
      changeFrequency: "monthly" as const,
      priority: 0.8,
    })),
  ];
}
