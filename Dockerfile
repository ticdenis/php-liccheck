FROM composer:2.0 as builder

COPY composer.* /app/

RUN composer install --no-dev --no-interaction --optimize-autoloader \
 && composer clear-cache

FROM php:5.4.45-cli as production

WORKDIR /app

COPY src .
COPY bin .
COPY --from=builder /app .

ENTRYPOINT liccheck

FROM production as development

COPY --from=builder /usr/bin/composer /usr/bin/composer
COPY tests .

RUN apt-get update \
 && apt-get install -y --no-install-recommends git zip unzip \
 && rm -rf /var/lib/apt/lists/*

RUN composer install --no-interaction --optimize-autoloader \
 && composer clear-cache
