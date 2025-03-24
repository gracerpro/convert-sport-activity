FROM php:8.3-cli-bullseye

WORKDIR /var/www

RUN apt-get update && apt-get upgrade --yes
RUN apt-get install --yes \
    build-essential \
    g++ \
    libzip-dev \
    unzip \
    nano \
# Clean temporary files
    && docker-php-source delete \
    && apt-get autoremove --purge -y && apt-get autoclean -y && apt-get clean -y

RUN docker-php-ext-install zip

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
