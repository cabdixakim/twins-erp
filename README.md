# Twins ERP (skeleton)

This ZIP contains a **minimal Laravel-style skeleton** for Twins ERP:

- Basic Laravel bootstrap (no `vendor/`)
- Auth (owner-first setup)
- Company onboarding wizard
- Simple dashboard shell

## To use

1. Install PHP 8.2+ and Composer.
2. Run `composer install` in this folder.
3. Copy `.env.example` to `.env` and set your DB details.
4. Run `php artisan key:generate` then `php artisan migrate`.
5. Run `php artisan serve` and visit `http://127.0.0.1:8000`.
