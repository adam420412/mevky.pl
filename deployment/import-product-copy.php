<?php
/** wp eval-file deployment/import-product-copy.php [apply]
 * Default: preview only. Does not change prices, stock, media or orders.
 */
if(!defined('WP_CLI') || !WP_CLI) { return; }
if(!function_exists('wc_get_product')) WP_CLI::error('WooCommerce musi być aktywny.');
$file=get_theme_file_path('content/products.json');
if(!is_file($file)) WP_CLI::error('Aktywuj motyw MEVKY z plikiem content/products.json.');
$copy=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
$apply=isset($args[0]) && $args[0]==='apply';
$missing=[]; $items=[];
foreach($copy as $slug=>$data){
 $post=get_page_by_path($slug,OBJECT,'product');
 if(!$post) { $missing[]=$slug; continue; }
 $items[]=array(wc_get_product($post->ID),$data);
}
if($missing) WP_CLI::error('Brak produktów: '.implode(', ',$missing).'. Nie wykonano zmian.');
foreach($items as [$product,$data]){
 WP_CLI::log(($apply?'Zapis: ':'Podgląd: ').$product->get_name().' (ID '.$product->get_id().')');
 if(!$apply) continue;
 if(!$product->meta_exists('_mevky_copy_backup_v1')) $product->update_meta_data('_mevky_copy_backup_v1',array('description'=>$product->get_description(),'short_description'=>$product->get_short_description()));
 $product->set_description(wp_kses_post($data['long_description']));
 $product->set_short_description(wpautop(esc_html($data['description'])));
 $product->save();
}
WP_CLI::success($apply?'Opisy zapisane; poprzednie treści zachowano w metadanych produktów.':'To był tylko podgląd. Dodaj argument apply, aby zapisać opisy.');
