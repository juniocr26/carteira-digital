FROM php:8.3-fpm

# Instalar dependências
RUN apt-get update && apt-get install -y \
    git curl unzip zip libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev npm gnupg supervisor \
    && docker-php-ext-configure gd \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

# Instalar Node.js 18 e npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar config do Supervisor
COPY docker/supervisor/normal.conf /etc/supervisor/conf.d/normal.conf

# Setar diretório de trabalho
WORKDIR /var/www

COPY . .

# Permissões
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www \
    && chmod +x /var/www/artisan   # <-- garante que o artisan é executável

# Rodar Supervisor como processo principal do container
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf", "-n"]
