<?php

namespace Kohana\Console;

use Kohana\Console\Commands\Make\MigrationCommand;

/**
 * Base class for commands that copy a module's migration template into
 * database/migrations, so the application decides when to run it.
 *
 * @package    Kohana/Minion
 * @category   Tasks
 */
abstract class PublishMigrationCommand extends Command
{
    protected $options = [
        'force' => false,
    ];

    /**
     * @return string Migration name without the date, e.g. "create_sessions_table"
     */
    abstract protected function migrationName(): string;

    /**
     * @return string Path of the migration template
     */
    abstract public static function stub(): string;

    protected function handle(array $params)
    {
        $name = $this->migrationName();
        $existing = glob(database_path('migrations') . DIRECTORY_SEPARATOR . '*_' . $name . '.php') ?: [];

        if ($existing && $params['force'] === false) {
            Cli::write(Cli::color('The migration already exists: ' . basename($existing[0]), 'red') . '; use --force to copy it again');

            return;
        }

        $file = MigrationCommand::path($name);

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        copy(static::stub(), $file);

        Cli::write(Cli::color('Created migration ' . basename($file, '.php'), 'green') . '. Run "php kohana migrate" to apply it.');
    }
}
