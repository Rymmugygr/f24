FROM php:8.2.35-fpm-alpine3.23

ENV TERM xterm

WORKDIR /var/www

RUN apk add libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

COPY --chown=www-data:www-data build/dev/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY --chown=www-data:www-data build/dev/php-fpm/php.ini /usr/local/etc/php/php.ini
COPY --chown=www-data:www-data bin/composer.phar /usr/bin/composer

RUN chmod +x /usr/bin/composer

USER www-data
