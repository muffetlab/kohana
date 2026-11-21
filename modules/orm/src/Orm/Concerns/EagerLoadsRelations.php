<?php

namespace Kohana\Orm\Concerns;

use Kohana\Database\CachedResult;
use Kohana\Database\DB;
use Kohana\Orm\Model;

/**
 * Eager loading of has_many relationships: `with('comments')` or nested
 * `with('comments.author')` loads them with one query per relationship.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait EagerLoadsRelations
{
    /**
     * @var array<string, true> has_many paths to load with the next findAll()/find()
     */
    protected $eagerLoad = [];

    /**
     * @var array<string, CachedResult> has_many results loaded in advance
     */
    protected $eagerLoaded = [];

    /**
     * @var array<int, Model>|null Preloaded records a has_many query returns
     */
    protected $preloaded = null;

    /**
     * @var int Pending query calls when the records were preloaded
     */
    protected $preloadedPending = 0;

    /**
     * Whether the first segment of a with() path is a has_many relationship.
     */
    protected function isEagerPath(string $path): bool
    {
        return isset($this->hasMany[explode('.', $path, 2)[0]]);
    }

    /**
     * Loads the queued has_many paths into the given models.
     *
     * @param array<int, Model> $models
     * @param array<int, string> $paths
     */
    protected function eagerLoadRelations(array $models, array $paths): void
    {
        $nested = [];
        foreach ($paths as $path) {
            $parts = explode('.', $path, 2);
            $nested[$parts[0]] ??= [];
            if (isset($parts[1])) {
                $nested[$parts[0]][] = $parts[1];
            }
        }

        foreach ($nested as $alias => $children) {
            $loaded = $this->eagerLoadRelation($models, $alias);
            if ($children && $loaded) {
                $loaded[0]->eagerLoadRelations($loaded, $children);
            }
        }
    }

    /**
     * Loads one has_many relationship for every model with a single query.
     *
     * @param array<int, Model> $models
     * @return array<int, Model> Every loaded related model
     */
    protected function eagerLoadRelation(array $models, string $alias): array
    {
        if (!$models || !isset($this->hasMany[$alias])) {
            return [];
        }

        $relation = $this->hasMany[$alias];
        $keys = array_values(array_unique(array_filter(array_map(fn (Model $model) => $model->pk(), $models), fn ($key) => $key !== null)));
        $groups = [];

        if ($keys) {
            $target = static::factory($relation['model']);
            $query = DB::select($target->objectName . '.*')->from([$target->tableName, $target->objectName]);

            if (isset($relation['through'])) {
                // The pivot gets its own alias in case it shares the target's name
                $owner = '__eager_pivot.' . $relation['foreign_key'];
                $query->select([$owner, '__eager_owner'])
                    ->join([$relation['through'], '__eager_pivot'])
                    ->on('__eager_pivot.' . $relation['far_key'], '=', $target->objectName . '.' . $target->primaryKey);
            } else {
                $owner = $target->objectName . '.' . $relation['foreign_key'];
            }

            if ($target->usesSoftDeletes()) {
                $query->where($target->qualifiedDeletedAtColumn(), 'IS', null);
            }

            foreach ($target->sorting ?: [] as $column => $direction) {
                $query->orderBy(str_contains($column, '.') ? $column : $target->objectName . '.' . $column, $direction);
            }

            foreach ($query->where($owner, 'IN', $keys)->asAssoc()->execute($target->db) as $row) {
                $ownerKey = isset($relation['through']) ? $row['__eager_owner'] : $row[$relation['foreign_key']];
                unset($row['__eager_owner']);

                $groups[(string) $ownerKey][] = static::factory($relation['model'])->loadValues($row);
            }
        }

        foreach ($models as $model) {
            $model->eagerLoaded[$alias] = new CachedResult($groups[(string) $model->pk()] ?? [], '', $relation['model']);
        }

        return $groups ? array_merge(...array_values($groups)) : [];
    }

    /**
     * Marks a has_many query model with records that were loaded in advance.
     */
    protected function preload(CachedResult $records): self
    {
        $this->preloaded = iterator_to_array($records, false);
        $this->preloadedPending = count($this->dbPending);

        return $this;
    }

    /**
     * Whether the preloaded records answer the query, which is true while no
     * condition was added after the relationship was read.
     */
    protected function usePreloaded(): bool
    {
        return $this->preloaded !== null && count($this->dbPending) === $this->preloadedPending;
    }

    /**
     * The has_many relationships that were loaded in advance.
     *
     * @return array<string, CachedResult>
     */
    public function eagerLoaded(): array
    {
        return $this->eagerLoaded;
    }
}
