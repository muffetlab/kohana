<?php

namespace Kohana\Database;

/**
 * Object used for caching the results of select queries.  See [Results](/database/results#select-cached) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query/Result
 * @author     Kohana Team
 * @copyright  (c) 2009 Kohana Team
 * @license    https://kohana.top/license
 */
class CachedResult extends Result
{
    public function __construct(array $result, $sql, $as_object = null)
    {
        parent::__construct($result, $sql, $as_object);

        // Find the number of rows in the result
        $this->totalRows = count($result);
    }

    public function __destruct()
    {
        // Cached results do not use resources
    }

    public function cached(): Result
    {
        return $this;
    }

    #[\ReturnTypeWillChange]
    public function seek($offset): bool
    {
        if ($this->offsetExists($offset)) {
            $this->currentRow = $offset;

            return true;
        } else {
            return false;
        }
    }

    public function current(): mixed
    {
        // Return an array of the row
        return $this->valid() ? $this->result[$this->currentRow] : null;
    }

}
