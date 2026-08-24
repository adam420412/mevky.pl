<?php
/**
 * Title: Kolekcja
 * Slug: mevky/kolekcja
 * Categories: mevky
 * Inserter: true
 */
?>
<!-- wp:group {"align":"wide","className":"mevky-reveal","style":{"spacing":{"margin":{"top":"var:preset|spacing|70"}}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignwide mevky-reveal" style="margin-top:var(--wp--preset--spacing--70)"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Kolekcja</h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-collection {"queryId":2,"query":{"perPage":3,"pages":1,"offset":0,"postType":"product","order":"asc","orderBy":"menu_order","inherit":false},"displayLayout":{"type":"flex","columns":3},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
<div class="wp-block-woocommerce-product-collection" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:woocommerce/product-template -->
<!-- wp:group {"className":"mevky-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group mevky-card"><!-- wp:woocommerce/product-image {"imageSizing":"single","className":"mevky-portrait"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"large"} /-->

<!-- wp:woocommerce/product-price /--></div>
<!-- /wp:group -->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group -->
