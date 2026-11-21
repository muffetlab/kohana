<?php

namespace Kohana\Orm\Concerns;

use Kohana\Orm\Paginator;

/**
 * Splits a query into pages or chunks.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait PaginatesQueries
{
    /**
     * Runs the query for one page and counts every matching record.
     *
     *     $articles = Article::query()->where('published', '=', true)->paginate(15, $page);
     */
    public function paginate(int $perPage = 15, int $page = 1): Paginator
    {
        $perPage = max(1, $perPage);
        $page = max(1, $page);

        // ORDER BY is not allowed next to COUNT() on PostgreSQL
        $counter = clone $this;
        $counter->dbPending = array_filter($counter->dbPending, fn (array $method) => $method['name'] !== 'orderBy');
        $total = $counter->countAll();

        $items = $this->limit($perPage)->offset(($page - 1) * $perPage)->findAll();

        return new Paginator($items, $total, $perPage, $page);
    }

    /**
     * Runs the query in chunks and passes each chunk to the callback. Return
     * false from the callback to stop. Without an orderBy() the chunks are
     * ordered by primary key so no record is skipped.
     *
     *     Article::query()->chunk(100, function ($articles) { ... });
     *
     * @param callable(\Kohana\Database\Result, int): mixed $callback Receives the chunk and the page number
     * @return bool False when the callback stopped the loop
     */
    public function chunk(int $size, callable $callback): bool
    {
        $size = max(1, $size);
        $base = clone $this;
        $this->reset();

        if (!in_array('orderBy', array_column($base->dbPending, 'name'), true)) {
            $base->orderBy($base->objectName . '.' . $base->primaryKey);
        }

        for ($page = 1; ; $page++) {
            $records = (clone $base)->limit($size)->offset(($page - 1) * $size)->findAll();

            if ($records->count() === 0) {
                return true;
            }

            if ($callback($records, $page) === false) {
                return false;
            }

            if ($records->count() < $size) {
                return true;
            }
        }
    }
}
