FROM php:8.3-apache AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_pgsql zip gd opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

FROM php-base AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize \
    && php artisan package:discover --ansi

FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY app app
COPY bootstrap bootstrap
COPY config config
COPY public public
COPY resources resources
COPY routes routes
COPY components.json tailwind.config.js tsconfig.json vite.config.ts ./
RUN npm run build

FROM php-base AS app
WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor vendor
COPY --from=assets /app/public/build public/build
COPY docker/entrypoint.sh /usr/local/bin/osce-entrypoint

RUN chmod +x /usr/local/bin/osce-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 80
ENTRYPOINT ["osce-entrypoint"]
CMD ["apache2-foreground"]
