<?php
/** Publication metadata and explicit, verified promotion reference prices. */
if (!defined('ABSPATH')) exit;

add_filter('wp_robots', function($robots){
 if (in_array(wp_get_environment_type(),array('local','development','staging'),true) || is_search() || is_404() || (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page()))) {
  $robots['noindex']=true; unset($robots['index']);
 }
 return $robots;
});

add_action('wp_head',function(){
 if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION')) return;
 $description='Poznaj lustra LED MEVKY: Aura 50, Crystal 40 i Crystal 30. Trzy barwy światła, dekoracyjna rama i darmowa dostawa. Wybierz model do swojej toaletki.';
 if(function_exists('is_product') && is_product()) {
  $product=wc_get_product(get_queried_object_id());
  if($product) $description=wp_strip_all_tags($product->get_short_description());
 } elseif(is_page('kontakt-i-dane-firmy')) {
  $description='Potrzebujesz pomocy w wyborze lustra MEVKY? Napisz na kontakt@mevky.pl lub zadzwoń: +48 514 318 292. Kontakt i dane firmy.';
 } elseif(!is_front_page() && is_singular()) {
  $description=wp_strip_all_tags(strip_shortcodes(get_the_excerpt()));
 }
 $description=wp_html_excerpt(preg_replace('/\s+/u',' ',trim($description)),180,'…');
 if(!$description || is_404() || is_search() || (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page()))) return;
 $url=is_front_page()?home_url('/'):get_permalink();
 if(function_exists('is_shop') && is_shop()) $url=wc_get_page_permalink('shop');
 printf('<meta name="description" content="%s"/>'."\n",esc_attr($description));
 printf('<meta property="og:title" content="%s"/><meta property="og:description" content="%s"/><meta property="og:type" content="website"/><meta property="og:url" content="%s"/><meta property="og:locale" content="pl_PL"/>'."\n",esc_attr(wp_get_document_title()),esc_attr($description),esc_url($url));
 if(function_exists('is_shop') && is_shop()) printf('<link rel="canonical" href="%s"/>'."\n",esc_url($url));
},5);

// The FSE purchase layout bypasses Woo's classic summary hook. Reuse Woo's
// own product schema builder and only fill in the missing Product entry.
add_action('wp_footer',function(){
 if(!function_exists('is_product') || !is_product()) return;
 $structured=WC()->structured_data;
 foreach($structured->get_data() as $entry) if(($entry['@type']??'')==='Product') return;
 $product=wc_get_product(get_queried_object_id());
 if($product) $structured->generate_product_data($product);
},5);

// A verified pre-promotion price is entered/imported explicitly. The old
// daily minimum was not a valid pre-promotion snapshot and is not displayed.
add_action('woocommerce_product_options_pricing',function(){
 woocommerce_wp_text_input(array('id'=>'_mevky_verified_reference_price','label'=>'Cena przed obniżką (30 dni)','description'=>'Wpisz zweryfikowaną najniższą cenę z okresu przed rozpoczęciem tej promocji. Przy nowej promocji zweryfikuj wartość ponownie.','desc_tip'=>true,'type'=>'number','custom_attributes'=>array('min'=>'0','step'=>'0.01')));
});
add_action('woocommerce_admin_process_product_object',function($product){
 if(!isset($_POST['_mevky_verified_reference_price'])) return;
 $raw=wc_clean(wp_unslash($_POST['_mevky_verified_reference_price']));
 if($raw==='') $product->delete_meta_data('_mevky_verified_reference_price');
 elseif(is_numeric($raw) && (float)$raw>=0) $product->update_meta_data('_mevky_verified_reference_price',wc_format_decimal($raw));
});
add_shortcode('mevky_sale_reference',function(){
 $product=mevky_product_context(); if(!$product || !$product->is_on_sale()) return '';
 $price=$product->get_meta('_mevky_verified_reference_price');
 if($price==='' || !is_numeric($price)) return '';
 return '<p class="mevky-omnibus">Najniższa cena z 30 dni przed obniżką: '.wp_kses_post(wc_price(wc_get_price_to_display($product,array('price'=>(float)$price)))).'</p>';
});
add_action('admin_notices',function(){
 $screen=get_current_screen(); if(!$screen || $screen->id!=='product') return;
 $id=isset($_GET['post'])?absint($_GET['post']):0;
 $product=$id?wc_get_product($id):false;
 if($product && $product->is_on_sale() && $product->get_meta('_mevky_verified_reference_price')==='') echo '<div class="notice notice-warning"><p>MEVKY: przed uruchomieniem promocji uzupełnij zweryfikowaną cenę z okresu przed obniżką w danych produktu. Motyw nie odtwarza historii cen sprzed wdrożenia.</p></div>';
});
