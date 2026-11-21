<?php

namespace Kohana\Database;

use Kohana\Foundation\KohanaException;

/**
 * Database result wrapper.  See [Results](/database/results) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query/Result
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class Result implements \Countable, \Iterator, \SeekableIterator, \ArrayAccess
{
    // Executed SQL for this result
    protected $query;
    // Raw result resource
    protected $result;
    // Total number of rows and current row
    protected $totalRows = 0;
    protected $currentRow = 0;
    // Return rows as an object or associative array
    protected $asObject;
    // Parameters for __construct when using object results
    protected $objectParams = null;

    /**
     * Sets the total number of rows and stores the result locally.
     *
     * @param mixed $result query result
     * @param string $sql SQL query
     * @param mixed $as_object
     * @param array|null $params
     */
    public function __construct($result, string $sql, $as_object = false, ?array $params = null)
    {
        // Store the result locally
        $this->result = $result;

        // Store the SQL locally
        $this->query = $sql;

        if (is_object($as_object)) {
            // Get the object class name
            $as_object = get_class($as_object);
        }

        // Results as objects or associative arrays
        $this->asObject = $as_object;

        if ($params) {
            // Object constructor params
            $this->objectParams = $params;
        }
    }

    /**
     * Result destruction cleans up all open result sets.
     *
     * @return  void
     */
    abstract public function __destruct();
    /**
     * Get a cached database result from the current result iterator.
     *
     *     $cachable = serialize($result->cached());
     *
     * @return  \Kohana\Database\CachedResult
     * @since   3.0.5
     */
    public function cached(): self
    {
        return new CachedResult($this->asArray(), $this->query, $this->asObject);
    }

    /**
     * Return all the rows in the result as an array.
     *
     *     // Indexed array of all rows
     *     $rows = $result->as_array();
     *
     *     // Associative array of rows by "id"
     *     $rows = $result->as_array('id');
     *
     *     // Associative array of rows, "id" => "name"
     *     $rows = $result->as_array('id', 'name');
     *
     * @param string|null $key Column for associative keys
     * @param string|null $value Column for values
     * @return  array
     */
    public function asArray(?string $key = null, ?string $value = null): array
    {
        $results = [];

        if ($key === null && $value === null) {
            // Indexed rows

            foreach ($this as $row) {
                $results[] = $row;
            }
        } elseif ($key === null) {
            // Indexed columns

            if ($this->asObject) {
                foreach ($this as $row) {
                    $results[] = $row->$value;
                }
            } else {
                foreach ($this as $row) {
                    $results[] = $row[$value];
                }
            }
        } elseif ($value === null) {
            // Associative rows

            if ($this->asObject) {
                foreach ($this as $row) {
                    $results[$row->$key] = $row;
                }
            } else {
                foreach ($this as $row) {
                    $results[$row[$key]] = $row;
                }
            }
        } else {
            // Associative columns

            if ($this->asObject) {
                foreach ($this as $row) {
                    $results[$row->$key] = $row->$value;
                }
            } else {
                foreach ($this as $row) {
                    $results[$row[$key]] = $row[$value];
                }
            }
        }

        $this->rewind();

        return $results;
    }

    /**
     * Return the named column from the current row.
     *
     *     // Get the "id" value
     *     $id = $result->get('id');
     *
     * @param string $name Column to get
     * @param   mixed   $default  default value if the column does not exist
     * @return  mixed
     */
    public function get(string $name, $default = null)
    {
        $row = $this->current();

        if ($this->asObject) {
            if (isset($row->$name)) {
                return $row->$name;
            }
        } else {
            if (isset($row[$name])) {
                return $row[$name];
            }
        }

        return $default;
    }

    /**
     * Implements [Countable::count], returns the total number of rows.
     *
     *     echo count($result);
     *
     * @return int
     */
    public function count(): int
    {
        return $this->totalRows;
    }

    /**
     * Implements [ArrayAccess::offsetExists], determines if row exists.
     *
     *     if (isset($result[10]))
     *     {
     *         // Row 10 exists
     *     }
     *
     * @param   int     $offset
     * @return  bool
     */
    public function offsetExists($offset): bool
    {
        return $offset >= 0 && $offset < $this->totalRows;
    }

    /**
     * Implements [ArrayAccess::offsetGet], gets a given row.
     *
     *     $row = $result[10];
     *
     * @param   int     $offset
     * @return  mixed
     */
    public function offsetGet($offset): mixed
    {
        if (!$this->seek($offset)) {
            return null;
        }

        return $this->current();
    }

    /**
     * Implements [ArrayAccess::offsetSet], throws an error.
     *
     * [!!] You cannot modify a database result.
     *
     * @param   int     $offset
     * @param   mixed   $value
     * @return  void
     * @throws  \Kohana\Foundation\KohanaException
     */
    final public function offsetSet($offset, $value): void
    {
        throw new KohanaException('Database results are read-only');
    }

    /**
     * Implements [ArrayAccess::offsetUnset], throws an error.
     *
     * [!!] You cannot modify a database result.
     *
     * @param   int     $offset
     * @return  void
     * @throws  \Kohana\Foundation\KohanaException
     */
    final public function offsetUnset($offset): void
    {
        throw new KohanaException('Database results are read-only');
    }

    /**
     * Implements [Iterator::key], returns the current row number.
     *
     *     echo key($result);
     *
     * @return int
     */
    public function key(): int
    {
        return $this->currentRow;
    }

    /**
     * Implements [Iterator::next], moves to the next row.
     *
     *     next($result);
     *
     * @return  $this
     */
    #[\ReturnTypeWillChange]
    public function next(): self
    {
        ++$this->currentRow;
        return $this;
    }

    /**
     * Implements [Iterator::prev], moves to the previous row.
     *
     *     prev($result);
     *
     * @return  $this
     */
    public function prev(): self
    {
        --$this->currentRow;
        return $this;
    }

    /**
     * Implements [Iterator::rewind], sets the current row to zero.
     *
     *     rewind($result);
     *
     * @return  $this
     */
    #[\ReturnTypeWillChange]
    public function rewind(): self
    {
        $this->currentRow = 0;
        return $this;
    }

    /**
     * Implements [Iterator::valid], checks if the current row exists.
     *
     * [!!] This method is only used internally.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->offsetExists($this->currentRow);
    }

}
