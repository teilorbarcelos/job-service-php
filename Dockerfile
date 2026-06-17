FROM php:8.3-cli-alpine AS base

RUN apk add --no-cache postgresql-dev linux-headers git autoconf g++ make \
    && docker-php-ext-install pdo_pgsql pcntl sockets \
    && pecl install redis && docker-php-ext-enable redis \
    && pecl install pcov && docker-php-ext-enable pcov \
    && apk del autoconf g++ make linux-headers

WORKDIR /app

FROM base AS builder

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-sockets

COPY . .
RUN composer dump-autoload --no-interaction

FROM base AS release

COPY --from=builder /app /app

CMD ["php", "src/Application.php"]
