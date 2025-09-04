#!/bin/bash
set -e

# Espera RabbitMQ estar pronto
echo "Aguardando RabbitMQ..."
until nc -z $RABBITMQ_HOST 5672; do
  echo "RabbitMQ não disponível, tentando novamente..."
  sleep 2
done
echo "RabbitMQ disponível!"

# Instala dependências PHP
if [ ! -d "vendor" ]; then
  echo "Instalando dependências PHP..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Inicializa supervisor no foreground
echo "Iniciando supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
