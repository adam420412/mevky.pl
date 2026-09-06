import assert from 'node:assert/strict';
import { writeFile } from 'node:fs/promises';
const origin = 'http://localhost:8080';
const checks = [];
const check = (ok, label) => { assert.ok(ok, label); checks.push(label); };
const productsResponse = await fetch(`${origin}/wp-json/wc/store/v1/products`);
const products = await productsResponse.json();
check(products.length === 3, 'Three live products');
const paths = ['/', '/sklep/', '/koszyk/', '/zamowienie/', '/kontakt-i-dane-firmy/', ...products.map(p => new URL(p.permalink).pathname), '/wp-content/themes/mevky/assets/css/storefront.css', '/wp-content/themes/mevky/assets/js/storefront.js'];
for (const path of paths) {
 const response = await fetch(origin + path);
 check(response.ok, `HTTP ${response.status}: ${path}`);
 const body = await response.text();
 if (!path.includes('/wp-content/')) {
  check(!body.includes('Wielkie rzeczy są na horyzoncie'), `No coming-soon wall: ${path}`);
  check(!/\[mevky_[a-z_]+/.test(body), `No raw storefront shortcode: ${path}`);
 }
 if (!path.includes('/wp-content/')) check(body.includes('mevky-payments__brands'), `Payment artwork rendered: ${path}`);
 if (path === '/') check((body.match(/<article class="mevky-product-card">/g) || []).length === 3, 'Homepage actually renders product cards');
 if (path.startsWith('/produkt/')) check(body.includes('single_add_to_cart_button') && body.includes('mevky-gallery__stage'), `Rendered purchase UI: ${path}`);
 if (path === '/koszyk/') check(body.includes('wc-empty-cart-message') || body.includes('woocommerce-cart-form'), 'Cart page has a visible cart or empty state');
}
for (const brand of ['blik','visa','mastercard','przelewy24']) {
 const response = await fetch(`${origin}/wp-content/themes/mevky/assets/images/payments/${brand}.png`);
 check(response.ok && response.headers.get('content-type')?.startsWith('image/'), `Payment logo loads: ${brand}`);
}
for (const product of products) {
 for (const image of product.images) {
  const response = await fetch(image.src, { method: 'HEAD' });
  check(response.ok && response.headers.get('content-type')?.startsWith('image/'), `Image loads: ${product.name}`);
 }
}
const cartResponse = await fetch(`${origin}/wp-json/wc/store/v1/cart`);
const token = cartResponse.headers.get('Cart-Token');
check(token, 'Independent test cart token');
const headers = {'Content-Type':'application/json', 'Cart-Token':token};
async function post(path, data) {
 const response = await fetch(`${origin}/wp-json/wc/store/v1/cart/${path}`, {method:'POST', headers, body:JSON.stringify(data)});
 const result = await response.json();
 check(response.ok, `Cart ${path}: ${response.status}`);
 return result;
}
const aura = products.find(p => p.slug === 'lustro-aura');
let cart = await post('add-item', {id:aura.id, quantity:1});
check(cart.items_count === 1 && cart.totals.total_items === '39900', 'Aura added at 399 PLN');
const key = cart.items[0].key;
cart = await post('update-item', {key, quantity:2});
check(cart.items_count === 2 && cart.totals.total_items === '79800', 'Quantity updates totals');
cart = await post('update-customer', {shipping_address:{first_name:'Test',last_name:'Lokalny',address_1:'Testowa 1',city:'Warszawa',postcode:'00-001',country:'PL'}});
check(cart.shipping_rates.some(zone => zone.shipping_rates.some(rate => rate.method_id === 'free_shipping' && rate.price === '0')), 'Free shipping available');
cart = await post('remove-item', {key});
check(cart.items_count === 0, 'Test cart cleaned up');
await writeFile(new URL('./.runtime/http-validation.json', import.meta.url), JSON.stringify({time:new Date().toISOString(),checks},null,2));
console.log(`PASS: ${checks.length} HTTP, asset and cart checks. No orders placed.`);
