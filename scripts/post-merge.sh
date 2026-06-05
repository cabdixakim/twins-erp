#!/bin/bash
set -e

php artisan migrate --force
php artisan view:clear
