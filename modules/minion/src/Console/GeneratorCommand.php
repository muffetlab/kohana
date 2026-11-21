<?php

namespace Kohana\Console;

/**
 * Base class for make:* commands that write a class into app/classes.
 *
 * @package    Kohana/Minion
 * @category   Tasks
 */
abstract class GeneratorCommand extends Command
{
    protected $options = [
        'force' => false,
    ];

    /**
     * @return string Namespace below App\ the class goes into, e.g. "Http\Controllers"
     */
    abstract protected function baseNamespace(): string;

    /**
     * @return string Suffix the class name must end with, e.g. "Controller"
     */
    protected function suffix(): string
    {
        return '';
    }

    /**
     * Returns the PHP source of the class.
     *
     * @param string $namespace Full namespace, e.g. App\Http\Controllers\Admin
     * @param string $class Short class name, e.g. UserController
     * @return string
     */
    abstract protected function stub(string $namespace, string $class): string;

    protected function handle(array $params)
    {
        $name = $this->argument(0);
        $name = $name === null ? null : $this->normalize($name);

        if ($name === null || !preg_match('#^[A-Za-z_][A-Za-z0-9_]*([/\\\\][A-Za-z_][A-Za-z0-9_]*)*$#', $name)) {
            Cli::write('Give a name, for example: php kohana ' . static::convertClassToTask($this) . ' ' . $this->example());

            return;
        }

        [$namespace, $class] = $this->qualify($name);
        $file = $this->path($namespace, $class);

        if (is_file($file) && $params['force'] === false) {
            Cli::write(Cli::color("$namespace\\$class already exists", 'red') . ' (' . $this->relative($file) . '); use --force to overwrite it');

            return;
        }

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        file_put_contents($file, $this->stub($namespace, $class));

        Cli::write(Cli::color("Created $namespace\\$class", 'green') . ' in ' . $this->relative($file));
    }

    /**
     * Converts the name given on the command line before it is validated.
     */
    protected function normalize(string $name): string
    {
        return $name;
    }

    /**
     * @return string Example name for the usage message
     */
    protected function example(): string
    {
        return 'Admin/User';
    }

    /**
     * Turns "admin/user" into ["App\Http\Controllers\Admin", "UserController"].
     *
     * @param string $name Name given on the command line
     * @return array [namespace, class]
     */
    public function qualify(string $name): array
    {
        $parts = array_map(fn ($part) => str_replace(' ', '', ucwords(str_replace('_', ' ', $part))), preg_split('#[/\\\\]#', $name));
        $class = array_pop($parts);

        if ($this->suffix() !== '' && !str_ends_with($class, $this->suffix())) {
            $class .= $this->suffix();
        }

        return [implode('\\', array_merge(['App', $this->baseNamespace()], $parts)), $class];
    }

    /**
     * @return string File that holds the class, following the App\ => app/classes/ mapping
     */
    public function path(string $namespace, string $class): string
    {
        $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($namespace, strlen('App\\')));

        return APPPATH . 'classes' . DIRECTORY_SEPARATOR . $relative . DIRECTORY_SEPARATOR . $class . '.php';
    }

    protected function relative(string $file): string
    {
        return str_replace(dirname(APPPATH) . DIRECTORY_SEPARATOR, '', $file);
    }
}
