#!/bin/sh
set -e

# Автоматически даем права на запись в ключевые папки Laravel при каждом старте
chmod -R 775 /var/www/laravel-app/storage /var/www/laravel-app/bootstrap/cache

# Запускаем основную команду контейнера (PHP-FPM)
exec "$@"
