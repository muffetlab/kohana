<?php

namespace Kohana\Database\Query;

use Kohana\Database\Connection;
use Kohana\Foundation\KohanaException;

/**
 * Database query builder for INSERT statements. See [Query Builder](/database/query/builder) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    https://kohana.top/license
 */
class InsertBuilder extends Builder
{
    // INSERT INTO ...
    protected $table;
    // (...)
    protected $columns = [];
    // VALUES (...)
    protected $values = [];

    /**
     * Set the table and columns for an insert.
     *
     * @param mixed $table table name or [$table, $alias] or object
     * @param array|null $columns column names
     * @throws \Kohana\Foundation\KohanaException
     */
    public function __construct($table = null, ?array $columns = null)
    {
        if ($table) {
            // Set the initial table name
            $this->table($table);
        }

        if ($columns) {
            // Set the column names
            $this->columns = $columns;
        }

        // Start the query with no SQL
        parent::__construct(Connection::INSERT, '');
    }

    /**
     * Sets the table to insert into.
     *
     * @param string $table table name
     * @return  $this
     * @throws \Kohana\Foundation\KohanaException
     */
    public function table(string $table): self
    {
        if (!is_string($table)) {
            throw new KohanaException('INSERT INTO syntax does not allow table aliasing');
        }

        $this->table = $table;

        return $this;
    }

    /**
     * Set the columns that will be inserted.
     *
     * @param   array  $columns  column names
     * @return  $this
     */
    public function columns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Adds or overwrites values. Multiple value sets can be added.
     *
     * @param array ...$values values list
     * @return  $this
     * @throws \Kohana\Foundation\KohanaException
     */
    public function values(array ...$values): self
    {
        if (!is_array($this->values)) {
            throw new KohanaException('INSERT INTO ... SELECT statements cannot be combined with INSERT INTO ... VALUES');
        }

        foreach ($values as $value) {
            $this->values[] = $value;
        }

        return $this;
    }

    /**
     * Use a sub-query to for the inserted values.
     *
     * @param \Kohana\Database\Query\Query $query Database_Query of SELECT type
     * @return  $this
     * @throws \Kohana\Foundation\KohanaException
     */
    public function select(Query $query): self
    {
        if ($query->type() !== Connection::SELECT) {
            throw new KohanaException('Only SELECT queries can be combined with INSERT queries');
        }

        $this->values = $query;

        return $this;
    }

    /**
     * Compile the SQL query and return it.
     *
     * @param mixed $db Database instance or name of instance
     * @return  string
     * @throws \Kohana\Foundation\KohanaException
     */
    public function compile($db = null): string
    {
        if (!is_object($db)) {
            // Get the database instance
            $db = Connection::instance($db);
        }

        // Start an insertion query
        $query = 'INSERT INTO ' . $db->quoteTable($this->table);

        // Add the column names
        $query .= ' (' . implode(', ', array_map([$db, 'quoteColumn'], $this->columns)) . ') ';

        if (is_array($this->values)) {
            $groups = [];
            foreach ($this->values as $group) {
                foreach ($group as $offset => $value) {
                    if ((is_string($value) && array_key_exists($value, $this->parameters)) === false) {
                        // Quote the value, it is not a parameter
                        $group[$offset] = $db->quote($value);
                    }
                }

                $groups[] = '(' . implode(', ', $group) . ')';
            }

            // Add the values
            $query .= 'VALUES ' . implode(', ', $groups);
        } else {
            // Add the sub-query
            $query .= $this->values;
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
        $this->table = null;

        $this->columns = $this->values = [];

        $this->parameters = [];

        $this->sql = null;

        return $this;
    }

}
