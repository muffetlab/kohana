<?php

namespace Kohana\Console\Commands\Make;

use Kohana\Console\GeneratorCommand;

/**
 * Creates a model factory in database/factories.
 *
 *     php kohana make:factory Article          # Database\Factories\ArticleFactory for App\Models\Article
 *
 * Options:
 *   --force  Overwrite an existing file
 */
class FactoryCommand extends GeneratorCommand
{
    protected function rootNamespace(): string
    {
        return 'Database\\Factories';
    }

    protected function rootPath(): string
    {
        return database_path('factories');
    }

    protected function baseNamespace(): string
    {
        return '';
    }

    protected function suffix(): string
    {
        return 'Factory';
    }

    protected function stub(string $namespace, string $class): string
    {
        $model = substr($class, 0, -strlen('Factory'));

        return <<<PHP
            <?php

            namespace $namespace;

            use App\\Models\\$model;
            use Kohana\\Orm\\Factories\\Factory;

            /**
             * @extends Factory<$model>
             */
            class $class extends Factory
            {
                protected string \$model = $model::class;

                public function definition(): array
                {
                    return [
                        // 'name' => '$model ' . \$this->sequence(),
                    ];
                }
            }

            PHP;
    }
}
