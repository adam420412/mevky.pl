<?php
/** Frontend optimizations. Cart and checkout remain native WooCommerce. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Render before the mini-cart block enqueues its drawer and React dependencies.
add_filter( 'pre_render_block', function ( $content, $block ) {
 if ( is_admin() || 'woocommerce/mini-cart' !== ( $block['blockName'] ?? '' ) || ! function_exists( 'wc_get_cart_url' ) ) { return $content; }
 return '<a class="mevky-cart-link" href="' . esc_url( wc_get_cart_url() ) . '" aria-label="Przejdź do koszyka">' . mevky_icon( 'bag' ) . '</a>';
}, 10, 2 );

// Only replace theme-owned editorial images; uploaded product media remain dynamic.
function mevky_fast_theme_images( $html ) {
 if ( is_admin() || ! class_exists( 'WP_HTML_Tag_Processor' ) ) { return $html; }
 $tags = new WP_HTML_Tag_Processor( $html );
 $base = get_theme_file_uri( 'assets/images/' );
 $names = array( 'hero.webp', 'historia-1.jpg', 'historia-2.jpg', 'aura-ritual-editorial.webp', 'crystal-ritual-editorial.webp' );
 while ( $tags->next_tag( 'IMG' ) ) {
  $src = $tags->get_attribute( 'src' );
  if ( ! is_string( $src ) || ! str_starts_with( $src, $base ) ) { continue; }
  $name = substr( $src, strlen( $base ) );
  if ( ! in_array( $name, $names, true ) ) { continue; }
  $stem = pathinfo( $name, PATHINFO_FILENAME );
  $variants = array();
  foreach ( array( 480, 800, 1280 ) as $width ) { $file = 'assets/performance/' . $stem . '-' . $width . '.webp'; $dimensions = wp_getimagesize( get_theme_file_path( $file ) ); if ( $dimensions ) { $variants[ $dimensions[0] ] = get_theme_file_uri( $file ) . ' ' . $dimensions[0] . 'w'; } }
  $tags->set_attribute( 'src', get_theme_file_uri( 'assets/performance/' . $stem . '-1280.webp' ) );
  $tags->set_attribute( 'srcset', implode( ', ', $variants ) );
  $tags->set_attribute( 'sizes', 'hero.webp' === $name ? '(max-width: 781px) 100vw, 57vw' : '(max-width: 781px) 100vw, 50vw' );
  $tags->set_attribute( 'decoding', 'async' );
 }
 return $tags->get_updated_html();
}
add_filter( 'render_block', 'mevky_fast_theme_images', 20 );
add_filter( 'the_content', 'mevky_fast_theme_images', 20 );

// The theme renders its own image-only product gallery. The native gallery's
// video player is unused here; keep it if product content embeds a video.
add_action( 'wp_enqueue_scripts', function () {
 if ( ! function_exists( 'is_product' ) || ! is_product() ) { return; }
 $post = get_post();
 if ( $post && preg_match( '/<video|\[video|wp:video|youtube|vimeo/i', $post->post_content ) ) { return; }
 foreach ( array( 'scripts', 'styles' ) as $type ) {
  $registry = 'scripts' === $type ? wp_scripts() : wp_styles();
  foreach ( $registry->queue as $handle ) {
   $src = $registry->registered[ $handle ]->src ?? '';
   if ( is_string( $src ) && str_contains( $src, '/video-wc-gallery/' ) ) {
    if ( 'scripts' === $type ) { wp_dequeue_script( $handle ); } else { wp_dequeue_style( $handle ); }
   }
  }
 }
}, 100 );

// P24's block checkout registration is queued globally by the gateway. It is
// needed in the native cart/checkout, not on catalog or informational pages.
function mevky_checkout_assets_scope() {
 if ( is_admin() || ! function_exists( 'is_cart' ) || is_cart() || is_checkout() || is_account_page() ) { return; }
 wp_dequeue_script( 'p24-block-checkout' );
 wp_dequeue_script( 'p24-online-payments' );
}
add_action( 'wp_enqueue_scripts', 'mevky_checkout_assets_scope', 999 );
add_action( 'wp_print_footer_scripts', 'mevky_checkout_assets_scope', 1 );

// Serve the gateway-independent Meta helper locally to avoid an external CDN
// redirect on the rendering path. Keep its existing consent and initialization.
add_action( 'wp_print_footer_scripts', function () {
 $scripts = wp_scripts();
 $handle = 'facebook-capi-param-builder';
 if ( isset( $scripts->registered[ $handle ] ) && '3.7.6' === (string) $scripts->registered[ $handle ]->ver ) {
  $scripts->registered[ $handle ]->src = get_theme_file_uri( 'assets/vendor/clientParamBuilder-1.3.2.js' );
 }
}, 1 );

// Exact, content-verified derivatives of existing media. If the customer
// replaces a source file, fall back immediately to WordPress image handling.
add_filter( 'wp_get_attachment_image_attributes', function ( $attr ) {
 $uploads = wp_get_upload_dir();
 $prefix = trailingslashit( $uploads['baseurl'] );
 if ( empty( $attr['src'] ) || ! str_starts_with( $attr['src'], $prefix ) ) { return $attr; }
 $relative = substr( $attr['src'], strlen( $prefix ) );
 $map = array(
  '2026/09/lustro-crystal-40-1-768x1024.jpg' => array( 'c5e84fe2766a9792a25527b289db93118ba8621804a0c3530fc5028065026e8e', 'crystal40', 600 ),
  '2026/09/lustro-crystal-30-0.png' => array( '3fe1b257848a0285ab8d07041f0e95c4e681f18ca5d1e151e7f68dbe8885e2df', 'crystal30', 622 ),
 );
 if ( ! isset( $map[ $relative ] ) ) { return $attr; }
 $item = $map[ $relative ];
 static $verified = array();
 if ( ! isset( $verified[ $relative ] ) ) {
  $file = trailingslashit( $uploads['basedir'] ) . $relative;
  $verified[ $relative ] = is_readable( $file ) && hash_equals( $item[0], hash_file( 'sha256', $file ) );
 }
 if ( ! $verified[ $relative ] ) { return $attr; }
 $base = get_theme_file_uri( 'assets/performance/' . $item[1] . '-product-' );
 $attr['src'] = $base . $item[2] . '.webp';
 $attr['srcset'] = $base . '400.webp 400w, ' . $base . $item[2] . '.webp ' . $item[2] . 'w';
 return $attr;
}, 30 );

// Reviews are below the product summary. Load their isolated stylesheet
// without holding up the first product image; retain a no-JavaScript fallback.
add_filter( 'style_loader_tag', function ( $html, $handle ) {
 if ( is_admin() || 'cr-frontend-css' !== $handle || ! function_exists( 'is_product' ) || ! is_product() ) { return $html; }
 return str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $html ) . '<noscript>' . $html . '</noscript>';
}, 10, 2 );

// These two small, URL-free theme sheets define the first viewport. Inline
// them in their original cascade position to avoid two blocking requests.
add_filter( 'style_loader_tag', function ( $html, $handle ) {
 if ( is_admin() ) { return $html; }
 $files = array( 'mevky-style' => 'style.css', 'mevky-storefront' => 'assets/css/storefront.css' );
 if ( ! isset( $files[ $handle ] ) ) { return $html; }
 $css = file_get_contents( get_theme_file_path( $files[ $handle ] ) );
 if ( ! $css || preg_match( '/url\s*\(|@import|<\/style/i', $css ) ) { return $html; }
 return '<style id="' . esc_attr( $handle ) . '-inline">' . $css . '</style>';
}, 20, 2 );
