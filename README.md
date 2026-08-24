# mevky.pl

Sklep internetowy marki MEVKY — lustra do makijażu z podświetleniem LED.

Motyw blokowy (FSE) zbudowany od zera dla WordPressa + WooCommerce.
Bez Elementora, bez motywu rodzica, bez frameworka CSS.

**Klient:** EMDE Norbert Białas
**Wykonawca:** FOTZ Studio Sp. z o.o.
**Umowa:** 1/08/2026
**Termin:** 20–28.08.2026 (7 dni roboczych)

---

## Szybki start

```bash
cd local
make setup
```

Sklep: http://localhost:8080 · Panel: `admin` / `admin`

Pierwszy przebieg trwa 2–3 minuty. Wymaga tylko Dockera.

---

## Struktura

```
.
├── mevky/              motyw blokowy (to jest właściwy produkt)
│   ├── theme.json      design system — kolory, typografia, spacing, layout
│   ├── style.css       tylko to, czego theme.json nie obsługuje
│   ├── functions.php   Woo, preload fontów, Omnibus, checkout
│   ├── templates/      7 szablonów
│   ├── parts/          header, footer
│   ├── patterns/       7 wzorców blokowych
│   └── assets/fonts/   Fraunces + Inter, self-hosted
└── local/              środowisko deweloperskie na Dockerze
    ├── docker-compose.yml
    ├── setup.sh        provisioning: WP + Woo + PL + produkty testowe
    └── Makefile
```

---

## Design system

| Token | Wartość | Zastosowanie |
|---|---|---|
| `base` | `#F7F4EF` | tło strony |
| `contrast` | `#1A1A18` | tekst, przyciski |
| `accent` | `#C9A227` | wyłącznie mikro-detale |
| `muted` | `#8A857C` | tekst drugorzędny |
| `line` | `#E3DDD3` | separatory |

Display: **Fraunces** (variable). Tekst: **Inter** (variable).
Oba self-hosted, latin + latin-ext, preload w `functions.php`.

Layout: treść 680 px, szeroko 1240 px. Zdjęcia w kadrze 4:5.

---

## Status

### Zrobione

- [x] design system w `theme.json`
- [x] szablony: front-page, single-product, archive-product, page, page-wide, index, 404
- [x] 7 wzorców blokowych
- [x] fonty self-hosted z `unicodeRange` (polskie znaki działają)
- [x] checkout odchudzony o zbędne pola
- [x] szkielet Omnibus
- [x] dane strukturalne Organization
- [x] akordeony bez JS
- [x] środowisko lokalne na Dockerze
- [x] test na WP 7.1 + Woo 11.0.1 — wszystkie ścieżki 200, zero błędów PHP

### Do zrobienia

- [ ] treści — miejsca oznaczone `[DO UZUPEŁNIENIA]`
- [ ] zdjęcia: hero (`patterns/hero.php`, atrybut `url`) i aranżacyjne (`historia.php`)
- [ ] menu — utworzyć i przypiąć w nagłówku i stopce
- [ ] ikona koszyka znika na mobile — podejrzenie zawijania flexa w `parts/header.html`
- [ ] Przelewy24 — wtyczka + konfiguracja na koncie klienta
- [ ] InPost + geowidget paczkomatów
- [ ] baner cookies z kategoriami i blokowaniem skryptów przed zgodą
- [ ] GA4 / GTM / Meta Pixel + CAPI, zdarzenia e-commerce
- [ ] mapa przekierowań 301 ze starych URL-i — **przed** przełączeniem
- [ ] pomiar Lighthouse na produkcji po wgraniu zdjęć

---

## Decyzje techniczne

**Dlaczego WooCommerce, a nie custom w Next.js.**
Przy trzech SKU wąskim gardłem nie jest silnik, tylko Elementor. Woo ma
gotowy checkout, panel zamówień, obsługę zwrotów i integracje płatnicze —
odtworzenie tego od zera to trzy tygodnie, nie siedem dni. Custom miałby
sens przy setkach produktów albo nietypowej konfiguracji.

**Dlaczego nie ma animacji przy scrollu.**
Pierwsza wersja używała `animation-timeline: view()`. Przy wysokim oknie
albo krótkiej stronie zakres `entry` nigdy nie postępuje i sekcja zostaje
na `opacity: 0`. Na renderze strony głównej nagłówek „Kolekcja" i trzy
produkty pod nim były niewidoczne na stałe. Efekt wycięty. Klasa
`.mevky-reveal` została jako hook — powrót tylko na IntersectionObserver
z domyślnym stanem widocznym.

**Uwaga o Omnibus.**
`functions.php` loguje cenę przy każdym zapisie produktu i pokazuje
minimum z 30 dni. Historia zbiera się dopiero od wdrożenia — sprzed
migracji nie ma. Jeśli klient planuje promocję w pierwszym miesiącu,
trzeba uzupełnić `_mevky_price_history` ręcznie.

---

## Wymagania

WordPress 6.5+ · PHP 8.1+ · WooCommerce
