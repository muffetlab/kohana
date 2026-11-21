<?php

namespace Kohana\Console\Commands;

use Kohana\Console\Cli;
use Kohana\Console\Command;
use Kohana\Database\Migrations\Migrator;
use Kohana\Database\Seeder;

/**
 * Runs the pending database migrations.
 *
 *     php kohana migrate
 *     php kohana migrate --database=pgsql --seed
 *
 * Options:
 *   --database  Database group (default: the default connection)
 *   --seed      Run App\Database\Seeders\DatabaseSeeder afterwards
 *   --force     Run in production
 */
class MigrateCommand extends Command
{
    protected $options = [
        'database' => null,
        'seed' => false,
        'force' => false,
    ];

    protected function handle(array $params)
    {
        if (!$this->confirmProduction($params)) {
            return;
        }

        $migrator = new Migrator($params['database']);
        $ran = $migrator->migrate(fn ($name) => Cli::write("Migrating: $name"));

        Cli::write($ran ? Cli::color('Migrated ' . count($ran) . ' migration(s).', 'green') : 'Nothing to migrate.');

        if ($params['seed'] !== false) {
            Seeder::seed('App\\Database\\Seeders\\DatabaseSeeder', $params['database'], fn ($class) => Cli::write("Seeding: $class"));
            Cli::write(Cli::color('Database seeded.', 'green'));
        }
    }
}
