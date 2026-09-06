<?php
/** Product presentation. All prices, stock and images come from WooCommerce. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mevky_product_editorial( $product ) {
 static $copy = null;
 if ( $copy === null ) $copy = json_decode(file_get_contents(get_theme_file_path('content/products.json')), true) ?: array();
 return $copy[$product->get_slug()] ?? array('label'=>'Kolekcja MEVKY','title'=>$product->get_name(),'intro'=>'Twoje światło na co dzień.','description'=>wp_strip_all_tags($product->get_short_description()),'fit'=>'Poznaj szczegóły');
}
function mevky_icon( $name ) {
 $paths = array('arrow'=>'<path d="M5 12h14M13 6l6 6-6 6"/>','sun'=>'<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/>','adjust'=>'<path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="2" fill="currentColor"/><circle cx="15" cy="17" r="2" fill="currentColor"/>','truck'=>'<path d="M2 5h12v12H2zM14 9h4l4 4v4h-8"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>','return'=>'<path d="M8 4 3 9l5 5M3 9h11a6 6 0 0 1 0 12"/>','bag'=>'<path d="M5 7h14l1 14H4L5 7Z"/><path d="M9 8V5a3 3 0 0 1 6 0v3"/>','zoom'=>'<circle cx="10" cy="10" r="7"/><path d="m15 15 6 6M7 10h6m-3-3v6"/>');
 return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name] ?? $paths['arrow']).'</svg>';
}
function mevky_product_grid( $atts ) {
 if ( ! function_exists('wc_get_products') ) { return ''; }
 $atts=shortcode_atts(array('related'=>'no'),$atts);
 $args=array('status'=>'publish','limit'=>12,'orderby'=>'menu_order','order'=>'ASC','visibility'=>'visible');
 if($atts['related']==='yes' && is_product()) $args['exclude']=array(get_queried_object_id());
 if(is_product_category()) $args['category']=array(get_queried_object()->slug);
 $products=wc_get_products($args);
 if(!$products) return '<p>W tej kolekcji nie ma jeszcze produktów.</p>';
 ob_start(); ?>
 <div class="mevky-product-grid<?php echo count($products)===2?' mevky-product-grid--two':''; ?>">
 <?php foreach($products as $index=>$item): $copy=mevky_product_editorial($item); $gallery=$item->get_gallery_image_ids(); ?>
 <article class="mevky-product-card">
  <a class="mevky-product-card__visual" href="<?php echo esc_url($item->get_permalink()); ?>" aria-label="<?php echo esc_attr('Poznaj '.$item->get_name()); ?>">
   <span class="mevky-product-card__label"><?php echo esc_html($copy['label']); ?></span>
   <?php echo $item->get_image('large',array('class'=>'mevky-product-card__primary','loading'=>'lazy','sizes'=>'(max-width: 600px) 92vw, (max-width: 900px) 45vw, 30vw')); ?>
   <?php if($gallery) echo wp_get_attachment_image($gallery[0],'large',false,array('class'=>'mevky-product-card__alternate','alt'=>'','loading'=>'lazy','aria-hidden'=>'true','sizes'=>'(max-width: 600px) 92vw, 30vw')); ?>
   <span class="mevky-product-card__explore">Poznaj model <?php echo mevky_icon('arrow'); ?></span>
  </a>
  <div class="mevky-product-card__meta"><span>MEVKY / LUSTRO LED</span><span><?php echo esc_html(sprintf('%02d',$index+1)); ?></span></div>
  <div class="mevky-product-card__heading"><h3><a href="<?php echo esc_url($item->get_permalink()); ?>"><?php echo esc_html($copy['title']); ?></a></h3><span class="mevky-product-card__price"><?php echo wp_kses_post($item->get_price_html()); ?></span></div>
  <p class="mevky-product-card__description"><?php echo esc_html($copy['intro']); ?></p>
  <div class="mevky-product-card__bottom"><span><?php echo esc_html($copy['fit']); ?></span><a href="<?php echo esc_url($item->get_permalink()); ?>" aria-label="<?php echo esc_attr('Zobacz szczegóły: '.$copy['title']); ?>"><?php echo mevky_icon('arrow'); ?></a></div>
 </article>
 <?php endforeach; ?></div>
 <?php return ob_get_clean();
}
add_shortcode('mevky_products','mevky_product_grid');

function mevky_product_context() {
 if(!function_exists('wc_get_product')) return false;
 return wc_get_product(get_queried_object_id());
}
add_shortcode('mevky_breadcrumb',function(){
 $product=mevky_product_context(); if(!$product) return '';
 return '<nav class="mevky-breadcrumb" aria-label="Ścieżka nawigacji"><a href="'.esc_url(home_url('/')).'">MEVKY</a><span>/</span><a href="'.esc_url(wc_get_page_permalink('shop')).'">Kolekcja</a><span>/</span><span aria-current="page">'.esc_html($product->get_name()).'</span></nav>';
});
add_shortcode('mevky_product_intro',function(){
 $product=mevky_product_context(); if(!$product) return '';
 $copy=mevky_product_editorial($product);
 return '<p class="mevky-eyebrow">'.esc_html($copy['label']).'</p><h1 class="mevky-product-title">'.esc_html($copy['title']).'</h1><p class="mevky-product-subtitle">'.esc_html($copy['intro']).'</p>';
});
add_shortcode('mevky_product_description',function(){
 $product=mevky_product_context(); if(!$product) return '';
 $copy=mevky_product_editorial($product);
 return '<p class="mevky-product-description">'.esc_html($copy['description']).'</p>';
});
add_shortcode('mevky_model_selector',function(){
 $product=mevky_product_context(); if(!$product) return '';
 $items=wc_get_products(array('status'=>'publish','limit'=>3,'orderby'=>'menu_order','order'=>'ASC','visibility'=>'visible'));
 $html='<div class="mevky-models"><p>Wybierz swój model <span>'.esc_html(mevky_product_editorial($product)['title']).'</span></p><nav aria-label="Modele luster">';
 foreach($items as $item) $html.='<a href="'.esc_url($item->get_permalink()).'"'.($item->get_id()===$product->get_id()?' aria-current="page"':'').'>'.esc_html(mevky_product_editorial($item)['title']).'</a>';
 return $html.'</nav></div>';
});
add_shortcode('mevky_product_benefits',function(){
 return '<div class="mevky-feature-list"><div>'.mevky_icon('sun').'<span>Trzy barwy światła</span></div><div>'.mevky_icon('adjust').'<span>Regulacja jasności</span></div></div><div class="mevky-delivery"><div>'.mevky_icon('truck').'<span><strong>Dostawa na nasz koszt</strong>Wysyłka w ciągu 48 godzin</span></div><div>'.mevky_icon('return').'<span><strong>Spokojny wybór</strong>14 dni na zwrot</span></div></div>';
});
add_shortcode('mevky_product_gallery',function(){
 $product=mevky_product_context(); if(!$product) return '';
 $ids=array_values(array_filter(array_merge(array($product->get_image_id()),$product->get_gallery_image_ids())));
 if(!$ids) return $product->get_image('large');
 $uid=wp_unique_id('mevky-gallery-');
 ob_start(); ?>
 <div class="mevky-gallery" data-gallery>
  <div class="mevky-gallery__stage">
   <span class="mevky-gallery__tag">ŚWIATŁO. FORMA. TWÓJ RYTUAŁ.</span>
   <?php foreach($ids as $i=>$id): ?>
    <a class="mevky-gallery__slide" id="<?php echo esc_attr($uid.$i); ?>" href="<?php echo esc_url(wp_get_attachment_image_url($id,'full')); ?>" data-gallery-slide data-full="<?php echo esc_url(wp_get_attachment_image_url($id,'full')); ?>" <?php echo $i?'hidden':''; ?> aria-label="<?php echo esc_attr('Powiększ zdjęcie '.($i+1).' — '.$product->get_name()); ?>">
     <?php echo wp_get_attachment_image($id,'large',false,array('loading'=>$i?'lazy':'eager','fetchpriority'=>$i?'auto':'high','sizes'=>'(max-width: 781px) 100vw, 56vw')); ?>
     <span class="mevky-gallery__zoom"><?php echo mevky_icon('zoom'); ?></span>
    </a>
   <?php endforeach; ?>
   <span class="mevky-gallery__count" aria-live="polite"><span data-gallery-count>01</span> / <?php echo esc_html(sprintf('%02d',count($ids))); ?></span>
  </div>
  <div class="mevky-gallery__bottom"><div class="mevky-gallery__thumbs" role="group" aria-label="Zdjęcia produktu">
   <?php foreach($ids as $i=>$id): ?><button type="button" data-gallery-thumb="<?php echo esc_attr($i); ?>" aria-controls="<?php echo esc_attr($uid.$i); ?>" aria-pressed="<?php echo $i?'false':'true'; ?>" aria-label="<?php echo esc_attr('Pokaż zdjęcie '.($i+1)); ?>"><?php echo wp_get_attachment_image($id,'thumbnail',false,array('alt'=>'','loading'=>'lazy')); ?></button><?php endforeach; ?>
  </div><span>Piękno tkwi w detalach.</span></div>
  <dialog class="mevky-lightbox" aria-label="Powiększone zdjęcie produktu"><button type="button" class="mevky-lightbox__close" aria-label="Zamknij powiększenie">×</button><img alt="<?php echo esc_attr($product->get_name()); ?>"/></dialog>
 </div>
 <?php return ob_get_clean();
});

// Patterns expand after the template's initial shortcode pass. Render our
// shortcode blocks when they are reached, including those inside patterns.
add_filter( 'render_block_core/shortcode', function ( $content ) {
 return str_contains( $content, '[mevky_' ) ? do_shortcode( $content ) : $content;
} );

add_shortcode('mevky_story_image',function($atts){
 $atts=shortcode_atts(array('key'=>''),$atts);
 $allowed=array('aura-ritual'=>'lustro-aura-0.webp','crystal-ritual'=>'lustro-crystal-40-0.png');
 if(!isset($allowed[$atts['key']])) return '';
 $generated='assets/images/'.$atts['key'].'-editorial.webp';
 $exists=file_exists(get_theme_file_path($generated));
 $path=$exists?$generated:'assets/images/'.$allowed[$atts['key']];
 $caption=$exists?'Przykładowa aranżacja wnętrza — wizualizacja. Akcesoria nie są częścią zestawu.':'Lustro MEVKY — zdjęcie z kolekcji.';
 return '<figure class="mevky-story-photo"><img src="'.esc_url(get_theme_file_uri($path)).'" alt="Lustro MEVKY w aranżacji toaletki" width="1536" height="1024" loading="lazy" decoding="async"/><figcaption>'.esc_html($caption).'</figcaption></figure>';
});
add_shortcode('mevky_story_detail',function(){
 $product=mevky_product_context(); if(!$product) return '';
 return '<figure class="mevky-story-photo mevky-story-photo--detail">'.$product->get_image('large',array('loading'=>'lazy','alt'=>$product->get_name().' — rama i podświetlenie','sizes'=>'(max-width: 781px) 92vw, 50vw')).'<figcaption>Przyjrzyj się wykończeniu modelu '.esc_html(mevky_product_editorial($product)['title']).'.</figcaption></figure>';
});
