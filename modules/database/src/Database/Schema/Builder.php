<?php

namespace Kohana\Database\Schema;

use Kohana\Database\Connection;
use Kohana\Database\DatabaseException;
use Kohana\Database\MySqliConnection;
use Kohana\Database\PdoConnection;
use Kohana\Database\PgsqlConnection;

/**
 * Runs schema changes on one connection. Get one with Schema::connection().
 *
 * @package    Kohana/Database
 * @category   Schema
 */
class Builder
{
    protected Grammar $grammar;

    public function __construct(protected Connection $db)
    {
        $this->grammar = static::grammarFor($db);
    }

    /**
     * Picks the grammar for a connection's database system.
     */
    public static function grammarFor(Connection $db): Grammar
    {
        if ($db instanceof PgsqlConnection) {
            return new PostgresGrammar($db);
        }

        if ($db instanceof MySqliConnection) {
            return new MySqlGrammar($db);
        }

        if ($db instanceof PdoConnection) {
            return match ($db->driver()) {
                'mysql' => new MySqlGrammar($db),
                'pgsql' => new PostgresGrammar($db),
                default => throw new DatabaseException('The schema builder does not support the :driver PDO driver', [':driver' => $db->driver()]),
            };
        }

        throw new DatabaseException('The schema builder does not support :class', [':class' => get_class($db)]);
    }

    public function connection(): Connection
    {
        return $this->db;
    }

    public function grammar(): Grammar
    {
        return $this->grammar;
    }

    /**
     * @param callable(Blueprint): void $callback
     */
    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, true);
        $callback($blueprint);

        $this->run($this->grammar->compile($blueprint));
    }

    /**
     * @param callable(Blueprint): void $callback
     */
    public function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $this->run($this->grammar->compile($blueprint));
    }

    public function drop(string $table): void
    {
        $this->run([$this->grammar->compileDrop($table)]);
    }

    public function dropIfExists(string $table): void
    {
        $this->run([$this->grammar->compileDrop($table, true)]);
    }

    public function rename(string $from, string $to): void
    {
        $this->run([$this->grammar->compileRename($from, $to)]);
    }

    public function hasTable(string $table): bool
    {
        return in_array($this->db->tablePrefix() . $table, $this->db->listTables(), true);
    }

    public function hasColumn(string $table, string $column): bool
    {
        return array_key_exists($column, $this->db->listColumns($table));
    }

    /**
     * Returns the SQL a callback would run on a new table, without running it.
     *
     * @param callable(Blueprint): void $callback
     * @return string[]
     */
    public function toSql(string $table, callable $callback, bool $create = true): array
    {
        $blueprint = new Blueprint($table, $create);
        $callback($blueprint);

        return $this->grammar->compile($blueprint);
    }

    /**
     * @param string[] $statements
     */
    protected function run(array $statements): void
    {
        foreach ($statements as $sql) {
            $this->db->query(Connection::UPDATE, $sql);
        }
    }
}
