FROM php:8.1

RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

COPY rootfs /

WORKDIR /app