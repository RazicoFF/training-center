FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

FROM php:8.3-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx libzip-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype-dev libonig-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install pdo_mysql gd zip mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP's default 2M upload limit silently drops typical phone photos; match nginx's client_max_body_size.
RUN printf 'upload_max_filesize = 20M\npost_max_size = 25M\n' > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor

RUN mkdir -p public/uploads storage/certificates \
    && chown -R www-data:www-data /var/www/html

COPY docker/nginx.conf /etc/nginx/sites-available/default

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN nginx -t

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
