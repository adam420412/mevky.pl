import { mkdir, rm, writeFile } from 'node:fs/promises';
import { dirname, extname } from 'node:path';

const origin = process.env.MEVKY_PREVIEW_SOURCE || 'http://localhost:8080';
const output = new URL('../public/', import.meta.url);
const wpCoreAssetPrefix = '/wp-core-assets/';

const routes = [
  '/',
  '/sklep/',
  '/produkt/lustro-aura/',
  '/produkt/lustro-crystal-40/',
  '/produkt/lustro-crystal-30/',
  '/kontakt-i-dane-firmy/',
  '/regulamin/',
  '/polityka-prywatnosci/',
  '/koszyk/',
  '/zamowienie/',
];

const assetPaths = new Map();

function rewriteAssetPathForPreview(path) {
  return path.replace(/^\/wp-includes\//, wpCoreAssetPrefix);
}

function rewriteAssetPaths(text) {
  return text.replaceAll('/wp-includes/', wpCoreAssetPrefix);
}

function discoverAssets(text) {
  for (const match of text.matchAll(/\/(?:wp-content|wp-includes)\/[^\s"'(),<>]+/g)) {
    const sourcePath = match[0].replace(/&amp;/g, '&').split(/[?#]/)[0].replace(/[\\/]+$/, '');
    if (extname(sourcePath) && !sourcePath.includes('{') && !sourcePath.includes('}')) {
      const destinationPath = rewriteAssetPathForPreview(sourcePath);
      assetPaths.set(sourcePath, destinationPath);
    }
  }
}

function prepareHtml(html) {
  discoverAssets(html);
  const normalizedHtml = rewriteAssetPaths(html);
  return normalizedHtml
    .replace(/<link[^>]+href=["']\/\/localhost["'][^>]*>\s*/gi, '')
    .replace(/<link[^>]+type=["'][^"']*\+oembed["'][^>]*>\s*/gi, '')
    .replace(/<link[^>]+type=["']application\/rss\+xml["'][^>]*>\s*/gi, '')
    .replace(/<link[^>]+href=["']\/xmlrpc\.php[^"']*["'][^>]*>\s*/gi, '')
    .replaceAll(origin.replaceAll('/', '\\/'), '')
    .replaceAll(origin, '')
    .replace(/<link[^>]+href=["']\/xmlrpc\.php[^"']*["'][^>]*>\s*/gi, '')
    .replace(/href=("|')\/moje-konto\/\1/g, 'href="#" data-mevky-preview-account')
    .replace(/<link[^>]+rel=["']canonical["'][^>]*>/gi, '')
    .replace(/<meta[^>]+property=["']og:url["'][^>]*>/gi, '')
    .replace('</head>', '<link rel="stylesheet" href="/preview.css"><script src="/preview.js" defer></script></head>')
    .replace(/[ \t]+$/gm, '');
}

async function fetchRequired(url, required = true) {
  const response = await fetch(url);
  if (!response.ok) {
    if (!required && response.status === 404) {
      return null;
    }
    throw new Error(`${response.status} ${url}`);
  }
  return response;
}

await rm(output, { recursive: true, force: true });
await mkdir(output, { recursive: true });

for (const route of routes) {
  const response = await fetchRequired(origin + route, true);
  const html = prepareHtml(await response.text());
  const destination = route === '/' ? new URL('index.html', output) : new URL(`.${route}index.html`, output);
  await mkdir(dirname(destination.pathname), { recursive: true });
  await writeFile(destination, html);
}

const copied = new Set();
while (copied.size < assetPaths.size) {
  for (const [sourcePath, destinationPath] of [...assetPaths]) {
    if (copied.has(sourcePath)) continue;

    const response = await fetchRequired(origin + sourcePath, false);
    if (!response) {
      copied.add(sourcePath);
      continue;
    }
    const bytes = Buffer.from(await response.arrayBuffer());
    const destination = new URL(`.${destinationPath}`, output);
    await mkdir(dirname(destination.pathname), { recursive: true });
    await writeFile(destination, bytes);

    copied.add(sourcePath);
    if (response.headers.get('content-type')?.includes('text/css')) {
      discoverAssets(bytes.toString());
    }
  }
}

await writeFile(new URL('preview.css', output), `
.mevky-preview-toast{position:fixed;z-index:99999;left:50%;bottom:24px;transform:translate(-50%,20px);max-width:min(520px,calc(100vw - 32px));box-sizing:border-box;padding:14px 18px;background:#303626;color:#fff;font:500 14px/1.45 Inter,Arial,sans-serif;box-shadow:0 12px 36px #0003;opacity:0;pointer-events:none;transition:.2s ease}
.mevky-preview-toast.is-visible{opacity:1;transform:translate(-50%,0)}
`);

await writeFile(new URL('preview.js', output), `
(()=>{const toast=document.createElement('div');toast.className='mevky-preview-toast';toast.setAttribute('role','status');toast.textContent='To jest wersja prezentacyjna. Pełny koszyk, konto i płatności działają po instalacji motywu w WordPressie.';document.body.append(toast);let timer;const show=()=>{toast.classList.add('is-visible');clearTimeout(timer);timer=setTimeout(()=>toast.classList.remove('is-visible'),4200)};document.addEventListener('click',event=>{const demo=event.target.closest('.single_add_to_cart_button,[data-mevky-preview-account]');if(demo){event.preventDefault();event.stopImmediatePropagation();show();return}const mini=event.target.closest('.wc-block-mini-cart__button');if(mini){event.preventDefault();event.stopImmediatePropagation();location.href='/koszyk/'}},true)})();
`);

const notFound = await fetch(origin + '/strona-nie-istnieje/');
await writeFile(new URL('404.html', output), prepareHtml(await notFound.text()));

console.log(`Static preview: ${routes.length} pages, ${copied.size} assets → ${output.pathname}`);
