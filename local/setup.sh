#!/bin/sh
# Provisioning lokalnego środowiska MEVKY.
# Uruchamiany wewnątrz kontenera cli: docker compose run --rm cli sh /setup.sh

set -e

URL="http://localhost:8080"

echo "==> Czekam na bazę…"
until wp db check --quiet 2>/dev/null; do sleep 2; done

if wp core is-installed 2>/dev/null; then
	echo "==> WordPress już zainstalowany, pomijam."
else
	echo "==> Instaluję WordPressa…"
	wp core install \
		--url="$URL" \
		--title="MEVKY" \
		--admin_user=admin \
		--admin_password=admin \
		--admin_email=adam@fotz.pl \
		--skip-email
fi

echo "==> Język i strefa czasowa…"
wp language core install pl_PL --activate || true
wp option update timezone_string "Europe/Warsaw"
wp option update date_format "j F Y"

echo "==> Przyjazne linki…"
wp rewrite structure '/%postname%/' --hard
wp rewrite flush --hard

echo "==> WooCommerce…"
wp plugin is-installed woocommerce || wp plugin install woocommerce
wp plugin activate woocommerce

echo "==> Ustawienia sklepu (PL)…"
wp option update woocommerce_store_address "ul. Poznańska 117/11"
wp option update woocommerce_store_city "Kamionki"
wp option update woocommerce_store_postcode "62-023"
wp option update woocommerce_default_country "PL:PL-WP"
wp option update woocommerce_currency "PLN"
wp option update woocommerce_price_decimal_sep ","
wp option update woocommerce_price_thousand_sep " "
wp option update woocommerce_currency_pos "right_space"
wp option update woocommerce_calc_taxes "yes"
wp option update woocommerce_enable_guest_checkout "yes"

echo "==> Motyw MEVKY…"
wp theme activate mevky

echo "==> Strona główna na front-page…"
wp option update show_on_front page
HOME_ID=$( wp post list --post_type=page --name=home --field=ID | head -1 )
if [ -z "$HOME_ID" ]; then
	HOME_ID=$( wp post create --post_type=page --post_title="Strona główna" --post_name=home --post_status=publish --porcelain )
fi
wp option update page_on_front "$HOME_ID"

echo "==> Produkty testowe…"
add_product() {
	NAME="$1"; PRICE="$2"
	EXISTING=$( wp post list --post_type=product --title="$NAME" --field=ID | head -1 )
	if [ -n "$EXISTING" ]; then
		echo "    $NAME — już jest (ID $EXISTING)"
		return
	fi
	ID=$( wp post create --post_type=product --post_title="$NAME" --post_status=publish --porcelain )
	wp post meta update "$ID" _regular_price "$PRICE" >/dev/null
	wp post meta update "$ID" _price "$PRICE" >/dev/null
	wp post meta update "$ID" _manage_stock no >/dev/null
	wp post meta update "$ID" _stock_status instock >/dev/null
	wp post meta update "$ID" _visibility visible >/dev/null
	echo "    $NAME — dodany (ID $ID)"
}

add_product "Lustro Aura 50" 499
add_product "Lustro Crystal 40" 299
add_product "Lustro Crystal 30" 249

echo ""
echo "======================================"
echo " Gotowe."
echo " Sklep:  $URL"
echo " Panel:  $URL/wp-admin  (admin / admin)"
echo "======================================"
