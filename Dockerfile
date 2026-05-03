FROM php:8.2-apache

COPY . /var/www/html/

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli pdo_pgsql pgsql

RUN a2enmod rewrite

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
