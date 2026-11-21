<?php

namespace Kohana\Console\Commands\Make;

use Kohana\Console\GeneratorCommand;

/**
 * Creates a controller in app/Http/Controllers.
 *
 *     php kohana make:controller Post          # App\Http\Controllers\PostController
 *     php kohana make:controller Admin/User    # App\Http\Controllers\Admin\UserController
 *
 * Options:
 *   --force  Overwrite an existing file
 */
class ControllerCommand extends GeneratorCommand
{
    protected function baseNamespace(): string
    {
        return 'Http\\Controllers';
    }

    protected function suffix(): string
    {
        return 'Controller';
    }

    protected function stub(string $namespace, string $class): string
    {
        return <<<PHP
            <?php

            namespace $namespace;

            use Kohana\\Routing\\Controller;

            class $class extends Controller
            {
                public function index()
                {
                    \$this->response->body('$class::index');
                }
            }

            PHP;
    }
}
