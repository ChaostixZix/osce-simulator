# Appwrite TablesDB Migrations

Store Appwrite-specific migration files here. Run `php artisan appwrite:migrate` to execute them. Each migration should return an anonymous class extending `App\Appwrite\Migrations\Migration` and receive an `AppwriteService` instance in `up()` and `down()`.
