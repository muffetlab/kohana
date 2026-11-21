<?php

namespace Kohana\Console\Commands\Session;

use Kohana\Console\PublishMigrationCommand;

/**
 * Copies the migration for the database session driver's table into database/migrations.
 *
 * Creates the "sessions" table used by SESSION_DRIVER=database. Run
 * "php kohana migrate" afterwards.
 *
 *     php kohana session:migration
 *
 * Options:
 *   --force  Copy it again even if database/migrations already has one
 */
class MigrationCommand extends PublishMigrationCommand
{
    protected function migrationName(): string
    {
        return 'create_sessions_table';
    }

    public static function stub(): string
    {
        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'create_sessions_table.php';
    }
}
