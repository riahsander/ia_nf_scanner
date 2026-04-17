FROM php:8.3-apache

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql gd zip

# INSTALA O COMPOSER (O que estava faltando!)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copia os arquivos do projeto
COPY . .

# INSTALA AS DEPENDÊNCIAS DO PHP
RUN composer install --no-dev --optimize-autoloader

# Dá permissão para o Laravel escrever nos logs e cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# O comando para rodar o servidor (ajustado para o Railway)
CMD php artisan serve --host=0.0.0.0 --port=80

# Habilita o mod_rewrite do Apache para o Laravel
RUN a2enmod rewrite
# Muda a raiz do site para a pasta public do Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
