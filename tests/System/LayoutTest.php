<?php

use Kohana\Foundation\Application;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * The application layer of the cascading filesystem uses the project layout
 * (config/, resources/views/, lang/, ...) while modules keep their own.
 */
#[Group('kohana')]
#[Group('kohana.core')]
class Kohana_LayoutTest extends TestCase
{
    public static function provider_directories(): array
    {
        return [
            ['classes', 'app/'],
            ['config', 'config/'],
            ['views', 'resources/views/'],
            ['messages', 'resources/messages/'],
            ['i18n', 'lang/'],
            ['migrations', 'database/migrations/'],
            ['media', 'resources/media/'],
        ];
    }

    #[DataProvider('provider_directories')]
    public function test_app_directories(string $type, string $expected)
    {
        $this->assertSame(BASEPATH . str_replace('/', DIRECTORY_SEPARATOR, $expected), Application::appDirectory($type));
    }

    public function test_find_file_uses_app_directories_before_modules()
    {
        $view = resource_path('views/layout-test-view.php');
        file_put_contents($view, 'app view');

        try {
            $this->assertSame($view, Application::findFile('views', 'layout-test-view'));
            // Modules and the system are still searched in their own views/ directory
            $this->assertSame(SYSPATH . 'views' . DIRECTORY_SEPARATOR . 'kohana' . DIRECTORY_SEPARATOR . 'error.php', Application::findFile('views', 'kohana/error'));
        } finally {
            unlink($view);
        }
    }

    public function test_list_files_includes_app_directories()
    {
        mkdir(resource_path('views/layout-test'), 0777, true);
        file_put_contents(resource_path('views/layout-test/one.php'), '');

        try {
            $files = Application::listFiles('views/layout-test');

            $this->assertSame(['views/layout-test/one.php'], array_map(fn ($key) => str_replace(DIRECTORY_SEPARATOR, '/', $key), array_keys($files)));
        } finally {
            unlink(resource_path('views/layout-test/one.php'));
            rmdir(resource_path('views/layout-test'));
        }
    }

    public function test_path_helpers()
    {
        $this->assertSame(BASEPATH . 'composer.json', base_path('composer.json'));
        $this->assertSame(APPPATH . 'Models', app_path('Models'));
        $this->assertSame(BASEPATH . 'storage/logs', storage_path('logs'));
        $this->assertSame(BASEPATH . 'resources/views', resource_path('views'));
        $this->assertSame(BASEPATH . 'database/migrations', database_path('migrations'));
        $this->assertSame(BASEPATH . 'config/app.php', config_path('app.php'));
        $this->assertSame(BASEPATH . 'lang/tr', lang_path('tr'));
        $this->assertSame(DOCROOT . 'index.php', public_path('index.php'));
        $this->assertSame(realpath(BASEPATH . 'storage/framework/cache'), Application::$cacheDir);
    }
}
