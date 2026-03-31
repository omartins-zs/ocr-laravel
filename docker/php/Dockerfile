FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    nodejs \
    npm \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_pgsql mbstring zip bcmath exif pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-ocr.ini
COPY docker/php/zz-www.conf /usr/local/etc/php-fpm.d/zz-www.conf

CMD ["php-fpm"]
