<?php

namespace Kohana\Orm\Concerns;

use Kohana\Database\DB;
use Kohana\Foundation\KohanaException;

/**
 * Small operations on a loaded record: increment(), decrement(), fresh(),
 * replicate() and exists().
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait ManagesRecords
{
    /**
     * Whether the model was loaded from or saved to the database.
     */
    public function exists(): bool
    {
        return $this->loaded;
    }

    /**
     * Adds to a column with a single UPDATE, without a read in between.
     *
     *     $article->increment('views');
     *     $article->increment('views', 5, ['title' => 'Popular']);
     *
     * @param array<string, mixed> $extra Other columns to set
     */
    public function increment(string $column, int|float $amount = 1, array $extra = []): self
    {
        return $this->incrementOrDecrement($column, $amount, $extra);
    }

    /**
     * Subtracts from a column with a single UPDATE.
     *
     * @param array<string, mixed> $extra Other columns to set
     */
    public function decrement(string $column, int|float $amount = 1, array $extra = []): self
    {
        return $this->incrementOrDecrement($column, -$amount, $extra);
    }

    /**
     * @param array<string, mixed> $extra
     */
    protected function incrementOrDecrement(string $column, int|float $amount, array $extra): self
    {
        if (!$this->loaded) {
            throw new KohanaException('Cannot change :column of :model because it is not loaded.', [':column' => $column, ':model' => $this->objectName]);
        }

        $quoted = $this->db->quoteColumn($column);
        $expression = DB::expr($quoted . ($amount < 0 ? ' - ' : ' + ') . abs($amount));

        foreach ($extra as $name => $value) {
            $this->set($name, $value);
            $extra[$name] = $this->object[$name];
        }

        if ($this->usesTimestamps() && static::UPDATED_AT) {
            $extra[static::UPDATED_AT] = $this->object[static::UPDATED_AT] = date($this->dateFormat);
        }

        DB::update($this->tableName)
            ->set([$column => $expression] + $extra)
            ->where($this->primaryKey, '=', $this->pk())
            ->execute($this->db);

        $this->object[$column] = ($this->object[$column] ?? 0) + $amount;

        // The new values are saved
        foreach ([...array_keys($extra), $column] as $name) {
            unset($this->changes[$name]);
            $this->originalValues[$name] = $this->object[$name];
        }

        return $this;
    }

    /**
     * Loads this record again into a new model, or returns null when it no
     * longer exists.
     */
    public function fresh(): ?static
    {
        if (!$this->loaded) {
            return null;
        }

        $model = new static();
        $model->trashedScope = 'with';
        $model->where($this->objectName . '.' . $this->primaryKey, '=', $this->pk())->find();

        return $model->loaded() ? $model : null;
    }

    /**
     * Copies the values into a new, unsaved model, without the primary key
     * and timestamps.
     *
     * @param array<int, string> $except Other columns to leave out
     */
    public function replicate(array $except = []): static
    {
        $except = array_merge($except, [$this->primaryKey, static::CREATED_AT, static::UPDATED_AT, static::DELETED_AT]);

        $model = new static();
        foreach (array_diff_key($this->object, array_flip($except)) as $column => $value) {
            // Raw values, so casts and filters are not applied twice
            $model->object[$column] = $value;
            $model->changes[$column] = $value;
        }

        return $model;
    }
}
