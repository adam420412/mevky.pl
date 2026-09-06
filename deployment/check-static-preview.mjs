import { access, readFile } from 'node:fs/promises';
import { extname } from 'node:path';

const root = new URL('../public/', import.meta.url);
const pages = [
  'index.html', 'sklep/index.html', 'produkt/lustro-aura/index.html',
  'produkt/lustro-crystal-40/index.html', 'produkt/lustro-crystal-30/index.html',
  'kontakt-i-dane-firmy/index.html', 'koszyk/index.html',
  'zamowienie/index.html', '404.html',
];
let checks = 0;
for (const page of pages) {
  const html = await readFile(new URL(page, root), 'utf8');
  if (html.includes('localhost:8080')) throw new Error(`Local URL remains in ${page}`);
  if (/\[mevky_[a-z_]+/.test(html)) throw new Error(`Raw shortcode remains in ${page}`);
  if (!html.includes('/preview.css') || !html.includes('/preview.js')) throw new Error(`Preview assets missing in ${page}`);
  for (const match of html.matchAll(/\/(?:wp-content|wp-includes)\/[^\s"'(),<>]+/g)) {
    const path = match[0].replace(/&amp;/g, '&').split(/[?#]/)[0].replace(/[\\/]+$/, '');
    if (!extname(path)) continue;
    await access(new URL(`.${path}`, root));
    checks++;
  }
  for (const match of html.matchAll(/href=["'](\/[^"'#?]*)/g)) {
    const path = match[1];
    if (path.startsWith('/wp-')) continue;
    const target = extname(path) ? new URL(`.${path}`, root) : new URL(`.${path.replace(/\/$/, '')}/index.html`, root);
    await access(target);
    checks++;
  }
  checks++;
}
const product = await readFile(new URL('produkt/lustro-aura/index.html', root), 'utf8');
if (!product.includes('single_add_to_cart_button') || !product.includes('mevky-gallery__stage')) throw new Error('Product presentation is incomplete');
console.log(`PASS: ${pages.length} static pages and ${checks} page/asset references.`);
