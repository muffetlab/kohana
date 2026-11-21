<?php

namespace Kohana\Console\Commands\Package;

use Kohana\Console\Cli;
use Kohana\Console\Command;
use Kohana\Foundation\Application;

/**
 * Rebuilds the cache of Kohana modules installed with Composer.
 *
 * Composer runs it after every install and update (post-autoload-dump).
 *
 *     php kohana package:discover
 */
class DiscoverCommand extends Command
{
    protected function handle(array $params)
    {
        if (Application::$packages === null) {
            Cli::write(Cli::color('Package discovery is not set up in bootstrap/app.php', 'red'));

            return;
        }

        Application::$packages->build();

        $packages = Application::$packages->packages();

        if (!$packages) {
            Cli::write('No Kohana packages found.');

            return;
        }

        foreach ($packages as $package => $module) {
            Cli::write('Discovered ' . Cli::color($package, 'green') . " (module \"$module\")");
        }
    }
}
