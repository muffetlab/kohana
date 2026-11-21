<?php

namespace Kohana\Orm;

use Kohana\Support\Arr;
use Kohana\Support\Inflector;
use Kohana\Database\Connection;
use Kohana\Validation\Validator;
use Kohana\Database\DB;
use Kohana\Foundation\KohanaException;

/**
 * [Object Relational Mapping][ref-orm] (ORM) is a method of abstracting database
 * access to standard PHP calls. All table rows are represented as model objects,
 * with object properties representing row data. ORM in Kohana generally follows
 * the [Active Record][ref-act] pattern.
 *
 * [ref-orm]: https://en.wikipedia.org/wiki/Object%E2%80%93relational_mapping
 * [ref-act]: https://en.wikipedia.org/wiki/Active_record_pattern
 *
 * @package    Kohana/ORM
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class Model extends \Kohana\Foundation\Model
{
    /**
     * Stores column information for ORM models
     * @var array
     */
    protected static $columnCache = [];

    /**
     * Initialization storage for ORM models
     * @var array
     */
    protected static $initCache = [];

    /**
     * Creates and returns a new model.
     * Model name must be passed with its original casing, e.g.
     *
     *    $model = ORM::factory('User_Token');
     *
     * @chainable
     * @param string $name Model name
     * @param   mixed   $id     Parameter for find()
     * @return  ORM
     */
    public static function factory(string $name, $id = null): \Kohana\Foundation\Model
    {
        // Set class name
        $model = str_contains($name, '\\') ? $name : 'App\\Models\\' . str_replace('_', '', ucwords($name, '_'));

        return new $model($id);
    }

    /**
     * "Has one" relationships
     * @var array
     */
    protected $hasOne = [];

    /**
     * "Belongs to" relationships
     * @var array
     */
    protected $belongsTo = [];

    /**
     * "Has many" relationships
     * @var array
     */
    protected $hasMany = [];

    /**
     * Relationships that should always be joined
     * @var array
     */
    protected $loadWith = [];

    /**
     * Validation object created before saving/updating
     * @var Validation
     */
    protected $validation = null;

    /**
     * Current object
     * @var array
     */
    protected $object = [];

    /**
     * @var array
     */
    protected $changes = [];

    /**
     * @var array
     */
    protected $originalValues = [];

    /**
     * @var array
     */
    protected $related = [];

    /**
     * @var bool
     */
    protected $valid = false;

    /**
     * @var bool
     */
    protected $loaded = false;

    /**
     * @var bool
     */
    protected $saved = false;

    /**
     * @var array
     */
    protected $sorting;

    /**
     * Foreign key suffix
     * @var string
     */
    protected $foreignKeySuffix = '_id';

    /**
     * Model name
     * @var string
     */
    protected $objectName;

    /**
     * Plural model name
     * @var string
     */
    protected $objectPlural;

    /**
     * Table name
     * @var string
     */
    protected $tableName;

    /**
     * Auto-update columns for updates
     * @var string
     */
    protected $updatedColumn = null;

    /**
     * Auto-update columns for creation
     * @var string
     */
    protected $createdColumn = null;

    /**
     * Auto-serialize and unserialize columns on get/set
     * @var array
     */
    protected $serializeColumns = [];

    /**
     * Table primary key
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Primary key value
     * @var mixed
     */
    protected $primaryKeyValue;

    /**
     * Model configuration, table names plural?
     * @var bool
     */
    protected $tableNamesPlural = true;

    /**
     * Model configuration, reload on wakeup?
     * @var bool
     */
    protected $reloadOnWakeup = true;

    /**
     * Database Object
     * @var Database
     */
    protected $db = null;

    /**
     * Database config group
     * @var String
     */
    protected $dbGroup = null;

    /**
     * Database methods applied
     * @var array
     */
    protected $dbApplied = [];

    /**
     * Database methods pending
     * @var array
     */
    protected $dbPending = [];

    /**
     * Reset builder
     * @var bool
     */
    protected $dbReset = true;

    /**
     * Database query builder
     * @var Database_Query_Builder_Select
     */
    protected $dbBuilder;

    /**
     * With calls already applied
     * @var array
     */
    protected $withApplied = [];

    /**
     * Data to be loaded into the model from a database call cast
     * @var array
     */
    protected $castData = [];

    /**
     * The message filename used for validation errors.
     * Defaults to ORM::$_object_name
     * @var string
     */
    protected $errorsFilename = null;

    /**
     * Constructs a new model and loads a record if given
     *
     * @param mixed $id Parameter for find or object to load
     * @throws Kohana_Exception
     */
    public function __construct($id = null)
    {
        $this->initialize();

        if ($id !== null) {
            if (is_array($id)) {
                foreach ($id as $column => $value) {
                    // Passing an array of column => values
                    $this->where($column, '=', $value);
                }

                $this->find();
            } else {
                // Passing the primary key
                $this->where($this->objectName . '.' . $this->primaryKey, '=', $id)->find();
            }
        } elseif (!empty($this->castData)) {
            // Load preloaded data from a database call cast
            $this->loadValues($this->castData);

            $this->castData = [];
        }
    }

    /**
     * Prepares the model database connection, determines the table name,
     * and loads column information.
     *
     * @return void
     * @throws Kohana_Exception
     */
    protected function initialize()
    {
        // Set the object name if none predefined
        if (empty($this->objectName)) {
            // App\Models\UserToken => user_token, App\Models\Blog\Post => blog_post
            $name = preg_replace('/^.*?\\\\Models\\\\/', '', static::class);
            $this->objectName = strtolower(preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', '_', str_replace('\\', '_', $name)));
        }

        // Check if this model has already been initialized
        if (!$init = Arr::get(\Kohana\Orm\Model::$initCache, $this->objectName, false)) {
            $init = [
                'belongsTo' => [],
                'hasOne' => [],
                'hasMany' => [],
            ];

            // Set the object plural name if none predefined
            if (!isset($this->objectPlural)) {
                $init['objectPlural'] = Inflector::plural($this->objectName);
            }

            if (!$this->errorsFilename) {
                $init['errorsFilename'] = $this->objectName;
            }

            if (!is_object($this->db)) {
                // Get database instance
                $init['db'] = Connection::instance($this->dbGroup);
            }

            if (empty($this->tableName)) {
                // Table name is the same as the object name
                $init['tableName'] = $this->objectName;

                if ($this->tableNamesPlural === true) {
                    // Make the table name plural
                    $init['tableName'] = Arr::get($init, 'objectPlural', $this->objectPlural);
                }
            }

            $defaults = [];

            foreach ($this->belongsTo as $alias => $details) {
                if (!isset($details['model'])) {
                    $defaults['model'] = str_replace(' ', '_', ucwords(str_replace('_', ' ', $alias)));
                }

                $defaults['foreign_key'] = $alias . $this->foreignKeySuffix;

                $init['belongsTo'][$alias] = array_merge($defaults, $details);
            }

            foreach ($this->hasOne as $alias => $details) {
                if (!isset($details['model'])) {
                    $defaults['model'] = str_replace(' ', '_', ucwords(str_replace('_', ' ', $alias)));
                }

                $defaults['foreign_key'] = $this->objectName . $this->foreignKeySuffix;

                $init['hasOne'][$alias] = array_merge($defaults, $details);
            }

            foreach ($this->hasMany as $alias => $details) {
                if (!isset($details['model'])) {
                    $defaults['model'] = str_replace(' ', '_', ucwords(str_replace('_', ' ', Inflector::singular($alias))));
                }

                $defaults['foreign_key'] = $this->objectName . $this->foreignKeySuffix;
                $defaults['through'] = null;

                if (!isset($details['far_key'])) {
                    $defaults['far_key'] = Inflector::singular($alias) . $this->foreignKeySuffix;
                }

                $init['hasMany'][$alias] = array_merge($defaults, $details);
            }

            \Kohana\Orm\Model::$initCache[$this->objectName] = $init;
        }

        // Assign initialized properties to the current object
        foreach ($init as $property => $value) {
            $this->{$property} = $value;
        }

        // Clear initial model state
        $this->clear();
    }

    /**
     * Initializes validation rules, and labels
     *
     * @return void
     */
    protected function initializeValidation()
    {
        // Build the validation object with its rules
        $this->validation = Validator::factory($this->object)
            ->bind(':model', $this)
            ->bind(':original_values', $this->originalValues)
            ->bind(':changes', $this->changes);

        // Use column names by default for labels
        $columns = [];

        foreach ($this->rules() as $field => $rules) {
            $columns[$field] = $field;
            $this->validation->rules($field, $rules);
        }

        // Merge user-defined labels
        $labels = array_merge($columns, $this->labels());

        $this->validation->labels($labels);
    }

    /**
     * Unloads the current object and clears the status.
     *
     * @chainable
     * @return Kohana_ORM
     */
    public function clear(): \Kohana\Orm\Model
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
     * @return Kohana_ORM
     * @throws Kohana_Exception
     */
    public function reload(): \Kohana\Orm\Model
    {
        $primary_key = $this->pk();

        // Replace the object and reset the object status
        $this->object = $this->changes = $this->related = $this->originalValues = [];

        // Only reload the object if we have one to reload
        if ($this->loaded)
            return $this->clear()
                    ->where($this->objectName . '.' . $this->primaryKey, '=', $primary_key)
                    ->find();
        else
            return $this->clear();
    }

    /**
     * Checks if object data is set.
     *
     * @param string $column Column name
     * @return bool
     */
    public function __isset(string $column)
    {
        return isset($this->object[$column]) OR
            isset($this->related[$column]) OR
            isset($this->hasOne[$column]) OR
            isset($this->belongsTo[$column]) OR
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
     * Displays the primary key of a model when it is converted to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->pk();
    }

    /**
     * Allows serialization of only the object data and state, to prevent
     * "stale" objects being unserialized, which also requires less memory.
     *
     * @return array
     */
    public function __serialize(): array
    {
        // Store only information about the object
        foreach (['primaryKeyValue', 'object', 'changes', 'loaded', 'saved', 'sorting', 'originalValues'] as $var) {
            $data[$var] = $this->{$var};
        }

        return $data;
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
     * Prepares the database connection and reloads the object.
     *
     * @param array $data Serialized object state
     * @return  void
     * @throws Kohana_Exception
     */
    public function __unserialize(array $data): void
    {
        // Initialize model
        $this->initialize();

        foreach ($data as $name => $var) {
            $this->{$name} = $var;
        }

        if ($this->reloadOnWakeup === true) {
            // Reload the object
            $this->reload();
        }
    }

    /**
     * Handles retrieval of all model values, relationships, and metadata.
     * [!!] This should not be overridden.
     *
     * @param string $column Column name
     * @return  mixed
     * @throws Kohana_Exception
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
     * @throws Kohana_Exception
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
            $model = \Kohana\Orm\Model::factory($this->hasMany[$column]['model']);

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
            return in_array($column, $this->serializeColumns) ? $this->unserializeValue($this->object[$column]) : $this->object[$column] ?? null;
        }
    }

    /**
     * Base set method.
     * [!!] This should not be overridden.
     *
     * @param string $column Column name
     * @param mixed $value Column value
     * @return void
     * @throws ReflectionException
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
     * @return Kohana_ORM
     * @throws ReflectionException
     */
    public function set(string $column, $value): \Kohana\Orm\Model
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
            $this->object[$this->belongsTo[$column]['foreign_key']] = $value instanceof \Kohana\Orm\Model ? $value->pk() : null;

            $this->changes[$column] = $value;
        } else {
            // Filter the data
            $value = $this->runFilter($column, $value);

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
     * @return Kohana_ORM
     */
    public function values(array $values, array $columns): \Kohana\Orm\Model
    {
        foreach ($columns as $key => $column) {
            if (is_string($key)) {
                // isset() fails when the value is null (we want it to pass)
                if (!array_key_exists($key, $values))
                    continue;

                // Try to set values to a related model
                $this->{$key}->values($values[$key], $column);
            }
            else {
                // isset() fails when the value is null (we want it to pass)
                if (!array_key_exists($column, $values))
                    continue;

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
     * @throws Kohana_Exception
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
     * Binds another one-to-one object to this model.  One-to-one objects
     * can be nested using 'object1:object2' syntax
     *
     * @param string $target_path Target model to bind to
     * @return Kohana_ORM
     */
    public function with(string $target_path): \Kohana\Orm\Model
    {
        if (isset($this->withApplied[$target_path])) {
            // Don't join anything already joined
            return $this;
        }

        // Split object parts
        $aliases = explode(':', $target_path);
        $target = $this;
        foreach ($aliases as $alias) {
            // Go down the line of objects to find the given target
            $parent = $target;
            $target = $parent->related($alias);

            if (!$target) {
                // Can't find related object
                return $this;
            }
        }

        // Target alias is at the end
        $target_alias = $alias;

        // Pop-off top alias to get the parent path (user:photo:tag becomes user:photo - the parent table prefix)
        array_pop($aliases);
        $parent_path = implode(':', $aliases);

        if (empty($parent_path)) {
            // Use this table name itself for the parent path
            $parent_path = $this->objectName;
        } else {
            if (!isset($this->withApplied[$parent_path])) {
                // If the parent path hasn't been joined yet, do it first (otherwise LEFT JOINs fail)
                $this->with($parent_path);
            }
        }

        // Add to with_applied to prevent duplicate joins
        $this->withApplied[$target_path] = true;

        // Use the keys of the empty object to determine the columns
        foreach (array_keys($target->object) as $column) {
            $name = $target_path . '.' . $column;
            $alias = $target_path . ':' . $column;

            // Add the prefix so that load_result can determine the relationship
            $this->select([$name, $alias]);
        }

        if (isset($parent->belongsTo[$target_alias])) {
            // Parent belongs_to target, use target's primary key and parent's foreign key
            $join_col1 = $target_path . '.' . $target->primaryKey;
            $join_col2 = $parent_path . '.' . $parent->belongsTo[$target_alias]['foreign_key'];
        } else {
            // Parent has_one target, use parent's primary key as target's foreign key
            $join_col1 = $parent_path . '.' . $parent->primaryKey;
            $join_col2 = $target_path . '.' . $parent->hasOne[$target_alias]['foreign_key'];
        }

        // Join the related object into the result
        $this->join([$target->tableName, $target_path], 'LEFT')->on($join_col1, '=', $join_col2);

        return $this;
    }

    /**
     * Initializes the Database Builder to given query type
     *
     * @param int $type Type of Database query
     * @return Kohana_ORM
     */
    protected function build(int $type): \Kohana\Orm\Model
    {
        // Construct new builder object based on query type
        switch ($type) {
            case Connection::SELECT:
                $this->dbBuilder = DB::select();
                break;
            case Connection::UPDATE:
                $this->dbBuilder = DB::update($this->tableName);
                break;
            case Connection::DELETE:
                // Cannot use an alias for DELETE queries
                $this->dbBuilder = DB::delete($this->tableName);
        }

        // Process pending database method calls
        foreach ($this->dbPending as $method) {
            $name = $method['name'];
            $args = $method['args'];

            $this->dbApplied[$name] = $name;

            call_user_func_array([$this->dbBuilder, $name], $args);
        }

        return $this;
    }

    /**
     * Finds and loads a single database row into the object.
     *
     * @chainable
     * @return Database_Result_Cached|Kohana_ORM|object
     * @throws Kohana_Exception
     */
    public function find()
    {
        if ($this->loaded)
            throw new KohanaException('Method find() cannot be called on loaded objects');

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
     * @throws Kohana_Exception
     * @return Database_Result
     */
    public function findAll()
    {
        if ($this->loaded)
            throw new KohanaException('Method find_all() cannot be called on loaded objects');

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
     * @return Database_Result_Cached|Kohana_ORM|object
     * @throws Kohana_Exception
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
     * Loads an array of values into the current object.
     *
     * @chainable
     * @param  array $values Values to load
     * @return Kohana_ORM
     */
    protected function loadValues(array $values): \Kohana\Orm\Model
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
                list ($prefix, $column) = explode(':', $column, 2);

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

    /**
     * Rule definitions for validation
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Filters a value for a specific column
     *
     * @param string $field The column name
     * @param mixed $value The value to filter
     * @return mixed
     * @throws ReflectionException
     */
    protected function runFilter(string $field, $value)
    {
        $filters = $this->filters();

        // Get the filters for this column
        $wildcards = empty($filters[true]) ? [] : $filters[true];

        // Merge in the wildcards
        $filters = empty($filters[$field]) ? $wildcards : array_merge($wildcards, $filters[$field]);

        // Bind the field name and model, so they can be used in the filter method
        $_bound = [
            ':field' => $field,
            ':model' => $this,
        ];

        foreach ($filters as $array) {
            // Value needs to be bound inside the loop, so we are always using the
            // version that was modified by the filters that already ran
            $_bound[':value'] = $value;

            // Filters are defined as [$filter, $params]
            $filter = $array[0];
            $params = Arr::get($array, 1, [':value']);

            foreach ($params as $key => $param) {
                if (is_string($param) && array_key_exists($param, $_bound)) {
                    // Replace with bound value
                    $params[$key] = $_bound[$param];
                }
            }

            if (is_callable($filter)) {
                // This is either a callback as an array or a lambda
                $value = call_user_func_array($filter, $params);
            } else {
                // Call $function($this[$field], $param, ...) with Reflection
                $value = (new \ReflectionFunction($filter))->invokeArgs($params);
            }
        }

        return $value;
    }

    /**
     * Filter definitions for validation
     *
     * @return array
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Label definitions for validation
     *
     * @return array
     */
    public function labels(): array
    {
        return [];
    }

    /**
     * Validates the current model's data
     *
     * @param Validation|null $extra_validation Validation object
     * @return Kohana_ORM
     * @throws ORM_Validation_Exception
     * @throws ReflectionException
     */
    public function check(?Validator $extra_validation = null): \Kohana\Orm\Model
    {
        // Determine if any external validation failed
        $extra_errors = $extra_validation && !$extra_validation->check();

        // Always build a new validation object
        $this->initializeValidation();

        $array = $this->validation;

        if (($this->valid = $array->check()) === false || $extra_errors) {
            $exception = new ValidationException($this->errorsFilename(), $array);

            if ($extra_errors) {
                // Merge any possible errors from the external object
                $exception->addObject('external', $extra_validation);
            }
            throw $exception;
        }

        return $this;
    }

    /**
     * Insert a new object to the database
     * @param Validation|null $validation Validation object
     * @return Kohana_ORM
     * @throws Kohana_Exception
     * @throws ORM_Validation_Exception
     * @throws ReflectionException
     */
    public function create(?Validator $validation = null): \Kohana\Orm\Model
    {
        if ($this->loaded)
            throw new KohanaException('Cannot create :model model because it is already loaded.', [':model' => $this->objectName]);

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
     * @param Validation|null $validation Validation object
     * @return Kohana_ORM
     * @throws Kohana_Exception
     * @throws ORM_Validation_Exception
     * @throws ReflectionException
     */
    public function update(?Validator $validation = null): \Kohana\Orm\Model
    {
        if (!$this->loaded)
            throw new KohanaException('Cannot update :model model because it is not loaded.', [':model' => $this->objectName]);

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
     * @param Validation|null $validation Validation object
     * @return Kohana_ORM
     * @throws Kohana_Exception
     * @throws ORM_Validation_Exception
     * @throws ReflectionException
     */
    public function save(?Validator $validation = null): \Kohana\Orm\Model
    {
        return $this->loaded() ? $this->update($validation) : $this->create($validation);
    }

    /**
     * Deletes a single record while ignoring relationships.
     *
     * @chainable
     * @return Kohana_ORM
     * @throws Kohana_Exception
     */
    public function delete(): \Kohana\Orm\Model
    {
        if (!$this->loaded)
            throw new KohanaException('Cannot delete :model model because it is not loaded.', [':model' => $this->objectName]);

        // Use primary key value
        $id = $this->pk();

        // Delete the object
        DB::delete($this->tableName)
            ->where($this->primaryKey, '=', $id)
            ->execute($this->db);

        return $this->clear();
    }

    /**
     * Tests if this object has a relationship to a different model,
     * or an array of different models. When providing far keys, the number
     * of relations must equal the number of keys.
     *
     *
     *     // Check if $model has the login role
     *     $model->has('roles', ORM::factory('Role', ['name' => 'login']));
     *     // Check for the login role if you know the role id is 5
     *     $model->has('roles', 5);
     *     // Check for all the following roles
     *     $model->has('roles', [1, 2, 3, 4]);
     *     // Check if $model has any roles
     *     $model->has('roles');
     *
     * @param string $alias Alias of the has_many "through" relationship
     * @param mixed $far_keys Related model, primary key, or an array of primary keys
     * @return bool
     * @throws Kohana_Exception
     */
    public function has(string $alias, $far_keys = null): bool
    {
        $count = $this->countRelations($alias, $far_keys);
        if ($far_keys === null) {
            return (bool) $count;
        }

        return $count === (is_array($far_keys) ? count($far_keys) : 1);
    }

    /**
     * Tests if this object has a relationship to a different model,
     * or an array of different models. When providing far keys, this function
     * only checks that at least one of the relationships is satisfied.
     *
     *     // Check if $model has the login role
     *     $model->has_any('roles', ORM::factory('Role', ['name' => 'login']));
     *     // Check for the login role if you know the role id is 5
     *     $model->has_any('roles', 5);
     *     // Check for any of the following roles
     *     $model->has_any('roles', [1, 2, 3, 4]);
     *     // Check if $model has any roles
     *     $model->has_any('roles');
     *
     * @param string $alias Alias of the has_many "through" relationship
     * @param mixed $far_keys Related model, primary key, or an array of primary keys
     * @return bool
     * @throws Kohana_Exception
     */
    public function hasAny(string $alias, $far_keys = null): bool
    {
        return (bool) $this->countRelations($alias, $far_keys);
    }

    /**
     * Returns the number of relationships
     *
     *     // Counts the number of times the login role is attached to current model
     *     $model->count_relations('roles', ORM::factory('Role', ['name' => 'login']));
     *     // Counts the number of times role 5 is attached to current model
     *     $model->count_relations('roles', 5);
     *     // Counts the number of times any of roles 1, 2, 3, or 4 are attached to current model
     *     $model->count_relations('roles', [1, 2, 3, 4]);
     *     // Counts the number roles attached to current model
     *     $model->count_relations('roles');
     *
     * @param string $alias Alias of the has_many "through" relationship
     * @param mixed $far_keys Related model, primary key, or an array of primary keys
     * @return int
     * @throws Kohana_Exception
     */
    public function countRelations(string $alias, $far_keys = null): int
    {
        if ($far_keys === null) {
            return (int) DB::select([DB::expr('COUNT(*)'), 'records_found'])
                    ->from($this->hasMany[$alias]['through'])
                    ->where($this->hasMany[$alias]['foreign_key'], '=', $this->pk())
                    ->execute($this->db)->get('records_found');
        }

        $far_keys = $far_keys instanceof \Kohana\Orm\Model ? $far_keys->pk() : $far_keys;

        // We need an array to simplify the logic
        $far_keys = (array) $far_keys;

        // Nothing to check if the model isn't loaded, or we don't have any far_keys
        if (!$far_keys || !$this->loaded)
            return 0;

        // Rows found need to match the rows searched
        return (int) DB::select([DB::expr('COUNT(*)'), 'records_found'])
            ->from($this->hasMany[$alias]['through'])
            ->where($this->hasMany[$alias]['foreign_key'], '=', $this->pk())
            ->where($this->hasMany[$alias]['far_key'], 'IN', $far_keys)
            ->groupBy($this->hasMany[$alias]['far_key'])
            ->execute($this->db)->get('records_found');
    }

    /**
     * Adds a new relationship to between this model and another.
     *
     *     // Add the login role using a model instance
     *     $model->add('roles', ORM::factory('Role', ['name' => 'login']));
     *     // Add the login role if you know the role id is 5
     *     $model->add('roles', 5);
     *     // Add multiple roles (for example, from checkboxes on a form)
     *     $model->add('roles', [1, 2, 3, 4]);
     *
     * @param string $alias Alias of the has_many "through" relationship
     * @param mixed $far_keys Related model, primary key, or an array of primary keys
     * @return Kohana_ORM
     * @throws Kohana_Exception
     */
    public function add(string $alias, $far_keys): \Kohana\Orm\Model
    {
        $far_keys = $far_keys instanceof \Kohana\Orm\Model ? $far_keys->pk() : $far_keys;

        $columns = [$this->hasMany[$alias]['foreign_key'], $this->hasMany[$alias]['far_key']];
        $foreign_key = $this->pk();

        $query = DB::insert($this->hasMany[$alias]['through'], $columns);

        foreach ((array) $far_keys as $key) {
            $query->values([$foreign_key, $key]);
        }

        $query->execute($this->db);

        return $this;
    }

    /**
     * Removes a relationship between this model and another.
     *
     *     // Remove a role using a model instance
     *     $model->remove('roles', ORM::factory('Role', ['name' => 'login']));
     *     // Remove the role knowing the primary key
     *     $model->remove('roles', 5);
     *     // Remove multiple roles (for example, from checkboxes on a form)
     *     $model->remove('roles', [1, 2, 3, 4]);
     *     // Remove all related roles
     *     $model->remove('roles');
     *
     * @param string $alias Alias of the has_many "through" relationship
     * @param mixed $far_keys Related model, primary key, or an array of primary keys
     * @return Kohana_ORM
     * @throws Kohana_Exception
     */
    public function remove(string $alias, $far_keys = null): \Kohana\Orm\Model
    {
        $far_keys = $far_keys instanceof \Kohana\Orm\Model ? $far_keys->pk() : $far_keys;

        $query = DB::delete($this->hasMany[$alias]['through'])
            ->where($this->hasMany[$alias]['foreign_key'], '=', $this->pk());

        if ($far_keys !== null) {
            // Remove all the relationships in the array
            $query->where($this->hasMany[$alias]['far_key'], 'IN', (array) $far_keys);
        }

        $query->execute($this->db);

        return $this;
    }

    /**
     * Count the number of records in the table.
     *
     * @return int
     * @throws Kohana_Exception
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

    /**
     * Returns an ORM model for the given one-one related alias
     *
     * @param string $alias Alias name
     * @return ORM
     */
    protected function related(string $alias): ?\Kohana\Orm\Model
    {
        switch (true) {
            case isset($this->related[$alias]):
                return $this->related[$alias];
            case isset($this->hasOne[$alias]):
                return $this->related[$alias] = \Kohana\Orm\Model::factory($this->hasOne[$alias]['model']);
            case isset($this->belongsTo[$alias]):
                return $this->related[$alias] = \Kohana\Orm\Model::factory($this->belongsTo[$alias]['model']);
            default:
                return null;
        }
    }

    /**
     * Returns the value of the primary key
     *
     * @return mixed Primary key
     */
    public function pk()
    {
        return $this->primaryKeyValue;
    }

    /**
     * Returns last executed query
     *
     * @return string
     */
    public function lastQuery(): string
    {
        return $this->db->last_query;
    }

    /**
     * Clears query builder.  Passing false is useful to keep the existing
     * query conditions for another query.
     *
     * @param bool $next Pass false to avoid resetting on the next call
     * @return Kohana_ORM
     */
    public function reset(bool $next = true): \Kohana\Orm\Model
    {
        if ($next && $this->dbReset) {
            $this->dbPending = [];
            $this->dbApplied = [];
            $this->dbBuilder = null;
            $this->withApplied = [];
        }

        // Reset on the next call?
        $this->dbReset = $next;

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

    public function objectName(): string
    {
        return $this->objectName;
    }

    public function objectPlural(): string
    {
        return $this->objectPlural;
    }

    public function loaded(): bool
    {
        return $this->loaded;
    }

    public function saved(): bool
    {
        return $this->saved;
    }

    public function primaryKey(): string
    {
        return $this->primaryKey;
    }

    public function tableName(): string
    {
        return $this->tableName;
    }

    public function hasOne(): array
    {
        return $this->hasOne;
    }

    public function belongsTo(): array
    {
        return $this->belongsTo;
    }

    public function hasMany(): array
    {
        return $this->hasMany;
    }

    public function loadWith(): array
    {
        return $this->loadWith;
    }

    public function originalValues(): array
    {
        return $this->originalValues;
    }

    public function createdColumn(): ?string
    {
        return $this->createdColumn;
    }

    public function updatedColumn(): ?string
    {
        return $this->updatedColumn;
    }

    public function validation(): ?Validator
    {
        if (!isset($this->validation)) {
            // Initialize the validation object
            $this->initializeValidation();
        }

        return $this->validation;
    }

    public function object(): array
    {
        return $this->object;
    }

    public function errorsFilename(): ?string
    {
        return $this->errorsFilename;
    }

    /**
     * Alias of and_where()
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function where($column, string $op, $value): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'where',
            'args' => [$column, $op, $value],
        ];

        return $this;
    }

    /**
     * Creates a new "AND WHERE" condition for the query.
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function andWhere($column, string $op, $value): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'andWhere',
            'args' => [$column, $op, $value],
        ];

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
    public function orWhere($column, string $op, $value): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orWhere',
            'args' => [$column, $op, $value],
        ];

        return $this;
    }

    /**
     * Alias of and_where_open()
     *
     * @return  $this
     */
    public function whereOpen(): \Kohana\Orm\Model
    {
        return $this->andWhereOpen();
    }

    /**
     * Opens a new "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function andWhereOpen(): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'andWhereOpen',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Opens a new "OR WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function orWhereOpen(): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orWhereOpen',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Closes an open "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function whereClose(): \Kohana\Orm\Model
    {
        return $this->andWhereClose();
    }

    /**
     * Closes an open "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function andWhereClose(): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'andWhereClose',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Closes an open "OR WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function orWhereClose(): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orWhereClose',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Applies sorting with "ORDER BY ..."
     *
     * @param   mixed   $column     column name or [$column, $alias] or object
     * @param string|null $direction Direction of sorting
     * @return  $this
     */
    public function orderBy($column, ?string $direction = null): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orderBy',
            'args' => [$column, $direction],
        ];

        return $this;
    }

    /**
     * Return up to "LIMIT ..." results
     *
     * @param int $number Maximum results to return
     * @return  $this
     */
    public function limit(int $number): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'limit',
            'args' => [$number],
        ];

        return $this;
    }

    /**
     * Enables or disables selecting only unique columns using "SELECT DISTINCT"
     *
     * @param bool $value enable or disable distinct columns
     * @return  $this
     */
    public function distinct(bool $value): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'distinct',
            'args' => [$value],
        ];

        return $this;
    }

    /**
     * Choose the columns to select from.
     *
     * @param mixed ...$columns column name or [$column, $alias] or object
     * @return  $this
     */
    public function select(...$columns): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'select',
            'args' => $columns,
        ];

        return $this;
    }

    /**
     * Choose the tables to select "FROM ..."
     *
     * @param mixed ...$tables table name or [$table, $alias] or object
     * @return  $this
     */
    public function from(...$tables): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'from',
            'args' => $tables,
        ];

        return $this;
    }

    /**
     * Adds addition tables to "JOIN ...".
     *
     * @param   mixed   $table  column name or [$column, $alias] or object
     * @param string|null $type Join type (LEFT, RIGHT, INNER, etc.)
     * @return  $this
     */
    public function join($table, ?string $type = null): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'join',
            'args' => [$table, $type],
        ];

        return $this;
    }

    /**
     * Adds "ON ..." conditions for the last created JOIN statement.
     *
     * @param   mixed   $c1  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $c2  column name or [$column, $alias] or object
     * @return  $this
     */
    public function on($c1, string $op, $c2): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'on',
            'args' => [$c1, $op, $c2],
        ];

        return $this;
    }

    /**
     * Creates a "GROUP BY ..." filter.
     *
     * @param mixed ...$columns column name or [$column, $alias] or object
     * @param   ...
     * @return  $this
     */
    public function groupBy(...$columns): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'groupBy',
            'args' => $columns,
        ];

        return $this;
    }

    /**
     * Alias of and_having()
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function having($column, string $op, $value = null): \Kohana\Orm\Model
    {
        return $this->andHaving($column, $op, $value);
    }

    /**
     * Creates a new "AND HAVING" condition for the query.
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function andHaving($column, string $op, $value = null): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'andHaving',
            'args' => [$column, $op, $value],
        ];

        return $this;
    }

    /**
     * Creates a new "OR HAVING" condition for the query.
     *
     * @param   mixed   $column  column name or [$column, $alias] or object
     * @param string $op Logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function orHaving($column, string $op, $value = null): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orHaving',
            'args' => [$column, $op, $value],
        ];

        return $this;
    }

    /**
     * Alias of and_having_open()
     *
     * @return  $this
     */
    public function havingOpen(): \Kohana\Orm\Model
    {
        return $this->andHavingOpen();
    }

    /**
     * Opens a new "AND HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function andHavingOpen(): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'andHavingOpen',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Opens a new "OR HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function orHavingOpen(): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orHavingOpen',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Closes an open "AND HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function havingClose(): \Kohana\Orm\Model
    {
        return $this->andHavingClose();
    }

    /**
     * Closes an open "AND HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function andHavingClose(): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'andHavingClose',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Closes an open "OR HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function orHavingClose(): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'orHavingClose',
            'args' => [],
        ];

        return $this;
    }

    /**
     * Start returning results after "OFFSET ..."
     *
     * @param int $number Starting result number
     * @return  $this
     */
    public function offset(int $number): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'offset',
            'args' => [$number],
        ];

        return $this;
    }

    /**
     * Enables the query to be cached for a specified amount of time.
     *
     * @param int|null $lifetime Number of seconds to cache
     * @return  $this
     * @uses    Kohana::$cache_life
     */
    public function cached(?int $lifetime = null): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'cached',
            'args' => [$lifetime],
        ];

        return $this;
    }

    /**
     * Set the value of a parameter in the query.
     *
     * @param string $param Parameter key to replace
     * @param   mixed    $value  value to use
     * @return  $this
     */
    public function param(string $param, $value): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'param',
            'args' => [$param, $value],
        ];

        return $this;
    }

    /**
     * Adds "USING ..." conditions for the last created JOIN statement.
     *
     * @param string $columns Column name
     * @return  $this
     */
    public function using(string $columns): \Kohana\Orm\Model
    {
        // Add pending database call which is executed after query type is determined
        $this->dbPending[] = [
            'name' => 'using',
            'args' => [$columns],
        ];

        return $this;
    }

    /**
     * Checks whether a column value is unique.
     * Excludes itself if loaded.
     *
     * @param string $field the field to check for uniqueness
     * @param mixed $value the value to check for uniqueness
     * @return  bool     whether the value is unique
     * @throws Kohana_Exception
     */
    public function unique(string $field, $value): bool
    {
        $model = \Kohana\Orm\Model::factory($this->objectName())
            ->where($field, '=', $value)
            ->find();

        if ($this->loaded()) {
            return !($model->loaded() && $model->pk() !== $this->pk());
        }

        return !$model->loaded();
    }

}
