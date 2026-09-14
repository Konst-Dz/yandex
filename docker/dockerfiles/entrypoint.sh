#!/bin/sh
set -e

for dir in \
    /var/www/app/storage/framework/sessions \
    /var/www/app/storage/framework/views \
    /var/www/app/storage/framework/cache/data \
    /var/www/app/storage/logs \
    /var/www/app/storage/app \
    /var/www/app/storage/app/public \
    /var/www/app/bootstrap/cache
do
    mkdir -p "$dir"
done

chown -R app:app /var/www/app/storage /var/www/app/bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx /var/www/app/storage /var/www/app/bootstrap/cache 2>/dev/null || true

exec "$@"
