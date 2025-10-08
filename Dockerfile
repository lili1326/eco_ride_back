FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git unzip curl libicu-dev libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip

# Pinner la version 1.16.x pour matcher le lock
RUN pecl install mongodb-1.16.2 \
    && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini

RUN a2enmod rewrite
WORKDIR /var/www/html

# Cache build: installer deps à partir des manifests
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN git config --global --add safe.directory /var/www/html || true
RUN if [ -f composer.json ]; then composer install --no-interaction --prefer-dist --no-dev; fi

# Puis le reste du code
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

