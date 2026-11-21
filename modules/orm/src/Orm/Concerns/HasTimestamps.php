<?php

namespace Kohana\Orm\Concerns;

/**
 * Fills created_at and updated_at.
 *
 *     protected $timestamps = true;
 *
 * The columns match $table->timestamps() of the schema builder. Override
 * CREATED_AT / UPDATED_AT to use other names; set one of them to null to skip it.
 * The older $createdColumn / $updatedColumn settings still work.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait HasTimestamps
{
    /**
     * @var bool Whether create() and update() fill the timestamp columns
     */
    protected $timestamps = false;

    public function usesTimestamps(): bool
    {
        return $this->timestamps;
    }

    /**
     * Adds the timestamp columns to the data of an INSERT or UPDATE.
     *
     * @param array $data Column => value, changed in place
     * @param bool $creating Whether the record is being inserted
     */
    protected function applyTimestamps(array &$data, bool $creating): void
    {
        if (!$this->timestamps) {
            return;
        }

        $now = date($this->dateFormat);

        if ($creating && static::CREATED_AT !== null && !isset($data[static::CREATED_AT])) {
            $data[static::CREATED_AT] = $this->object[static::CREATED_AT] = $now;
        }

        if (static::UPDATED_AT !== null && !isset($this->changes[static::UPDATED_AT])) {
            $data[static::UPDATED_AT] = $this->object[static::UPDATED_AT] = $now;
        }
    }

    /**
     * Updates only the updated_at column.
     */
    public function touch(): bool
    {
        if (!$this->timestamps || static::UPDATED_AT === null || !$this->loaded) {
            return false;
        }

        $this->object[static::UPDATED_AT] = date($this->dateFormat);
        $this->changes[static::UPDATED_AT] = $this->object[static::UPDATED_AT];

        $this->update();

        return true;
    }
}
