UID=$(shell id -u)
GID=$(shell id -g)
DOCKER_PHP_SERVICE=php

start: erase build composer-install bash

erase:
		docker-compose down -v

build:
		docker-compose pull && docker-compose build --build-arg UID=${UID} --build-arg GID=${GID}

composer-install:
		docker-compose run --rm -u ${UID}:${GID} ${DOCKER_PHP_SERVICE} composer install

composer-update:
		docker-compose run --rm -u ${UID}:${GID} ${DOCKER_PHP_SERVICE} composer update

bash:
		docker-compose run --rm -u ${UID}:${GID} ${DOCKER_PHP_SERVICE} sh

phpstan:
		docker run --rm -v ${PWD}:/app ghcr.io/phpstan/phpstan analyse ./src -l 8