<?php

namespace Kohana\Console\Commands\Make;

use Kohana\Console\GeneratorCommand;

/**
 * Creates a console command in app/Console/Commands.
 *
 *     php kohana make:command report           # php kohana report
 *     php kohana make:command db:seed          # php kohana db:seed
 *
 * Options:
 *   --force  Overwrite an existing file
 */
class CommandCommand extends GeneratorCommand
{
    protected function baseNamespace(): string
    {
        return 'Console\\Commands';
    }

    protected function suffix(): string
    {
        return 'Command';
    }

    protected function normalize(string $name): string
    {
        // Command names use the task separator: db:seed => db/seed
        return str_replace(static::$taskSeparator, '/', $name);
    }

    protected function example(): string
    {
        return 'db:seed';
    }

    protected function stub(string $namespace, string $class): string
    {
        return <<<PHP
            <?php

            namespace $namespace;

            use Kohana\\Console\\Cli;
            use Kohana\\Console\\Command;

            /**
             * Describe what the command does on this first line.
             *
             * Options:
             *   --example  An option, read with \$params['example']
             */
            class $class extends Command
            {
                protected \$options = [
                    'example' => null,
                ];

                protected function handle(array \$params)
                {
                    Cli::write('Hello from $class');
                }
            }

            PHP;
    }
}
