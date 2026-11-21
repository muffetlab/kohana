<?php

namespace Kohana\Orm;

use Kohana\Database\Connection;
use Kohana\Support\Arr;
use Kohana\Support\Inflector;

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
 *
 * @phpstan-consistent-constructor
 */
class Model extends \Kohana\Foundation\Model implements \JsonSerializable
{
    use Concerns\HasAttributes;
    use Concerns\HasRelationships;
    use Concerns\EagerLoadsRelations;
    use Concerns\BuildsQueries;
    use Concerns\PersistsModels;
    use Concerns\ValidatesAttributes;
    use Concerns\SerializesModels;
    use Concerns\GuardsAttributes;
    use Concerns\CastsAttributes;
    use Concerns\HasTimestamps;
    use Concerns\QueriesStatically;
    use Concerns\ConvertsToArray;
    use Concerns\PaginatesQueries;
    use Concerns\HasEvents;
    use Concerns\HandlesSoftDeletes;
    use Concerns\ManagesRecords;

    /**
     * Column filled when a record is created, with $timestamps = true; null to skip it
     */
    public const CREATED_AT = 'created_at';

    /**
     * Column filled when a record is saved, with $timestamps = true; null to skip it
     */
    public const UPDATED_AT = 'updated_at';

    public const DELETED_AT = 'deleted_at';

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
     * @return  \Kohana\Orm\Model
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
     * @var \Kohana\Validation\Validator
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
     * @var \Kohana\Database\Connection
     */
    protected $db = null;

    /**
     * Database config group; null uses Connection::$default
     * @var string|null
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
     * @var \Kohana\Database\Query\SelectBuilder
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
     * @throws \Kohana\Foundation\KohanaException
     */
    public function __construct($id = null)
    {
        $this->initialize();
        $this->bootIfNotBooted();

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
     * @throws \Kohana\Foundation\KohanaException
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
        // Per class and connection: one model class may be used on several databases
        $cacheKey = static::class . '|' . ($this->dbGroup ?? Connection::$default);

        if (!$init = Arr::get(static::$initCache, $cacheKey, false)) {
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

            static::$initCache[$cacheKey] = $init;
        }

        // Assign initialized properties to the current object
        foreach ($init as $property => $value) {
            $this->{$property} = $value;
        }

        // Clear initial model state
        $this->clear();
    }

    /**
     * Returns the value of the primary key
     *
     * @return mixed Primary key
     */
    public function pk()
    {
        return $this->keyType === 'int' && is_numeric($this->primaryKeyValue) ? (int) $this->primaryKeyValue : $this->primaryKeyValue;
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

    public function createdColumn(): ?string
    {
        return $this->createdColumn;
    }

    public function updatedColumn(): ?string
    {
        return $this->updatedColumn;
    }

}
