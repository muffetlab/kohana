<?php

namespace Kohana\Database\Query;

use Kohana\Database\Connection;

/**
 * Database query builder for DELETE statements. See [Query Builder](/database/query/builder) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    https://kohana.top/license
 */
class DeleteBuilder extends WhereBuilder
{
    // DELETE FROM ...
    protected $table;

    /**
     * Set the table for a deletion.
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
        parent::__construct(Connection::DELETE, '');
    }

    /**
     * Sets the table to delete from.
     *
     * @param   mixed  $table  table name or [$table, $alias] or object
     * @return  $this
     */
    public function table($table): \Kohana\Database\Query\DeleteBuilder
    {
        $this->table = $table;

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

        // Start a deletion query
        $query = 'DELETE FROM ' . $db->quoteTable($this->table);

        if (!empty($this->where)) {
            // Add deletion conditions
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
    public function reset(): \Kohana\Database\Query\DeleteBuilder
    {
        $this->table = null;
        $this->where = [];

        $this->parameters = [];

        $this->sql = null;

        return $this;
    }

}
