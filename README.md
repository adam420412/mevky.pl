# MEVKY — sklep WordPress + WooCommerce

Autorski motyw blokowy FSE, bez Elementora i motywu rodzica.
Przebudowa lokalna na podstawie archiwum projektu oraz mevky.pl.

## Uruchomienie

```bash
cd local
npm ci
npm start
```

Sklep: **http://localhost:8080** · Panel: **http://localhost:8080/wp-admin/**

Lokalny login `admin`, hasło `mevky-local`. Dane pozostają na dysku.
Szczegóły: [local/README.md](local/README.md).

## Wersja 0.3 — przygotowanie do wdrożenia

- Fotograficzny hero, duża typografia Fraunces, oliwkowe akcenty i wyraźny rytm sekcji.
- Autorskie karty kolekcji: zdjęcie główne i drugi kadr, opis modelu, aktualna cena,
  numeracja oraz czytelne przejście do szczegółów.
- Przebudowana strona produktu: galeria z miniaturami, powiększenie w natywnym
  dialogu, wybór modelu, panel zakupowy i informacje o dostawie.
- Koszyk i formularz zakupowy pozostają natywnymi funkcjami WooCommerce.
- Menu z prawdziwymi odnośnikami i widocznym koszykiem na telefonie.
- Uzupełnione fotografie, historia marki, FAQ i dane kontaktowe.
- Lokalne fonty i obrazy, obsługa klawiatury, reduced motion i układy mobilne.

## Struktura

- `mevky/theme.json` — tokeny i ustawienia edytora.
- `mevky/assets/css/storefront.css` — nowy system wizualny sklepu.
- `mevky/inc/storefront.php` — prezentacja danych WooCommerce; shortcode'y użyte w szablonach FSE.
- `mevky/assets/js/storefront.js` — miniatury i powiększanie zdjęć.
- `mevky/templates/`, `parts/`, `patterns/` — szablony, nagłówek, stopka, sekcje.
- `local/` — odtwarzalne środowisko i weryfikacja.

## Dane źródłowe

Publiczna oferta mevky.pl z 5.09.2026: Aura 50 — 399 zł, Crystal 40 — 299 zł,
Crystal 30 — 249 zł. Zdjęcia zapisane lokalnie wraz z manifestem źródeł.
Aktualne dane firmy zastępują nieaktualny adres z początkowego archiwum.
W opisie Crystal 30 na stronie źródłowej występują sprzeczne wymiary (30 i 40 cm).
Pominięto ten fragment specyfikacji w danych lokalnych; wymiary wymagają
potwierdzenia przed publikacją. Nazwy modeli zachowano.

## Przed wdrożeniem produkcyjnym

Ta wersja działa lokalnie. Realne płatności, InPost, zgody analityczne i piksele
nie zostały skonfigurowane. Lokalna metoda testowa i blokada e-maili są
oddzielone od motywu. Dokumenty prawne prowadzą do istniejącej domeny.
Przed migracją pozostają integracje sklepu, przekierowania i sprawdzenie
polityki prezentowania promocji. Błędny automatyczny odczyt historii cen został
zastąpiony polem zweryfikowanej ceny przed promocją.

Paczka motywu: `release/MEVKY-0.3.1.zip`. Dla instalacji bez SSH dostępna jest
również wtyczka `release/MEVKY-pomocnik-wdrozenia-1.0.0.zip`: po aktywacji motywu
otwórz **Narzędzia → Wdrożenie MEVKY** i zastosuj przygotowane opisy. Dokładny stan i instrukcja wdrożenia:
[deployment/READINESS.md](deployment/READINESS.md). Import opisów na istniejącej
stronie: `deployment/import-product-copy.php` (domyślnie tylko podgląd).

## Weryfikacja

`local/validate.php` — PHP, konfiguracja motywu i renderowanie elementów produktu.
`npm run check` w `local/` — trasy HTTP, zasoby i operacje na koszyku.
Zweryfikowano w przeglądarce wejście z karty produktu, przełączanie zdjęć, dodanie do koszyka i otwarcie formularza zamówienia. Testy HTTP dodatkowo wykrywają stronę coming soon, surowe shortcode’y i pustą zawartość koszyka.
