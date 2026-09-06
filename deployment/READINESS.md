# MEVKY 0.3.1 — przekazanie do wdrożenia

Pełny pakiet przekazania: `release/MEVKY-0.3.1-wdrozenie.zip`. Rozpakuj go; w panelu WordPress instaluj tylko wewnętrzny `MEVKY-0.3.1.zip`. Katalog `deployment` zawiera instrukcję, kontrolę konfiguracji i importer opisów.

Najprostsza instalacja bez SSH: po aktywacji motywu wgraj jako wtyczkę
`MEVKY-pomocnik-wdrozenia-1.0.0.zip`. Otwórz **Narzędzia → Wdrożenie MEVKY**,
sprawdź listę trzech produktów i kliknij import opisów. Po kontroli strony pomocnik
można wyłączyć i usunąć. Import zachowuje kopię poprzednich opisów i nie zmienia
cen, stanów, zdjęć, płatności ani zamówień.

## Stan

Gotowy motyw i lokalny sklep demonstracyjny. Domena mevky.pl nie została zmieniona.
Paczka `release/MEVKY-0.3.1.zip` zawiera wyłącznie motyw, lokalne fonty,
obrazy, treści i szablony. Nie zawiera WordPressa, bazy, wtyczek testowych,
haseł, kont klientów ani konfiguracji płatniczej.

## Wydanie 0.3.1

- Poprawne proporcje BLIK, Visa, Mastercard i Przelewy24 w stopce oraz przy zakupie.
- Logotypy są lokalnymi plikami z dotychczasowej strony, bez pobierania zewnętrznych zasobów przez klienta.
- Widoczność każdego znaku: WooCommerce → Ustawienia → Ogólne → MEVKY — logotypy płatności. Dopasuj do metod rzeczywiście dostępnych w bramce.
- Kontrola konfiguracji bez zmian w bazie: `wp eval-file deployment/preflight.php`. Punkty RĘCZNIE wymagają testów, nawet gdy konfiguracja przejdzie kontrolę.
- Odtworzenie ZIP: `python3 deployment/build-release.py`. Obok ZIP znajduje się suma SHA-256.

## Wykonane

- Strona główna, kolekcja, trzy strony produktów, kontakt, koszyk, checkout i 404.
- Menu i koszyk na telefonie, dostępność klawiaturą, focus i reduced motion.
- Edytowalne opisy WooCommerce, dwie dodatkowe wizualizacje aranżacyjne.
- Lekkie WebP zamiast PNG w opisach (około 4,2 MB → 237 KB łącznie).
- Opisy meta, canonical sklepu, Open Graph bez generowania dodatkowego obrazu.
- Natywne dane Product/Offer WooCommerce również w nowym szablonie FSE.
- Noindex lokalnie, na stagingu, w koszyku, checkout i koncie.
- Usunięty błędny automatyczny odczyt historycznej ceny. Pole ręcznie
  zweryfikowanej ceny przed promocją, komunikat w panelu przy brakującej wartości.

## Rzeczy wymagające danych lub konfiguracji przed sprzedażą

1. **Płatności:** podłączyć konto Przelewy24 i wykonać płatność testową, zwrot
   oraz sprawdzić zmianę statusu przez webhook. Lokalny COD jest tylko atrapą
   opisaną jako zamówienie testowe; nie przenosić jego ustawień.
2. **Wysyłka:** skonfigurować rzeczywiste konto i usługi przewoźnika/InPost,
   wybór paczkomatu, etykietę, gabaryty oraz potwierdzić deklarowane 48 h
   i zakres darmowej dostawy. Lokalna darmowa strefa jest demonstracyjna.
3. **Dane towaru i podatki:** potwierdzić wymiary Crystal 30 (źródło zawierało
   sprzeczne 30/40 cm), uzupełnić parametry Crystal 40, rzeczywiste stany,
   SKU oraz właściwe ustawienia podatkowe. Lokalnie podatki nie są naliczane.
4. **Treści sklepu:** zachować istniejący regulamin i politykę prywatności,
   dopasować je do faktycznych integracji. Zweryfikować informacje produktowe,
   podmiot odpowiedzialny, instrukcje i informacje bezpieczeństwa.
5. **Promocje:** przed promocją zaimportować/zweryfikować jej cenę odniesienia
   lub podłączyć docelowy system historii cen. Motyw nie odtwarza historii.
6. **E-mail i zgody:** sprawdzić wysyłkę zamówień przez właściwą domenę;
   zachować/skonfigurować mechanizm zgód przed uruchomieniem analityki i reklam.
   W motywie nie dodano GA4, GTM ani Meta Pixel.
7. **Hosting:** staging, kopia bazy i plików, HTTPS, PHP/Woo zgodne z hostingiem,
   cache z wykluczeniem koszyka, checkout, konta i API. Zweryfikować zaplanowane
   zadania WooCommerce i kopie zapasowe. Finalny pomiar wydajności na hostingu.

## Sposób wdrożenia

1. Zrobić kopię obecnej strony. Wgrać ZIP na staging i aktywować MEVKY.
2. Zachować istniejące produkty, zamówienia, media, strony prawne i integracje.
   Nie importować lokalnej bazy ani `local/mu-plugins` na produkcję.
3. Sprawdzić podgląd importu opisów:
   `wp eval-file deployment/import-product-copy.php`
4. Po sprawdzeniu zgodności produktów zastosować opisy:
   `wp eval-file deployment/import-product-copy.php apply`
   Skrypt nie zmienia cen, stanów ani zdjęć galerii; zapisuje kopię poprzednich opisów.
5. Usunąć ewentualne zapisane w bazie stare wersje szablonów MEVKY tylko po
   sprawdzeniu, czy nie zawierają potrzebnych zmian — mają pierwszeństwo nad plikami.
6. Zweryfikować wszystkie ścieżki klienta na stagingu i punkty powyżej.
7. Zachować istniejące adresy `/produkt/lustro-aura/`, `/produkt/lustro-crystal-40/`,
   `/produkt/lustro-crystal-30/`, `/kontakt-i-dane-firmy/`, `/koszyk/`.
   Checkout ma działać pod adresem przypisanym w WooCommerce. Motyw nie wymaga
   zmiany obecnych adresów prawnych. Przekierowania dodawać tylko dla faktycznie
   zmienionych adresów; nie kierować wszystkich starych URL na stronę główną.
8. Po finalnym przełączeniu upewnić się, że środowisko jest `production`, opcja
   widoczności WP pozwala na indeksowanie i WooCommerce nie jest w trybie coming soon.
   Sprawdzić mapę witryny, canonical, ceny i dane Product w odpowiedzi HTML.

## Rollback

Przywrócić poprzedni motyw i — jeśli były zmiany konfiguracji — kopię bazy.
Poprzednie opisy są dodatkowo dostępne w `_mevky_copy_backup_v1` każdego produktu.
Nie przywracać starej pełnej bazy ponad nowe zamówienia złożone po przełączeniu.
