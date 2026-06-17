.PHONY: help dev test coverage lint check infra-up infra-stop infra-down infra-clean generate-job build run sonar clean

help:
	@echo "Job Service PHP — available targets:"
	@echo "  make dev             Run in dev mode"
	@echo "  make test            Run unit tests"
	@echo "  make coverage        Run tests with coverage gate (100%)"
	@echo "  make lint            Run PHPStan analysis"
	@echo "  make check           Run lint + test + coverage gate"
	@echo "  make infra-up        Start PG + Redis + Rabbit via Docker Compose"
	@echo "  make infra-stop      Stop the dev infrastructure"
	@echo "  make infra-down      Stop and remove infra containers"
	@echo "  make infra-clean     Stop infra and remove volumes/images"
	@echo "  make generate-job    Generate new job (name=MyName schedule='0 3 * * *' description='TBD')"
	@echo "  make build           Build Docker image"
	@echo "  make run             Run the application (Docker)"
	@echo "  make sonar           Run SonarQube scan"
	@echo "  make clean           Remove build artifacts"

dev:
	@echo "🔧 Starting job runner (PHP)..."
	php -d display_errors=1 src/Application.php

test:
	@echo "🧪 Running tests..."
	php -d pcov.enabled=0 vendor/bin/phpunit --no-coverage

coverage:
	@echo "📊 Running tests with coverage (Docker)..."
	docker run --rm -v $(PWD):/app -w /app job-service-php:test php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text --coverage-clover coverage/clover.xml
	php scripts/check-coverage.php

lint:
	@echo "🔍 Running PHPStan..."
	vendor/bin/phpstan analyse --memory-limit=-1

check: lint test coverage
	@echo "✅ All checks passed"

infra-up:
	@echo "🐳 Starting infrastructure (PostgreSQL + Redis + RabbitMQ)..."
	docker compose -f docker-compose.infra.yml up -d

infra-stop:
	@echo "🛑 Stopping infrastructure..."
	docker compose -f docker-compose.infra.yml stop

infra-down:
	@echo "🗑️ Removing infrastructure containers..."
	docker compose -f docker-compose.infra.yml down

infra-clean:
	@echo "🧹 Cleaning infrastructure..."
	docker compose -f docker-compose.infra.yml down -v --rmi all

# Example: make generate-job name=CleanupOldRecords schedule="0 3 * * *" description="Remove old records"
generate-job:
	@echo "🏗️  Generating job $(name)..."
	@php scripts/generate-job.php "$(name)" "$(schedule)" "$(description)"

build:
	@echo "🐳 Building Docker image..."
	docker build -t job-service-php .

run:
	@echo "🚀 Running application container..."
	docker run --rm --network host job-service-php

sonar:
	@echo "🔍 Running SonarQube scan..."
	./scripts/sonar-scan.sh "job-service-php" "Job Service PHP"

clean:
	@echo "🧹 Cleaning artifacts..."
	rm -rf coverage/ .phpunit.cache/ .sonarqube/
