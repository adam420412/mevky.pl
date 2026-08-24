# MEVKY — środowisko lokalne

Kompletny sklep na localhost: WordPress + WooCommerce + motyw MEVKY,
z polskimi ustawieniami i trzema produktami testowymi.

## Wymagania

Docker Desktop. Nic więcej.

## Uruchomienie

```bash
cd local
make setup
```

Pierwszy przebieg trwa 2–3 minuty (pobieranie obrazów i WooCommerce).

- Sklep: http://localhost:8080
- Panel: http://localhost:8080/wp-admin — `admin` / `admin`

## Codzienna praca

```bash
make up      # podnieś
make down    # zatrzymaj
make reset   # wyczyść bazę i postaw od zera
make logs    # podgląd logów
make shell   # powłoka WP-CLI
```

Katalog `../mevky` jest zamontowany jako motyw. Edytujesz plik na dysku,
odświeżasz przeglądarkę — zmiana jest od razu. Nie trzeba nic przeładowywać.

## Uwaga o theme.json

WordPress cache'uje `theme.json` w trybie produkcyjnym. `WORDPRESS_DEBUG: 1`
w compose wyłącza ten cache, więc zmiany w tokenach widać natychmiast.
Gdyby coś się zacięło:

```bash
docker compose run --rm cli wp cache flush
```

## Bez Dockera

Jeśli wolisz LocalWP albo XAMPP: spakuj katalog `mevky/` do ZIP-a i wgraj
przez Wygląd → Motywy → Dodaj nowy → Wyślij motyw. Potem ręcznie:
WooCommerce, waluta PLN, przyjazne linki `/%postname%/`, strona główna
ustawiona na statyczną.
