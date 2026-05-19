# Clancy Asset Ledger

Laravel 12 internal inventory tracking and asset management platform for parking operations.

## Stack

- PHP 8.3+
- Laravel 12
- Blade templates
- Bootstrap 5
- PostgreSQL
- Laravel Breeze authentication
- Spatie Permission
- Spatie Activitylog
- PHPWord DOCX generation

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Default seeded administrator:

- Email: `admin@example.com`
- Password: `password`

## Documentation

- `docs/architecture.md`
- `docs/operational-documents.md`
- `docs/deployment/ubuntu-nginx-postgresql.md`
