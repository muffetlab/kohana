<?php

namespace Kohana\Orm\Concerns;

use Kohana\Database\Connection;
use Kohana\Database\DB;
use Kohana\Foundation\KohanaException;
use Kohana\Orm\Model;
use Kohana\Validation\Validator;

/**
 * Loads, creates, updates and deletes records.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait PersistsModels
{
    /**
     * Unloads the current object and clears the status.
     *
     * @chainable
     * @return \Kohana\Orm\Model
     */
    public function clear(): self
    {
        // Create an array with all the columns set to null
        $values = array_combine(array_keys($this->object), array_fill(0, count($this->object), null));

        // Replace the object and reset the object status
        $this->object = $this->changes = $this->related = $this->originalValues = [];

        // Replace the current object with an empty one
        $this->loadValues($values);

        // Reset primary key
        $this->primaryKeyValue = null;

        // Reset the loaded state
        $this->loaded = false;

        $this->reset();

        return $this;
    }

    /**
     * Reloads the current object from the database.
     *
     * @chainable
     * @return \Kohana\Orm\Model
     * @throws \Kohana\Foundation\KohanaException
     */
    public function reload(): self
    {
        $primary_key = $this->pk();

        // Replace the object and reset the object status
        $this->object = $this->changes = $this->related = $this->originalValues = [];

        // Only reload the object if we have one to reload
        if ($this->loaded) {
            return $this->clear()
                    ->where($this->objectName . '.' . $this->primaryKey, '=', $primary_key)
                    ->find();
        } else {
            return $this->clear();
        }
    }

    /**
     * Finds and loads a single database row into the object.
     *
     * @chainable
     * @return \Kohana\Database\CachedResult|\Kohana\Orm\Model|object
     * @throws \Kohana\Foundation\KohanaException
     */
    public function find()
    {
        if ($this->loaded) {
            throw new KohanaException('Method find() cannot be called on loaded objects');
        }

        if (!empty($this->loadWith)) {
            foreach ($this->loadWith as $alias) {
                // Bind auto relationships
                $this->with($alias);
            }
        }

        $this->build(Connection::SELECT);

        return $this->loadResult();
    }

    /**
     * Finds multiple database rows and returns an iterator of the rows found.
     *
     * @throws \Kohana\Foundation\KohanaException
     * @return \Kohana\Database\Result
     */
    public function findAll()
    {
        if ($this->loaded) {
            throw new KohanaException('Method find_all() cannot be called on loaded objects');
        }

        if (!empty($this->loadWith)) {
            foreach ($this->loadWith as $alias) {
                // Bind auto relationships
                $this->with($alias);
            }
        }

        $this->build(Connection::SELECT);

        return $this->loadResult(true);
    }

    /**
     * Loads a database result, either as a new record for this model, or as
     * an iterator for multiple rows.
     *
     * @chainable
     * @param bool $multiple Return an iterator or load a single row
     * @return \Kohana\Database\CachedResult|\Kohana\Orm\Model|object
     * @throws \Kohana\Foundation\KohanaException
     */
    protected function loadResult(bool $multiple = false)
    {
        $this->dbBuilder->from([$this->tableName, $this->objectName]);

        if ($multiple === false) {
            // Only fetch 1 record
            $this->dbBuilder->limit(1);
        }

        // Select all columns by default
        $this->dbBuilder->select($this->objectName . '.*');

        if (!isset($this->dbApplied['order_by']) && !empty($this->sorting)) {
            foreach ($this->sorting as $column => $direction) {
                if (strpos($column, '.') === false) {
                    // Sorting column for use in JOINs
                    $column = $this->objectName . '.' . $column;
                }

                $this->dbBuilder->orderBy($column, $direction);
            }
        }

        if ($multiple === true) {
            // Return database iterator casting to this object type
            $result = $this->dbBuilder->asObject(get_class($this))->execute($this->db);

            $this->reset();

            return $result;
        } else {
            // Load the result as an associative array
            $result = $this->dbBuilder->asAssoc()->execute($this->db);

            $this->reset();

            if ($result->count() === 1) {
                // Load object values
                $this->loadValues($result->current());
            } else {
                // Clear the object, nothing was found
                $this->clear();
            }

            return $this;
        }
    }

    /**
     * Insert a new object to the database
     * @param \Kohana\Validation\Validator|null $validation Validation object
     * @return \Kohana\Orm\Model
     * @throws \Kohana\Foundation\KohanaException
     * @throws \Kohana\Orm\ValidationException
     * @throws \ReflectionException
     */
    public function create(?Validator $validation = null): self
    {
        if ($this->loaded) {
            throw new KohanaException('Cannot create :model model because it is already loaded.', [':model' => $this->objectName]);
        }

        // Require model validation before saving
        if (!$this->valid || $validation) {
            $this->check($validation);
        }

        $data = [];
        foreach ($this->changes as $column => $value) {
            // Generate list of column => values
            $data[$column] = $this->object[$column];
        }

        if (is_array($this->createdColumn)) {
            // Fill the created column
            $column = $this->createdColumn['column'];
            $format = $this->createdColumn['format'];

            $data[$column] = $this->object[$column] = $format === true ? time() : date($format);
        }

        $result = DB::insert($this->tableName)
            ->columns(array_keys($data))
            ->values(array_values($data))
            ->execute($this->db);

        if (!array_key_exists($this->primaryKey, $data)) {
            // Load the insert id as the primary key if it was left out
            $this->object[$this->primaryKey] = $this->primaryKeyValue = $result[0];
        } else {
            $this->primaryKeyValue = $this->object[$this->primaryKey];
        }

        // Object is now loaded and saved
        $this->loaded = $this->saved = true;

        // All changes have been saved
        $this->changes = [];
        $this->originalValues = $this->object;

        return $this;
    }

    /**
     * Updates a single record or multiple records
     *
     * @chainable
     * @param \Kohana\Validation\Validator|null $validation Validation object
     * @return \Kohana\Orm\Model
     * @throws \Kohana\Foundation\KohanaException
     * @throws \Kohana\Orm\ValidationException
     * @throws \ReflectionException
     */
    public function update(?Validator $validation = null): self
    {
        if (!$this->loaded) {
            throw new KohanaException('Cannot update :model model because it is not loaded.', [':model' => $this->objectName]);
        }

        // Run validation if the model isn't valid, or we have additional validation rules.
        if (!$this->valid || $validation) {
            $this->check($validation);
        }

        if (empty($this->changes)) {
            // Nothing to update
            return $this;
        }

        $data = [];
        foreach ($this->changes as $column => $value) {
            // Compile changed data
            $data[$column] = $this->object[$column];
        }

        if (is_array($this->updatedColumn)) {
            // Fill the updated column
            $column = $this->updatedColumn['column'];
            $format = $this->updatedColumn['format'];

            $data[$column] = $this->object[$column] = $format === true ? time() : date($format);
        }

        // Use primary key value
        $id = $this->pk();

        // Update a single record
        DB::update($this->tableName)
            ->set($data)
            ->where($this->primaryKey, '=', $id)
            ->execute($this->db);

        if (isset($data[$this->primaryKey])) {
            // Primary key was changed, reflect it
            $this->primaryKeyValue = $data[$this->primaryKey];
        }

        // Object has been saved
        $this->saved = true;

        // All changes have been saved
        $this->changes = [];
        $this->originalValues = $this->object;

        return $this;
    }

    /**
     * Updates or Creates the record depending on loaded()
     *
     * @chainable
     * @param \Kohana\Validation\Validator|null $validation Validation object
     * @return \Kohana\Orm\Model
     * @throws \Kohana\Foundation\KohanaException
     * @throws \Kohana\Orm\ValidationException
     * @throws \ReflectionException
     */
    public function save(?Validator $validation = null): self
    {
        return $this->loaded() ? $this->update($validation) : $this->create($validation);
    }

    /**
     * Deletes a single record while ignoring relationships.
     *
     * @chainable
     * @return \Kohana\Orm\Model
     * @throws \Kohana\Foundation\KohanaException
     */
    public function delete(): self
    {
        if (!$this->loaded) {
            throw new KohanaException('Cannot delete :model model because it is not loaded.', [':model' => $this->objectName]);
        }

        // Use primary key value
        $id = $this->pk();

        // Delete the object
        DB::delete($this->tableName)
            ->where($this->primaryKey, '=', $id)
            ->execute($this->db);

        return $this->clear();
    }

    /**
     * Count the number of records in the table.
     *
     * @return int
     * @throws \Kohana\Foundation\KohanaException
     */
    public function countAll(): int
    {
        $selects = [];

        foreach ($this->dbPending as $key => $method) {
            if ($method['name'] === 'select') {
                // Ignore any selected columns for now
                $selects[$key] = $method;
                unset($this->dbPending[$key]);
            }
        }

        if (!empty($this->loadWith)) {
            foreach ($this->loadWith as $alias) {
                // Bind relationship
                $this->with($alias);
            }
        }

        $this->build(Connection::SELECT);

        $records = $this->dbBuilder->from([$this->tableName, $this->objectName])
            ->select([DB::expr('COUNT(' . $this->db->quoteColumn($this->objectName . '.' . $this->primaryKey) . ')'), 'records_found'])
            ->execute($this->db)
            ->get('records_found');

        // Add back in selected columns
        $this->dbPending += $selects;

        $this->reset();

        // Return the total number of records in a table
        return (int) $records;
    }

    /**
     * Proxy method to Database list_columns.
     *
     * @return array
     */
    public function listColumns(): array
    {
        // Proxy to database
        return $this->db->listColumns($this->tableName);
    }
}
