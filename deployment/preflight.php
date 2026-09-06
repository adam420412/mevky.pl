<?php
/** Read-only release checks. Run: wp eval-file deployment/preflight.php */
if (!defined('ABSPATH')) { exit; }
$rows=[];
$check=function($ok,$label,$action='') use (&$rows){ $rows[]=array('status'=>$ok?'OK':'SPRAWDŹ','check'=>$label,'action'=>$ok?'':$action); };
$check(wp_get_theme()->get_template()==='mevky','Motyw MEVKY','Aktywuj aktualną paczkę na stagingu.');
$check(wp_get_environment_type()==='production','Środowisko produkcyjne','Na stagingu to oczekiwane. Zmień dopiero przy uruchomieniu.');
$check(wp_parse_url(home_url(),PHP_URL_SCHEME)==='https','HTTPS','Ustaw HTTPS i sprawdź certyfikat.');
$check((string)get_option('blog_public')==='1','Indeksowanie WordPress','Włącz dopiero przy publikacji.');
$check(!defined('WP_DEBUG') || !WP_DEBUG,'Debug wyłączony','Wyłącz debug na produkcji.');
$check(!file_exists(WPMU_PLUGIN_DIR.'/local-only.php'),'Brak lokalnej wtyczki testowej','Nie przenoś local/mu-plugins na produkcję.');
$check(function_exists('WC'),'WooCommerce','Aktywuj WooCommerce.');
if(function_exists('WC')) {
 $check(get_option('woocommerce_coming_soon')!=='yes','Sklep widoczny','Wyłącz coming soon dopiero po testach.');
 foreach(array('shop','cart','checkout','myaccount') as $key) {
  $page=get_post(wc_get_page_id($key));
  $check($page && $page->post_status==='publish','Strona WooCommerce: '.$key,'Przypisz opublikowaną stronę w WooCommerce.');
  if($page && in_array($key,array('cart','checkout'),true)) {
   $content=$page->post_content;
   $has_ui=has_shortcode($content,'woocommerce_'.$key) || (has_block('woocommerce/'.$key,$content) && !preg_match('/<!-- wp:woocommerce\/'.$key.'(?:\s+\{[^>]*\})?\s*\/-->/',trim($content)));
   $check($has_ui,'Formularz: '.$key,'Sprawdź zawartość strony i działanie formularza w przeglądarce.');
  }
 }
 $online=[];
 foreach(WC()->payment_gateways()->payment_gateways() as $gateway) {
  if($gateway->enabled==='yes' && !in_array($gateway->id,array('cod','bacs','cheque'),true)) $online[]=$gateway->id;
 }
 $check((bool)$online,'Włączona bramka internetowa','Podłącz docelową bramkę. Same logotypy nie uruchamiają płatności.');
 $copy_file=get_theme_file_path('content/products.json');
 $copy=is_file($copy_file)?json_decode(file_get_contents($copy_file),true):array();
 foreach((array)$copy as $slug=>$data) {
  $post=get_page_by_path($slug,OBJECT,'product');
  $product=$post?wc_get_product($post->ID):false;
  $check($product && $product->get_status()==='publish','Produkt: '.$slug,'Zachowaj istniejący adres produktu.');
  if(!$product) continue;
  $check($product->get_price()!=='' && $product->get_image_id()>0,'Cena i zdjęcie: '.$slug,'Uzupełnij dane produktu.');
  $check(str_contains($product->get_description(),'mevky-product-story'),'Nowy opis: '.$slug,'Uruchom podgląd import-product-copy.php, a potem import opisów.');
  if($product->is_on_sale()) $check($product->get_meta('_mevky_verified_reference_price')!=='','Cena odniesienia: '.$slug,'Zweryfikuj wartość dla bieżącej promocji.');
 }
}
// Configuration checks cannot prove that transactions, email or delivery work.
foreach(array('Płatność, webhook i zwrot','Dostawa i zamówienie od początku do końca','E-maile transakcyjne','Parametry produktów, podatki i treści prawne','Zgodność widocznych logotypów z metodami płatności') as $manual) $rows[]=array('status'=>'RĘCZNIE','check'=>$manual,'action'=>'Potwierdź na docelowej konfiguracji.');
if(defined('WP_CLI') && WP_CLI) {
 WP_CLI\Utils\format_items('table',$rows,array('status','check','action'));
 $pending=count(array_filter($rows,function($row){return $row['status']==='SPRAWDŹ';}));
 if($pending) { WP_CLI::warning($pending.' punktów konfiguracji wymaga sprawdzenia. Nie dokonano zmian.'); WP_CLI::halt(1); }
 WP_CLI::success('Kontrola konfiguracji zakończona. Pozostają testy oznaczone RĘCZNIE. Nie dokonano zmian.');
} else { echo wp_json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE); }
