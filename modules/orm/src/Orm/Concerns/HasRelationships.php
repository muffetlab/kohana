<?php

namespace Kohana\Orm\Concerns;

use Kohana\Database\DB;
use Kohana\Orm\Model;

/**
 * belongs_to, has_one and has_many relationships.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait HasRelationships
{
    /**
     * Binds another one-to-one object to this model.  One-to-one objects
     * can be nested using 'object1:object2' syntax
     *
     * @param string $target_path Target model to bind to
     * @return \Kohana\Orm\Model
     */
    public function with(string $target_path): self
    {
        if ($this->isEagerPath($target_path)) {
            // has_many paths are loaded with a separate query after the main one
            $this->eagerLoad[$target_path] = true;

            return $this;
        }

        if (isset($this->withApplied[$target_path])) {
            // Don't join anything already joined
            return $this;
        }

        // Split object parts
        $aliases = explode(':', $target_path);
        $target = $this;
        foreach ($aliases as $alias) {
            // Go down the line of objects to find the given target
            $parent = $target;
            $target = $parent->related($alias);

            if (!$target) {
                // Can't find related object
                return $this;
            }
        }

        // Target alias is at the end
        $target_alias = $alias;

        // Pop-off top alias to get the parent path (user:photo:tag becomes user:photo - the parent table prefix)
        array_pop($aliases);
        $parent_path = implode(':', $aliases);

        if (empty($parent_path)) {
            // Use this table name itself for the parent path
            $parent_path = $this->objectName;
        } else {
            if (!isset($this->withApplied[$parent_path])) {
                // If the parent path hasn't been joined yet, do it first (otherwise LEFT JOINs fail)
                $this->with($parent_path);
            }
        }

        // Add to with_applied to prevent duplicate joins
        $this->withApplied[$target_path] = true;

        // Use the keys of the empty object to determine the columns
        foreach (array_keys($target->object) as $column) {
            $name = $target_path . '.' . $column;
            $alias = $target_path . ':' . $column;

            // Add the prefix so that load_result can determine the relationship
            $this->select([$name, $alias]);
        }

        if (isset($parent->belongsTo[$target_alias])) {
            // Parent belongs_to target, use target's primary key and parent's foreign key
            $join_col1 = $target_path . '.' . $target->primaryKey;
            $join_col2 = $parent_path . '.' . $parent->belongsTo[$target_alias]['foreign_key'];
        } else {
            // Parent has_one target, use parent's primary key as target's foreign key
            $join_col1 = $parent_path . '.' . $parent->primaryKey;
            $join_col2 = $target_path . '.' . $parent->hasOne[$target_alias]['foreign_key'];
        }

        // Join the related object into the result
        $this->join([$target->tableName, $target_path], 'LEFT')->on($join_col1, '=', $join_col2);

        return $this;
    }

    /**
     * Tests if this object has a relationship to a different model,
     * or an array of different models. When providing far keys, the number
     * of relations must equal the number of keys.
     *
     *
     *     // Check if $model has the login role
     *     $model->has('roles', ORM::factory('Role', ['name' => 'login']));
     *     // Check for the login role if you know the role id is 5
     *     $model->has('roles', 5);
     *     // Check for all the following roles
     *     $model->has('roles', [1, 2, 3, 4]);
     *     // Check if $model has any roles
     *     $model->has('roles');
     *
     * @param string $alias Alias of the has_many "through" relationship
     * @param mixed $far_keys Related model, primary key, or an array of primary keys
     * @return bool
     * @throws \Kohana\Foundation\KohanaException
     */
    public function has(string $alias, $far_keys = null): bool
    {
        $count = $this->countRelations($alias, $far_keys);
        if ($far_keys === null) {
            return (bool) $count;
        }

        return $count === (is_array($far_keys) ? count($far_keys) : 1);
    }

    /**
     * Tests if this object has a relationship to a different model,
     * or an array of different models. When providing far keys, this function
     * only checks that at least one of the relationships is satisfied.
     *
     *     // Check if $model has the login role
     *     $model->has_any('roles', ORM::factory('Role', ['name' => 'login']));
     *     // Check for the login role if you know the role id is 5
     *     $model->has_any('roles', 5);
     *     // Check for any of the following roles
     *     $model->has_any('roles', [1, 2, 3, 4]);
     *     // Check if $model has any roles
     *     $model->has_any('roles');
     *
     * @param string $alias Alias of the has_many "through" relationship
     * @param mixed $far_keys Related model, primary key, or an array of primary keys
     * @return bool
     * @throws \Kohana\Foundation\KohanaException
     */
    public function hasAny(string $alias, $far_keys = null): bool
    {
        return (bool) $this->countRelations($alias, $far_keys);
    }

    /**
     * Returns the number of relationships
     *
     *     // Counts the number of times the login role is attached to current model
     *     $model->count_relations('roles', ORM::factory('Role', ['name' => 'login']));
     *     // Counts the number of times role 5 is attached to current model
     *     $model->count_relations('roles', 5);
     *     // Counts the number of times any of roles 1, 2, 3, or 4 are attached to current model
     *     $model->count_relations('roles', [1, 2, 3, 4]);
     *     // Counts the number roles attached to current model
     *     $model->count_relations('roles');
     *
     * @param string $alias Alias of the has_many "through" relationship
     * @param mixed $far_keys Related model, primary key, or an array of primary keys
     * @return int
     * @throws \Kohana\Foundation\KohanaException
     */
    public function countRelations(string $alias, $far_keys = null): int
    {
        if ($far_keys === null) {
            return (int) DB::select([DB::expr('COUNT(*)'), 'records_found'])
                    ->from($this->hasMany[$alias]['through'])
                    ->where($this->hasMany[$alias]['foreign_key'], '=', $this->pk())
                    ->execute($this->db)->get('records_found');
        }

        $far_keys = $far_keys instanceof self ? $far_keys->pk() : $far_keys;

        // We need an array to simplify the logic
        $far_keys = (array) $far_keys;

        // Nothing to check if the model isn't loaded, or we don't have any far_keys
        if (!$far_keys || !$this->loaded) {
            return 0;
        }

        // Rows found need to match the rows searched
        return (int) DB::select([DB::expr('COUNT(*)'), 'records_found'])
            ->from($this->hasMany[$alias]['through'])
            ->where($this->hasMany[$alias]['foreign_key'], '=', $this->pk())
            ->where($this->hasMany[$alias]['far_key'], 'IN', $far_keys)
            ->execute($this->db)->get('records_found');
    }

    /**
     * Adds a new relationship to between this model and another.
     *
     *     // Add the login role using a model instance
     *     $model->add('roles', ORM::factory('Role', ['name' => 'login']));
     *     // Add the login role if you know the role id is 5
     *     $model->add('roles', 5);
     *     // Add multiple roles (for example, from checkboxes on a form)
     *     $model->add('roles', [1, 2, 3, 4]);
     *
     * @param string $alias Alias of the has_many "through" relationship
     * @param mixed $far_keys Related model, primary key, or an array of primary keys
     * @return \Kohana\Orm\Model
     * @throws \Kohana\Foundation\KohanaException
     */
    public function add(string $alias, $far_keys): self
    {
        $far_keys = $far_keys instanceof self ? $far_keys->pk() : $far_keys;

        $columns = [$this->hasMany[$alias]['foreign_key'], $this->hasMany[$alias]['far_key']];
        $foreign_key = $this->pk();

        $query = DB::insert($this->hasMany[$alias]['through'], $columns);

        foreach ((array) $far_keys as $key) {
            $query->values([$foreign_key, $key]);
        }

        $query->execute($this->db);

        return $this;
    }

    /**
     * Removes a relationship between this model and another.
     *
     *     // Remove a role using a model instance
     *     $model->remove('roles', ORM::factory('Role', ['name' => 'login']));
     *     // Remove the role knowing the primary key
     *     $model->remove('roles', 5);
     *     // Remove multiple roles (for example, from checkboxes on a form)
     *     $model->remove('roles', [1, 2, 3, 4]);
     *     // Remove all related roles
     *     $model->remove('roles');
     *
     * @param string $alias Alias of the has_many "through" relationship
     * @param mixed $far_keys Related model, primary key, or an array of primary keys
     * @return \Kohana\Orm\Model
     * @throws \Kohana\Foundation\KohanaException
     */
    public function remove(string $alias, $far_keys = null): self
    {
        $far_keys = $far_keys instanceof self ? $far_keys->pk() : $far_keys;

        $query = DB::delete($this->hasMany[$alias]['through'])
            ->where($this->hasMany[$alias]['foreign_key'], '=', $this->pk());

        if ($far_keys !== null) {
            // Remove all the relationships in the array
            $query->where($this->hasMany[$alias]['far_key'], 'IN', (array) $far_keys);
        }

        $query->execute($this->db);

        return $this;
    }

    /**
     * Links far keys through a has_many "through" table. Alias of add().
     *
     * @param mixed $far_keys Model, key or array of keys
     */
    public function attach(string $alias, $far_keys): self
    {
        return $this->add($alias, $far_keys);
    }

    /**
     * Unlinks far keys, or every key when none is given. Alias of remove().
     *
     * @param mixed $far_keys Model, key or array of keys
     */
    public function detach(string $alias, $far_keys = null): self
    {
        return $this->remove($alias, $far_keys);
    }

    /**
     * Makes the given keys the only links of a has_many "through" relationship.
     *
     *     $article->sync('tags', [1, 3]);
     *
     * @param array<int, mixed> $far_keys Models or keys
     * @return array{attached: array<int, mixed>, detached: array<int, mixed>}
     */
    public function sync(string $alias, array $far_keys): array
    {
        $relation = $this->hasMany[$alias];
        $wanted = array_map(fn ($key) => $key instanceof self ? $key->pk() : $key, $far_keys);

        $current = DB::select($relation['far_key'])
            ->from($relation['through'])
            ->where($relation['foreign_key'], '=', $this->pk())
            ->execute($this->db)
            ->asArray(null, $relation['far_key']);

        // Keys come back as strings on some drivers
        $attached = array_values(array_filter($wanted, fn ($key) => !in_array((string) $key, array_map('strval', $current), true)));
        $detached = array_values(array_filter($current, fn ($key) => !in_array((string) $key, array_map('strval', $wanted), true)));

        $detached && $this->remove($alias, $detached);
        $attached && $this->add($alias, $attached);

        return ['attached' => $attached, 'detached' => $detached];
    }

    /**
     * Returns an ORM model for the given one-one related alias
     *
     * @param string $alias Alias name
     * @return \Kohana\Orm\Model
     */
    protected function related(string $alias): ?self
    {
        switch (true) {
            case isset($this->related[$alias]):
                return $this->related[$alias];
            case isset($this->hasOne[$alias]):
                return $this->related[$alias] = static::factory($this->hasOne[$alias]['model']);
            case isset($this->belongsTo[$alias]):
                return $this->related[$alias] = static::factory($this->belongsTo[$alias]['model']);
            default:
                return null;
        }
    }

    public function hasOne(): array
    {
        return $this->hasOne;
    }

    public function belongsTo(): array
    {
        return $this->belongsTo;
    }

    public function hasMany(): array
    {
        return $this->hasMany;
    }

    public function loadWith(): array
    {
        return $this->loadWith;
    }
}
