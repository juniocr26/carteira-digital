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

# Copiar chaves RSA
COPY docker/rsa/id_rsa /var/www/storage/keys/id_rsa
COPY docker/rsa/id_rsa.pub /var/www/storage/keys/id_rsa.pub

# Setar permissões das chaves (privada só leitura do dono)
RUN mkdir -p /var/www/storage/keys \
    && chown -R www-data:www-data /var/www/storage/keys \
    && chmod 600 /var/www/storage/keys/id_rsa \
    && chmod 644 /var/www/storage/keys/id_rsa.pub

# Setar diretório de trabalho
WORKDIR /var/www

COPY . .

RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

# Rodar Supervisor como processo principal do container
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf", "-n"]
