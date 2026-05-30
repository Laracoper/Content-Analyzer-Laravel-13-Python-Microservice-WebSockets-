FROM php:8.4-fpm-alpine

# Устанавливаем локальные утилиты компиляции, которые уже есть в кэше alpine
RUN apk add --no-cache autoconf g++ make

# 1. Включаем встроенные модули (pdo_mysql, pcntl, bcmath)
RUN docker-php-ext-install pdo_mysql pcntl bcmath

# 2. Магия: качаем redis через встроенную в php команду pecl, но отключаем проверку сети
RUN pecl channel-update pecl.php.net || true
RUN pecl install --onlyreqdeps --force redis && docker-php-ext-enable redis

WORKDIR /var/www/laravel-app

# Копируем скрипт авто-настройки прав
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Инжектируем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENTRYPOINT ["docker-entrypoint.sh"]

EXPOSE 9000
CMD ["php-fpm"]
