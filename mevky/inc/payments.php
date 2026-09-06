<?php
/** Payment brand artwork. Gateway configuration remains with WooCommerce. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mevky_payment_brands() {
 return array(
  'blik' => array('name'=>'BLIK','width'=>169,'height'=>85),
  'visa' => array('name'=>'Visa','width'=>207,'height'=>68),
  'mastercard' => array('name'=>'Mastercard','width'=>127,'height'=>79),
  'przelewy24' => array('name'=>'Przelewy24','width'=>1000,'height'=>350),
 );
}

add_filter('woocommerce_get_settings_general',function($settings){
 $settings[]=array('title'=>'MEVKY — logotypy płatności','type'=>'title','id'=>'mevky_payment_artwork','desc'=>'Wybierz znaki widoczne w stopce i na karcie produktu. Zostaw tylko metody obsługiwane przez sklep. Ta sekcja nie uruchamia płatności — integracje skonfiguruj w zakładce Płatności.');
 foreach(mevky_payment_brands() as $key=>$brand) {
  $settings[]=array('title'=>$brand['name'],'id'=>'mevky_payment_logo_'.$key,'type'=>'checkbox','default'=>'yes','desc'=>'Pokaż logotyp '.$brand['name']);
 }
 $settings[]=array('type'=>'sectionend','id'=>'mevky_payment_artwork');
 return $settings;
});

add_shortcode('mevky_payment_logos',function($atts){
 $atts=shortcode_atts(array('compact'=>'no'),$atts);
 $items='';
 foreach(mevky_payment_brands() as $key=>$brand) {
  if(get_option('mevky_payment_logo_'.$key,'yes')!=='yes') continue;
  $items.=sprintf('<li class="mevky-payment-brand mevky-payment-brand--%s"><img src="%s" alt="%s" width="%d" height="%d" loading="lazy" decoding="async"></li>',esc_attr($key),esc_url(get_theme_file_uri('assets/images/payments/'.$key.'.png')),esc_attr($brand['name']),$brand['width'],$brand['height']);
 }
 if(!$items) return '';
 $compact=$atts['compact']==='yes';
 return '<div class="mevky-payments'.($compact?' mevky-payments--compact':' alignwide').'"><p class="mevky-payments__label">Płatności</p><ul class="mevky-payments__brands" aria-label="Metody płatności">'.$items.'</ul></div>';
});
