import { cacheLife, cacheTag } from "next/cache";
import type { Chocolate } from "@/types/chocolate";

const defaultApiUrl =
  "https://cms.fyldtechokolader.dk/wp-json/fyldtechokolader/v1";

export function apiUrl(path: string): string {
  const baseUrl = (process.env.WORDPRESS_API_URL ?? defaultApiUrl).replace(
    /\/$/,
    "",
  );

  return `${baseUrl}${path}`;
}

async function fetchApi<T>(path: string, notFound?: T): Promise<T> {
  const maxAttempts = 3;

  for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
    const response = await fetch(apiUrl(path), {
      headers: { Accept: "application/json" },
    });

    if (response.ok) {
      return (await response.json()) as T;
    }

    if (response.status === 404 && notFound !== undefined) {
      return notFound;
    }

    const canRetry = response.status === 429 || response.status >= 500;
    if (!canRetry || attempt === maxAttempts) {
      throw new Error(`WordPress API svarede med status ${response.status}.`);
    }

    const retryAfter = Number(response.headers.get("retry-after"));
    const delay = Number.isFinite(retryAfter)
      ? Math.min(retryAfter * 1000, 3000)
      : attempt * 750;
    await new Promise((resolve) => setTimeout(resolve, delay));
  }

  throw new Error("WordPress API kunne ikke hentes.");
}

type FrontPageCopy = {
  eyebrow: string;
  heading: string;
  description: string;
};

type FrontPage = {
  hero: FrontPageCopy & {
    buttonLabel: string;
    image: { src: string; alt: string } | null;
  };
  introduction: FrontPageCopy;
};

export async function getFrontPage(): Promise<FrontPage | null> {
  "use cache";
  cacheLife("minutes");

  return fetchApi<FrontPage | null>("/forside", null);
}

export async function getChocolates(): Promise<Chocolate[]> {
  "use cache";
  cacheLife("minutes");
  cacheTag("chocolates");

  const chocolates = await fetchApi<Chocolate[]>(
    "/chokolader?per_page=100&orderby=date&order=desc",
  );

  if (!Array.isArray(chocolates)) {
    throw new Error("WordPress API returnerede ikke en chokoladeliste.");
  }

  return chocolates.toSorted((a, b) => {
    const aDate = a.productionDate ?? `${a.productionYear ?? 0}-00-00`;
    const bDate = b.productionDate ?? `${b.productionYear ?? 0}-00-00`;
    return bDate.localeCompare(aDate);
  });
}

export async function getChocolate(slug: string): Promise<Chocolate | null> {
  const chocolates = await getChocolates();
  return chocolates.find((chocolate) => chocolate.slug === slug) ?? null;
}
