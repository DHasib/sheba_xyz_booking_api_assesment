# -------------------------------------------------
#  Laravel / Docker helper Makefile
# -------------------------------------------------
SERVICE      ?= booking_api          # docker-compose service name
NGINX        ?= nginx          # docker-compose service name
COMPOSE       = docker compose       # alias (v2 syntax)
PHP          ?= php                  # inside-container PHP alias

# Default target: full setup (build → up → install → env → migrate)
.PHONY: setup
setup: env build up install migrate

# -------------------------------------------------
# 1) Docker build & up
# -------------------------------------------------
.PHONY: build
build:
	$(COMPOSE) build $(SERVICE) $(NGINX)

.PHONY: up
up:
	$(COMPOSE) up -d $(SERVICE) $(NGINX)

.PHONY: stop
stop:
	$(COMPOSE) stop $(SERVICE) $(NGINX)

.PHONY: down
down:
	$(COMPOSE) down

# -------------------------------------------------
# 2) Composer install (inside container)
# -------------------------------------------------
.PHONY: install
install:
	$(COMPOSE) exec $(SERVICE) composer install --no-interaction --prefer-dist --optimize-autoloader

# -------------------------------------------------
# 3) Copy env template → .env (inside container)
# $(COMPOSE) exec $(SERVICE) sh -c 'if [ ! -f .env ]; then cp .env.dev .env; fi'
# -------------------------------------------------
.PHONY: env
env:
	sh -c 'if [ ! -f .env ]; then cp .env.dev .env; fi'

# -------------------------------------------------
# 4) DB migration + seed (inside container)
# -------------------------------------------------
.PHONY: migrate
migrate:
	$(COMPOSE) exec $(SERVICE) $(PHP) artisan migrate:fresh --seed --force

# -------------------------------------------------
# 5) Run PHPUnit tests (inside container)
# -------------------------------------------------
.PHONY: test
test:
	$(COMPOSE) exec $(SERVICE) $(PHP) artisan test

# -------------------------------------------------
# 6) Refresh vendor & caches quickly
# -------------------------------------------------
.PHONY: clear
clear:
	$(COMPOSE) exec $(SERVICE) $(PHP) artisan config:clear && \
	$(COMPOSE) exec $(SERVICE) $(PHP) artisan cache:clear && \
	$(COMPOSE) exec $(SERVICE) $(PHP) artisan route:clear

# -------------------------------------------------
# 7) Tail Laravel log
# -------------------------------------------------
.PHONY: log
log:
	$(COMPOSE) exec $(SERVICE) tail -f storage/logs/laravel.log
