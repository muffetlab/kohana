<?php

namespace Kohana\Database\Migrations;

use Kohana\Database\Connection;
use Kohana\Database\DatabaseException;
use Kohana\Database\DB;
use Kohana\Database\PgsqlConnection;
use Kohana\Database\Schema\Blueprint;
use Kohana\Database\Schema\Builder;
use Kohana\Database\Schema\Schema;
use Kohana\Foundation\Application;

/**
 * Runs and reverts migrations and records them in the `migrations` table.
 *
 * Migration files are found in the `migrations` directories of the cascading
 * filesystem (app/migrations and enabled modules), sorted by file name.
 *
 * @package    Kohana/Database
 * @category   Migrations
 */
class Migrator
{
    /**
     * @param string|null $connection Database group; null uses Connection::$default
     * @param string $table Table that records the migrations
     * @param array|null $files Migration name => file path; null finds them in the cascading filesystem
     */
    public function __construct(
        protected ?string $connection = null,
        protected string $table = 'migrations',
        protected ?array $files = null,
    ) {
    }

    public function db(): Connection
    {
        return Connection::instance($this->connection);
    }

    public function schema(): Builder
    {
        return new Builder($this->db());
    }

    /**
     * Creates the migrations table when it does not exist.
     */
    public function prepare(): void
    {
        if (!$this->schema()->hasTable($this->table)) {
            $this->schema()->create($this->table, function (Blueprint $table) {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
        }
    }

    /**
     * @return array Migration name => file path, in run order
     */
    public function files(): array
    {
        if ($this->files === null) {
            $files = [];
            foreach (Application::listFiles('migrations') as $path) {
                if (is_string($path) && str_ends_with($path, '.php')) {
                    $files[basename($path, '.php')] = $path;
                }
            }
            $this->files = $files;
        }

        ksort($this->files);

        return $this->files;
    }

    /**
     * @return array Name => batch of the migrations that ran, in run order
     */
    public function ran(): array
    {
        $this->prepare();

        return DB::select('migration', 'batch')
            ->from($this->table)
            ->orderBy('batch')
            ->orderBy('migration')
            ->execute($this->db())
            ->asArray('migration', 'batch');
    }

    /**
     * @return array Name => file path of migrations that have not run
     */
    public function pending(): array
    {
        return array_diff_key($this->files(), $this->ran());
    }

    /**
     * Runs every pending migration as one batch.
     *
     * @param callable|null $notify Called with each migration name before it runs
     * @return string[] Names of the migrations that ran
     */
    public function migrate(?callable $notify = null): array
    {
        $pending = $this->pending();

        if (!$pending) {
            return [];
        }

        $batch = (int) max([0, ...array_values($this->ran())]) + 1;

        foreach ($pending as $name => $path) {
            $notify && $notify($name);

            $this->runMigration($path, 'up');

            DB::insert($this->table, ['migration', 'batch'])->values([$name, $batch])->execute($this->db());
        }

        return array_keys($pending);
    }

    /**
     * Reverts migrations: the last batch by default, the last $steps
     * migrations when $steps > 0, or all of them when $all is true.
     *
     * @param callable|null $notify Called with each migration name before it is reverted
     * @return string[] Names of the reverted migrations
     */
    public function rollback(int $steps = 0, bool $all = false, ?callable $notify = null): array
    {
        $ran = $this->ran();

        if (!$ran) {
            return [];
        }

        if ($all) {
            $names = array_keys($ran);
        } elseif ($steps > 0) {
            $names = array_slice(array_keys($ran), -$steps);
        } else {
            $last = max($ran);
            $names = array_keys(array_filter($ran, fn ($batch) => (int) $batch === (int) $last));
        }

        $files = $this->files();
        $reverted = [];

        foreach (array_reverse($names) as $name) {
            if (!isset($files[$name])) {
                throw new DatabaseException('Migration :name ran but its file is missing', [':name' => $name]);
            }

            $notify && $notify($name);

            $this->runMigration($files[$name], 'down');

            DB::delete($this->table)->where('migration', '=', $name)->execute($this->db());

            $reverted[] = $name;
        }

        return $reverted;
    }

    /**
     * @return array Name => batch number, or null when pending; files that ran but no longer exist are included
     */
    public function status(): array
    {
        $ran = $this->ran();
        $status = [];

        foreach (array_keys($this->files() + $ran) as $name) {
            $status[$name] = isset($ran[$name]) ? (int) $ran[$name] : null;
        }

        ksort($status);

        return $status;
    }

    /**
     * Loads a migration file and runs its up() or down() method. On
     * PostgreSQL, where DDL is transactional, it runs in a transaction.
     */
    protected function runMigration(string $path, string $method): void
    {
        $migration = require $path;

        if (!$migration instanceof Migration) {
            throw new DatabaseException('Migration :file must return an instance of :class', [':file' => $path, ':class' => Migration::class]);
        }

        $previous = Schema::$connection;
        Schema::$connection = $migration->getConnection() ?? $this->connection;
        $db = Connection::instance(Schema::$connection);
        $transaction = $db instanceof PgsqlConnection;

        try {
            $transaction && $db->begin();
            $migration->$method();
            $transaction && $db->commit();
        } catch (\Throwable $e) {
            $transaction && $db->rollback();

            throw $e;
        } finally {
            Schema::$connection = $previous;
        }
    }
}
