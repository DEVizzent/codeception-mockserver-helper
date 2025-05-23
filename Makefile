
SHELL:=/bin/bash
.DEFAULT_GOAL:=help
.PHONY: help install up code-sniff code-format code-find-bugs code-find-bugs code-find-smells
PHP_CONTAINER_NAME=php

help:  ## Display this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n\nTargets:\n"} /^[a-zA-Z0-9_-]+:.*?##/ { printf "  \033[36m%-25s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)


install: composer-install build up  ## Install required software and initialize your local configuration

build:  ## Build application docker images
	@bash -c "docker compose build ${PHP_CONTAINER_NAME}"

up:  ## Start application containers and required services
	@bash -c "export MOCKSERVER_LOG_LEVEL=WARN; docker compose up -d"

debug:  ## Start application containers and required services in debug mode
	@bash -c "export MOCKSERVER_LOG_LEVEL=INFO; docker compose up -d"

down:  ## Stop application containers and required services
	@docker compose down

test:  ## Execute all phpunit test
	@docker compose exec ${PHP_CONTAINER_NAME} ./vendor/bin/phpunit ${TEST}

code-sniff cs:  ## Detect coding standard violations in all project files using code sniffer
	@docker compose exec ${PHP_CONTAINER_NAME} ./vendor/bin/phpcs

code-format cf:  ## Fix coding standard violations in all project files
	@docker compose exec ${PHP_CONTAINER_NAME} ./vendor/bin/phpcbf

code-find-bugs phpstan:  ## Run static analysis tool to find possible bugs using phpstan
	@docker compose exec ${PHP_CONTAINER_NAME} ./vendor/bin/phpstan analyse

code-find-smells md:  ## Run static analysis tool to find code smells using mess detector
	@docker compose exec ${PHP_CONTAINER_NAME} ./vendor/bin/phpmd src,tests text phpmd.xml --suffixes php

composer-update:  ## Run composer update
	@docker run --rm --interactive --tty --volume $PWD:/app composer update

composer-install:  ## Run composer update
	@docker run --rm --interactive --tty --volume $PWD:/app composer install

console c: ## Run the container bash console
	docker exec -it php /bin/bash
