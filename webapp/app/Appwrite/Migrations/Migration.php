<?php

namespace App\Appwrite\Migrations;

use App\Services\AppwriteService;

abstract class Migration
{
    /**
     * Run the migration against Appwrite TablesDB.
     */
    abstract public function up(AppwriteService $appwrite): void;

    /**
     * Reverse the migration.
     */
    public function down(AppwriteService $appwrite): void
    {
        // Optional: override when the migration supports rollback.
    }

    /**
     * Provide a human-readable name for logging.
     */
    public function name(): string
    {
        return static::class;
    }
}
