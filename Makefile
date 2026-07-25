# ============================================
# DEV
# ============================================
up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

restart:
	docker compose restart

logs:
	docker compose logs -f

bash:
	docker compose exec -u appuser php bash

# ============================================
# PROD
# ============================================
prod-up:
	docker compose -f docker-compose.prod.yml up -d

prod-down:
	docker compose -f docker-compose.prod.yml down

prod-build:
	docker compose -f docker-compose.prod.yml build

prod-restart:
	docker compose -f docker-compose.prod.yml restart

prod-logs:
	docker compose -f docker-compose.prod.yml logs -f

prod-bash:
	docker compose -f docker-compose.prod.yml exec php bash

prod-deploy: prod-build prod-up
	@echo "🚀 Production deployed!"
