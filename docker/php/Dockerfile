FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mbstring pdo_mysql \
    && a2enmod rewrite

COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/app

COPY api/composer.json api/composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist

COPY api/src ./src
COPY api/public/index.php ./public/index.php
COPY api/phpstan.neon.dist api/.php-cs-fixer.dist.php ./
COPY frontend/ ./public/
COPY docker/php/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/app
