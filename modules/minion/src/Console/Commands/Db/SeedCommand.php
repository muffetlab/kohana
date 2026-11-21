<?php

namespace Kohana\Console\Commands\Db;

use Kohana\Console\Cli;
use Kohana\Console\Command;
use Kohana\Database\Seeder;

/**
 * Fills the database with a seeder.
 *
 *     php kohana db:seed                      # Database\Seeders\DatabaseSeeder
 *     php kohana db:seed --class=UserSeeder   # Database\Seeders\UserSeeder
 *
 * Options:
 *   --class     Seeder class; short names are looked up in Database\Seeders
 *   --database  Database group (default: the default connection)
 *   --force     Run in production
 */
class SeedCommand extends Command
{
    protected $options = [
        'class' => 'DatabaseSeeder',
        'database' => null,
        'force' => false,
    ];

    protected function handle(array $params)
    {
        if (!$this->confirmProduction($params)) {
            return;
        }

        $class = str_contains($params['class'], '\\') ? ltrim($params['class'], '\\') : 'Database\\Seeders\\' . $params['class'];

        if (!class_exists($class)) {
            Cli::write(Cli::color("Seeder $class not found", 'red') . '; create it with: php kohana make:seeder ' . $params['class']);

            return;
        }

        Seeder::seed($class, $params['database'], fn ($seeder) => Cli::write("Seeding: $seeder"));

        Cli::write(Cli::color('Database seeded.', 'green'));
    }
}
