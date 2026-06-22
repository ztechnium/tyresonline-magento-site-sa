FROM php:8.1-fpm

RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libicu-dev libxml2-dev libxslt1-dev \
    libonig-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libcurl4-openssl-dev libssl-dev default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
       bcmath ctype curl dom fileinfo gd intl mbstring mysqli \
       pdo pdo_mysql simplexml soap sockets xsl zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
