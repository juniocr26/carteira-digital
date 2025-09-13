#!/bin/bash
set -e

# Instala dependências do Composer apenas se não existir vendor
if [ ! -d "vendor" ] || [ -z "$(ls -A vendor)" ]; then
  echo ">> Instalando dependências do Composer..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

exec "$@"
