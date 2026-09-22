import {access,readFile} from 'node:fs/promises';
import {extname} from 'node:path';
import {load} from 'cheerio';
const root=new URL('../public/',import.meta.url);
const pages=['index.html','sklep/index.html','produkt/lustro-aura/index.html','produkt/lustro-crystal-30/index.html','produkt/lustro-crystal-40/index.html','kontakt-i-dane-firmy/index.html','koszyk/index.html','zamowienie/index.html','404.html'];
let checks=0;
for(const page of pages){const html=await readFile(new URL(page,root),'utf8'),$=load(html);
 if(html.includes('localhost:8080')||/\[mevky_[a-z_]+/.test(html))throw Error('Unexported content: '+page);
 for(const el of $('[src],link[href],a[href]').toArray()){
  const url=$(el).attr('src')||$(el).attr('href');if(!url?.startsWith('/')||url.startsWith('//'))continue;
  const path=url.split(/[?#]/)[0];const target=extname(path)?'.'+path:'.'+path.replace(/\/$/,'')+'/index.html';
  await access(new URL(target,root));checks++;
 }
 for(const el of $('img[srcset]').toArray())for(const item of $(el).attr('srcset').split(',')){const path=item.trim().split(/\s/)[0];if(path.startsWith('/')){await access(new URL('.'+path,root));checks++;}}
 if(!$('script[src="/static-navigation.js"]').length)throw Error('Missing navigation: '+page);
}
console.log(`PASS: ${pages.length} pages, ${checks} references`);
