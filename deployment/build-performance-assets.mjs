import sharp from 'sharp';
import{mkdir,copyFile,readFile,writeFile}from'node:fs/promises';
const root = new URL('../mevky', import.meta.url).pathname;
await mkdir(root+'/assets/performance',{recursive:true});
for(const file of ['fraunces-latin.woff2','fraunces-latin-ext.woff2','inter-latin.woff2','inter-latin-ext.woff2'])await copyFile(new URL('../public/optimized/'+file, import.meta.url),root+'/assets/performance/'+file);
for(const name of ['hero.webp','historia-1.jpg','historia-2.jpg','aura-ritual-editorial.webp','crystal-ritual-editorial.webp']){
 for(const width of [480,800,1280])await sharp(root+'/assets/images/'+name).resize({width,withoutEnlargement:true}).webp({quality:78}).toFile(root+'/assets/performance/'+name.split('.')[0]+'-'+width+'.webp');
}

// Do not regenerate derivatives if the store owner has changed a source.
const {createHash} = await import('node:crypto');
const media = JSON.parse(await readFile(new URL('./performance-media-sources.json', import.meta.url)));
for (const item of media) {
 const response = await fetch(item.url);
 if (!response.ok) throw new Error('Media fetch failed: '+response.status);
 const data = Buffer.from(await response.arrayBuffer());
 if (createHash('sha256').update(data).digest('hex') !== item.sha256) throw new Error('Source changed: '+item.url);
 for (const width of item.widths) await sharp(data).resize({width, withoutEnlargement:true}).webp({quality: item.stem==='crystal40' && width!==600 ? 80 : item.quality}).toFile(root+'/assets/performance/'+item.stem+'-product-'+width+'.webp');
}
