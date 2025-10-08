FROM php:8.4-cli-alpine

WORKDIR /var/www

RUN apk update && apk upgrade
RUN apk add --no-cache bash \
    build-base \
    g++ \
    libzip-dev \
    unzip \
    nano
# Clean temporary files

RUN docker-php-ext-install zip

RUN apk add --no-cache linux-headers autoconf \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && pecl clear-cache

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
