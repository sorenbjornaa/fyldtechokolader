import { notFound } from "next/navigation";
import { SiteHeader } from "@/components/site-header";
import { getPrivacyPage } from "@/lib/api";
import { pageMetadata } from "@/lib/metadata";

export async function generateMetadata() {
  const page = await getPrivacyPage();
  return pageMetadata("/privatlivspolitik", {
    title: page?.title || "Privatlivspolitik",
    description: "Læs om privatliv og cookies på Fyldte chokolader.",
  }, page?.seo);
}

export default async function PrivacyPolicy() {
  const page = await getPrivacyPage();
  if (!page) notFound();

  return (
    <>
      <SiteHeader />
      <main className="privacy-page page-shell">
        <h1>{page.title}</h1>
        <div className="recipe__body" dangerouslySetInnerHTML={{ __html: page.contentHtml }} />
      </main>
    </>
  );
}
