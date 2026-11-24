import { cacheLife, cacheTag } from "next/cache";
import type { Chocolate } from "@/types/chocolate";

const defaultApiUrl =
  "https://cms.fyldtechokolader.dk/wp-json/fyldtechokolader/v1";

function apiUrl(path: string): string {
  const baseUrl = (process.env.WORDPRESS_API_URL ?? defaultApiUrl).replace(
    /\/$/,
    "",
  );

  return `${baseUrl}${path}`;
}

async function fetchApi<T>(path: string): Promise<T> {
  const response = await fetch(apiUrl(path), {
    headers: { Accept: "application/json" },
  });

  if (!response.ok) {
    throw new Error(`WordPress API svarede med status ${response.status}.`);
  }

  return (await response.json()) as T;
}

export async function getChocolates(): Promise<Chocolate[]> {
  "use cache";
  cacheLife("minutes");
  cacheTag("chocolates");

  const chocolates = await fetchApi<Chocolate[]>(
    "/chokolader?per_page=100&orderby=produktionsdato&order=desc",
  );

  if (!Array.isArray(chocolates)) {
    throw new Error("WordPress API returnerede ikke en chokoladeliste.");
  }

  return chocolates;
}

export async function getChocolate(slug: string): Promise<Chocolate | null> {
  "use cache";
  cacheLife("minutes");
  cacheTag("chocolates", `chocolate-${slug}`);

  const response = await fetch(apiUrl(`/chokolader/${encodeURIComponent(slug)}`), {
    headers: { Accept: "application/json" },
  });

  if (response.status === 404) {
    return null;
  }

  if (!response.ok) {
    throw new Error(`WordPress API svarede med status ${response.status}.`);
  }

  return (await response.json()) as Chocolate;
}
