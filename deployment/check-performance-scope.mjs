import {PHP} from '../local/node_modules/@php-wasm/universal/index.js';
import {loadNodeRuntime} from '../local/node_modules/@php-wasm/node/index.js';
import {readFile} from 'node:fs/promises';
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:1}}));
php.writeFile('/performance.php',await readFile('mevky/inc/performance.php'));
const result=await php.run({code:`<?php
 define('ABSPATH','/');
 function add_action(...$args){} function add_filter(...$args){}
 function is_admin(){return $GLOBALS['context']==='admin';}
 function is_cart(){return $GLOBALS['context']==='cart';}
 function is_checkout(){return $GLOBALS['context']==='checkout';}
 function is_account_page(){return $GLOBALS['context']==='account';}
 function wp_dequeue_script($handle){$GLOBALS['removed'][]=$handle;}
 require '/performance.php';
 foreach(['admin','cart','checkout','account','product','home'] as $context){
  $GLOBALS['context']=$context; $GLOBALS['removed']=[]; mevky_checkout_assets_scope();
  $expected=in_array($context,['product','home'])?['p24-block-checkout','p24-online-payments']:[];
  if($GLOBALS['removed']!==$expected){throw new Exception('FAIL '.$context);}
 }
 echo 'PASS: checkout assets preserved on cart, checkout, account and admin; omitted only on storefront pages';
`});if (!result.text.startsWith('PASS:') || result.errors) throw new Error(result.text+result.errors);
console.log(result.text);php.exit();
