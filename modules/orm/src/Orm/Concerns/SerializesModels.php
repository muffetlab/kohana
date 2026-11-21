<?php

namespace Kohana\Orm\Concerns;

use Kohana\Database\Connection;
use Kohana\Orm\Model;

/**
 * PHP serialization of a model and its string form.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait SerializesModels
{
    /**
     * Displays the primary key of a model when it is converted to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->pk();
    }

    /**
     * Allows serialization of only the object data and state, to prevent
     * "stale" objects being unserialized, which also requires less memory.
     *
     * @return array
     */
    public function __serialize(): array
    {
        // Store only information about the object
        foreach (['primaryKeyValue', 'object', 'changes', 'loaded', 'saved', 'sorting', 'originalValues'] as $var) {
            $data[$var] = $this->{$var};
        }

        return $data;
    }

    /**
     * Prepares the database connection and reloads the object.
     *
     * @param array $data Serialized object state
     * @return  void
     * @throws \Kohana\Foundation\KohanaException
     */
    public function __unserialize(array $data): void
    {
        // Initialize model
        $this->initialize();

        foreach ($data as $name => $var) {
            $this->{$name} = $var;
        }

        if ($this->reloadOnWakeup === true) {
            // Reload the object
            $this->reload();
        }
    }
}
