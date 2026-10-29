FROM php:8.2.35-fpm-alpine3.23

ENV TERM xterm

WORKDIR /var/www

RUN apk add --no-cache rabbitmq-c \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS rabbitmq-c-dev linux-headers \
    && pecl install amqp-2.1.2 \
    && docker-php-ext-enable amqp \
    && docker-php-ext-install sockets \
    && apk del .build-deps

COPY --chown=www-data:www-data build/dev/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY --chown=www-data:www-data build/dev/php-fpm/php.ini /usr/local/etc/php/php.ini
COPY --chown=www-data:www-data bin/composer.phar /usr/bin/composer

RUN chmod +x /usr/bin/composer

USER www-data
