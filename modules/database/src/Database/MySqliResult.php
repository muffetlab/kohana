<?php

namespace Kohana\Database;

/**
 * MySQLi database result.   See [Results](/database/results) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query/Result
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    https://kohana.top/license
 */
class MySqliResult extends Result
{
    protected $internalRow = 0;

    public function __construct($result, $sql, $as_object = false, ?array $params = null)
    {
        parent::__construct($result, $sql, $as_object, $params);

        // Find the number of rows in the result
        $this->totalRows = $result->num_rows;
    }

    public function __destruct()
    {
        if (is_resource($this->result)) {
            $this->result->free();
        }
    }

    #[\ReturnTypeWillChange]
    public function seek($offset): bool
    {
        if ($this->offsetExists($offset) && $this->result->data_seek($offset)) {
            // Set the current row to the offset
            $this->currentRow = $this->internalRow = $offset;

            return true;
        } else {
            return false;
        }
    }

    public function current(): mixed
    {
        if ($this->currentRow !== $this->internalRow && !$this->seek($this->currentRow))
            return null;

        // Increment internal row for optimization assuming rows are fetched in order
        $this->internalRow++;

        if ($this->asObject === true) {
            // Return an stdClass
            return $this->result->fetch_object();
        } elseif (is_string($this->asObject)) {
            // Return an object of given class name
            return $this->result->fetch_object($this->asObject, (array) $this->objectParams);
        } else {
            // Return an array of the row
            return $this->result->fetch_assoc();
        }
    }

}
