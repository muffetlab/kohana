<?php

namespace Kohana\Console\Commands\Migrate;

use Kohana\Console\Cli;
use Kohana\Console\Command;
use Kohana\Database\Migrations\Migrator;

/**
 * Shows which migrations have run.
 *
 *     php kohana migrate:status
 *
 * Options:
 *   --database  Database group (default: the default connection)
 */
class StatusCommand extends Command
{
    protected $options = [
        'database' => null,
    ];

    protected function handle(array $params)
    {
        $migrator = new Migrator($params['database']);
        $status = $migrator->status();

        if (!$status) {
            Cli::write('No migrations found.');

            return;
        }

        $files = $migrator->files();
        $width = max(array_map('strlen', array_keys($status)));

        Cli::write(Cli::color(str_pad('Migration', $width + 2) . 'Status', 'yellow'));

        foreach ($status as $name => $batch) {
            $state = match (true) {
                $batch === null => Cli::color('Pending', 'yellow'),
                !isset($files[$name]) => Cli::color("Ran (batch $batch), file missing", 'red'),
                default => Cli::color("Ran (batch $batch)", 'green'),
            };

            Cli::write(str_pad($name, $width + 2) . $state);
        }
    }
}
