<?php

namespace Kohana\Database\Query;

use Kohana\Database\Connection;
use Kohana\Database\DB;
use Kohana\Foundation\KohanaException;

/**
 * Database query builder for SELECT statements. See [Query Builder](/database/query/builder) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    https://kohana.top/license
 */
class SelectBuilder extends WhereBuilder
{
    // SELECT ...
    protected $select = [];
    // DISTINCT
    protected $distinct = false;
    // FROM ...
    protected $from = [];
    // JOIN ...
    protected $join = [];
    // GROUP BY ...
    protected $groupBy = [];
    // HAVING ...
    protected $having = [];
    // OFFSET ...
    protected $offset = null;
    // UNION ...
    protected $union = [];
    // The last JOIN statement created
    protected $lastJoin;

    /**
     * Sets the initial columns to select from.
     *
     * @param array|null $columns column list
     */
    public function __construct(?array $columns = null)
    {
        if (!empty($columns)) {
            // Set the initial columns
            $this->select = $columns;
        }

        // Start the query with no actual SQL statement
        parent::__construct(Connection::SELECT, '');
    }

    /**
     * Enables or disables selecting only unique columns using "SELECT DISTINCT"
     *
     * @param bool $value enable or disable distinct columns
     * @return  $this
     */
    public function distinct(bool $value): self
    {
        $this->distinct = $value;

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
        $this->select = array_merge($this->select, $columns);

        return $this;
    }

    /**
     * Choose the columns to select from, using an array.
     *
     * @param   array  $columns  list of column names or aliases
     * @return  $this
     */
    public function selectArray(array $columns): self
    {
        $this->select = array_merge($this->select, $columns);

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
        $this->from = array_merge($this->from, $tables);

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
        $this->join[] = $this->lastJoin = new JoinClause($table, $type);

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
        $this->lastJoin->on($c1, $op, $c2);

        return $this;
    }

    /**
     * Adds "USING ..." conditions for the last created JOIN statement.
     *
     * @param string ...$columns column name
     * @return  $this
     */
    public function using(...$columns): self
    {
        call_user_func_array([$this->lastJoin, 'using'], $columns);

        return $this;
    }

    /**
     * Creates a "GROUP BY ..." filter.
     *
     * @param mixed ...$columns column name or [$column, $alias] or object
     * @return  $this
     */
    public function groupBy(...$columns): self
    {
        $this->groupBy = array_merge($this->groupBy, $columns);

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
        $this->having[] = ['AND' => [$column, $op, $value]];

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
        $this->having[] = ['OR' => [$column, $op, $value]];

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
        $this->having[] = ['AND' => '('];

        return $this;
    }

    /**
     * Opens a new "OR HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function orHavingOpen(): self
    {
        $this->having[] = ['OR' => '('];

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
        $this->having[] = ['AND' => ')'];

        return $this;
    }

    /**
     * Closes an open "OR HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function orHavingClose(): self
    {
        $this->having[] = ['OR' => ')'];

        return $this;
    }

    /**
     * Adds a UNION clause.
     *
     * @param mixed $select If a string, it must be the name of a table.
     * Otherwise, it must be an instance of Database_Query_Builder_Select.
     * @param bool $all Determines if it's a UNION or UNION ALL clause.
     * @return $this
     * @throws \Kohana\Foundation\KohanaException
     */
    public function union($select, bool $all = true): self
    {
        if (is_string($select)) {
            $select = DB::select()->from($select);
        }
        if (!$select instanceof self) {
            throw new KohanaException('first parameter must be a string or an instance of Database_Query_Builder_Select');
        }
        $this->union [] = ['select' => $select, 'all' => $all];
        return $this;
    }

    /**
     * Start returning results after "OFFSET ..."
     *
     * @param int|null $number Starting result number or null to reset
     * @return  $this
     */
    public function offset(?int $number): self
    {
        $this->offset = $number;

        return $this;
    }

    /**
     * Compile the SQL query and return it.
     *
     * @param mixed $db Database instance or name of instance
     * @return  string
     * @throws \Kohana\Database\DatabaseException
     * @throws \Kohana\Foundation\KohanaException
     */
    public function compile($db = null): string
    {
        if (!is_object($db)) {
            // Get the database instance
            $db = Connection::instance($db);
        }

        // Callback to quote columns
        $quote_column = [$db, 'quoteColumn'];

        // Callback to quote tables
        $quote_table = [$db, 'quoteTable'];

        // Start a selection query
        $query = 'SELECT ';

        if ($this->distinct === true) {
            // Select only unique results
            $query .= 'DISTINCT ';
        }

        if (empty($this->select)) {
            // Select all columns
            $query .= '*';
        } else {
            // Select all columns
            $query .= implode(', ', array_unique(array_map($quote_column, $this->select)));
        }

        if (!empty($this->from)) {
            // Set tables to select from
            $query .= ' FROM ' . implode(', ', array_unique(array_map($quote_table, $this->from)));
        }

        if (!empty($this->join)) {
            // Add tables to join
            $query .= ' ' . $this->compileJoin($db, $this->join);
        }

        if (!empty($this->where)) {
            // Add selection conditions
            $query .= ' WHERE ' . $this->compileConditions($db, $this->where);
        }

        if (!empty($this->groupBy)) {
            // Add grouping
            $query .= ' ' . $this->compileGroupBy($db, $this->groupBy);
        }

        if (!empty($this->having)) {
            // Add filtering conditions
            $query .= ' HAVING ' . $this->compileConditions($db, $this->having);
        }

        if (!empty($this->orderBy)) {
            // Add sorting
            $query .= ' ' . $this->compileOrderBy($db, $this->orderBy);
        }

        if ($this->limit !== null) {
            // Add limiting
            $query .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            // Add offsets
            $query .= ' OFFSET ' . $this->offset;
        }

        if (!empty($this->union)) {
            $query = '(' . $query . ')';
            foreach ($this->union as $u) {
                $query .= ' UNION ';
                if ($u['all'] === true) {
                    $query .= 'ALL ';
                }
                $query .= '(' . $u['select']->compile($db) . ')';
            }
        }

        $this->sql = $query;

        return parent::compile($db);
    }

    /**
     * @return $this
     * @deprecated 3.5.0
     */
    public function reset(): self
    {
        $this->select = $this->from = $this->join = $this->where = $this->groupBy = $this->having = $this->orderBy = $this->union = [];

        $this->distinct = false;

        $this->limit = $this->offset = $this->lastJoin = null;

        $this->parameters = [];

        $this->sql = null;

        return $this;
    }

}
