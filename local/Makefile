.PHONY: up setup down reset logs shell

up:            ## Podnosi kontenery
	docker compose up -d

setup: up      ## Podnosi i provisionuje (WP + Woo + motyw + produkty)
	docker compose run --rm cli sh /setup.sh

down:          ## Zatrzymuje kontenery
	docker compose down

reset:         ## Kasuje wszystko razem z bazą i stawia od nowa
	docker compose down -v
	$(MAKE) setup

logs:          ## Podgląd logów WordPressa
	docker compose logs -f wordpress

shell:         ## Powłoka WP-CLI
	docker compose run --rm cli bash
