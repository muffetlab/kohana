<?php

namespace Kohana\Database\Query;

use Kohana\Database\Connection;
use Kohana\Foundation\KohanaException;
use Kohana\Foundation\Application;
use Kohana\Database\CachedResult;

/**
 * Database query wrapper.  See [Parameterized Statements](database/query/parameterized) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    https://kohana.top/license
 */
class Query
{
    // Query type
    protected $type;
    // Execute the query during a cache hit
    protected $forceExecute = false;
    // Cache lifetime
    protected $lifetime = null;
    // SQL statement
    protected $sql;
    // Quoted query parameters
    protected $parameters = [];
    // Return results as associative arrays or objects
    protected $asObject = false;
    // Parameters for __construct when using object results
    protected $objectParams = [];

    /**
     * Creates a new SQL query of the specified type.
     *
     * @param int|null $type Query type: Database::SELECT, Database::INSERT, etc.
     * @param string|null $sql Query string
     */
    public function __construct(?int $type, ?string $sql)
    {
        $this->type = $type;
        $this->sql = $sql;
    }

    /**
     * Return the SQL query string.
     *
     * @return  string
     */
    public function __toString()
    {
        try {
            // Return the SQL string
            return $this->compile(Connection::instance());
        } catch (\Exception $e) {
            return KohanaException::text($e);
        }
    }

    /**
     * Get the type of the query.
     *
     * @return int
     */
    public function type(): int
    {
        return $this->type;
    }

    /**
     * Enables the query to be cached for a specified amount of time.
     *
     * @param int|null $lifetime Number of seconds to cache, 0 deletes it from the cache
     * @param bool $force whether to execute the query during a cache hit
     * @return  $this
     * @uses    Kohana::$cache_life
     */
    public function cached(?int $lifetime = null, bool $force = false): self
    {
        if ($lifetime === null) {
            // Use the global setting
            $lifetime = Application::$cacheLife;
        }

        $this->forceExecute = $force;
        $this->lifetime = $lifetime;

        return $this;
    }

    /**
     * Returns results as associative arrays
     *
     * @return  $this
     */
    public function asAssoc(): self
    {
        $this->asObject = false;

        $this->objectParams = [];

        return $this;
    }

    /**
     * Returns results as objects
     *
     * @param string|bool $class classname or true for stdClass
     * @param array|null $params
     * @return  $this
     */
    public function asObject($class = true, ?array $params = null): self
    {
        $this->asObject = $class;

        if ($params) {
            // Add object parameters
            $this->objectParams = $params;
        }

        return $this;
    }

    /**
     * Set the value of a parameter in the query.
     *
     * @param string $param Parameter key to replace
     * @param   mixed    $value  value to use
     * @return  $this
     */
    public function param(string $param, $value): self
    {
        // Add or overload a new parameter
        $this->parameters[$param] = $value;

        return $this;
    }

    /**
     * Bind a variable to a parameter in the query.
     *
     * @param string $param Parameter key to replace
     * @param   mixed   $var    variable to use
     * @return  $this
     */
    public function bind(string $param, &$var): self
    {
        // Bind a value to a variable
        $this->parameters[$param] = &$var;

        return $this;
    }

    /**
     * Add multiple parameters to the query.
     *
     * @param   array  $params  list of parameters
     * @return  $this
     */
    public function parameters(array $params): self
    {
        // Merge the new parameters in
        $this->parameters = $params + $this->parameters;

        return $this;
    }

    /**
     * Compile the SQL query and return it. Replaces any parameters with their
     * given values.
     *
     * @param mixed $db Database instance or name of instance
     * @return  string
     * @throws Kohana_Exception
     */
    public function compile($db = null): string
    {
        if (!is_object($db)) {
            // Get the database instance
            $db = Connection::instance($db);
        }

        // Import the SQL locally
        $sql = $this->sql;

        if (!empty($this->parameters)) {
            // Quote all the values
            $values = array_map([$db, 'quote'], $this->parameters);

            // Replace the values in the SQL
            $sql = strtr($sql, $values);
        }

        return $sql;
    }

    /**
     * Execute the current query on the given database.
     *
     * @param mixed $db Database instance or name of instance
     * @param string|null $as_object Result object classname, true for stdClass or false for array
     * @param array|null $object_params Result object constructor arguments
     * @return Database_Result|array|int Database_Result for SELECT queries, insert ID for INSERT queries, number of affected rows for all other queries.
     * @throws Kohana_Exception
     */
    public function execute($db = null, ?string $as_object = null, ?array $object_params = null)
    {
        if (!is_object($db)) {
            // Get the database instance
            $db = Connection::instance($db);
        }

        if ($as_object === null) {
            $as_object = $this->asObject;
        }

        if ($object_params === null) {
            $object_params = $this->objectParams;
        }

        // Compile the SQL query
        $sql = $this->compile($db);

        if ($this->lifetime !== null && $this->type === Connection::SELECT) {
            // Set the cache key based on the database instance name and SQL
            $cache_key = 'Kohana\Database\Connection::query("' . $db . '", "' . $sql . '")';

            // Read the cache first to delete a possible hit with lifetime <= 0
            if (($result = Application::cache($cache_key, null, $this->lifetime)) !== null && !$this->forceExecute) {
                // Return a cached result
                return new CachedResult($result, $sql, $as_object);
            }
        }

        // Execute the query
        $result = $db->query($this->type, $sql, $as_object, $object_params);

        if (isset($cache_key) && $this->lifetime > 0) {
            // Cache the result array
            Application::cache($cache_key, $result->asArray(), $this->lifetime);
        }

        return $result;
    }

}
