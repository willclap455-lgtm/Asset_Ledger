#!/usr/bin/env bash
set -euo pipefail

composer install --no-interaction --prefer-dist

if [ ! -f .env ]; then
    cp .env.example .env
fi

php -r 'exit(preg_match("/^APP_KEY=.+/m", file_get_contents(".env")) ? 0 : 1);' \
    || php artisan key:generate --ansi --force
