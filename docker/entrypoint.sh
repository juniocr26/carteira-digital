#!/bin/bash
set -e

# Executar scripts do composer (ex: package:discover, config:cache)
echo ">> Executando scripts do Laravel..."
composer run-script post-install-cmd --no-interaction || true
composer run-script post-update-cmd --no-interaction || true

# Se quiser rodar migrations automaticamente:
# php artisan migrate --force

# Executar o comando principal do container (Supervisor)
exec "$@"
