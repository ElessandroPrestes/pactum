DC  = docker compose
APP = $(DC) exec app
FRONT = $(DC) exec frontend

.PHONY: up down build shell logs migrate seed fresh test test-coverage infection pint analyse check swagger \
	front-shell front-install front-dev front-build front-lint front-test

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

## Documentacao OpenAPI
swagger:
	$(APP) php artisan l5-swagger:generate

## Frontend
front-shell:
	$(FRONT) sh

front-install:
	$(FRONT) npm install

front-dev:
	$(FRONT) npm run dev -- --host 0.0.0.0

front-build:
	$(FRONT) npm run build

front-lint:
	$(FRONT) npm run lint

front-test:
	$(FRONT) npm run test
