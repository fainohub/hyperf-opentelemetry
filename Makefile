install:
	@make build
	@make composer-install

build:
	@docker-compose build image

sh:
	@docker-compose run --rm hyperf-opentelemetry

composer-install:
	@docker-compose run --rm composer install

composer-update:
	@docker-compose run --rm composer update