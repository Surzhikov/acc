#!/bin/sh

echo "Sleep 10 seconds before start"
sleep 10

APP_PATH="/var/www"
cd /var/www

composer install --ignore-platform-reqs --no-scripts
echo "✅ Composer packages installed"

echo "🔵 Migration"
php artisan migrate --force

CONTAINER_ALREADY_STARTED="app_container_already_started"

if [ ! -e $CONTAINER_ALREADY_STARTED ]; then
    touch $CONTAINER_ALREADY_STARTED
    echo "🟡 First container startup"

	rm /var/www/public/storage
	php /var/www/artisan storage:link
	echo "✅ Laravel storage linked"

	echo "🔵 Database seed"
	php artisan db:seed --force

else
    echo "🔵 Not first container startup"
fi


supervisord -c /var/www/docker/app/config/supervisord.conf

