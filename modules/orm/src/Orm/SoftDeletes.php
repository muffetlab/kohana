<?php

namespace Kohana\Orm;

use Kohana\Database\DB;

/**
 * Soft deletes for a model: delete() fills the deleted_at column and queries
 * leave those rows out.
 *
 *     class Article extends Model
 *     {
 *         use SoftDeletes;
 *     }
 *
 *     Article::query()->withTrashed()->findAll();
 *
 * @package    Kohana/ORM
 */
trait SoftDeletes
{
    public function usesSoftDeletes(): bool
    {
        return true;
    }

    /**
     * Includes soft deleted rows in the next query.
     */
    public function withTrashed(): static
    {
        $this->trashedScope = 'with';

        return $this;
    }

    /**
     * Returns only soft deleted rows in the next query.
     */
    public function onlyTrashed(): static
    {
        $this->trashedScope = 'only';

        return $this;
    }

    /**
     * Whether this model has been soft deleted.
     */
    public function trashed(): bool
    {
        return ($this->object[static::DELETED_AT] ?? null) !== null;
    }

    /**
     * Clears the soft delete column.
     */
    public function restore(): bool
    {
        if (!$this->loaded() || !$this->fireEvent('restoring')) {
            return false;
        }

        DB::update($this->tableName)
            ->set([static::DELETED_AT => null])
            ->where($this->primaryKey, '=', $this->pk())
            ->execute($this->db);

        $this->object[static::DELETED_AT] = $this->originalValues[static::DELETED_AT] = null;
        $this->fireEvent('restored');

        return true;
    }

    /**
     * Removes the row from the table.
     */
    public function forceDelete(): static
    {
        $this->forceDeleting = true;

        try {
            $this->delete();
        } finally {
            $this->forceDeleting = false;
        }

        return $this;
    }
}
