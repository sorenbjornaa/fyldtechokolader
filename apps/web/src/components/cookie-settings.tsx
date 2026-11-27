"use client";

import { useEffect } from "react";

type ConsentWindow = Window & {
  Cookiebot?: { consent?: { statistics?: boolean }; hasResponse?: boolean; renew: () => void };
  dataLayer?: unknown[];
};

const statisticsEvents = ["CookiebotOnConsentReady", "CookiebotOnAccept", "CookiebotOnDecline"];
const analyticsTagId = "ft-google-analytics";

export function CookieSettings({ measurementId }: { measurementId?: string }) {
  useEffect(() => {
    if (!measurementId) return;

    const id = measurementId;
    const consentWindow = window as ConsentWindow;
    const dataLayer = (consentWindow.dataLayer ??= []);
    function gtag() {
      // eslint-disable-next-line prefer-rest-params -- Use Google's gtag queue format.
      dataLayer.push(arguments);
    }
    const enqueue = gtag as (...args: unknown[]) => void;

    function updateStatistics() {
      const accepted = consentWindow.Cookiebot?.consent?.statistics === true;
      Object.assign(consentWindow, { [`ga-disable-${id}`]: !accepted });
      const loaded = Boolean(document.getElementById(analyticsTagId));
      if (loaded) {
        enqueue("consent", "update", { analytics_storage: accepted ? "granted" : "denied" });
      }
      if (!accepted) {
        for (const name of ["_ga", `_ga_${id.slice(2)}`]) {
          document.cookie = `${name}=; Max-Age=0; path=/`;
          document.cookie = `${name}=; Max-Age=0; path=/; domain=${window.location.hostname}`;
          if (window.location.hostname.endsWith(".fyldtechokolader.dk")) {
            document.cookie = `${name}=; Max-Age=0; path=/; domain=fyldtechokolader.dk`;
          }
        }
        return;
      }

      if (loaded) return;
      enqueue("consent", "default", {
        analytics_storage: "denied",
        ad_storage: "denied",
        ad_user_data: "denied",
        ad_personalization: "denied",
      });
      enqueue("consent", "update", { analytics_storage: "granted" });
      enqueue("js", new Date());
      enqueue("config", id, {
        allow_google_signals: false,
        allow_ad_personalization_signals: false,
        cookie_domain: window.location.hostname,
      });

      const script = document.createElement("script");
      script.id = analyticsTagId;
      script.async = true;
      script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`;
      document.head.append(script);
    }

    for (const event of statisticsEvents) window.addEventListener(event, updateStatistics);
    // Cookiebot may have loaded before React hydrates. Wait for it to read saved consent.
    if (consentWindow.Cookiebot?.hasResponse) updateStatistics();
    return () => {
      for (const event of statisticsEvents) window.removeEventListener(event, updateStatistics);
    };
  }, [measurementId]);

  return (
    <button className="site-footer__cookies" type="button" onClick={() => (window as ConsentWindow).Cookiebot?.renew()}>
      Cookieindstillinger
    </button>
  );
}
