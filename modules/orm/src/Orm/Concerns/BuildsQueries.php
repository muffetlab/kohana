<?php

namespace Kohana\Orm\Concerns;

use Kohana\Database\Connection;
use Kohana\Database\DB;
use Kohana\Orm\Model;

/**
 * Query builder methods that are applied when the model runs a query.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait BuildsQueries
{
    /**
     * Initializes the Database Builder to given query type
     *
     * @param int $type Type of Database query
     * @return \Kohana\Orm\Model
     */
    protected function build(int $type): self
    {
        // Construct new builder object based on query type
        switch ($type) {
            case Connection::SELECT:
                $this->dbBuilder = DB::select();
                break;
            case Connection::UPDATE:
                $this->dbBuilder = DB::update($this->tableName);
                break;
            case Connection::DELETE:
                // Cannot use an alias for DELETE queries
                $this->dbBuilder = DB::delete($this->tableName);
        }

        // Process pending database method calls
        $applyPending = function (): void {
            foreach ($this->dbPending as $method) {
                $name = $method['name'];
                $args = $method['args'];

                $this->dbApplied[$name] = $name;

                call_user_func_array([$this->dbBuilder, $name], $args);
            }
        };

        $type === Connection::SELECT ? $this->applySoftDeleteScope($applyPending) : $applyPending();

        return $this;
    }

    /**
     * Returns last executed query
     *
     * @return string
     */
    public function lastQuery(): string
    {
        return (string) $this->db->lastQuery;
    }

    /**
     * Clears query builder.  Passing false is useful to keep the existing
     * query conditions for another query.
     *
     * @param bool $next Pass false to avoid resetting on the next call
     * @return \Kohana\Orm\Model
     */
    public function reset(bool $next = true): self
    {
        if ($next && $this->dbReset) {
            $this->dbPending = [];
            $this->dbApplied = [];
            $this->dbBuilder = null;
            $this->withApplied = [];
        }

        // Reset on the next call?
        $this->dbReset = $next;

        return $this;
    }

    /**
     * Alias of and_where()
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function where($column, string $op, $value): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'where',
            'args' => [$column, $op, $value],
        ];

        return $this;
    }

    /**
     * Creates a new "AND WHERE" condition for the query.
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function andWhere($column, string $op, $value): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'andWhere',
            'args' => [$column, $op, $value],
        ];

        return $this;
    }

    /**
     * Creates a new "OR WHERE" condition for the query.
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function orWhere($column, string $op, $value): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orWhere',
            'args' => [$column, $op, $value],
        ];

        return $this;
    }

    /**
     * Alias of and_where_open()
     *
     * @return  $this
     */
    public function whereOpen(): self
    {
        return $this->andWhereOpen();
    }

    /**
     * Opens a new "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function andWhereOpen(): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'andWhereOpen',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Opens a new "OR WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function orWhereOpen(): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orWhereOpen',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Closes an open "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function whereClose(): self
    {
        return $this->andWhereClose();
    }

    /**
     * Closes an open "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function andWhereClose(): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'andWhereClose',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Closes an open "OR WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function orWhereClose(): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orWhereClose',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Applies sorting with "ORDER BY ..."
     *
     * @param   mixed   $column     column name or [$column, $alias] or object
     * @param string|null $direction Direction of sorting
     * @return  $this
     */
    public function orderBy($column, ?string $direction = null): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orderBy',
            'args' => [$column, $direction],
        ];

        return $this;
    }

    /**
     * Return up to "LIMIT ..." results
     *
     * @param int $number Maximum results to return
     * @return  $this
     */
    public function limit(int $number): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'limit',
            'args' => [$number],
        ];

        return $this;
    }

    /**
     * Enables or disables selecting only unique columns using "SELECT DISTINCT"
     *
     * @param bool $value enable or disable distinct columns
     * @return  $this
     */
    public function distinct(bool $value): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'distinct',
            'args' => [$value],
        ];

        return $this;
    }

    /**
     * Choose the columns to select from.
     *
     * @param mixed ...$columns column name or [$column, $alias] or object
     * @return  $this
     */
    public function select(...$columns): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'select',
            'args' => $columns,
        ];

        return $this;
    }

    /**
     * Choose the tables to select "FROM ..."
     *
     * @param mixed ...$tables table name or [$table, $alias] or object
     * @return  $this
     */
    public function from(...$tables): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'from',
            'args' => $tables,
        ];

        return $this;
    }

    /**
     * Adds addition tables to "JOIN ...".
     *
     * @param   mixed   $table  column name or [$column, $alias] or object
     * @param string|null $type Join type (LEFT, RIGHT, INNER, etc.)
     * @return  $this
     */
    public function join($table, ?string $type = null): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'join',
            'args' => [$table, $type],
        ];

        return $this;
    }

    /**
     * Adds "ON ..." conditions for the last created JOIN statement.
     *
     * @param   mixed   $c1  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $c2  column name or [$column, $alias] or object
     * @return  $this
     */
    public function on($c1, string $op, $c2): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'on',
            'args' => [$c1, $op, $c2],
        ];

        return $this;
    }

    /**
     * Creates a "GROUP BY ..." filter.
     *
     * @param mixed ...$columns column name or [$column, $alias] or object
     * @param   ...
     * @return  $this
     */
    public function groupBy(...$columns): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'groupBy',
            'args' => $columns,
        ];

        return $this;
    }

    /**
     * Alias of and_having()
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function having($column, string $op, $value = null): self
    {
        return $this->andHaving($column, $op, $value);
    }

    /**
     * Creates a new "AND HAVING" condition for the query.
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function andHaving($column, string $op, $value = null): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'andHaving',
            'args' => [$column, $op, $value],
        ];

        return $this;
    }

    /**
     * Creates a new "OR HAVING" condition for the query.
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function orHaving($column, string $op, $value = null): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orHaving',
            'args' => [$column, $op, $value],
        ];

        return $this;
    }

    /**
     * Alias of and_having_open()
     *
     * @return  $this
     */
    public function havingOpen(): self
    {
        return $this->andHavingOpen();
    }

    /**
     * Opens a new "AND HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function andHavingOpen(): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'andHavingOpen',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Opens a new "OR HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function orHavingOpen(): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orHavingOpen',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Closes an open "AND HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function havingClose(): self
    {
        return $this->andHavingClose();
    }

    /**
     * Closes an open "AND HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function andHavingClose(): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'andHavingClose',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Closes an open "OR HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function orHavingClose(): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orHavingClose',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Start returning results after "OFFSET ..."
     *
     * @param int $number Starting result number
     * @return  $this
     */
    public function offset(int $number): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'offset',
            'args' => [$number],
        ];

        return $this;
    }

    /**
     * Enables the query to be cached for a specified amount of time.
     *
     * @param int|null $lifetime Number of seconds to cache
     * @return  $this
     * @uses    \Kohana\Foundation\Application::$cache_life
     */
    public function cached(?int $lifetime = null): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'cached',
            'args' => [$lifetime],
        ];

        return $this;
    }

    /**
     * Set the value of a parameter in the query.
     *
     * @param string $param Parameter key to replace
     * @param   mixed    $value  value to use
     * @return  $this
     */
    public function param(string $param, $value): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'param',
            'args' => [$param, $value],
        ];

        return $this;
    }

    /**
     * Adds "USING ..." conditions for the last created JOIN statement.
     *
     * @param string $columns Column name
     * @return  $this
     */
    public function using(string $columns): self
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'using',
            'args' => [$columns],
        ];

        return $this;
    }
}
