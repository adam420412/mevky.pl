<?php
/**
 * Plugin Name: MEVKY — pomocnik wdrożenia
 * Description: Bezpieczny import przygotowanych opisów produktów z motywu MEVKY.
 * Version: 1.0.0
 * Author: MEVKY
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mevky_deployment_copy_file() {
	return get_theme_file_path( 'content/products.json' );
}

function mevky_deployment_items() {
	$file = mevky_deployment_copy_file();
	if ( ! is_file( $file ) ) {
		return new WP_Error( 'missing_copy', 'Aktywuj najpierw motyw MEVKY. Nie znaleziono pliku opisów.' );
	}
	try {
		$copy = json_decode( file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
	} catch ( Throwable $error ) {
		return new WP_Error( 'invalid_copy', 'Plik opisów jest nieprawidłowy.' );
	}
	$items   = array();
	$missing = array();
	foreach ( $copy as $slug => $data ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( ! $post ) {
			$missing[] = $slug;
			continue;
		}
		$items[] = array( 'product' => wc_get_product( $post->ID ), 'data' => $data );
	}
	if ( $missing ) {
		return new WP_Error( 'missing_products', 'Brakuje produktów: ' . implode( ', ', $missing ) . '. Nie wykonano zmian.' );
	}
	return $items;
}

add_action( 'admin_menu', function () {
	add_management_page( 'Wdrożenie MEVKY', 'Wdrożenie MEVKY', 'manage_woocommerce', 'mevky-deployment', 'mevky_deployment_page' );
} );

add_action( 'admin_post_mevky_import_product_copy', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Brak uprawnień.' ); }
	check_admin_referer( 'mevky_import_product_copy' );
	$items = mevky_deployment_items();
	if ( is_wp_error( $items ) ) {
		wp_safe_redirect( add_query_arg( 'mevky_error', rawurlencode( $items->get_error_message() ), admin_url( 'tools.php?page=mevky-deployment' ) ) );
		exit;
	}
	foreach ( $items as $item ) {
		$product = $item['product'];
		$data    = $item['data'];
		if ( ! $product->meta_exists( '_mevky_copy_backup_v1' ) ) {
			$product->update_meta_data( '_mevky_copy_backup_v1', array(
				'description'       => $product->get_description(),
				'short_description' => $product->get_short_description(),
			) );
		}
		$product->set_description( wp_kses_post( $data['long_description'] ) );
		$product->set_short_description( wpautop( esc_html( $data['description'] ) ) );
		$product->save();
	}
	wp_safe_redirect( add_query_arg( 'mevky_imported', count( $items ), admin_url( 'tools.php?page=mevky-deployment' ) ) );
	exit;
} );

function mevky_deployment_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
	$items = function_exists( 'wc_get_product' ) ? mevky_deployment_items() : new WP_Error( 'woocommerce', 'WooCommerce nie jest aktywny.' );
	?>
	<div class="wrap">
		<h1>Wdrożenie MEVKY</h1>
		<?php if ( isset( $_GET['mevky_imported'] ) ) : ?>
			<div class="notice notice-success"><p>Opisy produktów zostały zapisane. Poprzednie wersje zachowano w kopii produktu.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['mevky_error'] ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( wp_unslash( $_GET['mevky_error'] ) ); ?></p></div>
		<?php endif; ?>
		<p>Pomocnik zmienia wyłącznie krótki i pełny opis trzech produktów. Nie zmienia cen, stanów, zdjęć, zamówień ani konfiguracji płatności.</p>
		<?php if ( is_wp_error( $items ) ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $items->get_error_message() ); ?></p></div>
		<?php else : ?>
			<table class="widefat striped"><thead><tr><th>Produkt</th><th>Adres</th><th>Kopia poprzedniego opisu</th></tr></thead><tbody>
			<?php foreach ( $items as $item ) : $product = $item['product']; ?>
				<tr><td><?php echo esc_html( $product->get_name() ); ?></td><td><code><?php echo esc_html( $product->get_slug() ); ?></code></td><td><?php echo $product->meta_exists( '_mevky_copy_backup_v1' ) ? 'już istnieje' : 'zostanie utworzona'; ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:20px">
				<input type="hidden" name="action" value="mevky_import_product_copy">
				<?php wp_nonce_field( 'mevky_import_product_copy' ); submit_button( 'Wgraj przygotowane opisy produktów' ); ?>
			</form>
		<?php endif; ?>
		<p>Po sprawdzeniu strony pomocnik można wyłączyć i usunąć.</p>
	</div>
	<?php
}
