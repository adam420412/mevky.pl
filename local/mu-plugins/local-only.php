<?php
/** Local preview: never send email or process a real payment. */
add_filter('pre_wp_mail','__return_true');
add_filter('woocommerce_available_payment_gateways',function($gateways){ return isset($gateways['cod']) ? array('cod'=>$gateways['cod']) : array(); },99);
add_filter('woocommerce_admin_disabled','__return_true');
add_filter('woocommerce_allow_tracking','__return_false');
add_filter('woocommerce_order_button_text',function(){ return 'Złóż zamówienie testowe'; });
