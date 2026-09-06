# MEVKY — lokalny sklep

WordPress 7.1, PHP 8.3 i WooCommerce, uruchamiane przez WordPress Playground.
Wymagany Node.js 20.18+; Docker nie jest potrzebny.

```bash
cd local
npm ci
npm start
```

- Sklep: http://localhost:8080
- Panel: http://localhost:8080/wp-admin/
- Użytkownik: `admin`, hasło lokalne: `mevky-local`
- Zatrzymanie: Ctrl+C w terminalu serwera.

Motyw jest montowany bezpośrednio z `../mevky`. Po zmianie pliku odśwież stronę.
WordPress, produkty, media i baza SQLite pozostają w `.runtime/` także po
zatrzymaniu procesu. Archiwa wejściowe nie są zmieniane.

## Dane i płatności

Produkty, ceny i zdjęcia pochodzą z publicznego API mevky.pl, stan 5.09.2026.
Źródła zdjęć zapisano w `data/asset-sources.json`. Przygotowanie bazy jest
wersjonowane i nie powiela produktów ani zdjęć przy kolejnym uruchomieniu.

Lokalna wtyczka `mu-plugins/local-only.php` blokuje wysyłanie e-maili i udostępnia
wyłącznie metodę opisaną jako zamówienie testowe. Nie ma integracji płatniczej;
nie są pobierane pieniądze ani realizowane wysyłki. Tej wtyczki nie należy
przenosić do sklepu produkcyjnego. Regulamin i polityka prywatności w stopce
prowadzą do istniejącej strony mevky.pl.

## Sprawdzenie

Przy uruchomieniu `validate.php` sprawdza składnię PHP, dane produktów,
rejestrację bloków WooCommerce, galerie, wybór modelu i brak placeholderów.
Wynik: `.runtime/validation.json`.

Przy działającym serwerze:

```bash
npm run check
```

Sprawdza odpowiedzi HTTP, zdjęcia, dodawanie do oddzielnego koszyka,
zmianę ilości, darmową dostawę i usuwanie produktu. Nie składa zamówień.
Wynik: `.runtime/http-validation.json`.

## Docker

Pliki Docker z archiwum zachowano jako alternatywę, ale główną, sprawdzoną
ścieżką lokalnego uruchomienia jest obecnie `npm start`. Dawny `make setup`
używa uproszczonych danych testowych i nie zastępuje powyższej konfiguracji.
