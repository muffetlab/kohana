<?php

use Kohana\Console\Command;
use Kohana\Console\Commands\Cache\ClearCommand;
use Kohana\Console\Commands\Make\CommandCommand;
use Kohana\Console\Commands\Make\ControllerCommand;
use Kohana\Console\Commands\Make\ModelCommand;
use Kohana\Routing\Route;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('kohana')]
#[Group('kohana.minion')]
class Kohana_ConsoleTest extends TestCase
{
    private ?string $dir = null;

    public function tearDown(): void
    {
        if ($this->dir) {
            ClearCommand::clearDirectory($this->dir);
            foreach (glob($this->dir . '/.*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->dir);
        }

        parent::tearDown();
    }

    private function run_command(array $options): string
    {
        ob_start();
        Command::factory($options)->execute();

        return ob_get_clean();
    }

    public function test_positional_arguments_are_separated_from_options()
    {
        $command = Command::factory(['make:controller', 'Admin/User', 'Extra', 'force' => null]);

        $this->assertInstanceOf(ControllerCommand::class, $command);
        $this->assertSame(['Admin/User', 'Extra'], $command->arguments());
        $this->assertSame('Admin/User', $command->argument(0));
        $this->assertNull($command->argument(5));
        $this->assertArrayNotHasKey(1, $command->getOptions());
    }

    public function test_task_option_keeps_first_positional_as_argument()
    {
        $command = Command::factory(['task' => 'make:model', 'Post']);

        $this->assertSame(['Post'], $command->arguments());
    }

    public function test_unknown_options_are_rejected()
    {
        $output = $this->run_command(['route:list', 'nope' => null]);

        $this->assertStringContainsString('Unknown option --nope', $output);
    }

    public static function provider_qualify(): array
    {
        return [
            [ControllerCommand::class, 'post', ['App\Http\Controllers', 'PostController']],
            [ControllerCommand::class, 'PostController', ['App\Http\Controllers', 'PostController']],
            [ControllerCommand::class, 'admin/user_profile', ['App\Http\Controllers\Admin', 'UserProfileController']],
            [ControllerCommand::class, 'Admin\User', ['App\Http\Controllers\Admin', 'UserController']],
            [ModelCommand::class, 'blog_post', ['App\Models', 'BlogPost']],
            [CommandCommand::class, 'Db/Seed', ['App\Console\Commands\Db', 'SeedCommand']],
        ];
    }

    #[DataProvider('provider_qualify')]
    public function test_generator_names(string $class, string $name, array $expected)
    {
        $command = Command::factory([Command::convertClassToTask($class)]);

        $this->assertSame($expected, $command->qualify($name));
        $this->assertSame(
            APPPATH . str_replace('\\', '/', substr($expected[0], 4)) . '/' . $expected[1] . '.php',
            str_replace(DIRECTORY_SEPARATOR, '/', $command->path(...$expected))
        );
    }

    public function test_generated_classes_are_valid_php()
    {
        foreach (['make:controller' => ['App\Http\Controllers\Admin', 'UserController'], 'make:model' => ['App\Models', 'Post'], 'make:command' => ['App\Console\Commands\Db', 'SeedCommand'], 'make:factory' => ['Database\Factories', 'PostFactory']] as $task => [$namespace, $class]) {
            $command = Command::factory([$task, 'table' => 'posts']);
            $source = (fn () => $this->stub($namespace, $class))->call($command);

            $file = tempnam(sys_get_temp_dir(), 'stub');
            file_put_contents($file, $source);
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file), $lint, $status);
            unlink($file);

            $this->assertSame(0, $status, "$task stub:\n$source");
            $this->assertStringContainsString("namespace $namespace;", $source);
            $this->assertStringContainsString("class $class extends", $source);
        }
    }

    public function test_make_command_accepts_task_names()
    {
        $command = Command::factory(['make:command']);
        $name = (fn () => $this->normalize('db:seed'))->call($command);

        $this->assertSame(['App\Console\Commands\Db', 'SeedCommand'], $command->qualify($name));
    }

    public function test_route_list()
    {
        $output = $this->run_command(['route:list']);

        $this->assertMatchesRegularExpression('/default\s+\(<controller>\(\/<action>\(\/<id>\)\)\)\s+WelcomeController\s+index/', $output);
        $this->assertSame('(<controller>(/<action>(/<id>)))', Route::get('default')->pattern());
    }

    public function test_clear_directory_keeps_dot_files()
    {
        $this->dir = sys_get_temp_dir() . '/kohana-cache-' . uniqid();
        mkdir($this->dir . '/ab/cd', 0777, true);
        file_put_contents($this->dir . '/.gitignore', '*');
        file_put_contents($this->dir . '/ab/one', '1');
        file_put_contents($this->dir . '/ab/cd/two', '2');
        file_put_contents($this->dir . '/three', '3');

        $this->assertSame(3, ClearCommand::clearDirectory($this->dir));
        $this->assertSame(['.gitignore'], array_values(array_diff(scandir($this->dir), ['.', '..'])));
        $this->assertSame(0, ClearCommand::clearDirectory($this->dir . '/missing'));
    }
    public static function provider_global_flags(): array
    {
        return [
            [['-V'], Kohana\Console\Commands\VersionCommand::class],
            [['--version'], Kohana\Console\Commands\VersionCommand::class],
            [['version' => null], Kohana\Console\Commands\VersionCommand::class],
            [['-h'], Kohana\Console\Commands\HelpCommand::class],
            [['help' => null], Kohana\Console\Commands\HelpCommand::class],
            [[], Kohana\Console\Commands\HelpCommand::class],
        ];
    }

    #[DataProvider('provider_global_flags')]
    public function test_global_flags(array $options, string $expected)
    {
        $this->assertInstanceOf($expected, Command::factory($options));
    }

    public function test_bare_help_lists_commands()
    {
        $this->assertStringContainsString('Available commands:', $this->run_command(['help' => null]));
        $this->assertStringContainsString(Kohana\Foundation\Application::version(), $this->run_command(['-V']));
    }

    public function test_unknown_flags_and_commands_are_rejected()
    {
        foreach ([['-x'], ['nope']] as $options) {
            try {
                Command::factory($options);
                $this->fail('Expected an exception for ' . $options[0]);
            } catch (Kohana\Console\InvalidCommandException $e) {
                $this->assertStringContainsString('php kohana', $e->getMessage());
            }
        }
    }
    public function test_package_commands_are_resolved_and_listed()
    {
        $previous = Kohana\Foundation\Application::$packages;
        Kohana\Foundation\Application::$packages = new class ('', '', '') extends Kohana\Foundation\PackageManifest {
            public function manifest(): array
            {
                return ['modules' => [], 'packages' => ['acme/demo' => 'demo'], 'commands' => ['demo:route-list' => Kohana\Console\Commands\Route\ListCommand::class]];
            }
        };

        try {
            $this->assertInstanceOf(Kohana\Console\Commands\Route\ListCommand::class, Command::factory(['demo:route-list']));
            $this->assertMatchesRegularExpression('/demo:route-list\s+Lists the registered routes/', $this->run_command([]));
        } finally {
            Kohana\Foundation\Application::$packages = $previous;
        }
    }
}
