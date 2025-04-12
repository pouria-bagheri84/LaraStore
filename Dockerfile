FROM php:8.2-apache

# نصب extensionهای لازم
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# نصب composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# کپی پروژه به کانتینر
COPY . /var/www/html

# تنظیم دایرکتوری لاراول
WORKDIR /var/www/html

RUN composer install
RUN cp .env.example .env
RUN php artisan key:generate

# تنظیم permission‌ها
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
