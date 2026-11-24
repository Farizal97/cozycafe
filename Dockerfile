FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libzip-dev libicu-dev unzip \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/cozycafe.ini

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p /var/www/html/storage/uploads/proofs \
             /var/www/html/storage/uploads/products \
             /var/www/html/storage/uploads/qris \
             /var/www/html/storage/logs \
             /var/www/html/storage/temp \
             /var/www/html/backups \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/backups \
    && chmod -R 0775 /var/www/html/storage /var/www/html/backups

EXPOSE 80
