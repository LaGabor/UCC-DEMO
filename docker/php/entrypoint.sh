#!/bin/sh
set -e

cd /var/www/php

if [ ! -f artisan ]; then
  echo "artisan not found in /var/www/php"
  exit 1
fi

mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rw storage bootstrap/cache || true

if [ ! -d vendor ]; then
  composer install
fi

php-fpm
