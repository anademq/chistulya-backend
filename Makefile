COMPOSE      := docker compose
APP          := $(COMPOSE) exec app
BACKUP_DIR   := ./backups
STAMP        := $(shell date +%Y%m%d-%H%M%S)

.DEFAULT_GOAL := help
.PHONY: help check build up down restart deploy logs ps shell tinker \
        migrate rollback provision backup restore prune stats

help: ## Show this help
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

check: ## Validate the compose file and required secrets
	@test -f .env || { echo "FATAL: .env is missing"; exit 1; }
	@$(COMPOSE) config --quiet && echo "compose: ok"
	@for v in APP_KEY POSTGRES_PASSWORD REDIS_PASSWORD JWT_SECRET MINIO_ROOT_PASSWORD; do \
		grep -qE "^$$v=.+" .env || { echo "FATAL: $$v is empty in .env"; exit 1; }; \
	done
	@echo "secrets: ok"

build: check ## Build the application image
	$(COMPOSE) build --pull

up: check ## Start the whole stack
	$(COMPOSE) up -d

down: ## Stop the stack (volumes are kept)
	$(COMPOSE) down

restart: ## Restart the PHP services without touching the datastores
	$(COMPOSE) restart app queue scheduler reverb

deploy: check backup ## Backup, rebuild, restart, verify
	$(COMPOSE) build --pull
	$(COMPOSE) up -d --remove-orphans
	@echo "Waiting for the app to report healthy…"
	@for i in $$(seq 1 40); do \
		state=$$(docker inspect -f '{{.State.Health.Status}}' chistulya_app 2>/dev/null || echo starting); \
		[ "$$state" = "healthy" ] && { echo "app: healthy"; exit 0; }; \
		sleep 5; \
	done; \
	echo "FATAL: app did not become healthy — check 'make logs'"; exit 1

logs: ## Tail logs of every service (make logs s=app for one)
	$(COMPOSE) logs -f --tail=200 $(s)

ps: ## Show container status
	$(COMPOSE) ps

stats: ## Live resource usage
	docker stats --no-stream $$($(COMPOSE) ps -q)

shell: ## Interactive shell in the app container
	$(COMPOSE) exec app bash

tinker: ## Laravel tinker
	$(APP) php artisan tinker

migrate: ## Run pending migrations
	$(APP) php artisan migrate --force --isolated

rollback: ## Roll back the last migration batch
	$(APP) php artisan migrate:rollback --force

provision: ## Re-run object storage provisioning
	$(APP) php artisan storage:provision

backup: ## Dump the database to ./backups
	@mkdir -p $(BACKUP_DIR)
	@if ! $(COMPOSE) ps --status running --services 2>/dev/null | grep -qx postgres; then \
		echo "postgres is not running — nothing to back up (first deploy?)"; \
		exit 0; \
	fi; \
	tmp=$(BACKUP_DIR)/db-$(STAMP).sql.gz.part; \
	if $(COMPOSE) exec -T postgres sh -c \
			'pg_dump -U "$$POSTGRES_USER" -d "$$POSTGRES_DB" --clean --if-exists' \
			| gzip > $$tmp; then \
		mv $$tmp $(BACKUP_DIR)/db-$(STAMP).sql.gz; \
		echo "Saved $(BACKUP_DIR)/db-$(STAMP).sql.gz"; \
		ls -1t $(BACKUP_DIR)/db-*.sql.gz | tail -n +15 | xargs -r rm --; \
		echo "Kept the 14 most recent dumps."; \
	else \
		rm -f $$tmp; \
		echo "FATAL: pg_dump failed — refusing to continue without a backup"; \
		exit 1; \
	fi

restore: ## Restore a dump: make restore f=backups/db-....sql.gz
	@test -n "$(f)" || { echo "usage: make restore f=backups/db-<stamp>.sql.gz"; exit 1; }
	@test -f "$(f)" || { echo "no such file: $(f)"; exit 1; }
	@echo "This overwrites the current database. Ctrl-C within 5s to abort."
	@sleep 5
	gunzip -c "$(f)" | $(COMPOSE) exec -T postgres sh -c \
		'psql -U "$$POSTGRES_USER" -d "$$POSTGRES_DB"'

prune: ## Remove dangling images and build cache
	docker image prune -f
	docker builder prune -f
