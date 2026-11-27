import { createHash, createHmac, randomBytes } from "node:crypto";
import { revalidateTag } from "next/cache";
import type { NextRequest } from "next/server";
import { NextResponse } from "next/server";
import { apiUrl } from "@/lib/api";

const VOTER_COOKIE = "ft_voter_id";
const VOTER_COOKIE_MAX_AGE = 60 * 60 * 24 * 365 * 2;

type RatingBody = {
  score?: unknown;
};

function errorResponse(message: string, status: number) {
  return NextResponse.json({ message }, { status });
}

export async function POST(
  request: NextRequest,
  { params }: { params: Promise<{ slug: string }> },
) {
  if (process.env.VERCEL_ENV === "preview" || process.env.VERCEL_ENV === "development") {
    return errorResponse("Bedømmelser gemmes kun på det offentlige site.", 403);
  }

  const origin = request.headers.get("origin");
  if (!origin || origin !== request.nextUrl.origin) {
    return errorResponse("Requesten kunne ikke godkendes.", 403);
  }

  const { slug } = await params;
  if (!/^[a-z0-9-]+$/.test(slug)) {
    return errorResponse("Chokoladen findes ikke.", 404);
  }

  let input: RatingBody;
  try {
    input = (await request.json()) as RatingBody;
  } catch {
    return errorResponse("Bedømmelsen er ugyldig.", 400);
  }

  const score = input.score;
  if (!Number.isInteger(score) || Number(score) < 1 || Number(score) > 7) {
    return errorResponse("Bedømmelsen skal være et helt tal fra 1 til 7.", 400);
  }

  const secret = process.env.FYLDTECHOKOLADER_API_SECRET;
  if (!secret || secret.length < 32) {
    return errorResponse("Ratingfunktionen er ikke konfigureret endnu.", 503);
  }

  const voterToken =
    request.cookies.get(VOTER_COOKIE)?.value ?? randomBytes(32).toString("hex");
  const body = JSON.stringify({ score, voterToken });
  const timestamp = Math.floor(Date.now() / 1000).toString();
  const route = `/fyldtechokolader/v1/chokolader/${slug}/rating`;
  const bodyHash = createHash("sha256").update(body).digest("hex");
  const signature = createHmac("sha256", secret)
    .update(`${timestamp}\nPOST\n${route}\n${bodyHash}`)
    .digest("hex");

  const wordpressResponse = await fetch(apiUrl(`/chokolader/${slug}/rating`), {
    method: "POST",
    cache: "no-store",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-Fyldtechokolader-Signature": signature,
      "X-Fyldtechokolader-Timestamp": timestamp,
    },
    body,
  });

  const wordpressResult = (await wordpressResponse.json().catch(() => null)) as
    | {
        average?: number | null;
        count?: number;
        userScore?: number;
        message?: string;
      }
    | null;

  if (!wordpressResponse.ok || !wordpressResult) {
    const message =
      wordpressResponse.status === 429
        ? "Du har sendt for mange bedømmelser. Prøv igen senere."
        : wordpressResult?.message || "Bedømmelsen kunne ikke gemmes. Prøv igen.";
    return errorResponse(message, wordpressResponse.status || 502);
  }

  revalidateTag("chocolates", { expire: 0 });

  const response = NextResponse.json({
    average: wordpressResult.average ?? null,
    count: wordpressResult.count ?? 0,
    score: wordpressResult.userScore ?? score,
  });
  response.cookies.set(VOTER_COOKIE, voterToken, {
    httpOnly: true,
    maxAge: VOTER_COOKIE_MAX_AGE,
    path: "/",
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
  });

  return response;
}
