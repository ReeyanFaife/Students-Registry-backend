FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

COPY public/ /var/www/html/
COPY db.php /var/www/html/db.php
COPY index.php /var/www/html/

EXPOSE 80
