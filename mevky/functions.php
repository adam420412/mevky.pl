<?php
/**
 * MEVKY — motyw blokowy (FSE)
 *
 * @package mevky
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MEVKY_VERSION', '0.3.2' );

/**
 * Wsparcie motywu.
 */
function mevky_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'style.css', 'assets/css/editor.css', 'assets/css/storefront.css' ) );

	// WooCommerce.
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	load_theme_textdomain( 'mevky', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'mevky_setup' );

/**
 * Style motywu.
 */
function mevky_assets() {
	wp_enqueue_style(
		'mevky-style',
		get_stylesheet_uri(),
		array(),
		MEVKY_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'mevky_assets' );

/**
 * Preload fontu display — realny wpływ na LCP.
 */
function mevky_preload_fonts() {
	$fonts = array(
		'/assets/fonts/fraunces-latin.woff2',
		'/assets/fonts/inter-latin.woff2',
	);
	foreach ( $fonts as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_template_directory_uri() . $font )
		);
	}
}
add_action( 'wp_head', 'mevky_preload_fonts', 1 );

/**
 * Odchudzenie frontu — usuwamy to, czego sklep z trzema produktami nie potrzebuje.
 */
function mevky_dequeue_bloat() {
	// Emoji.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );

	// Globalne style bloków ładowane inline zamiast osobnych plików.
	wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', 'mevky_dequeue_bloat', 20 );

/**
 * Ładowanie CSS bloków tylko tam, gdzie blok faktycznie występuje.
 */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

/**
 * Wzorce — własna kategoria.
 */
function mevky_register_pattern_category() {
	register_block_pattern_category(
		'mevky',
		array( 'label' => __( 'MEVKY', 'mevky' ) )
	);
}
add_action( 'init', 'mevky_register_pattern_category' );

/**
 * WooCommerce — checkout bez zbędnych pól.
 * Marka sprzedaje 3 produkty do Polski; każde pole to spadek konwersji.
 */
function mevky_simplify_checkout_fields( $fields ) {
	unset( $fields['billing']['billing_address_2'] );
	unset( $fields['shipping']['shipping_address_2'] );

	if ( isset( $fields['billing']['billing_company'] ) ) {
		$fields['billing']['billing_company']['required'] = false;
	}
	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['placeholder'] = __( 'Uwagi do zamówienia (opcjonalnie)', 'mevky' );
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'mevky_simplify_checkout_fields' );

/**
 * Dane strukturalne Organization.
 * Product obsługuje WooCommerce natywnie — nie dublujemy.
 */
function mevky_organization_schema() {
	if ( ! is_front_page() ) {
		return;
	}

	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Organization',
		'name'     => get_bloginfo( 'name' ),
		'url'      => home_url( '/' ),
	);

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}
add_action( 'wp_head', 'mevky_organization_schema' );

require_once get_theme_file_path( 'inc/storefront.php' );
add_action( 'wp_enqueue_scripts', function () {
 wp_enqueue_style( 'mevky-storefront', get_theme_file_uri( 'assets/css/storefront.css' ), array('mevky-style'), (string) filemtime( get_theme_file_path('assets/css/storefront.css') ) );
 if ( function_exists('is_product') && is_product() ) {
  wp_enqueue_script( 'mevky-storefront', get_theme_file_uri( 'assets/js/storefront.js' ), array(), MEVKY_VERSION, array('strategy'=>'defer','in_footer'=>true) );
 }
} );

require_once get_theme_file_path( 'inc/publication.php' );
require_once get_theme_file_path( 'inc/payments.php' );
