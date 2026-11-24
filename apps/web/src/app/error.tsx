"use client";

export default function ErrorPage({ reset }: { reset: () => void }) {
  return (
    <main className="message-page page-shell">
      <p className="eyebrow">Noget gik galt</p>
      <h1>Chokoladerne kunne ikke hentes.</h1>
      <p>Prøv igen om et øjeblik.</p>
      <button className="button button--primary" type="button" onClick={reset}>
        Prøv igen
      </button>
    </main>
  );
}
