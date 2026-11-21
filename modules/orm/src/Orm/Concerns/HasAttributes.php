<?php

namespace Kohana\Orm\Concerns;

use Kohana\Orm\Model;

/**
 * Reads and writes column values, tracks changes and converts the model to an array.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait HasAttributes
{
    /**
     * Checks if object data is set.
     *
     * @param string $column Column name
     * @return bool
     */
    public function __isset(string $column)
    {
        return isset($this->object[$column]) or
            isset($this->related[$column]) or
            isset($this->hasOne[$column]) or
            isset($this->belongsTo[$column]) or
            isset($this->hasMany[$column]);
    }

    /**
     * Unsets object data.
     *
     * @param string $column Column name
     * @return void
     */
    public function __unset(string $column)
    {
        unset($this->object[$column], $this->changes[$column], $this->related[$column]);
    }

    /**
     * Check whether the model data has been modified.
     * If $field is specified, checks whether that field was modified.
     *
     * @param string|null $field Field to check for changes
     * @return  bool  Whether the field has changed
     */
    public function changed(?string $field = null): bool
    {
        return $field === null ? count($this->changes) > 0 : array_key_exists($field, $this->changes);
    }

    /**
     * Returns an array of changed fields and their new values.
     *
     * @return array
     * @since 3.5.0
     */
    public function changes(): array
    {
        return $this->changes;
    }

    /**
     * Handles retrieval of all model values, relationships, and metadata.
     * [!!] This should not be overridden.
     *
     * @param string $column Column name
     * @return  mixed
     * @throws \Kohana\Foundation\KohanaException
     */
    public function __get(string $column)
    {
        return $this->get($column);
    }

    /**
     * Handles getting of column
     * Override this method to add custom get behavior
     *
     * @param string $column Column name
     * @return mixed
     * @throws \Kohana\Foundation\KohanaException
     */
    public function get(string $column)
    {
        if (isset($this->related[$column])) {
            // Return related model that has already been fetched
            return $this->related[$column];
        } elseif (isset($this->belongsTo[$column])) {
            $model = $this->related($column);

            // Use this model's column and foreign model's primary key
            $col = $model->objectName . '.' . $model->primaryKey;
            $val = $this->object[$this->belongsTo[$column]['foreign_key']];

            // Make sure we don't run WHERE "AUTO_INCREMENT column" = NULL queries. This would
            // return the last inserted record instead of an empty result.
            // See: http://mysql.localhost.net.ar/doc/refman/5.1/en/server-session-variables.html#sysvar_sql_auto_is_null
            if ($val !== null) {
                $model->where($col, '=', $val)->find();
            }

            return $this->related[$column] = $model;
        } elseif (isset($this->hasOne[$column])) {
            $model = $this->related($column);

            // Use this model's primary key value and foreign model's column
            $col = $model->objectName . '.' . $this->hasOne[$column]['foreign_key'];
            $val = $this->pk();

            $model->where($col, '=', $val)->find();

            return $this->related[$column] = $model;
        } elseif (isset($this->hasMany[$column])) {
            $model = static::factory($this->hasMany[$column]['model']);

            if (isset($this->hasMany[$column]['through'])) {
                // Grab has_many "through" relationship table
                $through = $this->hasMany[$column]['through'];

                // Join on through model's target foreign key (far_key) and target model's primary key
                $join_col1 = $through . '.' . $this->hasMany[$column]['far_key'];
                $join_col2 = $model->objectName . '.' . $model->primaryKey;

                $model->join($through)->on($join_col1, '=', $join_col2);

                // Through table's source foreign key (foreign_key) should be this model's primary key
                $col = $through . '.' . $this->hasMany[$column]['foreign_key'];
            } else {
                // Simple has_many relationship, search where target model's foreign key is this model's primary key
                $col = $model->objectName . '.' . $this->hasMany[$column]['foreign_key'];
            }
            $val = $this->pk();

            return $model->where($col, '=', $val);
        } else {
            return $this->castGet($column, in_array($column, $this->serializeColumns) ? $this->unserializeValue($this->object[$column]) : $this->object[$column] ?? null);
        }
    }

    /**
     * Base set method.
     * [!!] This should not be overridden.
     *
     * @param string $column Column name
     * @param mixed $value Column value
     * @return void
     * @throws \ReflectionException
     */
    public function __set(string $column, $value)
    {
        $this->set($column, $value);
    }

    /**
     * Handles setting of columns
     * Override this method to add custom set behavior
     *
     * @param string $column Column name
     * @param mixed $value Column value
     * @return \Kohana\Orm\Model
     * @throws \ReflectionException
     */
    public function set(string $column, $value): self
    {
        if (!isset($this->objectName)) {
            // Object not yet constructed, so we're loading data from a database call cast
            $this->castData[$column] = $value;

            return $this;
        }

        if (in_array($column, $this->serializeColumns)) {
            $value = $this->serializeValue($value);
        }

        if (isset($this->belongsTo[$column])) {
            // Update related object itself
            $this->related[$column] = $value;

            // Update the foreign key of this model
            $this->object[$this->belongsTo[$column]['foreign_key']] = $value instanceof self ? $value->pk() : null;

            $this->changes[$column] = $value;
        } else {
            // Filter the data
            $value = $this->castSet($column, $this->runFilter($column, $value));

            // See if the data really changed
            if (!isset($this->object[$column]) || $value !== $this->object[$column]) {
                $this->object[$column] = $value;

                // Data has changed
                $this->changes[$column] = $value;

                // Object is no longer saved or valid
                $this->saved = $this->valid = false;
            }
        }

        return $this;
    }

    /**
     * Set values from an array with support for one-one relationships.
     * This method should be used for loading in post data, etc.
     *
     * @param array $values Array of column => value pairs
     * @param array $columns Array of columns to be set
     * @return \Kohana\Orm\Model
     */
    public function values(array $values, ?array $columns = null): self
    {
        // Without a column list, only the fillable columns are set
        $columns ??= $this->fillableColumns($values);

        foreach ($columns as $key => $column) {
            if (is_string($key)) {
                // isset() fails when the value is null (we want it to pass)
                if (!array_key_exists($key, $values)) {
                    continue;
                }

                // Try to set values to a related model
                $this->{$key}->values($values[$key], $column);
            } else {
                // isset() fails when the value is null (we want it to pass)
                if (!array_key_exists($column, $values)) {
                    continue;
                }

                // Update the column, respects __set()
                $this->$column = $values[$column];
            }
        }

        return $this;
    }

    /**
     * Returns the values of this object as an array, including any related one-one
     * models that have already been loaded using with()
     *
     * @return array
     * @throws \Kohana\Foundation\KohanaException
     */
    public function asArray(): array
    {
        $object = [];

        foreach ($this->object as $column => $value) {
            // Call __get for any user processing
            $object[$column] = $this->__get($column);
        }

        foreach ($this->related as $column => $model) {
            // Include any related objects that are already loaded
            $object[$column] = $model->asArray();
        }

        return $object;
    }

    /**
     * Loads an array of values into the current object.
     *
     * @chainable
     * @param  array $values Values to load
     * @return \Kohana\Orm\Model
     */
    protected function loadValues(array $values): self
    {
        if (array_key_exists($this->primaryKey, $values)) {
            if ($values[$this->primaryKey] !== null) {
                // Flag as loaded and valid
                $this->loaded = $this->valid = true;

                // Store primary key
                $this->primaryKeyValue = $values[$this->primaryKey];
            } else {
                // Not loaded or valid
                $this->loaded = $this->valid = false;
            }
        }

        // Related objects
        $related = [];

        foreach ($values as $column => $value) {
            if (strpos($column, ':') === false) {
                // Load the value to this model
                $this->object[$column] = $value;
            } else {
                // Column belongs to a related model
                list($prefix, $column) = explode(':', $column, 2);

                $related[$prefix][$column] = $value;
            }
        }

        if (!empty($related)) {
            foreach ($related as $object => $values) {
                // Load the related objects with the values in the result
                $this->related($object)->loadValues($values);
            }
        }

        if ($this->loaded) {
            // Store the object in its original state
            $this->originalValues = $this->object;
        }

        return $this;
    }

    protected function serializeValue($value)
    {
        return json_encode($value);
    }

    protected function unserializeValue($value)
    {
        return json_decode($value, true);
    }

    public function originalValues(): array
    {
        return $this->originalValues;
    }

    public function object(): array
    {
        return $this->object;
    }
}
