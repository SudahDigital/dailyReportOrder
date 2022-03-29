#!/bin/sh

umask 0000

cd /var/www

# php artisan migrate:fresh --seed
php artisan cache:clear
php artisan route:cache
php artisan storage:link
#php artisan migrate
#php artisan schedule:work

/usr/bin/supervisord -c /etc/supervisord.conf

# Run scheduler
while [ true ]
do
  php artisan schedule:run --verbose --no-interaction
  sleep 60
done

