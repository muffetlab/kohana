<?php

namespace Kohana\Orm\Concerns;

/**
 * Converts column values between their database form and PHP types.
 *
 *     protected $casts = [
 *         'votes' => 'int',
 *         'price' => 'float',
 *         'active' => 'bool',
 *         'settings' => 'array',         // JSON column, decoded to an array
 *         'published_at' => 'datetime',  // DateTimeImmutable
 *     ];
 *
 * Types: int, float, bool, string, array (or json), object, datetime, date, timestamp.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait CastsAttributes
{
    /**
     * @var array Column => type
     */
    protected $casts = [];

    /**
     * @var string Format datetime columns are stored in
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * @var string Type of the primary key: int, or string for UUIDs and other text keys
     */
    protected $keyType = 'int';

    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * Converts a stored value to the column's PHP type.
     */
    protected function castGet(string $column, $value)
    {
        if ($value === null) {
            return $value;
        }

        if (!isset($this->casts[$column])) {
            // Auto-increment keys come back as strings from some drivers
            return $column === $this->primaryKey && $this->keyType === 'int' && is_numeric($value) ? (int) $value : $value;
        }

        return match (strtolower($this->casts[$column])) {
            'int', 'integer' => (int) $value,
            'float', 'double', 'real' => (float) $value,
            'bool', 'boolean' => is_string($value) ? !in_array(strtolower($value), ['', '0', 'f', 'false'], true) : (bool) $value,
            'string' => (string) $value,
            'array', 'json' => is_array($value) ? $value : json_decode((string) $value, true),
            'object' => is_object($value) ? $value : json_decode((string) $value),
            'datetime', 'immutable_datetime' => $this->asDateTime($value),
            'date' => $this->asDateTime($value)->setTime(0, 0),
            'timestamp' => $value instanceof \DateTimeInterface ? $value->getTimestamp() : (is_numeric($value) ? (int) $value : strtotime((string) $value)),
            default => $value,
        };
    }

    /**
     * Converts a PHP value to the form stored in the column.
     */
    protected function castSet(string $column, $value)
    {
        if ($value === null || !isset($this->casts[$column])) {
            return $value;
        }

        return match (strtolower($this->casts[$column])) {
            // Kept as a PHP bool: the query builder quotes it in a form MySQL and PostgreSQL accept
            'bool', 'boolean' => (bool) $value,
            'array', 'json', 'object' => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'datetime', 'immutable_datetime' => $this->asDateTime($value)->format($this->dateFormat),
            'date' => $this->asDateTime($value)->format('Y-m-d'),
            'timestamp' => $value instanceof \DateTimeInterface ? $value->getTimestamp() : $value,
            default => $value,
        };
    }

    protected function asDateTime($value): \DateTimeImmutable
    {
        return match (true) {
            $value instanceof \DateTimeImmutable => $value,
            $value instanceof \DateTimeInterface => \DateTimeImmutable::createFromInterface($value),
            is_int($value) || (is_string($value) && ctype_digit($value)) => (new \DateTimeImmutable())->setTimestamp((int) $value),
            default => new \DateTimeImmutable((string) $value),
        };
    }
}
