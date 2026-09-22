// Only the static presentation is stripped of WordPress runtime scripts.
import {readFile,writeFile,mkdir,readdir,copyFile} from 'node:fs/promises';
import {join,dirname} from 'node:path';
import {createHash} from 'node:crypto';
import {load} from 'cheerio';
import sharp from 'sharp';
import {PurgeCSS} from 'purgecss';
import {transform} from 'lightningcss';
const root=new URL('../public/',import.meta.url).pathname;
const theme='/wp-content/themes/mevky/';
for(const file of ['style.css','assets/css/storefront.css','assets/js/storefront.js']) await copyFile(new URL('../mevky/'+file,import.meta.url),join(root,theme,file));
await mkdir(join(root,'optimized'),{recursive:true});
for(const file of ['static-navigation.css','static-navigation.js']) await copyFile(new URL(file,import.meta.url),join(root,file));
const pages=[];
async function walk(dir){for(const e of await readdir(dir,{withFileTypes:true})){if(e.isDirectory())await walk(join(dir,e.name));else if(e.name.endsWith('.html'))pages.push(join(dir,e.name));}}
await walk(root);
const cache=new Map();
for(const file of pages){
 const $=load(await readFile(file,'utf8'));
 $('script').not('[type="application/ld+json"]').remove();
 $('link[rel="modulepreload"],link[rel="https://api.w.org/"],link[type="application/json"],.wc-block-mini-cart__template-part').remove();
 for(const el of $('link[rel="stylesheet"]').toArray()){
  const url=$(el).attr('href').split('?')[0];
  if(url==='/static-navigation.css'||url.includes('/plugins/woocommerce/')){$(el).remove();continue;}
  if(url.startsWith('/wp-includes/')||url.startsWith('/wp-core-assets/')){$(el).remove();continue;}
  let css=await readFile(join(root,url),'utf8');
  css=css.replace(/url\((['"]?)(?!data:|https?:|\/|#)([^)'"\s]+)\1\)/g,(_,q,p)=>`url("${join(dirname(url),p)}")`);
  $(el).replaceWith('<style>'+css.replace(/\/\*[\s\S]*?\*\//g,'')+'</style>');
 }
 for(const img of $('img[src]').toArray()){
  const node=$(img),src=node.attr('src').split('?')[0];
  if(!src.startsWith('/')||src.startsWith('/optimized/'))continue;
  let variants=cache.get(src);
  if(!variants){
   const input=await readFile(join(root,src)),meta=await sharp(input).metadata();
   const hash=createHash('sha256').update(input).digest('hex').slice(0,12);
   const widths=[...new Set([320,640,960,1280,Math.min(meta.width,1600)].filter(w=>w<=meta.width))].sort((a,b)=>a-b);
   variants=[];
   for(const width of widths){const path=`/optimized/${hash}-${width}.webp`;await sharp(input).resize({width,withoutEnlargement:true}).webp({quality:78}).toFile(join(root,path));variants.push({width,path});}
   cache.set(src,variants);
  }
  node.attr('src',variants.at(-1).path).attr('srcset',variants.map(v=>`${v.path} ${v.width}w`).join(', ')).attr('decoding','async');
  if(!node.attr('sizes')) node.attr('sizes',node.closest('.mevky-hero-image').length?'(max-width: 781px) 100vw, 57vw':'(max-width: 781px) 100vw, 50vw');
 }
 // Keep the requested Instagram available in both menus after re-export.
 for(const nav of $('.mevky-header .wp-block-navigation__container,.mevky-footer .wp-block-navigation__container').toArray())if(!$(nav).find('a[href*="instagram.com"]').length)$(nav).append('<li class="wp-block-navigation-item wp-block-navigation-link"><a class="wp-block-navigation-item__content" href="https://www.instagram.com/mevky.official/">Instagram</a></li>');
 $('link[href="/static-navigation.css"]').remove();
 const combined=$('style').toArray().map(el=>$(el).html()).join('\n');
 const purged=await new PurgeCSS().purge({content:[{raw:$.html(),extension:'html'}],css:[{raw:combined}],safelist:[/^is-/,/^has-/,/^mevky-/,/^wp-block-navigation/,/^wc-block-mini-cart/],keyframes:true});
 $('style').remove();
 $('head').append('<style>'+transform({code:Buffer.from(purged[0].css),minify:true,errorRecovery:true}).code.toString()+'</style>');
 $('head').append('<link rel="stylesheet" href="/static-navigation.css"><script defer src="/static-navigation.js"></script><script defer src="/preview.js"></script><script defer src="'+theme+'assets/js/storefront.js"></script>');
 await writeFile(file,$.html().replaceAll('/wp-content/themes/mevky/assets/fonts/','/optimized/').replace(/[ \t]+$/gm,''));
}
console.log(`Optimized ${pages.length} pages and ${cache.size} images`);
