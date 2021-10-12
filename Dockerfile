FROM php:8.0-cli-alpine3.13

WORKDIR /app

RUN apk add --update --no-cache git openssh
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

ENV PATH /app/bin:/app/vendor/bin:$PATH