<?php

namespace Kohana\Console\Commands\Auth;

use Kohana\Console\Cli;
use Kohana\Console\Command;
use Kohana\Console\Commands\Make\MigrationCommand as MakeMigration;

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
class MigrationCommand extends Command
{
    protected $options = [
        'force' => false,
    ];

    protected function handle(array $params)
    {
        $existing = glob(database_path('migrations') . DIRECTORY_SEPARATOR . '*_create_auth_tables.php') ?: [];

        if ($existing && $params['force'] === false) {
            Cli::write(Cli::color('The auth migration already exists: ' . basename($existing[0]), 'red') . '; use --force to copy it again');

            return;
        }

        $file = MakeMigration::path('create_auth_tables');

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        copy(static::stub(), $file);

        Cli::write(Cli::color('Created migration ' . basename($file, '.php'), 'green') . '. Run "php kohana migrate" to create the auth tables.');
    }

    /**
     * @return string Path of the migration template shipped with the ORM module
     */
    public static function stub(): string
    {
        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'create_auth_tables.php';
    }
}
