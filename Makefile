DC  = docker compose
APP = $(DC) exec app

.PHONY: up down build shell logs migrate seed fresh test test-coverage infection pint analyse check

## Ambiente
up:
	$(DC) up -d

down:
	$(DC) down

build:
	$(DC) build

shell:
	$(APP) sh

logs:
	$(DC) logs -f

## Banco de dados
migrate:
	$(APP) php artisan migrate

seed:
	$(APP) php artisan db:seed

fresh:
	$(APP) php artisan migrate:fresh --seed

## Qualidade
test:
	$(APP) php artisan test

test-coverage:
	$(APP) php artisan test --coverage --min=90

infection:
	$(APP) vendor/bin/infection --min-msi=70 --min-covered-msi=80

pint:
	$(APP) vendor/bin/pint

analyse:
	$(APP) vendor/bin/phpstan analyse --memory-limit=512M

check: pint analyse test
