FROM php:8.2-apache

COPY . /var/www/html/

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite (important for APIs)
RUN a2enmod rewrite

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80