<?php

namespace Kohana\Orm\Concerns;

use Kohana\Orm\ModelNotFoundException;

/**
 * Static entry points, so a model class can be used without factory():
 *
 *     $posts = Post::query()->where('published', '=', 1)->findAll();
 *     $post = Post::findOrFail($id);
 *     $post = Post::make(['title' => 'Hello'])->save();
 *     $user = User::firstOrCreate(['email' => $email], ['name' => $name]);
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait QueriesStatically
{
    /**
     * A new, empty model to build a query on.
     */
    public static function query(): static
    {
        return new static();
    }

    /**
     * Every record.
     */
    public static function all()
    {
        return static::query()->findAll();
    }

    /**
     * A new, unsaved model filled with fillable values.
     */
    public static function make(array $values = []): self
    {
        return $values ? static::query()->values($values) : static::query();
    }

    /**
     * Loads a record by primary key.
     *
     * @throws ModelNotFoundException
     */
    public static function findOrFail($id): static
    {
        $model = new static($id);

        if (!$model->loaded()) {
            throw ModelNotFoundException::for(static::class, (array) $id);
        }

        return $model;
    }

    /**
     * The first record matching the column values, or null.
     */
    public static function firstWhere(array $attributes): ?self
    {
        $model = static::query()->whereAll($attributes)->find();

        return $model->loaded() ? $model : null;
    }

    /**
     * The first record matching $attributes, or a new saved one with
     * $attributes and $values.
     */
    public static function firstOrCreate(array $attributes, array $values = []): self
    {
        if ($model = static::firstWhere($attributes)) {
            return $model;
        }

        return static::query()->values($attributes + $values, array_keys($attributes + $values))->create();
    }

    /**
     * Updates the first record matching $attributes with $values, or creates it.
     */
    public static function updateOrCreate(array $attributes, array $values = []): self
    {
        $model = static::firstWhere($attributes) ?? static::query()->values($attributes, array_keys($attributes));

        return $model->values($values, array_keys($values))->save();
    }

    /**
     * Deletes records by primary key and returns how many were deleted.
     */
    public static function destroy(...$ids): int
    {
        $ids = array_merge(...array_map(fn ($id) => (array) $id, $ids));
        $count = 0;

        foreach ($ids as $id) {
            $model = new static($id);

            if ($model->loaded()) {
                $model->delete();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Adds a WHERE column = value for each pair.
     */
    public function whereAll(array $attributes): static
    {
        foreach ($attributes as $column => $value) {
            $this->where($this->objectName . '.' . $column, $value === null ? 'IS' : '=', $value);
        }

        return $this;
    }

    /**
     * Runs the query and returns the first record, or throws.
     *
     * @throws ModelNotFoundException
     */
    public function firstOrFail(): static
    {
        $this->find();

        if (!$this->loaded()) {
            throw ModelNotFoundException::for(static::class);
        }

        return $this;
    }
}
