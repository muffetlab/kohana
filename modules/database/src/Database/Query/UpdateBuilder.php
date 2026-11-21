<?php

namespace Kohana\Database\Query;

use Kohana\Database\Connection;

/**
 * Database query builder for UPDATE statements. See [Query Builder](/database/query/builder) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    https://kohana.top/license
 */
class UpdateBuilder extends WhereBuilder
{
    // UPDATE ...
    protected $table;
    // SET ...
    protected $set = [];

    /**
     * Set the table for an update.
     *
     * @param   mixed  $table  table name or [$table, $alias] or object
     * @return  void
     */
    public function __construct($table = null)
    {
        if ($table) {
            // Set the initial table name
            $this->table = $table;
        }

        // Start the query with no SQL
        parent::__construct(Connection::UPDATE, '');
    }

    /**
     * Sets the table to update.
     *
     * @param   mixed  $table  table name or [$table, $alias] or object
     * @return  $this
     */
    public function table($table): \Kohana\Database\Query\UpdateBuilder
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Set the values to update with an associative array.
     *
     * @param   array   $pairs  associative (column => value) list
     * @return  $this
     */
    public function set(array $pairs): \Kohana\Database\Query\UpdateBuilder
    {
        foreach ($pairs as $column => $value) {
            $this->set[] = [$column, $value];
        }

        return $this;
    }

    /**
     * Set the value of a single column.
     *
     * @param   mixed  $column  table name or [$table, $alias] or object
     * @param   mixed  $value   column value
     * @return  $this
     */
    public function value($column, $value): \Kohana\Database\Query\UpdateBuilder
    {
        $this->set[] = [$column, $value];

        return $this;
    }

    /**
     * Compile the SQL query and return it.
     *
     * @param mixed $db Database instance or name of instance
     * @return  string
     * @throws Database_Exception
     * @throws Kohana_Exception
     */
    public function compile($db = null): string
    {
        if (!is_object($db)) {
            // Get the database instance
            $db = Connection::instance($db);
        }

        // Start an update query
        $query = 'UPDATE ' . $db->quoteTable($this->table);

        // Add the columns to update
        $query .= ' SET ' . $this->compileSet($db, $this->set);

        if (!empty($this->where)) {
            // Add selection conditions
            $query .= ' WHERE ' . $this->compileConditions($db, $this->where);
        }

        if (!empty($this->orderBy)) {
            // Add sorting
            $query .= ' ' . $this->compileOrderBy($db, $this->orderBy);
        }

        if ($this->limit !== null) {
            // Add limiting
            $query .= ' LIMIT ' . $this->limit;
        }

        $this->sql = $query;

        return parent::compile($db);
    }

    /**
     * @return $this
     * @deprecated 3.5.0
     */
    public function reset(): \Kohana\Database\Query\UpdateBuilder
    {
        $this->table = null;

        $this->set = $this->where = [];

        $this->limit = null;

        $this->parameters = [];

        $this->sql = null;

        return $this;
    }

}
