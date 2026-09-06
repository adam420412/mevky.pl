<?php
if ( ! defined('ABSPATH') ) require '/wordpress/wp-load.php';
update_option('woocommerce_checkout_privacy_policy_text', 'To lokalny podgląd sklepu. Do testowania używaj wyłącznie fikcyjnych danych.');
update_option('woocommerce_coming_soon', 'no');
update_option('woocommerce_store_pages_only', 'no');
// A self-closing WooCommerce cart/checkout block has no inner layout.
// Replace only those original empty fixtures; retain user-edited page content.
foreach (array('cart', 'checkout') as $type) {
 $page = get_post((int)get_option('woocommerce_'.$type.'_page_id'));
 if ($page && trim($page->post_content) === '<!-- wp:woocommerce/'.$type.' /-->') {
  wp_update_post(array('ID'=>$page->ID, 'post_content'=>'<!-- wp:shortcode -->[woocommerce_'.$type.']<!-- /wp:shortcode -->'));
 }
}
if ( get_option('mevky_local_seed_version') === '3' ) { return; }
switch_theme('mevky');
foreach (array('blogname'=>'MEVKY','blogdescription'=>'Lustra LED. Piękno w codzienności.','timezone_string'=>'Europe/Warsaw','woocommerce_currency'=>'PLN','woocommerce_currency_pos'=>'right_space','woocommerce_price_decimal_sep'=>',','woocommerce_price_thousand_sep'=>' ','woocommerce_default_country'=>'PL','woocommerce_store_address'=>'Nowa Paprocka Kolonia 2','woocommerce_store_city'=>'Krzymów','woocommerce_store_postcode'=>'62-513','woocommerce_calc_taxes'=>'no','woocommerce_enable_guest_checkout'=>'yes','woocommerce_onboarding_profile'=>array('completed'=>true)) as $key=>$value) update_option($key,$value);
function mevky_local_page($title,$slug,$content) {
 $page=get_page_by_path($slug); if($page) return $page->ID;
 return wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>$title,'post_name'=>$slug,'post_content'=>$content));
}
// Only local fixtures: a test checkout with no money movement or email.
update_option('woocommerce_cod_settings',array('enabled'=>'yes','title'=>'Zamówienie testowe — bez płatności','description'=>'Podgląd lokalny. Zamówienie nie zostanie wysłane i nie pobierzemy płatności.','instructions'=>'To zamówienie testowe utworzone lokalnie.','enable_for_virtual'=>'yes'));
$zone=new WC_Shipping_Zone(0);
$has_free=false; foreach($zone->get_shipping_methods() as $method) if($method->id==='free_shipping') $has_free=true;
if(!$has_free) $zone->add_shipping_method('free_shipping');
$admin=get_user_by('login','admin');
if($admin) wp_update_user(array('ID'=>$admin->ID,'user_pass'=>'mevky-local','user_email'=>'admin@example.invalid'));
$home=mevky_local_page('Strona główna','home',''); update_option('show_on_front','page'); update_option('page_on_front',$home);
foreach (array('shop'=>array('Kolekcja','sklep',''),'cart'=>array('Koszyk','koszyk','<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->'),'checkout'=>array('Zamówienie','zamowienie','<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->'),'myaccount'=>array('Moje konto','moje-konto','[woocommerce_my_account]')) as $key=>$args) {
 $id=mevky_local_page(...$args); update_option('woocommerce_'.$key.'_page_id',$id);
}
mevky_local_page('Porozmawiajmy','kontakt-i-dane-firmy','<!-- wp:paragraph --><p>Potrzebujesz pomocy w wyborze lustra albo masz pytanie o zamówienie? Napisz do nas. Odpowiadamy od poniedziałku do piątku, w ciągu 24 godzin roboczych.</p><!-- /wp:paragraph --><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Jesteśmy do Twojej dyspozycji</h2><!-- /wp:heading --><!-- wp:paragraph --><p><a href="mailto:kontakt@mevky.pl">kontakt@mevky.pl</a><br><a href="tel:+48514318292">+48 514 318 292</a></p><!-- /wp:paragraph --><!-- wp:paragraph --><p><strong>EMDE Norbert Białas</strong><br>Nowa Paprocka Kolonia 2, 62-513 Krzymów<br>NIP: 6652975239 · REGON: 384408284</p><!-- /wp:paragraph -->');
require_once ABSPATH.'wp-admin/includes/image.php';
$data=json_decode(file_get_contents('/mevky-local/data/products.json'),true);
usort($data,fn($a,$b)=>$a['prices']['price'] < $b['prices']['price'] ? 1 : -1);
foreach($data as $order=>$item) {
 $existing=get_page_by_path($item['slug'],OBJECT,'product');
 $product=$existing ? wc_get_product($existing->ID) : new WC_Product_Simple();
 $product->set_name($item['name']); $product->set_slug($item['slug']); $product->set_status('publish');
 $product->set_regular_price((string)($item['prices']['regular_price']/100));
 if ( $item['slug'] === 'lustro-crystal-30' ) { $item['description'] = explode('<p><strong>Specyfikacja:', $item['description'])[0]; }
 $product->set_description(wp_kses_post($item['description'])); $product->set_short_description(wp_kses_post($item['short_description']));
 $product->set_stock_status('instock'); $product->set_menu_order($order);
 $attrs=[];
 foreach($item['attributes'] as $row){ $attr=new WC_Product_Attribute(); $attr->set_name($row['name']); $attr->set_options(array_column($row['terms'],'name')); $attr->set_visible(true); $attrs[]=$attr; }
 $product->set_attributes($attrs); $id=$product->save(); if ( $product->get_image_id() ) { continue; } $images=[];
 foreach($item['local_images'] as $index=>$name) {
  $upload=wp_upload_dir(); $file=$upload['path'].'/'.$name;
  copy(get_theme_file_path('assets/images/'.$name),$file);
  $attachment=wp_insert_attachment(array('post_mime_type'=>wp_check_filetype($name)['type'],'post_title'=>$item['name'].' — zdjęcie '.($index+1),'post_status'=>'inherit'),$file,$id);
  update_post_meta($attachment,'_wp_attachment_image_alt',$item['name'].' — widok '.($index+1));
  wp_update_attachment_metadata($attachment,wp_generate_attachment_metadata($attachment,$file)); $images[]=$attachment;
 }
 $product->set_image_id(array_shift($images)); $product->set_gallery_image_ids($images); $product->save();
}
update_option('woocommerce_permalinks',array('product_base'=>'/produkt','category_base'=>'kategoria-produktu'));
global $wp_rewrite; $wp_rewrite->set_permalink_structure('/%postname%/'); flush_rewrite_rules();
update_option('mevky_local_seed_version','3');
