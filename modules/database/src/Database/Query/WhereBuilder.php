<?php

namespace Kohana\Database\Query;

/**
 * Database query builder for WHERE statements. See [Query Builder](/database/query/builder) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class WhereBuilder extends Builder
{
    // WHERE ...
    protected $where = [];
    // ORDER BY ...
    protected $orderBy = [];
    // LIMIT ...
    protected $limit = null;

    /**
     * Alias of and_where()
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function where($column, string $op, $value): self
    {
        return $this->andWhere($column, $op, $value);
    }

    /**
     * Creates a new "AND WHERE" condition for the query.
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function andWhere($column, string $op, $value): self
    {
        $this->where[] = ['AND' => [$column, $op, $value]];

        return $this;
    }

    /**
     * Creates a new "OR WHERE" condition for the query.
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function orWhere($column, string $op, $value): self
    {
        $this->where[] = ['OR' => [$column, $op, $value]];

        return $this;
    }

    /**
     * Alias of and_where_open()
     *
     * @return  $this
     */
    public function whereOpen(): self
    {
        return $this->andWhereOpen();
    }

    /**
     * Opens a new "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function andWhereOpen(): self
    {
        $this->where[] = ['AND' => '('];

        return $this;
    }

    /**
     * Opens a new "OR WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function orWhereOpen(): self
    {
        $this->where[] = ['OR' => '('];

        return $this;
    }

    /**
     * Closes an open "WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function whereClose(): self
    {
        return $this->andWhereClose();
    }

    /**
     * Closes an open "WHERE (...)" grouping or removes the grouping when it is
     * empty.
     *
     * @return  $this
     */
    public function whereCloseEmpty(): self
    {
        $group = end($this->where);

        if ($group && reset($group) === '(') {
            array_pop($this->where);

            return $this;
        }

        return $this->whereClose();
    }

    /**
     * Closes an open "WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function andWhereClose(): self
    {
        $this->where[] = ['AND' => ')'];

        return $this;
    }

    /**
     * Closes an open "WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function orWhereClose(): self
    {
        $this->where[] = ['OR' => ')'];

        return $this;
    }

    /**
     * Applies sorting with "ORDER BY ..."
     *
     * @param   mixed   $column     column name or [$column, $alias] or object
     * @param string|null $direction Direction of sorting
     * @return  $this
     */
    public function orderBy($column, ?string $direction = null): self
    {
        $this->orderBy[] = [$column, $direction];

        return $this;
    }

    /**
     * Return up to "LIMIT ..." results
     *
     * @param int|null $number Maximum results to return or null to reset
     * @return  $this
     */
    public function limit(?int $number): self
    {
        $this->limit = $number === null ? null : $number;

        return $this;
    }

}
