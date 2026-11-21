<?php

namespace Kohana\Console\Commands\Migrate;

use Kohana\Console\Cli;
use Kohana\Console\Command;
use Kohana\Database\Migrations\Migrator;

/**
 * Reverts the last batch of migrations.
 *
 *     php kohana migrate:rollback              # the last batch
 *     php kohana migrate:rollback --step=2     # the last two migrations
 *     php kohana migrate:rollback --all        # every migration
 *
 * Options:
 *   --database  Database group (default: the default connection)
 *   --step      Number of migrations to revert
 *   --all       Revert every migration
 *   --force     Run in production
 */
class RollbackCommand extends Command
{
    protected $options = [
        'database' => null,
        'step' => 0,
        'all' => false,
        'force' => false,
    ];

    public function buildValidation(\Kohana\Validation\Validator $validation): \Kohana\Validation\Validator
    {
        return parent::buildValidation($validation)->rule('step', 'digit');
    }

    protected function handle(array $params)
    {
        if (!$this->confirmProduction($params)) {
            return;
        }

        $migrator = new Migrator($params['database']);
        $reverted = $migrator->rollback((int) $params['step'], $params['all'] !== false, fn ($name) => Cli::write("Rolling back: $name"));

        Cli::write($reverted ? Cli::color('Rolled back ' . count($reverted) . ' migration(s).', 'green') : 'Nothing to roll back.');
    }
}
