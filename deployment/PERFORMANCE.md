# Static presentation performance

Vercel serves the committed `public/` directory. Theme edits alone do not update this export.

After exporting from local WordPress, run `npm ci`, regenerate font subsets with `python deployment/subset-fonts.py` (requires `fonttools[woff]`), then `npm run optimize` and `npm run check`. Commit the generated public files as well as source changes.

The optimizer is exclusively for the static presentation. It replaces WordPress/WooCommerce runtime scripts with accessible menu handling and keeps the product galleries and presentation-mode notice. It must not be applied to a live WooCommerce checkout.

Images get responsive WebP variants with content-based names. Fonts retain Latin characters, Polish letters and punctuation. New languages require expanding the font subset. Navigation source lives in deployment/static-navigation.*; the optimizer copies it into public.

Check the actual production URL using Lighthouse mobile and desktop, and review mobile navigation, gallery, images, checkout notice and responsive layout after export. PageSpeed API can return quota errors independently of site performance; do not report local Lighthouse results as a Google PageSpeed run.
