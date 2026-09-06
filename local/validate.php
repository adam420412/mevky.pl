<?php
if(!defined('ABSPATH')) require '/wordpress/wp-load.php';
$checks=[];
function mevky_check($condition,$name) { global $checks; $checks[$name]=(bool)$condition; if(!$condition) throw new RuntimeException('Validation failed: '.$name); }
$directory=new RecursiveDirectoryIterator(get_theme_file_path(),FilesystemIterator::SKIP_DOTS);
foreach(new RecursiveIteratorIterator($directory) as $file) if($file->getExtension()==='php') token_get_all(file_get_contents($file->getPathname()),TOKEN_PARSE);
mevky_check(true,'PHP source syntax');
json_decode(file_get_contents(get_theme_file_path('theme.json')),true,512,JSON_THROW_ON_ERROR);
mevky_check(true,'Theme JSON');
mevky_check(wp_get_theme()->get_stylesheet()==='mevky','Active MEVKY theme');
mevky_check(get_option('woocommerce_coming_soon') === 'no', 'Store visible to visitors');
mevky_check(get_locale()==='pl_PL','Polish locale');
foreach(array('woocommerce/product-price','woocommerce/add-to-cart-form','woocommerce/product-details','woocommerce/mini-cart') as $name) mevky_check(WP_Block_Type_Registry::get_instance()->is_registered($name),'Registered block '.$name);
mevky_check(substr_count(do_shortcode('[mevky_payment_logos]'), '<li class=')===4,'Four payment brands');
add_filter('pre_option_mevky_payment_logo_blik', '__return_empty_string');
mevky_check(substr_count(do_shortcode('[mevky_payment_logos]'), '<li class=')===3,'Disabled payment brand hidden');
remove_filter('pre_option_mevky_payment_logo_blik', '__return_empty_string');
$products=wc_get_products(array('status'=>'publish','limit'=>-1));
mevky_check(count($products)===3,'Three products');
$grid=do_shortcode('[mevky_products]');
mevky_check(substr_count($grid,'<article class="mevky-product-card">')===3,'Three rendered product cards');
foreach($products as $product) {
 mevky_check($product->get_image_id()>0 && count($product->get_gallery_image_ids())===2,'Gallery '.$product->get_slug());
 foreach(array_merge(array($product->get_image_id()),$product->get_gallery_image_ids()) as $image) mevky_check(file_exists(get_attached_file($image)),'Image '.$image);
 global $wp_query; $wp_query=new WP_Query(array('post_type'=>'product','p'=>$product->get_id()));
 $gallery=do_shortcode('[mevky_product_gallery]');
 mevky_check(substr_count($gallery,'data-gallery-slide')===3,'Rendered gallery '.$product->get_slug());
 $models=do_shortcode('[mevky_model_selector]');
 mevky_check(substr_count($models,'aria-current="page"')===1,'Active model '.$product->get_slug());
 $related=do_shortcode('[mevky_products related="yes"]');
 mevky_check(substr_count($related,'<article class="mevky-product-card">')===2,'Related products '.$product->get_slug());
}
wp_reset_query();
$files=array_merge(glob(get_theme_file_path('patterns').'/*.php'),glob(get_theme_file_path('templates').'/*.html'),glob(get_theme_file_path('parts').'/*.html'));
foreach($files as $file) mevky_check(!str_contains(file_get_contents($file),'DO UZUPEŁNIENIA'),'No placeholders '.basename($file));
file_put_contents('/mevky-local/.runtime/validation.json',wp_json_encode(array('time'=>gmdate('c'),'checks'=>$checks),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo 'PASS: '.count($checks).' checks';
