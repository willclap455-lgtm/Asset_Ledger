# Ubuntu, Nginx, and PostgreSQL Deployment

Target platform: Ubuntu Linux, Nginx, PHP 8.3+, PostgreSQL, Composer, and a process supervisor for Laravel queues.

## Server packages

Install PHP and extensions:

```bash
sudo apt-get update
sudo apt-get install -y nginx postgresql postgresql-contrib \
  php8.3-fpm php8.3-cli php8.3-common php8.3-curl php8.3-mbstring \
  php8.3-xml php8.3-zip php8.3-pgsql php8.3-gd php8.3-bcmath unzip composer
```

## PostgreSQL

Create database and role:

```sql
CREATE DATABASE clancy_asset_ledger;
CREATE USER clancy_assets WITH ENCRYPTED PASSWORD 'change-this-password';
GRANT ALL PRIVILEGES ON DATABASE clancy_asset_ledger TO clancy_assets;
```

Use managed backups or a scheduled dump. Example:

```bash
pg_dump -Fc clancy_asset_ledger > /var/backups/clancy_asset_ledger-$(date +%F).dump
```

## Laravel environment

Set `.env` values:

```dotenv
APP_NAME="Clancy Asset Ledger"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://assets.example.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=clancy_asset_ledger
DB_USERNAME=clancy_assets
DB_PASSWORD=12pg34

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

Deploy:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Nginx site

```nginx
server {
    listen 80;
    server_name assets.example.com;
    root /var/www/clancy-asset-ledger/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Queue worker

Use Supervisor for queued document generation and future background jobs:

```ini
[program:clancy-asset-ledger-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/clancy-asset-ledger/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/clancy-asset-ledger-worker.log
stopwaitsecs=3600
```

## Scheduler

Add one cron entry:

```cron
* * * * * cd /var/www/clancy-asset-ledger && php artisan schedule:run >> /dev/null 2>&1
```
