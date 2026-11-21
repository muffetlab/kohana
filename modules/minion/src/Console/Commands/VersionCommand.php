<?php

namespace Kohana\Console\Commands;

use Kohana\Console\Cli;
use Kohana\Console\Command;
use Kohana\Foundation\Application;

/**
 * Shows the Kohana and PHP versions.
 *
 *     php kohana version
 *     php kohana -V
 *     php kohana --version
 */
class VersionCommand extends Command
{
    protected function handle(array $params)
    {
        Cli::write(Application::version() . ' (PHP ' . PHP_VERSION . ')');
    }
}
