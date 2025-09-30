# syntax=docker/dockerfile:1
FROM php:8.1-fpm

# 1) Install system dependencies + PHP extensions
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      git \
      curl \
      zip \
      unzip \
      libzip-dev \
      libonig-dev \
      libxml2-dev \
      libpng-dev \
      libjpeg-dev \
      libfreetype6-dev \
      libicu-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install \
      pdo_mysql \
      zip \
      mbstring \
      xml \
      bcmath \
      intl \
      gd \
 && rm -rf /var/lib/apt/lists/*

# 2) Install Node.js & npm (v16 LTS)
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash - \
 && apt-get update \
 && apt-get install -y --no-install-recommends nodejs \
 && rm -rf /var/lib/apt/lists/*

# 3) Bring in Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 4) Copy your application code
COPY . .

# 7) Fix permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
