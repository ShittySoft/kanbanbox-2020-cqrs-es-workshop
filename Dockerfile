FROM php:7.4-cli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

