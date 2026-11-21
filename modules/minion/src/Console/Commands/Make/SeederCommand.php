<?php

namespace Kohana\Console\Commands\Make;

use Kohana\Console\GeneratorCommand;

/**
 * Creates a database seeder in database/seeders.
 *
 *     php kohana make:seeder User              # Database\Seeders\UserSeeder
 *     php kohana make:seeder DatabaseSeeder
 *
 * Options:
 *   --force  Overwrite an existing file
 */
class SeederCommand extends GeneratorCommand
{
    protected function rootNamespace(): string
    {
        return 'Database\\Seeders';
    }

    protected function rootPath(): string
    {
        return database_path('seeders');
    }

    protected function baseNamespace(): string
    {
        return '';
    }

    protected function suffix(): string
    {
        return 'Seeder';
    }

    protected function stub(string $namespace, string $class): string
    {
        return <<<PHP
            <?php

            namespace $namespace;

            use Kohana\\Database\\DB;
            use Kohana\\Database\\Seeder;

            class $class extends Seeder
            {
                public function run(): void
                {
                    // DB::insert('table', ['column'])->values(['value'])->execute();
                    // \$this->call([OtherSeeder::class]);
                }
            }

            PHP;
    }
}
