FROM php:8.3-fpm

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl

# Instala extensões do PHP
RUN docker-php-ext-install pdo pdo_mysql gd

WORKDIR /var/www/html

COPY . .

# Dá permissão para o Laravel escrever nos logs
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
