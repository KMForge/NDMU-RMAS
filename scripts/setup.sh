#!/usr/bin/env bash
set -euo pipefail

composer install
test -f .env || cp .env.example .env
php artisan key:generate --no-interaction
php artisan migrate --seed
npm install
npm run build
