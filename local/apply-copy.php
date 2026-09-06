<?php
if(!defined('ABSPATH')) require '/wordpress/wp-load.php';
$file=get_theme_file_path('content/products.json');
$revision=hash_file('sha256',$file);
if(get_option('mevky_product_copy_revision')===$revision) return;
$data=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
foreach($data as $slug=>$copy){
 $post=get_page_by_path($slug,OBJECT,'product'); if(!$post) continue;
 $product=wc_get_product($post->ID);
 $product->set_short_description(wpautop(esc_html($copy['description'])));
 $product->set_description(wp_kses_post($copy['long_description']));
 $product->save();
}
update_option('mevky_product_copy_revision',$revision);
