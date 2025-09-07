FROM php:8.3-fpm

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git curl unzip zip libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev npm gnupg supervisor openssh-client \
    iputils-ping netcat-traditional \
    && docker-php-ext-configure gd \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd sockets \
    && rm -rf /var/lib/apt/lists/*

# Instalar Node.js 22 e npm
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar configuração do Supervisor
COPY docker/supervisor/normal.conf /etc/supervisor/conf.d/normal.conf

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar composer.json e composer.lock para que o Composer consiga instalar
COPY composer.json composer.lock ./

# Copiar o restante do código
COPY . .

# Copiar chaves SSH para acessar repositórios privados
RUN mkdir -p /root/.ssh
COPY docker/ssh/id_rsa /root/.ssh/id_rsa
COPY docker/ssh/id_rsa.pub /root/.ssh/id_rsa.pub
RUN chmod 600 /root/.ssh/id_rsa && chmod 600 /root/.ssh/id_rsa.pub \
    && ssh-keyscan -t rsa github.com >> /root/.ssh/known_hosts

# Copiar entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Ajustar permissões do projeto para www-data (opcional)
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www \
    && chmod +x /var/www/artisan

# Definir entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Rodar Supervisor como processo principal
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf", "-n"]
