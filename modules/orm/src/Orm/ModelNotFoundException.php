<?php

namespace Kohana\Orm;

use Kohana\Http\Exceptions\Http404Exception;

/**
 * findOrFail() and firstOrFail() found nothing. Left uncaught in a
 * controller, it becomes a 404 response.
 *
 * @package    Kohana/ORM
 *
 * @phpstan-consistent-constructor
 */
class ModelNotFoundException extends Http404Exception
{
    /**
     * @var string Model class
     */
    protected string $model = '';

    /**
     * @var array Keys that were looked up
     */
    protected array $ids = [];

    public static function for(string $model, array $ids = []): static
    {
        $exception = new static('No :model found' . ($ids ? ' for :ids' : ''), [':model' => $model, ':ids' => implode(', ', $ids)]);
        $exception->model = $model;
        $exception->ids = $ids;

        return $exception;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getIds(): array
    {
        return $this->ids;
    }
}
