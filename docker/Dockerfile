FROM php:8.3-fpm

# Instalar dependências
RUN apt-get update && apt-get install -y \
    git curl unzip zip libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev npm gnupg supervisor openssh-client \
    && docker-php-ext-configure gd \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd sockets \
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

# Copiar código do projeto
COPY . .

# Copiar chaves SSH para acessar repositórios privados
RUN mkdir -p /root/.ssh
COPY docker/ssh/id_rsa /root/.ssh/id_rsa
COPY docker/ssh/id_rsa.pub /root/.ssh/id_rsa.pub

# Ajustar permissões das chaves
RUN chmod 600 /root/.ssh/id_rsa && chmod 600 /root/.ssh/id_rsa.pub \
    && ssh-keyscan -t rsa github.com >> /root/.ssh/known_hosts

# Instalar dependências do Composer (sem scripts do Laravel)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copiar entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Definir entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Rodar Supervisor como processo principal do container
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf", "-n"]

