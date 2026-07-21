$ErrorActionPreference = 'Stop'

composer install
if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
}
php artisan key:generate --no-interaction
php artisan migrate --seed
npm install
npm run build
