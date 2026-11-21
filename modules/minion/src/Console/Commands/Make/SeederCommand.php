<?php

namespace Kohana\Console\Commands\Make;

use Kohana\Console\GeneratorCommand;

/**
 * Creates a database seeder in app/classes/Database/Seeders.
 *
 *     php kohana make:seeder User              # App\Database\Seeders\UserSeeder
 *     php kohana make:seeder DatabaseSeeder
 *
 * Options:
 *   --force  Overwrite an existing file
 */
class SeederCommand extends GeneratorCommand
{
    protected function baseNamespace(): string
    {
        return 'Database\\Seeders';
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
