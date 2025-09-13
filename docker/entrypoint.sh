#!/bin/bash
set -e

if [ ! -d "vendor" ] || [ -z "$(ls -A vendor)" ]; then
  echo ">> Instalando dependências do Composer no host..."
  composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts
fi

echo ">> Executando scripts do Laravel..."
composer run-script post-install-cmd --no-interaction || true
composer run-script post-update-cmd --no-interaction || true

exec "$@"
