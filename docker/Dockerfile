FROM php:8.3-fpm

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git curl unzip zip libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev gnupg supervisor openssh-client netcat-openbsd \
    && docker-php-ext-configure gd \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

# Instalar Node.js 18 + npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar composer.json e composer.lock para aproveitar cache
COPY composer.json composer.lock ./

# Copiar chaves SSH para acesso ao repositório privado
COPY docker/ssh/id_rsa /root/.ssh/id_rsa
COPY docker/ssh/id_rsa.pub /root/.ssh/id_rsa.pub
RUN chmod 600 /root/.ssh/id_rsa \
    && ssh-keyscan github.com >> /root/.ssh/known_hosts

# Copiar projeto inteiro
COPY . .

# Copiar configuração do Supervisor
COPY docker/supervisor/normal.conf /etc/supervisor/conf.d/normal.conf

# Copiar entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Permissões para o Laravel
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www \
    && chmod +x /var/www/artisan

# Entrypoint padrão
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
