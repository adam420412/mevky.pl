<?php
/**
 * MEVKY — motyw blokowy (FSE)
 *
 * @package mevky
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MEVKY_VERSION', '0.1.0' );

/**
 * Wsparcie motywu.
 */
function mevky_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );

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
 * Lazy loading: pierwszy obraz na stronie ładujemy zachłannie (LCP),
 * resztę leniwie.
 */
add_filter( 'wp_img_tag_add_loading_optimization_attrs', function ( $attr, $image, $context ) {
	static $first = true;
	if ( $first && ! is_admin() ) {
		$first            = false;
		$attr['loading']  = 'eager';
		$attr['fetchpriority'] = 'high';
	}
	return $attr;
}, 10, 3 );

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

/**
 * Omnibus — najniższa cena z 30 dni.
 *
 * UWAGA: to jest szkielet. Zapisuje historię zmian ceny i wyświetla
 * minimum z ostatnich 30 dni przy produktach w promocji.
 * Przed wdrożeniem produkcyjnym zweryfikować z klientem sposób prezentacji.
 */
function mevky_log_price_change( $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return;
	}

	$history = get_post_meta( $product_id, '_mevky_price_history', true );
	$history = is_array( $history ) ? $history : array();

	$price = (float) $product->get_price();
	$today = gmdate( 'Y-m-d' );

	$history[ $today ] = $price;

	// Trzymamy 40 dni, żeby meta nie puchła.
	$cutoff  = gmdate( 'Y-m-d', strtotime( '-40 days' ) );
	$history = array_filter(
		$history,
		function ( $date ) use ( $cutoff ) {
			return $date >= $cutoff;
		},
		ARRAY_FILTER_USE_KEY
	);

	update_post_meta( $product_id, '_mevky_price_history', $history );
}
add_action( 'woocommerce_update_product', 'mevky_log_price_change' );

function mevky_omnibus_notice() {
	global $product;
	if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
		return;
	}

	$history = get_post_meta( $product->get_id(), '_mevky_price_history', true );
	if ( ! is_array( $history ) || empty( $history ) ) {
		return;
	}

	$cutoff = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
	$window = array_filter(
		$history,
		function ( $date ) use ( $cutoff ) {
			return $date >= $cutoff;
		},
		ARRAY_FILTER_USE_KEY
	);

	if ( empty( $window ) ) {
		return;
	}

	printf(
		'<p class="mevky-omnibus">%s %s</p>',
		esc_html__( 'Najniższa cena z 30 dni przed obniżką:', 'mevky' ),
		wp_kses_post( wc_price( min( $window ) ) )
	);
}
add_action( 'woocommerce_single_product_summary', 'mevky_omnibus_notice', 11 );
