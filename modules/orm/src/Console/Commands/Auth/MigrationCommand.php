<?php

namespace Kohana\Console\Commands\Auth;

use Kohana\Console\PublishMigrationCommand;

/**
 * Copies the migration for the ORM auth tables into database/migrations.
 *
 * Creates users, roles, roles_users and user_tokens with the "login" and
 * "admin" roles. Run "php kohana migrate" afterwards.
 *
 *     php kohana auth:migration
 *
 * Options:
 *   --force  Copy it again even if database/migrations already has one
 */
class MigrationCommand extends PublishMigrationCommand
{
    protected function migrationName(): string
    {
        return 'create_auth_tables';
    }

    public static function stub(): string
    {
        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'create_auth_tables.php';
    }
}
