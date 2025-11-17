.PHONY: up-mysql up-postgres down logs clean help migrate migrate-rollback composer-install

# Variables
DOCKER_COMPOSE = docker-compose

# Comandos principales
help:
	@echo "Comandos disponibles:"
	@echo "  make up-mysql          - Levanta el proyecto con MySQL"
	@echo "  make up-postgres       - Levanta el proyecto con PostgreSQL"
	@echo "  make down              - Detiene todos los contenedores"
	@echo "  make logs              - Muestra los logs de los contenedores"
	@echo "  make clean             - Limpia los contenedores y volúmenes"
	@echo "  make composer-install  - Instala dependencias de Composer"
	@echo "  make migrate           - Ejecuta las migraciones de la base de datos"
	@echo "  make migrate-rollback  - Revierte la última migración"

up-mysql:
	@echo "Levantando proyecto con MySQL..."
	@sed -i 's/DB_HOST=.*/DB_HOST=mysql/' .env
	@sed -i 's/DB_DRIVER=.*/DB_DRIVER=mysql/' .env
	$(DOCKER_COMPOSE) --profile mysql up -d
	@echo "Proyecto levantado con MySQL"
	@echo "PhpMyAdmin: http://localhost:8081"
	@echo "Aplicación: http://localhost:8080"

up-postgres:
	@echo "Levantando proyecto con PostgreSQL..."
	@sed -i 's/DB_HOST=.*/DB_HOST=postgres/' .env
	@sed -i 's/DB_DRIVER=.*/DB_DRIVER=pgsql/' .env
	$(DOCKER_COMPOSE) --profile postgres up -d
	@echo "Proyecto levantado con PostgreSQL"
	@echo "PgAdmin: http://localhost:5050"
	@echo "Aplicación: http://localhost:8080"

down:
	@echo "Deteniendo contenedores..."
	$(DOCKER_COMPOSE) down

logs:
	@echo "Mostrando logs..."
	$(DOCKER_COMPOSE) logs -f

clean:
	@echo "Limpiando contenedores y volúmenes..."
	$(DOCKER_COMPOSE) down -v
	@echo "Limpieza completada"

composer-install:
	@echo "Instalando dependencias de Composer..."
	@docker exec -it --user $(shell id -u):$(shell id -g) php_app composer install
	@echo "Dependencias instaladas"

migrate:
	@echo "Ejecutando migraciones..."
	@docker exec -it php_app php src/Database/migrate.php

migrate-rollback:
	@echo "Revirtiendo última migración..."
	@docker exec -it php_app php src/Database/migrate.php --rollback 