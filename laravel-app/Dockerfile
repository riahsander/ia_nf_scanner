FROM php:8.3-apache

# 1. Instala dependências e extensões
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

# 2. CORREÇÃO DO ERRO MPM: Desativa o event e garante o prefork (necessário para o PHP)
RUN a2dismod mpm_event && a2enmod mpm_prefork

# 3. Configura o Apache para o Laravel
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# ... (resto do seu Dockerfile: Composer, COPY, chown, etc.)

CMD ["apache2-foreground"]
