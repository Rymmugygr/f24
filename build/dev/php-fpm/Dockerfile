FROM php:8.2.35-fpm-alpine3.23

ENV TERM xterm

WORKDIR /var/www

COPY build/dev/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY build/dev/php-fpm/php.ini /usr/local/etc/php/php.ini
COPY bin/composer.phar /usr/bin/composer

RUN chmod +x /usr/bin/composer

RUN apk add libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
