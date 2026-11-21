<?php

namespace Kohana\Config;

use Kohana\Config\Contracts\Reader;
use Kohana\Database\Connection;
use Kohana\Database\DB;

/**
 * Database reader for the kohana config system
 *
 * @package    Kohana/Database
 * @category   Configuration
 * @author     Kohana Team
 * @copyright  (c) 2012 Kohana Team
 * @license    https://kohana.top/license
 */
class DatabaseReader implements Reader
{
    protected $dbInstance;
    protected $tableName = 'config';

    /**
     * Constructs the database reader object
     *
     * @param array|null $config Configuration for the reader
     */
    public function __construct(?array $config = null)
    {
        if (isset($config['instance'])) {
            $this->dbInstance = $config['instance'];
        } elseif ($this->dbInstance === null) {
            $this->dbInstance = Connection::$default;
        }

        if (isset($config['table_name'])) {
            $this->tableName = $config['table_name'];
        }
    }

    /**
     * Tries to load the specified configuration group
     *
     * Returns false if group does not exist or an array if it does
     *
     * @param string $group Configuration group
     * @return bool|array
     * @throws Kohana_Exception
     */
    public function load(string $group)
    {
        /**
         * Prevents the catch-22 scenario where the database config reader attempts to load the
         * database connections details from the database.
         *
         * @link http://dev.kohanaframework.org/issues/4316
         */
        if ($group === 'database')
            return false;

        $query = DB::select('config_key', 'config_value')
            ->from($this->tableName)
            ->where('group_name', '=', $group)
            ->execute($this->dbInstance);

        return count($query) ? array_map('unserialize', $query->asArray('config_key', 'config_value')) : false;
    }

}
