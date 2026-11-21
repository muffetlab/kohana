<?php

namespace Kohana\Orm;

use Kohana\Database\Result;

/**
 * One page of models returned by Model::paginate().
 *
 * @package    Kohana/ORM
 * @implements \IteratorAggregate<int, Model>
 */
class Paginator implements \Countable, \IteratorAggregate, \JsonSerializable
{
    /**
     * @param Result $items Models on this page
     * @param int $total Matching records on every page
     */
    public function __construct(
        protected Result $items,
        protected int $total,
        protected int $perPage,
        protected int $currentPage,
    ) {
    }

    public function items(): Result
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    public function count(): int
    {
        return $this->items->count();
    }

    public function getIterator(): \Iterator
    {
        return $this->items;
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(fn (Model $model) => $model->toArray(), iterator_to_array($this->items, false)),
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
