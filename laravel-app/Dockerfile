FROM php:8.3-apache

# 1. Instala dependências do sistema e extensões PHP
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

# 2. RESOLVE O ERRO MPM (More than one MPM loaded)
# Removemos o 'event' e forçamos o 'prefork' para o PHP funcionar
RUN a2dismod mpm_event || true && \
    a2enmod mpm_prefork && \
    rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf

# 3. Configurações do Apache para o Laravel
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Define diretório de trabalho e copia os arquivos
WORKDIR /var/www/html
COPY . .

# 6. CORREÇÃO AQUI: Instala as dependências do PHP corretamente
RUN composer install --no-dev --optimize-autoloader

# 7. Ajusta permissões para o Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# 8. Comando final para iniciar o Apache
CMD ["apache2-foreground"]
