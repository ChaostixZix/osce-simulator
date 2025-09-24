web: sh -lc 'php artisan config:clear || true; php artisan route:clear || true; vendor/bin/heroku-php-apache2 public/'
worker: php artisan queue:work --queue=assessments,management,default --tries=3 --sleep=1 --max-time=3600
