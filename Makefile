# ============================================
# 🎵 MyMixes.Ru - Makefile
# ============================================

.PHONY: help assets build dev logs migrate cc test shell

# По умолчанию показываем help
.DEFAULT_GOAL := help

help: ## 📚 Показать все доступные команды
	@echo "🎵 MyMixes.Ru - доступные команды:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'
	@echo ""

# ============================================
# 🔧 Билд и ассеты
# ============================================

assets: ## 🔨 Собрать CSS и ассеты
	docker compose exec -u appuser php bin/console sass:build
	docker compose exec -u appuser php bin/console asset-map:compile

build: ## 🏗️ Полная пересборка проекта
	docker compose build
	docker compose up -d
	make assets

# ============================================
# 🐳 Docker
# ============================================

up: ## 🚀 Запустить контейнеры
	docker compose up -d

down: ## ⏹️ Остановить контейнеры
	docker compose down

restart: ## 🔄 Перезапустить контейнеры
	docker compose restart

rebuild: ## ♻️ Пересобрать и перезапустить
	docker compose down
	docker compose build --no-cache
	docker compose up -d
	make assets

logs: ## 📋 Посмотреть логи (всех контейнеров)
	docker compose logs -f --tail=100

log-php: ## 📋 Логи PHP
	docker compose logs -f php --tail=50

log-nginx: ## 📋 Логи Nginx
	docker compose logs -f nginx --tail=50

log-mysql: ## 📋 Логи MySQL
	docker compose logs -f mysql --tail=50

# ============================================
# 🐘 Symfony
# ============================================

cc: ## 🧹 Очистить кэш Symfony
	docker compose exec -u appuser php bin/console cache:clear

warmup: ## 🔥 Собрать кэш (warmup)
	docker compose exec -u appuser php bin/console cache:warmup

migrate: ## 🗄️ Применить миграции
	docker compose exec -u appuser php bin/console doctrine:migrations:migrate

migrate-gen: ## 📝 Сгенерировать миграцию
	docker compose exec -u appuser php bin/console doctrine:migrations:diff

migrate-rollback: ## ↩️ Откатить последнюю миграцию
	docker compose exec -u appuser php bin/console doctrine:migrations:rollback

routes: ## 🛣️ Показать все маршруты
	docker compose exec -u appuser php bin/console debug:router

container: ## 📦 Показать все сервисы
	docker compose exec -u appuser php bin/console debug:container

# ============================================
# 🛠️ Утилиты
# ============================================

shell: ## 🐚 Зайти в контейнер PHP
	docker compose exec -u appuser php bash

mysql: ## 🗄️ Зайти в MySQL
	docker compose exec mysql mysql -u root -proot

redis: ## 🔴 Зайти в Redis
	docker compose exec redis redis-cli

dev-log: ## 📋 Лог разработки (tail -f var/log/dev.log)
	docker compose exec -u appuser php tail -f var/log/dev.log

test: ## 🧪 Запустить тесты
	docker compose exec -u appuser php bin/console doctrine:schema:drop --env=test --force
	docker compose exec -u appuser php bin/console doctrine:schema:create --env=test
	docker compose exec -u appuser php bin/phpunit --testdox --display-notices

# ============================================
# 🧹 Очистка
# ============================================

clean: ## 🧹 Очистить кэш и ассеты
	rm -rf var/cache/*
	rm -rf var/log/*
	rm -rf public/assets/*
	make assets

prune: ## 🗑️ Полная очистка Docker (все образы, тома)
	docker system prune -af
	# docker volume prune -f  # Закомментировано, чтобы не удалить данные БД случайно

prune-all: ## ☢️ ПОЛНАЯ очистка ВСЕГО (включая volume с данными)
	docker system prune -af
	docker volume prune -f
	@echo "⚠️ Все данные удалены! Не забудь восстановить БД из бекапа."

# ============================================
# 📦 Разработка
# ============================================

dev: ## 🚀 Поднять проект в dev-режиме
	docker compose up -d
	make assets
	make cc
	@echo "✅ Проект готов: http://localhost:49020"

watch: ## 👀 Следить за изменениями ассетов
	docker compose exec -u appuser php bin/console asset-map:watch

# ============================================
# 📖 Чтение метаданных (бонус)
# ============================================

meta: ## 🎵 Показать метаданные файла (FILE=путь)
	docker compose exec -u appuser php bin/console debug:metadata $(FILE)

text:
	{ \
		find config -name "*.yaml" -print0 ; \
		find src -name "*.php" -print0 ; \
		find templates -name "*.twig" -print0 ; \
		find tests -name "*.php" -print0 ; \
		find assets -name "app.js" -print0 ; \
		find assets -name "player.js" -print0 ; \
		find assets -name "upload.js" -print0 ; \
		find assets -name "mix.list.js" -print0 ; \
		find assets -name "mix.show.js" -print0 ; \
		find assets/styles -name "*.scss" -print0 ; \
		find docker/php -name "Dockerfile" -print0 ; \
		find . -name "docker-compose.yml" -print0 ; \
		find . -name "Makefile" -print0 ; \
		find . -name "importmap.php" -print0 ; \
	} | xargs -0 -I {} sh -c 'echo "=== {} ===" && cat "{}" && echo ""' > /home/max/www/tech.code.txt

deploy: ## 🚀 Деплой на продакшен
	@echo "🔍 Проверка готовности к деплою..."
	docker compose exec -u appuser php symfony console app:deploy:check
	@echo "✅ Проверка пройдена, деплоим..."
	git pull origin main
	docker compose -f docker-compose.prod.yml build
	docker compose -f docker-compose.prod.yml up -d
	docker compose -f docker-compose.prod.yml exec -T php symfony console doctrine:migrations:migrate --no-interaction
	docker compose -f docker-compose.prod.yml exec -T php symfony console cache:clear
