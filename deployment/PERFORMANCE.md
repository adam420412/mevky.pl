# Static presentation performance

Vercel serves the committed `public/` directory. Theme edits alone do not update this export.

After exporting from local WordPress, run `npm ci`, regenerate font subsets with `python deployment/subset-fonts.py` (requires `fonttools[woff]`), then `npm run optimize` and `npm run check`. Commit the generated public files as well as source changes.

The optimizer is exclusively for the static presentation. It replaces WordPress/WooCommerce runtime scripts with accessible menu handling and keeps the product galleries and presentation-mode notice. It must not be applied to a live WooCommerce checkout.

Images get responsive WebP variants with content-based names. Fonts retain Latin characters, Polish letters and punctuation. New languages require expanding the font subset. Navigation source lives in deployment/static-navigation.*; the optimizer copies it into public.

Check the actual production URL using Lighthouse mobile and desktop, and review mobile navigation, gallery, images, checkout notice and responsive layout after export. PageSpeed API can return quota errors independently of site performance; do not report local Lighthouse results as a Google PageSpeed run.


# Production WordPress — 22 September 2026, theme 0.3.4

Deployed on https://mevky.pl through its existing WordPress theme updater.
These are local Lighthouse audits of the public production URLs, not Google
PageSpeed Insights API results, Vercel results, or field/Core Web Vitals data.
Guest navigation, standard mobile/desktop presets, cold browser cache, warmed
LiteSpeed page cache. Scores vary between runs. Full source reports are in
`/tmp/mevky-perf/wp-final-*.json`; portable metric extracts are committed in
`production-performance-2026-09-22.json`.

| Page | Mobile | Desktop |
| --- | ---: | ---: |
| home | 95 | 100 |
| aura | 95 | 100 |
| crystal40 | 92 | 100 |
| crystal30 | 95 | 100 |

Before changes: homepage 91 mobile; Aura 66 mobile, LCP 8.3 seconds.
A score of 95 on every product page has NOT been established: Crystal 40
remains below target. Do not represent these measurements as a guarantee.

Changes: subsetted local fonts; responsive editorial WebP; content-hash-verified
WebP copies for existing Crystal media (falls back to native WP if replaced);
light cart link instead of the React mini-cart drawer; checkout registration
scripts scoped away from storefront pages; unused video-gallery player omitted
on image-only product content; local pinned Meta helper for Facebook for WooCommerce plugin
version 3.7.6, with original consent initialization and vendor license retained;
review CSS loaded without blocking the first product image; theme CSS inlined
at its original cascade position. LiteSpeed CSS minify ON, CSS combine OFF.
The tested CSS combination and AVIF variants did not improve results and were
reverted. Existing uploaded media, prices, stock and orders were preserved.

QA: PHP 8.3 syntax (12 files); checkout script boundary regression check;
live mobile menu and gallery; cart and checkout rendering with original cart
contents unchanged; payment scripts present on checkout; 390px width checks
for store, products and checkout. No payment transaction or order was submitted.
Fixed header contact link to the footer contact section and the terms URL to
the existing /regulamin-sklepu/ page.

Rebuild assets: `node deployment/build-performance-assets.mjs` after font subset
preparation described above. Crystal inputs are pinned by SHA-256 in
`performance-media-sources.json`. The vendor helper comes from the official
npm package meta-capi-param-builder-clientjs@1.3.2 and includes its license.
Regression check: `node deployment/check-performance-scope.mjs` (requires the
existing local WordPress Playground PHP-WASM dependencies).
