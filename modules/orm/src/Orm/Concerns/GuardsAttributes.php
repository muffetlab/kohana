<?php

namespace Kohana\Orm\Concerns;

use Kohana\Orm\MassAssignmentException;

/**
 * Which columns values() may set without an explicit column list.
 *
 *     protected $fillable = ['title', 'body'];   // only these
 *     protected $guarded = ['id', 'is_admin'];   // everything but these
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait GuardsAttributes
{
    /**
     * @var string[] Columns that values() sets when no column list is given
     */
    protected $fillable = [];

    /**
     * @var string[] Columns values() never sets without a column list; ['*'] guards all
     */
    protected $guarded = ['*'];

    /**
     * @return string[]
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * @return string[]
     */
    public function getGuarded(): array
    {
        return $this->guarded;
    }

    /**
     * Whether values() may set a column without an explicit column list.
     */
    public function isFillable(string $column): bool
    {
        if (in_array($column, $this->fillable, true)) {
            return true;
        }

        if ($this->fillable || in_array('*', $this->guarded, true)) {
            return false;
        }

        return !in_array($column, $this->guarded, true) && $column !== $this->primaryKey;
    }

    /**
     * Picks the fillable keys of $values.
     *
     * @throws MassAssignmentException when the model declares neither $fillable nor $guarded
     */
    protected function fillableColumns(array $values): array
    {
        if (!$this->fillable && $this->guarded === ['*']) {
            throw new MassAssignmentException(
                'Add the columns that may be mass assigned to :model::$fillable (or the protected ones to $guarded), or pass them to values()',
                [':model' => static::class]
            );
        }

        return array_values(array_filter(array_keys($values), fn ($column) => is_string($column) && $this->isFillable($column)));
    }

    /**
     * Sets the fillable values; values() without a column list.
     */
    public function fill(array $values): self
    {
        return $this->values($values);
    }
}
