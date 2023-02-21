#!make

init: docker-down docker-pull docker-build docker-up

docker-up:
	docker-compose up --build -d

docker-down:
	docker-compose down --remove-orphans

docker-pull:
	docker-compose pull

docker-build:
	docker-compose build

composer:
	docker-compose run --rm php-cli composer $(arg)

cli:
	docker-compose run --rm php-cli $(arg)

console:
	docker-compose run --rm php-cli ./bin/console $(arg)

test:
	docker-compose run --rm php-cli ./vendor/bin/phpunit $(arg)
