<?php

namespace Kohana\Database;

/**
 * Base class of database seeders, which fill tables with initial or test data.
 *
 *     namespace Database\Seeders;
 *
 *     class DatabaseSeeder extends Seeder
 *     {
 *         public function run(): void
 *         {
 *             DB::insert('roles', ['name', 'description'])
 *                 ->values(['login', 'Login privileges'])
 *                 ->execute();
 *
 *             $this->call([UserSeeder::class, PostSeeder::class]);
 *         }
 *     }
 *
 * Run it with `php kohana db:seed`.
 *
 * @package    Kohana/Database
 * @category   Seeders
 */
abstract class Seeder
{
    /**
     * @var callable|null Receives a message for each seeder that runs
     */
    protected $output = null;

    /**
     * Fills the database.
     */
    abstract public function run(): void;

    /**
     * Runs a seeder on a connection group: Connection::$default points to it
     * while the seeder runs, so DB::insert()->execute() and ORM models use it.
     *
     * @param string $class Seeder class
     * @param string|null $connection Database group; null keeps the default
     * @param callable|null $output Receives a message for each seeder that runs
     */
    public static function seed(string $class, ?string $connection = null, ?callable $output = null): void
    {
        if (!is_subclass_of($class, self::class)) {
            throw new DatabaseException('Seeder :class must extend :base', [':class' => $class, ':base' => self::class]);
        }

        $previous = Connection::$default;

        if ($connection !== null) {
            Connection::$default = $connection;
        }

        try {
            $seeder = new $class();
            $seeder->output = $output;
            $output && $output($class);
            $seeder->run();
        } finally {
            Connection::$default = $previous;
        }
    }

    /**
     * Runs other seeders from this one.
     *
     * @param string|string[] $classes Seeder classes
     */
    public function call(string|array $classes): void
    {
        foreach ((array) $classes as $class) {
            static::seed($class, null, $this->output);
        }
    }
}
