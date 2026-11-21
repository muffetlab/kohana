<?php

namespace Kohana\Database\Schema;

use Kohana\Database\Connection;

/**
 * Static entry point to the schema builder:
 *
 *     Schema::create('posts', function (Blueprint $table) { ... });
 *     Schema::table('posts', fn (Blueprint $table) => $table->string('slug')->nullable());
 *     Schema::dropIfExists('posts');
 *     Schema::connection('pgsql')->create(...);
 *
 * @method static void create(string $table, callable $callback)
 * @method static void table(string $table, callable $callback)
 * @method static void drop(string $table)
 * @method static void dropIfExists(string $table)
 * @method static void rename(string $from, string $to)
 * @method static bool hasTable(string $table)
 * @method static bool hasColumn(string $table, string $column)
 * @method static string[] toSql(string $table, callable $callback, bool $create = true)
 *
 * @package    Kohana/Database
 * @category   Schema
 */
class Schema
{
    /**
     * @var string|null Connection used by the static methods; the migrator sets it while it runs
     */
    public static ?string $connection = null;

    /**
     * Returns a builder for a connection group (default: Schema::$connection, then Connection::$default).
     */
    public static function connection(?string $name = null): Builder
    {
        return new Builder(Connection::instance($name ?? static::$connection));
    }

    public static function __callStatic(string $method, array $arguments)
    {
        return static::connection()->$method(...$arguments);
    }
}
