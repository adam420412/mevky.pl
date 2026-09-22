# MEVKY performance — 2026-09-22

Production deployment: dpl_EMQrfezBqEZQXUFp3TQ5t78VwdaV, alias https://mevky-pl.vercel.app/.

| Measurement | Performance | FCP | LCP | TBT | CLS |
| --- | --- | --- | --- | --- | --- |
| Original public URL, mobile Lighthouse | 92 | 2.0 s | 3.1 s | 0 ms | 0 |
| Optimized export, local compressed server, mobile Lighthouse | 97 | 1.2 s | 2.6 s | 0 ms | 0 |
| Optimized export, local compressed server, desktop Lighthouse | 100 | 0.3 s | 0.5 s | 0 ms | 0 |

These are Lighthouse lab runs, not successful PageSpeed Insights API results. Local measurements use gzip, default Lighthouse throttling and Chrome; they do not include production network latency. Google API returned HTTP 429 quota exceeded. Post-deployment Lighthouse and a normal headless Chrome navigation returned Vercel Security Checkpoint HTTP 403, so a public 95+ PageSpeed score remains unverified. Do not count checkpoint pages as the site.

Functional QA: eight routes at widths 360, 390, 430, 768, 1024, 1440; no horizontal overflow or broken loaded images. Menu open/close/Escape, collection navigation, gallery thumbnails, lightbox and static purchase notice passed. Static link validation: nine pages, 523 references.

Run browser QA with `node reports/performance/qa.mjs` and `node reports/performance/interactions.mjs` against a local server on 8091; override with MEVKY_TEST_URL. Chrome must be installed.
