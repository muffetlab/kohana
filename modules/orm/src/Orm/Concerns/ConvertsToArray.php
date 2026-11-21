<?php

namespace Kohana\Orm\Concerns;

/**
 * Array and JSON form of a model, without hidden columns:
 *
 *     protected $hidden = ['password'];
 *     protected $visible = [];          // when set, only these columns
 *
 *     $user->toArray();
 *     json_encode($user);
 *
 * Columns go through their casts; dates become ISO 8601 strings. Loaded
 * relationships, including eager loaded has_many ones, are included.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait ConvertsToArray
{
    /**
     * @var string[] Columns left out of toArray() and JSON
     */
    protected $hidden = [];

    /**
     * @var string[] When not empty, the only columns in toArray() and JSON
     */
    protected $visible = [];

    public function toArray(): array
    {
        $array = [];

        foreach (array_keys($this->object) as $column) {
            if ($this->isVisible($column)) {
                $array[$column] = $this->serializeForArray($this->get($column));
            }
        }

        foreach ($this->related as $alias => $model) {
            if ($this->isVisible($alias)) {
                $array[$alias] = $model instanceof self && $model->loaded() ? $model->toArray() : null;
            }
        }

        foreach ($this->eagerLoaded ?? [] as $alias => $models) {
            if ($this->isVisible($alias)) {
                $array[$alias] = array_map(fn ($model) => $model->toArray(), iterator_to_array($models, false));
            }
        }

        return $array;
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode($this->jsonSerialize(), $flags | JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Hides more columns on this instance.
     */
    public function makeHidden(string ...$columns): static
    {
        $this->hidden = array_values(array_unique(array_merge($this->hidden, $columns)));

        return $this;
    }

    /**
     * Shows hidden columns on this instance.
     */
    public function makeVisible(string ...$columns): static
    {
        $this->hidden = array_values(array_diff($this->hidden, $columns));

        if ($this->visible) {
            $this->visible = array_values(array_unique(array_merge($this->visible, $columns)));
        }

        return $this;
    }

    protected function isVisible(string $column): bool
    {
        return !in_array($column, $this->hidden, true) && (!$this->visible || in_array($column, $this->visible, true));
    }

    protected function serializeForArray($value)
    {
        return $value instanceof \DateTimeInterface ? $value->format(DATE_ATOM) : $value;
    }
}
