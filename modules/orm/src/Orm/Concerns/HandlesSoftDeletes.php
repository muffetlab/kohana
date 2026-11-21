<?php

namespace Kohana\Orm\Concerns;

use Kohana\Database\DB;

/**
 * The parts of soft deleting every model has. A model turns soft deletes on
 * with the Kohana\Orm\SoftDeletes trait.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait HandlesSoftDeletes
{
    /**
     * @var bool Whether delete() removes the row even with soft deletes
     */
    protected $forceDeleting = false;

    /**
     * @var string Which rows the next query returns: without, with or only trashed
     */
    protected $trashedScope = 'without';

    public function usesSoftDeletes(): bool
    {
        return false;
    }

    /**
     * The soft delete column with the table alias.
     */
    public function qualifiedDeletedAtColumn(): string
    {
        return $this->objectName . '.' . static::DELETED_AT;
    }

    /**
     * Applies the trashed scope to the SELECT being built, keeping the other
     * conditions in parentheses so an orWhere() cannot bypass it.
     *
     * @param callable(): void $applyPending Adds the pending conditions
     */
    protected function applySoftDeleteScope(callable $applyPending): void
    {
        $scope = $this->trashedScope;
        $this->trashedScope = 'without';

        if (!$this->usesSoftDeletes() || $scope === 'with') {
            $applyPending();

            return;
        }

        $this->dbBuilder->where($this->qualifiedDeletedAtColumn(), $scope === 'only' ? 'IS NOT' : 'IS', null);

        $wrap = (bool) array_filter($this->dbPending, fn (array $method) => stripos($method['name'], 'where') !== false);
        $wrap && $this->dbBuilder->whereOpen();
        $applyPending();
        $wrap && $this->dbBuilder->whereClose();
    }

    /**
     * Sets the soft delete column instead of removing the row.
     */
    protected function performSoftDelete(): void
    {
        $now = date($this->dateFormat);

        DB::update($this->tableName)
            ->set([static::DELETED_AT => $now])
            ->where($this->primaryKey, '=', $this->pk())
            ->execute($this->db);

        $this->object[static::DELETED_AT] = $this->originalValues[static::DELETED_AT] = $now;
    }
}
