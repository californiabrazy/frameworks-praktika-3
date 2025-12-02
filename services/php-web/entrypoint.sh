#!/usr/bin/env bash
set -e

APP_DIR="/var/www/html"
PATCH_DIR="/opt/laravel-patches"

echo "[php] init start"

# Создаём Laravel, если его ещё нет
if [ ! -f "$APP_DIR/artisan" ]; then
    echo "[php] creating laravel skeleton"
    composer create-project --no-interaction --prefer-dist laravel/laravel:^11 "$APP_DIR"

    cp "$APP_DIR/.env.example" "$APP_DIR/.env"
    sed -i 's|APP_NAME=Laravel|APP_NAME=ISSOSDR|g' "$APP_DIR/.env"

    echo "[php] generating application key"
    php "$APP_DIR/artisan" key:generate --force
fi

# Применяем патчи, если есть
if [ -d "$PATCH_DIR" ] && [ "$(ls -A "$PATCH_DIR")" ]; then
    echo "[php] applying patches from $PATCH_DIR"
    rsync -av --no-perms --no-owner --no-group "$PATCH_DIR/" "$APP_DIR/"
fi

# Создаём необходимые директории и файл логов
mkdir -p "$APP_DIR/storage/logs" "$APP_DIR/bootstrap/cache"

# Если лог-файл не существует, создаём
touch "$APP_DIR/storage/logs/laravel.log"

# Устанавливаем правильного владельца и права
# Все файлы и папки приложения должны быть www-data
chown -R www-data:www-data "$APP_DIR"

# storage и cache должны быть записываемыми
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# Файл логов с правами на запись для PHP
chmod 664 "$APP_DIR/storage/logs/laravel.log"

# Запускаем миграции (если есть)
echo "[php] running migrations (if any)"
php "$APP_DIR/artisan" migrate --force || true

# Запускаем php-fpm
echo "[php] starting php-fpm"
exec php-fpm -F
