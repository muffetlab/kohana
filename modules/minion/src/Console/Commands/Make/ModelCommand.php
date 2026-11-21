<?php

namespace Kohana\Console\Commands\Make;

use Kohana\Console\GeneratorCommand;

/**
 * Creates an ORM model in app/Models.
 *
 *     php kohana make:model Post               # App\Models\Post, table "posts"
 *     php kohana make:model Post --table=blog_posts
 *
 * Options:
 *   --table  Table name, when it differs from the plural of the model name
 *   --force  Overwrite an existing file
 */
class ModelCommand extends GeneratorCommand
{
    protected $options = [
        'table' => null,
        'force' => false,
    ];

    protected function baseNamespace(): string
    {
        return 'Models';
    }

    protected function stub(string $namespace, string $class): string
    {
        $table = $this->options['table'] ? "\n    protected \$tableName = '{$this->options['table']}';\n" : '';

        return <<<PHP
            <?php

            namespace $namespace;

            use Kohana\\Orm\\Model;

            class $class extends Model
            {{$table}
                protected \$timestamps = true;

                protected \$fillable = [];

                protected \$hidden = [];

                protected \$casts = [];

                public function rules(): array
                {
                    return [];
                }
            }

            PHP;
    }
}
